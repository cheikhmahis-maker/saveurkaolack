<?php
/**
 * EXPORT_COMMANDES.PHP - Export CSV des commandes (comptabilité)
 * Saveur Kaolack
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../connexion.php');
    exit();
}

require_once '../includes/config.php';
require_once '../includes/db.php';

// Filtre de période (optionnel) : dates au format YYYY-MM-DD depuis le formulaire
$date_debut = $_GET['date_debut'] ?? '';
$date_fin   = $_GET['date_fin'] ?? '';

$where  = [];
$params = [];

if (!empty($date_debut) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_debut)) {
    $where[]  = 'DATE(c.created_at) >= ?';
    $params[] = $date_debut;
}
if (!empty($date_fin) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_fin)) {
    $where[]  = 'DATE(c.created_at) <= ?';
    $params[] = $date_fin;
}

$sql = "
    SELECT c.numero_tracking, c.created_at, r.nom AS resto_nom, c.client_info,
           c.total, c.statut, c.mode_paiement
    FROM commandes c
    LEFT JOIN restaurants r ON c.restaurant_id = r.id
";
if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY c.created_at DESC';

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erreur export_commandes: ' . $e->getMessage());
    http_response_code(500);
    echo 'Erreur lors de la génération du fichier.';
    exit;
}

$statutsLabels = [
    'en_attente'     => 'En attente',
    'confirmee'      => 'Confirmée',
    'en_preparation' => 'En préparation',
    'en_route'       => 'En livraison',
    'livree'         => 'Livrée',
    'annulee'        => 'Annulée',
];
$paiementLabels = [
    'especes'      => 'Espèces',
    'wave'         => 'Wave',
    'orange_money' => 'Orange Money',
    'free_money'   => 'Free Money',
];

$nomFichier = 'commandes_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nomFichier . '"');

// Empêche l'injection de formule dans Excel/LibreOffice : préfixe d'une apostrophe
// toute valeur commençant par =, +, -, @ (sinon Excel l'interprète comme une formule).
function csvChampSecurise($valeur) {
    $valeur = (string) $valeur;
    if ($valeur !== '' && in_array($valeur[0], ['=', '+', '-', '@'], true)) {
        return "'" . $valeur;
    }
    return $valeur;
}

$out = fopen('php://output', 'w');

// BOM UTF-8 : nécessaire pour qu'Excel affiche correctement les accents français
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, [
    'Numéro de suivi', 'Date', 'Restaurant', 'Client', 'Téléphone', 'Email',
    'Adresse', 'Total (FCFA)', 'Statut', 'Mode de paiement',
], ';');

foreach ($commandes as $cmd) {
    $infos = json_decode($cmd['client_info'] ?? '{}', true) ?: [];

    fputcsv($out, [
        $cmd['numero_tracking'] ?? '',
        date('d/m/Y H:i', strtotime($cmd['created_at'])),
        $cmd['resto_nom'] ?? '',
        csvChampSecurise(trim(($infos['prenom'] ?? '') . ' ' . ($infos['nom'] ?? ''))),
        csvChampSecurise($infos['telephone'] ?? ''),
        csvChampSecurise($infos['email'] ?? ''),
        csvChampSecurise($infos['adresse'] ?? ''),
        $cmd['total'] ?? 0,
        $statutsLabels[$cmd['statut']] ?? $cmd['statut'],
        $paiementLabels[$cmd['mode_paiement']] ?? $cmd['mode_paiement'],
    ], ';');
}

fclose($out);
exit;
