<?php
/**
 * MODIFIER EMAIL/TELEPHONE RESTAURANT (admin)
 * Met à jour la table restaurants ET la table utilisateurs liée.
 */

session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/fonctions.php';

header('Content-Type: application/json');

if (empty($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Erreur de sécurité. Rechargez la page.']);
    exit();
}

$restaurant_id = intval($_POST['id'] ?? 0);
$email      = trim($_POST['email'] ?? '');
$telephone  = trim($_POST['telephone'] ?? '');

if (!$restaurant_id) {
    echo json_encode(['success' => false, 'message' => 'ID restaurant manquant']);
    exit();
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Adresse email invalide']);
    exit();
}

$email     = substr($email, 0, 150);
$telephone = substr($telephone, 0, 20);

try {
    $pdo = getDB();

    // Vérifier que le restaurant existe et récupérer l'utilisateur lié
    $stmt = $pdo->prepare("SELECT id, utilisateur_id FROM restaurants WHERE id = ?");
    $stmt->execute([$restaurant_id]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$restaurant) {
        echo json_encode(['success' => false, 'message' => 'Restaurant introuvable']);
        exit();
    }

    // Vérifier unicité email dans utilisateurs (hors l'utilisateur lié)
    if ($restaurant['utilisateur_id']) {
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
        $stmt->execute([$email, $restaurant['utilisateur_id']]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé par un autre compte']);
            exit();
        }
    }

    $pdo->beginTransaction();

    // Mettre à jour la table restaurants
    $stmt = $pdo->prepare("UPDATE restaurants SET email = ?, telephone = ? WHERE id = ?");
    $stmt->execute([$email, $telephone, $restaurant_id]);

    // Mettre à jour la table utilisateurs si un compte est lié
    if ($restaurant['utilisateur_id']) {
        $stmt = $pdo->prepare("UPDATE utilisateurs SET email = ? WHERE id = ? AND role = 'restaurant'");
        $stmt->execute([$email, $restaurant['utilisateur_id']]);
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Email et téléphone mis à jour avec succès']);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Erreur modifier_email_restaurant: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur technique. Réessayez.']);
}
?>
