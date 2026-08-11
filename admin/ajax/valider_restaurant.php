<?php
/**
 * VALIDER_RESTAURANT.PHP - Endpoint AJAX pour validation des restaurants
 * Saveur Kaolack
 * 
 * POST : restaurant_id, action (valider|rejeter)
 * Retourne : JSON {success: bool, message: string}
 */

// Démarrer la session en premier
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/fonctions.php';
require_once '../../includes/notifications.php';

// Vérifier que l'utilisateur est admin
if (empty($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Vérifier le token CSRF
if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Erreur de sécurité. Rechargez la page.']);
    exit();
}

// Récupérer les paramètres
$restaurant_id = isset($_POST['restaurant_id']) ? intval($_POST['restaurant_id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Validation des paramètres
if ($restaurant_id <= 0 || !in_array($action, ['valider', 'rejeter'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit();
}

try {
    $pdo = getDB();
    
    // Vérifier que le restaurant existe et est en attente
    $stmt = $pdo->prepare("SELECT id, nom, statut, email, telephone FROM restaurants WHERE id = ?");
    $stmt->execute([$restaurant_id]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$restaurant) {
        echo json_encode(['success' => false, 'message' => 'Restaurant non trouvé']);
        exit();
    }
    
    if ($restaurant['statut'] !== 'en_attente') {
        echo json_encode(['success' => false, 'message' => 'Ce restaurant n\'est pas en attente']);
        exit();
    }
    
    // Déterminer le nouveau statut
    $nouveau_statut = ($action === 'valider') ? 'actif' : 'refuse';
    
    // Si validation, créer un compte utilisateur pour le restaurant
    $compte_info = '';
    if ($action === 'valider') {
        // Utiliser les vraies coordonnées du restaurant
        $email = $restaurant['email'];
        $telephone = $restaurant['telephone'];
        $mot_de_passe = bin2hex(random_bytes(4)); // 8 caractères aléatoires
        $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

        // Vérifier si un compte existe déjà avec cet email
        $stmt_check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? LIMIT 1");
        $stmt_check->execute([$email]);
        if ($stmt_check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Un compte existe déjà avec cet email.']);
            exit();
        }

        // Créer l'utilisateur avec les vraies informations
        $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, email, telephone, password, role, statut) VALUES (?, ?, ?, ?, ?, 'restaurant', 'actif')");
        $stmt->execute([$restaurant['nom'], 'Gérant', $email, $telephone, $hash]);
        $utilisateur_id = $pdo->lastInsertId();
        
        // Lier l'utilisateur au restaurant
        $stmt = $pdo->prepare("UPDATE restaurants SET utilisateur_id = ? WHERE id = ?");
        $stmt->execute([$utilisateur_id, $restaurant_id]);

        // Envoyer les identifiants par email au restaurant
        $email_envoye = envoyerEmailIdentifiantsRestaurant($restaurant['nom'], $email, $mot_de_passe);

        $compte_info = $email_envoye
            ? " | Identifiants envoyés par email à $email"
            : " | ⚠️ Email non envoyé — transmettez-les manuellement : Login: $email | Mot de passe: $mot_de_passe";
    }
    
    // Mettre à jour le statut
    $stmt = $pdo->prepare("UPDATE restaurants SET statut = ? WHERE id = ?");
    $stmt->execute([$nouveau_statut, $restaurant_id]);
    
    // Compter les restaurants restants en attente
    $nb_restos_attente = $pdo->query("SELECT COUNT(*) FROM restaurants WHERE statut = 'en_attente'")->fetchColumn();
    
    // Message de succès
    $message = ($action === 'valider') 
        ? "Restaurant '{$restaurant['nom']}' validé avec succès !{$compte_info}"
        : "Restaurant '{$restaurant['nom']}' rejeté.";
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'nb_restos_attente' => $nb_restos_attente,
        'action' => $action,
        'restaurant_id' => $restaurant_id
    ]);
    
} catch (PDOException $e) {
    error_log('Erreur valider_restaurant: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Une erreur technique est survenue.']);
}
