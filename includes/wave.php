<?php
/**
 * INCLUDES/WAVE.PHP — Intégration API Wave Checkout
 * Chaque restaurant utilise sa propre clé API Wave.
 */

/**
 * Vérifie que les colonnes nécessaires au paiement Wave existent
 * et les crée automatiquement si elles sont absentes.
 */
function assurerSchemaWave(PDO $pdo): void {
    // wave_api_key dans restaurants
    try {
        $pdo->query("SELECT wave_api_key FROM restaurants LIMIT 0");
    } catch (PDOException $e) {
        $pdo->exec("ALTER TABLE restaurants ADD COLUMN wave_api_key VARCHAR(255) NULL");
    }

    // mode_paiement dans commandes
    try {
        $pdo->query("SELECT mode_paiement FROM commandes LIMIT 0");
    } catch (PDOException $e) {
        $pdo->exec("ALTER TABLE commandes ADD COLUMN mode_paiement ENUM('especes','wave','orange_money','free_money') DEFAULT 'especes'");
    }
}

function creerSessionWave(int $montant, string $reference, string $success_url, string $error_url, string $api_key = ''): ?array {
    $key  = !empty($api_key) ? $api_key : WAVE_API_KEY;
    $data = [
        'amount'           => (string) $montant,
        'currency'         => 'XOF',
        'success_url'      => $success_url,
        'error_url'        => $error_url,
        'client_reference' => $reference,
    ];

    $ch = curl_init(WAVE_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode !== 200 || !$response) {
        return null;
    }

    $result = json_decode($response, true);
    return isset($result['wave_launch_url']) ? $result : null;
}

function verifierSessionWave(string $session_id, string $api_key = ''): ?array {
    $key = !empty($api_key) ? $api_key : WAVE_API_KEY;

    $ch = curl_init(WAVE_API_URL . '/' . $session_id);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $key,
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode !== 200 || !$response) {
        return null;
    }

    return json_decode($response, true);
}
