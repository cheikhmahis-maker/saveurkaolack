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

// Récupérer les paramètres
$restaurant_id = isset($_POST['restaurant_id']) ? intval($_POST['restaurant_id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Validation des paramètres
if ($restaurant_id <= 0 || !in_array($action, ['valider', 'rejeter'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit();
}

try {
    // Connexion BDD
    $pdo = new PDO('mysql:host=localhost;dbname=saveur_kaolack;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Vérifier que le restaurant existe et est en attente
    $stmt = $pdo->prepare("SELECT id, nom, statut FROM restaurants WHERE id = ?");
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
    $nouveau_statut = ($action === 'valider') ? 'actif' : 'suspendu';
    
    // Si validation, créer un compte utilisateur pour le restaurant
    $compte_info = '';
    if ($action === 'valider') {
        // Générer un email et mot de passe
        $email = 'restaurant' . $restaurant_id . '@saveurkaolack.sn';
        $mot_de_passe = bin2hex(random_bytes(4)); // 8 caractères aléatoires
        $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
        
        // Créer l'utilisateur
        $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, email, telephone, password, role, statut) VALUES (?, ?, ?, ?, ?, 'restaurant', 'actif')");
        $stmt->execute([$restaurant['nom'], 'Gérant', $email, '770000000', $hash]);
        $utilisateur_id = $pdo->lastInsertId();
        
        // Lier l'utilisateur au restaurant
        $stmt = $pdo->prepare("UPDATE restaurants SET utilisateur_id = ? WHERE id = ?");
        $stmt->execute([$utilisateur_id, $restaurant_id]);
        
        $compte_info = " | Login: $email | Mot de passe: $mot_de_passe";
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
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()]);
}
