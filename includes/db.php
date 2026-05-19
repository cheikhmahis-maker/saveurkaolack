<?php
/**
 * =====================================================
 * INCLUDES/DB.PHP — Connexion base de données PDO
 * Saveur Kaolack
 * =====================================================
 * 
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
