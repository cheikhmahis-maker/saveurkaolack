<?php
/**
 * WAVE_SUCCESS.PHP - Callback Wave après paiement réussi
 * Wave redirige ici avec ?wave_session_id=xxx&numero=SK-XXXX
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';
require_once 'includes/wave.php';
require_once 'includes/notifications.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$wave_session_id = trim($_GET['wave_session_id'] ?? '');
$numero_hint     = trim($_GET['numero'] ?? '');

if (empty($wave_session_id)) {
    header('Location: index.php');
    exit();
}

// Récupérer la clé Wave : d'abord depuis la session, sinon depuis la BDD
$wave_api_key = $_SESSION['wave_api_key'] ?? '';

if (empty($wave_api_key) && !empty($numero_hint)) {
    try {
        $pdo_hint = getDB();
        $stmt_hint = $pdo_hint->prepare("
            SELECT r.wave_api_key
            FROM commandes c
            JOIN restaurants r ON r.id = c.restaurant_id
            WHERE c.numero_tracking = ?
            LIMIT 1
        ");
        $stmt_hint->execute([$numero_hint]);
        $wave_api_key = (string) ($stmt_hint->fetchColumn() ?: '');
    } catch (PDOException $e) {
        // Fallback silencieux — wave_api_key reste vide, la vérification échouera proprement
    }
}

// Vérifier le paiement auprès de l'API Wave du restaurant
$session_wave = verifierSessionWave($wave_session_id, $wave_api_key);

if (!$session_wave || ($session_wave['payment_status'] ?? '') !== 'succeeded') {
    $numero = urlencode($_SESSION['wave_numero_tracking'] ?? $numero_hint);
    header('Location: wave_error.php?raison=echec&numero=' . $numero);
    exit();
}

// Sécurité : le client_reference doit correspondre au numéro de commande
$numero_tracking = $session_wave['client_reference'] ?? '';

if (empty($numero_tracking)) {
    header('Location: wave_error.php?raison=introuvable');
    exit();
}

try {
    $pdo = getDB();

    $stmt = $pdo->prepare("SELECT * FROM commandes WHERE numero_tracking = ? LIMIT 1");
    $stmt->execute([$numero_tracking]);
    $commande = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$commande || $commande['mode_paiement'] !== 'wave') {
        header('Location: wave_error.php?raison=introuvable');
        exit();
    }

    // Idempotence : ne pas retraiter une commande déjà confirmée
    if ($commande['statut'] !== 'en_attente') {
        $_SESSION['dernier_numero_tracking']   = $numero_tracking;
        $_SESSION['derniere_commande_id']      = $commande['id'];
        $_SESSION['email_confirmation_envoye'] = false;
        $_SESSION['email_client']              = '';
        header('Location: confirmation.php');
        exit();
    }

    // Confirmer la commande
    $pdo->prepare("UPDATE commandes SET statut = 'confirmee' WHERE numero_tracking = ?")
        ->execute([$numero_tracking]);

    // Récupérer infos client depuis client_info
    $client_info  = json_decode($commande['client_info'] ?? '{}', true);
    $email_client = $client_info['email']     ?? '';
    $prenom       = $client_info['prenom']    ?? 'Client';
    $telephone    = $client_info['telephone'] ?? '';
    $adresse      = $client_info['adresse']   ?? '';
    $quartier     = $client_info['quartier']  ?? '';
    $notes        = $client_info['notes']     ?? '';

    // Récupérer les articles pour l'email et les notifications
    $stmt_details = $pdo->prepare("
        SELECT cd.quantite, cd.prix_unitaire, p.nom, p.photo
        FROM commande_details cd
        JOIN plats p ON p.id = cd.plat_id
        WHERE cd.commande_id = ?
    ");
    $stmt_details->execute([$commande['id']]);
    $items_email = array_map(fn($row) => [
        'nom'      => $row['nom'],
        'photo'    => $row['photo'],
        'prix'     => $row['prix_unitaire'],
        'quantite' => $row['quantite'],
    ], $stmt_details->fetchAll(PDO::FETCH_ASSOC));

    // Récupérer toutes les infos restaurant pour les notifications
    $stmt_resto = $pdo->prepare("SELECT * FROM restaurants WHERE id = ? LIMIT 1");
    $stmt_resto->execute([$commande['restaurant_id']]);
    $restaurant_row = $stmt_resto->fetch(PDO::FETCH_ASSOC) ?: [];
    $restaurant_nom = $restaurant_row['nom'] ?? 'Restaurant';

    // Email de confirmation au client
    $email_envoye = envoyerEmailConfirmation(
        $email_client, $prenom, $numero_tracking,
        $commande['total'], $restaurant_nom, $items_email
    );

    // Notifier le restaurant (email + Telegram) — paiement Wave confirmé
    envoyerEmailRestaurant($restaurant_row, $prenom, $telephone, $adresse, $quartier, $notes, (float) $commande['total'], 'wave', $numero_tracking, $items_email);
    $chat_id = $restaurant_row['telegram_chat_id'] ?? '';
    if (!empty($chat_id)) {
        $msg = buildMessageTelegram($restaurant_nom, $prenom, $telephone, $adresse, $quartier, $notes, (float) $commande['total'], 'wave', $numero_tracking, $items_email);
        envoyerNotificationTelegram('', $chat_id, $msg);
    }

    $_SESSION['cart']                        = [];
    $_SESSION['dernier_numero_tracking']     = $numero_tracking;
    $_SESSION['derniere_commande_id']        = $commande['id'];
    $_SESSION['email_confirmation_envoye']   = $email_envoye;
    $_SESSION['email_client']                = $email_client;
    $_SESSION['paiement_wave_confirme']      = true;
    unset($_SESSION['wave_api_key'], $_SESSION['wave_numero_tracking'], $_SESSION['wave_commande_id']);

    header('Location: confirmation.php');
    exit();

} catch (PDOException $e) {
    header('Location: wave_error.php?raison=erreur_serveur');
    exit();
}
