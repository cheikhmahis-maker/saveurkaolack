<?php
/**
 * =====================================================
 * INCLUDES/CONFIG.PHP — Configuration globale
 * Saveur Kaolack
 * =====================================================
 */

// ─── Environnement — détecté automatiquement ─────────────────────────────────
// localhost = développement, tout autre domaine = production
$_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_is_local = in_array(strtolower(explode(':', $_host)[0]), ['localhost', '127.0.0.1', '::1']);

if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', $_is_local ? 'development' : 'production');
}

define('DEBUG', ENVIRONMENT === 'development');

// ─── Erreurs PHP ──────────────────────────────────────────────────────────────
if (ENVIRONMENT === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', dirname(__DIR__) . '/logs/php_errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}

// ─── URL de base — détectée automatiquement ───────────────────────────────────
// En local  → http://localhost/saveur-php/
// En ligne  → https://www.votredomaine.sn/  (racine du domaine)
if (!defined('BASE_URL')) {
    if ($_is_local) {
        define('BASE_URL', 'http://localhost/saveur-php/');
    } else {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        define('BASE_URL', $protocol . '://' . $_host . '/');
    }
}

// ─── URLs publiques ───────────────────────────────────────────────────────────
define('UPLOADS_URL',   BASE_URL . 'uploads/');
define('PLATS_URL',     UPLOADS_URL . 'plats/');
define('RESTOS_URL',    UPLOADS_URL . 'restaurants/');
define('BANNIERES_URL', UPLOADS_URL . 'restaurants/bannieres/');
define('LOGOS_URL',     UPLOADS_URL . 'restaurants/logos/');
define('DEFAUT_URL',    UPLOADS_URL . 'defaut/');
define('ASSETS_URL',    BASE_URL . 'assets/');
define('CSS_URL',       ASSETS_URL . 'css/');
define('JS_URL',        ASSETS_URL . 'js/');
define('IMAGES_URL',    ASSETS_URL . 'images/');

// ─── Chemins physiques — détectés automatiquement ─────────────────────────────
// dirname(__DIR__) remonte d'un niveau depuis includes/ → racine du projet
// Fonctionne sur Windows (XAMPP) ET Linux (hébergeur)
define('ROOT_PATH',      dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('UPLOADS_PATH',   ROOT_PATH . 'uploads' . DIRECTORY_SEPARATOR);
define('PLATS_PATH',     UPLOADS_PATH . 'plats' . DIRECTORY_SEPARATOR);
define('RESTOS_PATH',    UPLOADS_PATH . 'restaurants' . DIRECTORY_SEPARATOR);
define('BANNIERES_PATH', RESTOS_PATH . 'bannieres' . DIRECTORY_SEPARATOR);
define('LOGOS_PATH',     RESTOS_PATH . 'logos' . DIRECTORY_SEPARATOR);
define('DEFAUT_PATH',    UPLOADS_PATH . 'defaut' . DIRECTORY_SEPARATOR);
define('ASSETS_PATH',    ROOT_PATH . 'assets' . DIRECTORY_SEPARATOR);

// ─── Images par défaut ────────────────────────────────────────────────────────
define('IMG_PLAT_DEFAUT',      DEFAUT_URL  . 'plat_defaut.svg');
define('IMG_PLAT_DEFAUT_FILE', DEFAUT_PATH . 'plat_defaut.svg');
define('IMG_RESTO_DEFAUT',     DEFAUT_URL  . 'resto_defaut.svg');
define('IMG_RESTO_DEFAUT_FILE',DEFAUT_PATH . 'resto_defaut.svg');
define('IMG_BANNIERE_DEFAUT',      DEFAUT_URL  . 'banniere_defaut.svg');
define('IMG_BANNIERE_DEFAUT_FILE', DEFAUT_PATH . 'banniere_defaut.svg');
define('IMG_LOGO_DEFAUT',      DEFAUT_URL  . 'logo_defaut.svg');
define('IMG_LOGO_DEFAUT_FILE', DEFAUT_PATH . 'logo_defaut.svg');

// ─── Informations du site ─────────────────────────────────────────────────────
define('SITE_NOM',     'Saveur Kaolack');
define('SITE_SLOGAN',  'La cuisine kaolackoise authentique à domicile');
define('SITE_EMAIL',   'saveurkaolack1@gmail.com');
define('SITE_TEL',     '+221 78 521 65 68');
define('SITE_ADRESSE', 'Kaolack, Sénégal');

// ─── Uploads ─────────────────────────────────────────────────────────────────
define('UPLOAD_MAX_SIZE',   2097152);
define('UPLOAD_MAX_WIDTH',  1920);
define('UPLOAD_MAX_HEIGHT', 1080);
define('UPLOAD_TYPES',      ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
define('UPLOAD_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp', 'gif']);

// ─── Session ──────────────────────────────────────────────────────────────────
define('SESSION_DUREE', 7200);
define('SESSION_NOM',   'saveur_kaolack_session');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_DUREE,
        'path'     => '/',
        'domain'   => '',
        'secure'   => (ENVIRONMENT === 'production'),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

// ─── Secrets (clés privées) ───────────────────────────────────────────────────
// Chargé depuis includes/secrets.php — ne jamais mettre les clés ici directement
if (file_exists(__DIR__ . '/secrets.php')) {
    require_once __DIR__ . '/secrets.php';
} else {
    // Fichier absent : le site reste opérationnel mais les emails et Wave sont désactivés.
    // Créer includes/secrets.php avec SMTP_EMAIL, SMTP_PASSWORD, WAVE_API_KEY, DB_USER_PROD, DB_PASS_PROD.
    if (!defined('SMTP_EMAIL'))    define('SMTP_EMAIL',    '');
    if (!defined('SMTP_PASSWORD')) define('SMTP_PASSWORD', '');
    if (!defined('DB_USER_PROD'))  define('DB_USER_PROD',  '');
    if (!defined('DB_PASS_PROD'))  define('DB_PASS_PROD',  '');
    error_log('[Saveur Kaolack] ATTENTION : includes/secrets.php est introuvable. Emails, paiement Wave et BDD production désactivés.');
}

// ─── Base de données ─────────────────────────────────────────────────────────
if ($_is_local) {
    define('DB_HOST',    'localhost');
    define('DB_NAME',    'saveur_kaolack');
    define('DB_USER',    'root');
    define('DB_PASS',    '');
} else {
    // ⚠️ À REMPLIR une fois l'hébergeur de production choisi (host + nom de BDD fournis par l'hébergeur).
    define('DB_HOST',    'À_DEFINIR');
    define('DB_NAME',    'À_DEFINIR');
    define('DB_USER',    DB_USER_PROD);
    define('DB_PASS',    DB_PASS_PROD);
}
define('DB_CHARSET', 'utf8mb4');

// Wave Checkout
define('WAVE_API_URL', 'https://api.wave.com/v1/checkout/sessions');
define('WAVE_API_KEY', '');

// ─── Timezone ─────────────────────────────────────────────────────────────────
date_default_timezone_set('Africa/Dakar');
