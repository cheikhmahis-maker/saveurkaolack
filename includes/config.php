<?php
/**
 * =====================================================
 * INCLUDES/CONFIG.PHP — Configuration globale
 * Saveur Kaolack
 * =====================================================
 * 
 * À inclure EN PREMIER dans chaque page PHP
 */

// Empêcher l'accès direct
if (!defined('BASE_URL')) {
    // URL de base du site (adapter selon l'environnement)
    // XAMPP Local :
    define('BASE_URL', 'http://localhost/saveur-php/');
    
    // Production (décommenter quand en ligne) :
    // define('BASE_URL', 'https://www.saveurkaolack.sn/');
}

// Chemins URL (pour src="..." et affichage)
define('UPLOADS_URL', BASE_URL . 'uploads/');
define('PLATS_URL', UPLOADS_URL . 'plats/');
define('RESTOS_URL', UPLOADS_URL . 'restaurants/');
define('BANNIERES_URL', UPLOADS_URL . 'restaurants/bannieres/');
define('LOGOS_URL', UPLOADS_URL . 'restaurants/logos/');
define('DEFAUT_URL', UPLOADS_URL . 'defaut/');
define('ASSETS_URL', BASE_URL . 'assets/');
define('CSS_URL', ASSETS_URL . 'css/');
define('JS_URL', ASSETS_URL . 'js/');
define('IMAGES_URL', ASSETS_URL . 'images/');

// Chemins physiques Windows (pour PHP file_exists, upload)
define('ROOT_PATH', 'C:/xampp/htdocs/saveur-php/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');
define('PLATS_PATH', UPLOADS_PATH . 'plats/');
define('RESTOS_PATH', UPLOADS_PATH . 'restaurants/');
define('BANNIERES_PATH', UPLOADS_PATH . 'restaurants/bannieres/');
define('LOGOS_PATH', UPLOADS_PATH . 'restaurants/logos/');
define('DEFAUT_PATH', UPLOADS_PATH . 'defaut/');
define('ASSETS_PATH', ROOT_PATH . 'assets/');

// Images par défaut (URLs complètes)
define('IMG_PLAT_DEFAUT', DEFAUT_URL . 'plat_defaut.svg');
define('IMG_PLAT_DEFAUT_FILE', DEFAUT_PATH . 'plat_defaut.svg');
define('IMG_RESTO_DEFAUT', DEFAUT_URL . 'resto_defaut.svg');
define('IMG_RESTO_DEFAUT_FILE', DEFAUT_PATH . 'resto_defaut.svg');
define('IMG_BANNIERE_DEFAUT', DEFAUT_URL . 'banniere_defaut.svg');
define('IMG_BANNIERE_DEFAUT_FILE', DEFAUT_PATH . 'banniere_defaut.svg');
define('IMG_LOGO_DEFAUT', DEFAUT_URL . 'logo_defaut.svg');
define('IMG_LOGO_DEFAUT_FILE', DEFAUT_PATH . 'logo_defaut.svg');

// Informations du site
define('SITE_NOM', 'Saveur Kaolack');
define('SITE_SLOGAN', 'La cuisine kaolackoise authentique à domicile');
define('SITE_EMAIL', 'contact@saveurkaolack.sn');
define('SITE_TEL', '+221 78 521 65 68');
define('SITE_ADRESSE', 'Kaolack, Sénégal');

// Configuration des uploads
define('UPLOAD_MAX_SIZE', 2097152); // 2 Mo en octets
define('UPLOAD_MAX_WIDTH', 1920);   // Largeur max image
define('UPLOAD_MAX_HEIGHT', 1080); // Hauteur max image
define('UPLOAD_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
define('UPLOAD_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp', 'gif']);

// Configuration de session
define('SESSION_DUREE', 1800); // 30 minutes en secondes
define('SESSION_NOM', 'saveur_kaolack_session');

// Configuration sécurisée des cookies de session
$secure_cookie = (defined('ENVIRONMENT') && ENVIRONMENT === 'production'); // Secure uniquement en HTTPS
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_DUREE,
        'path' => '/',
        'domain' => '', // Domaine actuel
        'secure' => $secure_cookie, // True uniquement en HTTPS
        'httponly' => true, // Empêche l'accès via JavaScript
        'samesite' => 'Strict' // Protection CSRF
    ]);
}

// Configuration base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'saveur_kaolack');
define('DB_USER', 'root');
define('DB_PASS', ''); // Mot de passe vide pour XAMPP
define('DB_CHARSET', 'utf8mb4');

// Configuration de l'environnement
define('ENVIRONMENT', 'development'); // 'development' ou 'production'

// Mode debug (afficher les erreurs en développement)
define('DEBUG', ENVIRONMENT === 'development');

// Configuration des erreurs PHP selon l'environnement
if (ENVIRONMENT === 'production') {
    // Cacher toutes les erreurs en production
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
} else {
    // Afficher les erreurs en développement
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}

// Timezone
date_default_timezone_set('Africa/Dakar');
