<?php
/**
 * =====================================================
 * INCLUDES/HEAD.PHP — Balises HEAD réutilisables
 * Saveur Kaolack
 * =====================================================
 * 
 * À inclure dans le <head> de chaque page
 * Nécessite que config.php soit déjà inclus
 */

// Vérifier que config.php est inclus
if (!defined('BASE_URL')) {
    die('Erreur : config.php doit être inclus avant head.php');
}
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?= SITE_SLOGAN ?>">
<meta name="author" content="<?= SITE_NOM ?>">
<meta name="theme-color" content="#FF6B35">

<!-- Favicon -->
<link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>favicon.ico">

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<!-- Google Fonts : Poppins + Playfair Display -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- CSS principal Saveur Kaolack -->
<link href="<?= CSS_URL ?>style.css" rel="stylesheet">

<!-- Styles additionnels spécifiques si définis -->
<?php if (isset($pageCss) && is_array($pageCss)): ?>
    <?php foreach ($pageCss as $css): ?>
        <link href="<?= CSS_URL . $css ?>" rel="stylesheet">
    <?php endforeach; ?>
<?php endif; ?>
