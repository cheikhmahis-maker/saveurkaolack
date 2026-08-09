<?php
/**
 * AJAX — Reverse geocoding via Nominatim (OpenStreetMap)
 * Reçoit lat/lng → retourne adresse lisible en JSON
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

// === RATE LIMITING : 15 requêtes par minute par visiteur ===
// Nominatim bannit les IP abusives ; ça casserait la géolocalisation pour tout le site.
$cleRate = 'geocoder_rate';
if (!isset($_SESSION[$cleRate]) || (time() - $_SESSION[$cleRate]['debut']) >= 60) {
    $_SESSION[$cleRate] = ['count' => 0, 'debut' => time()];
}
if ($_SESSION[$cleRate]['count'] >= 15) {
    echo json_encode(['erreur' => 'Trop de requêtes. Attendez une minute avant de réessayer.']);
    exit();
}
$_SESSION[$cleRate]['count']++;

$lat = floatval($_GET['lat'] ?? 0);
$lng = floatval($_GET['lng'] ?? 0);

if (!$lat || !$lng || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    echo json_encode(['erreur' => 'Coordonnées invalides']);
    exit();
}

$url  = "https://nominatim.openstreetmap.org/reverse?lat={$lat}&lon={$lng}&format=json&accept-language=fr";
$opts = [
    'http' => [
        'method'  => 'GET',
        'header'  => "User-Agent: SaveurKaolack/1.0\r\n",
        'timeout' => 6,
    ],
];

$result = @file_get_contents($url, false, stream_context_create($opts));

if (!$result) {
    echo json_encode(['erreur' => 'Service de géolocalisation indisponible']);
    exit();
}

$data = json_decode($result, true);
if (!$data || isset($data['error'])) {
    echo json_encode(['erreur' => 'Aucune adresse trouvée à cet endroit']);
    exit();
}

$addr     = $data['address'] ?? [];
$route    = $addr['road'] ?? $addr['pedestrian'] ?? '';
$numero   = $addr['house_number'] ?? '';
$quartier = $addr['suburb'] ?? $addr['neighbourhood'] ?? $addr['quarter'] ?? $addr['residential'] ?? '';
$ville    = $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? '';

// Construire une adresse courte et propre
$adresse_parts = array_filter([$numero, $route]);
$adresse = implode(' ', $adresse_parts);
if (empty($adresse)) {
    $adresse = $quartier ?: $ville;
}

echo json_encode([
    'adresse'  => $adresse,
    'quartier' => $quartier,
    'ville'    => $ville,
    'display'  => $data['display_name'] ?? '',
    'lat'      => $lat,
    'lng'      => $lng,
]);
