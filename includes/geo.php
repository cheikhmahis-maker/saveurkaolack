<?php
/**
 * GEO.PHP — Géolocalisation : auto-migration + calculs
 */

if (!defined('BASE_URL')) {
    die('Erreur : config.php doit être inclus avant geo.php');
}

/**
 * Ajoute les colonnes latitude/longitude à la table restaurants si absentes
 */
function assurerSchemaGeo(PDO $pdo): void {
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM restaurants LIKE 'latitude'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE restaurants ADD COLUMN latitude DECIMAL(10,8) DEFAULT NULL");
        }
        $cols2 = $pdo->query("SHOW COLUMNS FROM restaurants LIKE 'longitude'")->fetchAll();
        if (empty($cols2)) {
            $pdo->exec("ALTER TABLE restaurants ADD COLUMN longitude DECIMAL(11,8) DEFAULT NULL");
        }
    } catch (PDOException) {
        // Non bloquant
    }
}

/**
 * Corrige la table avis : ajoute commande_id, rend utilisateur_id nullable
 */
function assurerSchemaAvis(PDO $pdo): void {
    try {
        $tables = $pdo->query("SHOW TABLES LIKE 'avis'")->fetchAll();
        if (empty($tables)) return;

        // Ajouter commande_id si absent
        $cols = $pdo->query("SHOW COLUMNS FROM avis LIKE 'commande_id'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE avis ADD COLUMN commande_id INT(11) DEFAULT NULL AFTER id");
        }

        // Rendre utilisateur_id nullable (pour les commandes invité)
        $col = $pdo->query("SHOW COLUMNS FROM avis LIKE 'utilisateur_id'")->fetch(PDO::FETCH_ASSOC);
        if ($col && $col['Null'] === 'NO') {
            $pdo->exec("ALTER TABLE avis MODIFY COLUMN utilisateur_id INT(11) DEFAULT NULL");
            // Supprimer la contrainte FK qui interdit NULL — recréer sans
            try {
                $pdo->exec("ALTER TABLE avis DROP FOREIGN KEY avis_utilisateur_fk");
                $pdo->exec("ALTER TABLE avis ADD CONSTRAINT avis_utilisateur_fk FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL");
            } catch (PDOException) {}
        }
    } catch (PDOException) {
        // Non bloquant
    }
}

/**
 * Calcule la distance en km entre 2 points GPS — formule Haversine
 */
function calculerDistance(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $R    = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a    = sin($dLat / 2) ** 2
          + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return round($R * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);
}
