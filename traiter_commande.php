<?php
/**
 * TRAITER_COMMANDE.PHP - Crée la commande avec numéro de tracking
 * Gère les paiements : espèces et Wave Checkout
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';
require_once 'includes/notifications.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Accepte les membres connectés ET les invités (sans compte)
$estConnecte = !empty($_SESSION['id']) && ($_SESSION['role'] ?? '') === 'client';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: panier.php');
    exit();
}

if (empty($_SESSION['cart'])) {
    header('Location: panier.php');
    exit();
}

if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
    $_SESSION['erreur_commande'] = "Erreur de sécurité. Veuillez réessayer.";
    header('Location: checkout.php');
    exit();
}

$prenom       = substr(trim($_POST['prenom']        ?? ''), 0, 100);
$telephone    = substr(trim($_POST['telephone']     ?? ''), 0, 20);
$email        = substr(trim($_POST['email']         ?? ''), 0, 150);
$adresse      = substr(trim($_POST['adresse']       ?? ''), 0, 300);
$quartier     = substr(trim($_POST['quartier']      ?? ''), 0, 100);
$notes        = substr(trim($_POST['notes']         ?? ''), 0, 500);
$restaurant_id = intval($_POST['restaurant_id'] ?? 0);
$paiement_post = trim($_POST['paiement']     ?? 'espece');

if (empty($prenom) || empty($telephone) || empty($adresse)) {
    $_SESSION['erreur_commande'] = "Veuillez remplir les champs obligatoires : nom, téléphone et adresse.";
    header('Location: checkout.php');
    exit();
}

// Valider le numéro de téléphone : au moins 7 chiffres après suppression des séparateurs
$chiffres_tel = preg_replace('/\D/', '', $telephone);
if (strlen($chiffres_tel) < 7 || strlen($chiffres_tel) > 15) {
    $_SESSION['erreur_commande'] = "Le numéro de téléphone n'est pas valide. Exemple : 77 123 45 67";
    header('Location: checkout.php');
    exit();
}

// Email obligatoire pour les invités
if (!$estConnecte && empty($email)) {
    $_SESSION['erreur_commande'] = "L'adresse email est obligatoire pour recevoir votre numéro de suivi.";
    header('Location: checkout.php');
    exit();
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['erreur_commande'] = "L'adresse email n'est pas valide.";
    header('Location: checkout.php');
    exit();
}

$mode_paiement_db = ($paiement_post === 'wave') ? 'wave' : 'especes';
$items = $_SESSION['cart'];

function _envoyerNotificationsRestaurant(array $restaurant, string $prenom, string $telephone, string $adresse, string $quartier, string $notes, float $total, string $mode_paiement, string $numero_tracking, array $items): void {
    if (empty($restaurant)) {
        return;
    }
    // Email au restaurant
    envoyerEmailRestaurant($restaurant, $prenom, $telephone, $adresse, $quartier, $notes, $total, $mode_paiement, $numero_tracking, $items);

    // Telegram — le token vient de config.php (TELEGRAM_BOT_TOKEN), seul le chat_id est par restaurant
    $chat_id = $restaurant['telegram_chat_id'] ?? '';
    if (!empty($chat_id)) {
        $msg = buildMessageTelegram($restaurant['nom'] ?? '', $prenom, $telephone, $adresse, $quartier, $notes, $total, $mode_paiement, $numero_tracking, $items);
        envoyerNotificationTelegram('', $chat_id, $msg); // '' → utilise TELEGRAM_BOT_TOKEN
    }
}

function genererNumeroTracking(): string {
    $prefix = 'SK';
    $date   = date('ymd');
    $random = strtoupper(bin2hex(random_bytes(2)));
    return $prefix . '-' . $date . '-' . $random;
}

try {
    $pdo = getDB();
    assurerSchemaEssai($pdo);

    // Revalider les prix depuis la BDD
    $plat_ids     = array_column($items, 'id');
    $placeholders = implode(',', array_fill(0, count($plat_ids), '?'));
    $stmt_prix    = $pdo->prepare("SELECT id, prix, disponible FROM plats WHERE id IN ($placeholders)");
    $stmt_prix->execute($plat_ids);
    $prix_reels = [];
    foreach ($stmt_prix->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $prix_reels[$row['id']] = $row;
    }

    $total = 0;
    foreach ($items as &$item) {
        if (!isset($prix_reels[$item['id']]) || !$prix_reels[$item['id']]['disponible']) {
            $_SESSION['erreur_commande'] = "Un plat n'est plus disponible. Veuillez mettre à jour votre panier.";
            header('Location: panier.php');
            exit();
        }
        $item['prix'] = (float) $prix_reels[$item['id']]['prix'];
        $total += $item['prix'] * $item['quantite'];
    }
    unset($item);

    // Frais de livraison retirés du site : le restaurant et le client s'arrangent directement.
    // Récupérer le minimum de commande du restaurant
    $commande_minimum = 0;
    if ($restaurant_id) {
        $stmt_frais = $pdo->prepare("SELECT commande_minimum FROM restaurants WHERE id = ? LIMIT 1");
        $stmt_frais->execute([$restaurant_id]);
        $frais_row = $stmt_frais->fetch(PDO::FETCH_ASSOC);
        if ($frais_row) {
            $commande_minimum = (int) ($frais_row['commande_minimum'] ?? 0);
        }
    }

    // Vérifier le minimum de commande côté serveur
    if ($commande_minimum > 0 && $total < $commande_minimum) {
        $_SESSION['erreur_commande'] = "Le montant minimum de commande est de " . number_format($commande_minimum, 0, ',', "\xc2\xa0") . " FCFA.";
        header('Location: checkout.php');
        exit();
    }

    // Générer un numéro de tracking unique
    $numero_tracking = genererNumeroTracking();
    $stmt_check = $pdo->prepare("SELECT id FROM commandes WHERE numero_tracking = ?");
    $stmt_check->execute([$numero_tracking]);
    while ($stmt_check->rowCount() > 0) {
        $numero_tracking = genererNumeroTracking();
        $stmt_check->execute([$numero_tracking]);
    }

    $client_info = json_encode([
        'prenom'    => $prenom,
        'telephone' => $telephone,
        'email'     => $email,
        'adresse'   => $adresse,
        'quartier'  => $quartier,
        'notes'     => $notes,
    ]);

    assurerSchemaTelegram($pdo);

    $stmt_resto = $pdo->prepare("SELECT * FROM restaurants WHERE id = ? LIMIT 1");
    $stmt_resto->execute([$restaurant_id]);
    $restaurant_row = $stmt_resto->fetch(PDO::FETCH_ASSOC) ?: [];
    $restaurant_nom = $restaurant_row['nom'] ?? 'Restaurant';

    // Revalider que le restaurant est actif et ouvert (ne pas se fier au bouton désactivé côté client)
    if (empty($restaurant_row) || $restaurant_row['statut'] !== 'actif' || !restaurantEnRegle($restaurant_row)) {
        $_SESSION['erreur_commande'] = "Ce restaurant n'est plus disponible. Veuillez choisir un autre restaurant.";
        header('Location: panier.php');
        exit();
    }
    if (!estRestaurantOuvert($restaurant_row['heure_ouverture'], $restaurant_row['heure_fermeture'])) {
        $_SESSION['erreur_commande'] = "Ce restaurant est fermé pour le moment. Veuillez réessayer pendant ses heures d'ouverture.";
        header('Location: panier.php');
        exit();
    }

    $client_id = $estConnecte ? $_SESSION['id'] : null;

    // Sauvegarder téléphone et adresse dans le profil (membres connectés uniquement)
    if ($estConnecte && $client_id) {
        try {
            $stmt_upd = $pdo->prepare("
                UPDATE utilisateurs
                SET telephone = COALESCE(NULLIF(?, ''), telephone),
                    adresse   = COALESCE(NULLIF(?, ''), adresse),
                    quartier  = COALESCE(NULLIF(?, ''), quartier)
                WHERE id = ?
            ");
            $stmt_upd->execute([$telephone, $adresse, $quartier, $client_id]);
        } catch (PDOException $e) {
            // Non bloquant
        }
    }

    $pdo->beginTransaction();

    // Pour les invités : client_id = NULL (colonne nullable, FK MySQL autorise NULL)
    $stmt = $pdo->prepare(
        $client_id
            ? "INSERT INTO commandes (numero_tracking, client_id, restaurant_id, total, statut, client_info, mode_paiement, created_at) VALUES (?, ?, ?, ?, 'en_attente', ?, ?, NOW())"
            : "INSERT INTO commandes (numero_tracking, restaurant_id, total, statut, client_info, mode_paiement, created_at) VALUES (?, ?, ?, 'en_attente', ?, ?, NOW())"
    );
    $params = $client_id
        ? [$numero_tracking, $client_id, $restaurant_id, $total, $client_info, $mode_paiement_db]
        : [$numero_tracking, $restaurant_id, $total, $client_info, $mode_paiement_db];
    $stmt->execute($params);
    $commande_id = $pdo->lastInsertId();

    $stmt_detail = $pdo->prepare("
        INSERT INTO commande_details (commande_id, plat_id, quantite, prix_unitaire)
        VALUES (?, ?, ?, ?)
    ");
    foreach ($items as $item) {
        $stmt_detail->execute([$commande_id, $item['id'], $item['quantite'], $item['prix']]);
    }

    // ─── Paiement Wave ────────────────────────────────────────────────────────
    if ($mode_paiement_db === 'wave') {
        require_once 'includes/wave.php';
        assurerSchemaWave($pdo);

        // Récupérer la clé Wave propre à ce restaurant
        $stmt_wave = $pdo->prepare("SELECT wave_api_key FROM restaurants WHERE id = ? LIMIT 1");
        $stmt_wave->execute([$restaurant_id]);
        $resto_wave_key = (string) ($stmt_wave->fetchColumn() ?: '');

        if (empty($resto_wave_key)) {
            $pdo->rollBack();
            $_SESSION['erreur_commande'] = "Ce restaurant n'a pas encore configuré son paiement Wave. Veuillez choisir le paiement à la livraison.";
            header('Location: checkout.php');
            exit();
        }

        $success_url = BASE_URL . 'wave_success.php?wave_session_id={WAVE_CHECKOUT_SESSION_ID}&numero=' . urlencode($numero_tracking);
        $error_url   = BASE_URL . 'wave_error.php?numero=' . urlencode($numero_tracking);

        $wave_session = creerSessionWave((int) $total, $numero_tracking, $success_url, $error_url, $resto_wave_key);

        if (!$wave_session) {
            $pdo->rollBack();
            $_SESSION['erreur_commande'] = "Impossible d'initialiser le paiement Wave. Réessayez ou choisissez un autre mode de paiement.";
            header('Location: checkout.php');
            exit();
        }

        $pdo->commit();

        // La notification restaurant sera envoyée dans wave_success.php,
        // uniquement après confirmation du paiement par Wave.

        $_SESSION['wave_api_key']         = $resto_wave_key;
        $_SESSION['wave_numero_tracking'] = $numero_tracking;
        $_SESSION['wave_commande_id']     = $commande_id;

        header('Location: ' . $wave_session['wave_launch_url']);
        exit();
    }

    // ─── Paiement espèces ─────────────────────────────────────────────────────
    $pdo->commit();

    // Email de confirmation au client (si email fourni)
    $email_envoye = !empty($email)
        ? envoyerEmailConfirmation($email, $prenom, $numero_tracking, $total, $restaurant_nom, $items)
        : false;

    // Notifier le restaurant (email + Telegram)
    _envoyerNotificationsRestaurant($restaurant_row, $prenom, $telephone, $adresse, $quartier, $notes, $total, $mode_paiement_db, $numero_tracking, $items);

    $_SESSION['cart']                        = [];
    $_SESSION['dernier_numero_tracking']     = $numero_tracking;
    $_SESSION['derniere_commande_id']        = $commande_id;
    $_SESSION['email_confirmation_envoye']   = $email_envoye;
    $_SESSION['email_client']                = $email;
    setcookie('saveur_cart', '', time() - 3600, '/');

    header('Location: confirmation.php');
    exit();

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Erreur commande: ' . $e->getMessage());
    $_SESSION['erreur_commande'] = "Une erreur technique est survenue. Veuillez réessayer.";
    header('Location: checkout.php');
    exit();
}
