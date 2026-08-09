<?php
/**
 * ACTIONS RESTAURANT - Activer, Suspendre, Supprimer
 */

session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/fonctions.php';

header('Content-Type: application/json');

// Vérifier que c'est un admin
if (empty($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

// Vérifier le token CSRF
if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Erreur de sécurité. Rechargez la page.']);
    exit();
}

$action = $_POST['action'] ?? '';
$restaurant_id = intval($_POST['id'] ?? 0);

if (!$restaurant_id) {
    echo json_encode(['success' => false, 'message' => 'ID restaurant manquant']);
    exit();
}

try {
    $pdo = getDB();
    
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

        case 'prolonger_abonnement':
            assurerSchemaEssai($pdo);
            $jours = intval($_POST['jours'] ?? 0);
            if ($jours < 1 || $jours > 365) {
                echo json_encode(['success' => false, 'message' => 'Nombre de jours invalide (1 à 365).']);
                break;
            }
            // Repart de la date de fin actuelle si elle est encore dans le futur, sinon d'aujourd'hui
            $stmt = $pdo->prepare("
                UPDATE restaurants
                SET abonnement_jusquau = DATE_ADD(GREATEST(CURDATE(), COALESCE(abonnement_jusquau, CURDATE())), INTERVAL ? DAY)
                WHERE id = ?
            ");
            $stmt->execute([$jours, $restaurant_id]);
            $stmt = $pdo->prepare("SELECT abonnement_jusquau FROM restaurants WHERE id = ?");
            $stmt->execute([$restaurant_id]);
            $nouvelleDate = $stmt->fetchColumn();
            echo json_encode(['success' => true, 'message' => "Abonnement actif jusqu'au " . date('d/m/Y', strtotime($nouvelleDate))]);
            break;
            
        case 'supprimer':
            $pdo->beginTransaction();
            try {
                // Supprimer les plats du restaurant
                $stmt = $pdo->prepare("DELETE FROM plats WHERE restaurant_id = ?");
                $stmt->execute([$restaurant_id]);

                // Supprimer les détails de commandes
                $stmt = $pdo->prepare("DELETE FROM commande_details WHERE commande_id IN (SELECT id FROM commandes WHERE restaurant_id = ?)");
                $stmt->execute([$restaurant_id]);

                // Supprimer les commandes
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

                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Restaurant supprimé définitivement']);
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log('Erreur suppression restaurant: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression. Aucune donnée modifiée.']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
    
} catch (PDOException $e) {
    error_log('Erreur restaurant_action: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Une erreur technique est survenue.']);
}
?>
