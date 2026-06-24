<?php
/**
 * AJAX — Vérifie s'il y a de nouvelles commandes depuis la dernière vérification
 * Retourne JSON : { "nouvelles": N, "timestamp": unixtime }
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Vérification que c'est bien un restaurant connecté
if (empty($_SESSION['id']) || ($_SESSION['role'] ?? '') !== 'restaurant') {
    echo json_encode(['nouvelles' => 0, 'erreur' => 'non_connecte']);
    exit();
}

require_once '../includes/config.php';
require_once '../includes/db.php';

try {
    $pdo = getDB();

    $stmt = $pdo->prepare("SELECT id FROM restaurants WHERE utilisateur_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['id']]);
    $restaurant_id = $stmt->fetchColumn();

    if (!$restaurant_id) {
        echo json_encode(['nouvelles' => 0]);
        exit();
    }

    // `depuis` = timestamp UNIX envoyé par le client JS
    $depuis_ts = intval($_GET['depuis'] ?? 0);
    // Si 0 ou invalide, regarder la dernière minute
    $depuis_dt = ($depuis_ts > 0)
        ? date('Y-m-d H:i:s', $depuis_ts)
        : date('Y-m-d H:i:s', time() - 60);

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM commandes
        WHERE restaurant_id = ? AND created_at > ?
    ");
    $stmt->execute([$restaurant_id, $depuis_dt]);
    $nb = (int) $stmt->fetchColumn();

    echo json_encode(['nouvelles' => $nb, 'timestamp' => time()]);

} catch (PDOException $e) {
    echo json_encode(['nouvelles' => 0, 'erreur' => 'db']);
}
