<?php
/**
 * TRAITER_COMMANDE.PHP - Crée la commande avec numéro de tracking
 */

require_once 'includes/config.php';
require_once 'includes/fonctions.php';

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier que l'utilisateur est connecté comme client
if (empty($_SESSION['id']) || $_SESSION['role'] !== 'client') {
    $_SESSION['erreur_commande'] = "Vous devez être connecté pour passer une commande.";
    header('Location: connexion.php?redirect=checkout.php');
    exit();
}

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: panier.php');
    exit();
}

// Vérifier le panier
if (empty($_SESSION['cart'])) {
    header('Location: panier.php');
    exit();
}

// Récupérer les données
$prenom = trim($_POST['prenom'] ?? '');
$telephone = trim($_POST['telephone'] ?? '');
$email = trim($_POST['email'] ?? '');
$adresse = trim($_POST['adresse'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$restaurant_id = intval($_POST['restaurant_id'] ?? 0);

// Validation
if (empty($prenom) || empty($telephone) || empty($email) || empty($adresse)) {
    $_SESSION['erreur_commande'] = "Veuillez remplir tous les champs obligatoires (nom, téléphone, email, adresse).";
    header('Location: checkout.php');
    exit();
}

// Validation email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['erreur_commande'] = "L'adresse email n'est pas valide.";
    header('Location: checkout.php');
    exit();
}

// Calculer le total
$items = $_SESSION['cart'];
$total = 0;
foreach ($items as $item) {
    $total += $item['prix'] * $item['quantite'];
}
$total += 1000; // Frais de livraison

// Générer un numéro de tracking unique
function genererNumeroTracking() {
    $prefix = 'SK';
    $date = date('ymd');
    $random = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 4));
    return $prefix . '-' . $date . '-' . $random;
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=saveur_kaolack;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Générer un numéro unique
    $numero_tracking = genererNumeroTracking();
    $stmt = $pdo->prepare("SELECT id FROM commandes WHERE numero_tracking = ?");
    $stmt->execute([$numero_tracking]);
    
    // Si existe déjà, régénérer
    while ($stmt->rowCount() > 0) {
        $numero_tracking = genererNumeroTracking();
        $stmt->execute([$numero_tracking]);
    }
    
    // Préparer les infos client
    $client_info = json_encode([
        'prenom' => $prenom,
        'telephone' => $telephone,
        'email' => $email,
        'adresse' => $adresse,
        'notes' => $notes
    ]);
    
    // Récupérer le nom du restaurant
    $stmt_resto = $pdo->prepare("SELECT nom FROM restaurants WHERE id = ? LIMIT 1");
    $stmt_resto->execute([$restaurant_id]);
    $restaurant_nom = $stmt_resto->fetchColumn() ?: 'Restaurant';
    
    // Récupérer l'ID client (déjà vérifié en haut du fichier)
    $client_id = $_SESSION['id'];
    
    // Insérer la commande (avec ou sans client_id)
    $stmt = $pdo->prepare("
        INSERT INTO commandes (numero_tracking, client_id, restaurant_id, total, statut, client_info, created_at) 
        VALUES (?, ?, ?, ?, 'en_attente', ?, NOW())
    ");
    $stmt->execute([$numero_tracking, $client_id, $restaurant_id, $total, $client_info]);
    $commande_id = $pdo->lastInsertId();
    
    // Insérer les détails de la commande
    $stmt_detail = $pdo->prepare("
        INSERT INTO commande_details (commande_id, plat_id, quantite, prix_unitaire) 
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($items as $item) {
        $stmt_detail->execute([
            $commande_id,
            $item['id'],
            $item['quantite'],
            $item['prix']
        ]);
    }
    
    // Envoyer l'email de confirmation
    $email_envoye = envoyerEmailConfirmation($email, $prenom, $numero_tracking, $total, $restaurant_nom, $items);
    
    // Vider le panier
    $_SESSION['cart'] = [];
    
    // Stocker le numéro pour la page de confirmation
    $_SESSION['dernier_numero_tracking'] = $numero_tracking;
    $_SESSION['derniere_commande_id'] = $commande_id;
    $_SESSION['email_confirmation_envoye'] = $email_envoye;
    $_SESSION['email_client'] = $email;
    
    // Redirection vers la page de confirmation
    header('Location: confirmation.php');
    exit();
    
} catch (PDOException $e) {
    $_SESSION['erreur_commande'] = "Erreur lors de la création de la commande : " . $e->getMessage();
    header('Location: checkout.php?mode=invite');
    exit();
}
