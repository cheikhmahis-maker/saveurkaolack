<?php
/**
 * ACTIONS RESTAURANT - Activer, Suspendre, Supprimer
 */

session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Vérifier que c'est un admin
if (empty($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

$action = $_POST['action'] ?? '';
$restaurant_id = intval($_POST['id'] ?? 0);

if (!$restaurant_id) {
    echo json_encode(['success' => false, 'message' => 'ID restaurant manquant']);
    exit();
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=saveur_kaolack;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    switch ($action) {
        case 'activer':
            $stmt = $pdo->prepare("UPDATE restaurants SET statut = 'actif' WHERE id = ?");
            $stmt->execute([$restaurant_id]);
            echo json_encode(['success' => true, 'message' => 'Restaurant activé avec succès']);
            break;
            
        case 'suspendre':
            $stmt = $pdo->prepare("UPDATE restaurants SET statut = 'inactif' WHERE id = ?");
            $stmt->execute([$restaurant_id]);
            echo json_encode(['success' => true, 'message' => 'Restaurant suspendu avec succès']);
            break;
            
        case 'supprimer':
            // Supprimer les plats du restaurant
            $stmt = $pdo->prepare("DELETE FROM plats WHERE restaurant_id = ?");
            $stmt->execute([$restaurant_id]);
            
            // Supprimer les commandes du restaurant
            $stmt = $pdo->prepare("DELETE FROM commande_details WHERE commande_id IN (SELECT id FROM commandes WHERE restaurant_id = ?)");
            $stmt->execute([$restaurant_id]);
            
            $stmt = $pdo->prepare("DELETE FROM commandes WHERE restaurant_id = ?");
            $stmt->execute([$restaurant_id]);
            
            // Récupérer l'utilisateur associé avant de supprimer
            $stmt = $pdo->prepare("SELECT utilisateur_id FROM restaurants WHERE id = ?");
            $stmt->execute([$restaurant_id]);
            $resto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Supprimer le restaurant
            $stmt = $pdo->prepare("DELETE FROM restaurants WHERE id = ?");
            $stmt->execute([$restaurant_id]);
            
            // Supprimer l'utilisateur associé
            if ($resto && $resto['utilisateur_id']) {
                $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ? AND role = 'restaurant'");
                $stmt->execute([$resto['utilisateur_id']]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Restaurant supprimé définitivement']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur BDD: ' . $e->getMessage()]);
}
?>
