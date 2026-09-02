<?php
/**
 * =====================================================
 * INCLUDES/DB.PHP — Connexion base de données PDO
 * Saveur Kaolack
 * =====================================================
 * ²
 * Connexion PDO sécurisée avec gestion d'erreurs
 * Inclure APRES config.php
 */

// Vérifier que config.php est inclus
if (!defined('BASE_URL')) {
    die('Erreur : config.php doit être inclus avant db.php');
}

// Connexion PDO
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,           // Lancer exceptions en cas d'erreur
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,      // Retourner tableaux associatifs
        PDO::ATTR_EMULATE_PREPARES => false,                   // Désactiver émulation prepared statements
        PDO::ATTR_PERSISTENT => true,                          // Connexions persistantes pour performance
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci"
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    // En production : ne pas afficher les détails de l'erreur
    if (DEBUG) {
        die('<strong>Erreur base de données :</strong> ' . htmlspecialchars($e->getMessage()));
    } else {
        die('Erreur de connexion à la base de données. Veuillez réessayer plus tard.');
    }
}

/**
 * Fonction helper pour obtenir la connexion PDO
 * @return PDO
 */
function getDB(): PDO {
    global $pdo;
    return $pdo;
}

/**
 * Crée les index manquants utilisés par les pages les plus visitées
 * (fiches restaurant, listes filtrées, tableau de bord). Sans effet si
 * les index existent déjà. Non bloquant : une erreur ici ne doit jamais
 * empêcher le site de fonctionner.
 */
function assurerIndexPerformance(PDO $pdo): void {
    $index = [
        'avis'        => ['idx_avis_restaurant'        => 'restaurant_id',  'idx_avis_utilisateur'       => 'utilisateur_id'],
        'restaurants' => ['idx_restaurants_statut'      => 'statut'],
        'plats'       => ['idx_plats_disponible'        => 'disponible'],
        'commandes'   => ['idx_commandes_created'        => 'created_at',
                           'idx_commandes_resto_created' => '`restaurant_id`, `created_at`'],
    ];
    try {
        foreach ($index as $table => $cles) {
            foreach ($cles as $nomIndex => $colonnes) {
                $existe = $pdo->query("SHOW INDEX FROM `$table` WHERE Key_name = '$nomIndex'")->fetchAll();
                if (empty($existe)) {
                    $pdo->exec("ALTER TABLE `$table` ADD INDEX `$nomIndex` ($colonnes)");
                }
            }
        }
    } catch (PDOException $e) {
        // Non bloquant — table ou colonne peut-être absente sur une ancienne installation
    }
}
assurerIndexPerformance($pdo);

/**
 * Vérifier si la connexion est active
 * @return bool
 */
function dbEstConnecte(): bool {
    try {
        global $pdo;
        $pdo->query('SELECT 1');
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
