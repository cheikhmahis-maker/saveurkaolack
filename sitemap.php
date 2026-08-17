<?php
/**
 * SITEMAP.PHP - Plan du site généré dynamiquement
 * Inclut les pages fixes + une entrée par restaurant actif.
 * Remplace l'ancien sitemap.xml statique (voir robots.txt).
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

header('Content-Type: application/xml; charset=utf-8');

$restaurants = [];
try {
    $pdo = getDB();
    assurerSchemaEssai($pdo);

    $stmt = $pdo->query("
        SELECT id, created_at, essai_debut, abonnement_jusquau
        FROM restaurants
        WHERE statut = 'actif'
        ORDER BY id
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if (restaurantEnRegle($r)) {
            $restaurants[] = $r;
        }
    }
} catch (PDOException $e) {
    // En cas d'erreur BDD, le sitemap sort quand même avec les pages fixes ci-dessous.
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?php echo BASE_URL; ?>index.php</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?php echo BASE_URL; ?>restaurants.php</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?php echo BASE_URL; ?>plats.php</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?php echo BASE_URL; ?>partenaire.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc><?php echo BASE_URL; ?>suivi.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.3</priority>
    </url>
    <?php foreach ($restaurants as $r): ?>
    <url>
        <loc><?php echo BASE_URL; ?>restaurant.php?id=<?php echo (int) $r['id']; ?></loc>
        <lastmod><?php echo date('Y-m-d', strtotime($r['created_at'])); ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>
</urlset>
