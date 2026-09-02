<?php
/**
 * RESTAURANT.PHP - Détail d'un restaurant avec ses plats
 * Saveur Kaolack
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';
require_once 'includes/geo.php';

// Récupérer l'ID du restaurant
$restaurant_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Détecter le restaurant actuellement dans le panier
$cart_restaurant_id  = 0;
$cart_restaurant_nom = '';
if (!empty($_SESSION['cart'])) {
    $firstItem = reset($_SESSION['cart']);
    $cart_restaurant_id  = (int) ($firstItem['restaurant_id'] ?? 0);
    $cart_restaurant_nom = $firstItem['restaurant_nom'] ?? '';
}

if ($restaurant_id <= 0) {
    header('Location: restaurants.php');
    exit;
}

try {
    $pdo = getDB();
    assurerSchemaEssai($pdo);

    // Récupérer le restaurant
    $stmt = $pdo->prepare("
        SELECT r.*, c.nom as categorie_nom, c.icone 
        FROM restaurants r 
        LEFT JOIN categories c ON r.categorie_id = c.id 
        WHERE r.id = ? AND r.statut = 'actif'
          AND (r.essai_debut IS NULL OR DATE_ADD(r.essai_debut, INTERVAL 45 DAY) >= CURDATE() OR (r.abonnement_jusquau IS NOT NULL AND r.abonnement_jusquau >= CURDATE()))
        LIMIT 1
    ");
    $stmt->execute([$restaurant_id]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

    assurerSchemaGeo($pdo);

    if (!$restaurant) {
        header('Location: restaurants.php');
        exit;
    }
    
    // Récupérer les plats par catégorie
    $stmt = $pdo->prepare("
        SELECT * FROM plats 
        WHERE restaurant_id = ? AND disponible = 1 
        ORDER BY categorie, nom
    ");
    $stmt->execute([$restaurant_id]);
    $plats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Grouper les plats par catégorie
    $plats_par_categorie = [];
    foreach ($plats as $plat) {
        $cat = $plat['categorie'] ?? 'Autres';
        $plats_par_categorie[$cat][] = $plat;
    }
    
    // Avis clients approuvés + note moyenne
    $avis        = [];
    $note_moy    = 0;
    $nb_avis     = 0;
    try {
        assurerSchemaAvis($pdo);
        $stmt = $pdo->prepare("
            SELECT a.note, a.commentaire, a.created_at,
                   COALESCE(u.prenom, 'Client') AS prenom
            FROM avis a
            LEFT JOIN utilisateurs u ON a.utilisateur_id = u.id
            WHERE a.restaurant_id = ? AND a.statut = 'approuve'
            ORDER BY a.created_at DESC
            LIMIT 6
        ");
        $stmt->execute([$restaurant_id]);
        $avis    = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $nb_avis = count($avis);

        $stmt2 = $pdo->prepare("SELECT AVG(note), COUNT(*) FROM avis WHERE restaurant_id = ? AND statut = 'approuve'");
        $stmt2->execute([$restaurant_id]);
        [$note_moy, $nb_avis] = $stmt2->fetch(PDO::FETCH_NUM);
        $note_moy = round((float)$note_moy, 1);
        $nb_avis  = (int)$nb_avis;
    } catch (PDOException) {
        // Table avis absente — non bloquant
    }
    
} catch (PDOException $e) {
    header('Location: restaurants.php');
    exit;
}

$pageTitle = $restaurant['nom'];

// Déterminer si ouvert (utilise la fonction robuste)
$isOpen = estRestaurantOuvert($restaurant['heure_ouverture'], $restaurant['heure_fermeture']);

// Métadonnées SEO / partage pour cette page restaurant
$pageDescription = !empty($restaurant['description'])
    ? mb_substr(trim($restaurant['description']), 0, 155)
    : 'Commandez chez ' . $restaurant['nom'] . ' à ' . ($restaurant['quartier'] ?: 'Kaolack') . ' — livraison via Saveur Kaolack.';
$pageImage = urlImage($restaurant['photo_banniere'] ?? null, 'banniere');

require_once 'includes/header.php';

// Données structurées pour Google (fiche restaurant enrichie dans les résultats de recherche)
$jsonLdRestaurant = [
    '@context'  => 'https://schema.org',
    '@type'     => 'Restaurant',
    'name'      => $restaurant['nom'],
    'image'     => $pageImage,
    'telephone' => $restaurant['telephone'] ?: null,
    'servesCuisine' => $restaurant['categorie_nom'] ?? null,
    'address'   => array_filter([
        '@type'           => 'PostalAddress',
        'streetAddress'   => $restaurant['adresse'] ?: null,
        'addressLocality' => $restaurant['quartier'] ?: 'Kaolack',
        'addressCountry'  => 'SN',
    ], fn($v) => $v !== null),
    'openingHoursSpecification' => [
        '@type'    => 'OpeningHoursSpecification',
        'opens'    => substr($restaurant['heure_ouverture'], 0, 5),
        'closes'   => substr($restaurant['heure_fermeture'], 0, 5),
    ],
];
if ((float) $restaurant['nb_avis'] > 0) {
    $jsonLdRestaurant['aggregateRating'] = [
        '@type'       => 'AggregateRating',
        'ratingValue' => (string) $restaurant['note_moyenne'],
        'reviewCount' => (string) $restaurant['nb_avis'],
    ];
}
$jsonLdRestaurant = array_filter($jsonLdRestaurant, fn($v) => $v !== null);
?>
<script type="application/ld+json"><?php echo json_encode($jsonLdRestaurant, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<!-- Hero Restaurant -->
<section class="relative">
    <!-- Bannière -->
    <div class="relative h-48 md:h-64 overflow-hidden">
        <?php echo afficherBanniere(
            $restaurant['photo_banniere'] ?? null,
            $restaurant['nom'],
            'w-full h-full object-cover'
        ); ?>
        <div class="absolute inset-0 bg-gradient-to-t from-[hsl(20_30%_14%)]/80 to-transparent"></div>
    </div>
    
    <!-- Info -->
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="relative -mt-20 md:-mt-24 mb-8">
            <div class="rounded-3xl bg-[hsl(36_50%_98%)] p-6 shadow-warm">
                <div class="flex flex-col md:flex-row gap-6">
                    <!-- Logo -->
                    <div class="shrink-0">
                        <div class="w-20 h-20 md:w-24 md:h-24 rounded-2xl bg-gradient-warm flex items-center justify-center text-3xl md:text-4xl shadow-warm">
                            <?php echo $restaurant['icone'] ?? '🍽️'; ?>
                        </div>
                    </div>
                    
                    <!-- Info texte -->
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-3 mb-2">
                            <h1 class="font-display text-2xl md:text-3xl font-bold text-[hsl(20_30%_14%)]">
                                <?php echo htmlspecialchars($restaurant['nom']); ?>
                            </h1>
                            <?php if ($restaurant['est_nouveau']): ?>
                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-[hsl(14_72%_46%)] text-white">✨ Nouveau</span>
                            <?php endif; ?>
                        </div>
                        
                        <p class="text-[hsl(25_15%_42%)] mb-3">
                            <?php echo htmlspecialchars($restaurant['categorie_nom']); ?> • 
                            <?php echo htmlspecialchars($restaurant['quartier']); ?>, Kaolack
                        </p>
                        
                        <div class="flex flex-wrap items-center gap-4 text-sm">
                            <!-- Note -->
                            <div class="flex items-center gap-1">
                                <svg class="w-5 h-5 fill-[hsl(38_92%_52%)]" viewBox="0 0 24 24">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                                <span class="font-bold text-[hsl(20_30%_14%)]"><?php echo $note_moy > 0 ? number_format($note_moy, 1) : '—'; ?></span>
                                <span class="text-[hsl(25_15%_42%)]">(<?php echo $nb_avis; ?> avis)</span>
                            </div>
                            
                            <!-- Délai -->
                            <div class="flex items-center gap-1 text-[hsl(25_15%_42%)]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <?php echo $restaurant['delai_livraison_min']; ?>-<?php echo $restaurant['delai_livraison_max']; ?> min
                            </div>
                            
                            <!-- Horaires -->
                            <div class="flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full <?php echo $isOpen ? 'bg-green-500' : 'bg-gray-400'; ?>"></span>
                                <span class="<?php echo $isOpen ? 'text-green-600' : 'text-gray-500'; ?>">
                                    <?php echo $isOpen ? 'Ouvert' : 'Fermé'; ?> • 
                                    <?php echo substr($restaurant['heure_ouverture'], 0, 5); ?> - <?php echo substr($restaurant['heure_fermeture'], 0, 5); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Description -->
                <p class="mt-4 text-[hsl(25_15%_42%)] leading-relaxed">
                    <?php echo htmlspecialchars($restaurant['description']); ?>
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Menu -->
<section class="container mx-auto px-4 max-w-7xl pb-16">
    <div class="grid gap-8 lg:grid-cols-[1fr_320px]">
        <!-- Plats -->
        <div class="space-y-8">

            <!-- Infos livraison (mobile uniquement — sur desktop c'est dans la sidebar) -->
            <div class="lg:hidden grid grid-cols-2 gap-2 rounded-2xl bg-[hsl(36_50%_98%)] border border-[hsl(30_25%_86%)] p-4">
                <div class="text-center border-r border-[hsl(30_25%_86%)]">
                    <div class="text-lg font-bold text-[hsl(20_30%_14%)]">
                        <?php echo $restaurant['delai_livraison_min']; ?>-<?php echo $restaurant['delai_livraison_max']; ?> min
                    </div>
                    <div class="text-xs text-[hsl(25_15%_42%)] mt-0.5">Délai</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-bold text-[hsl(20_30%_14%)]">
                        <?php echo number_format($restaurant['commande_minimum'], 0, ',', ' '); ?> F
                    </div>
                    <div class="text-xs text-[hsl(25_15%_42%)] mt-0.5">Minimum</div>
                </div>
            </div>
            <p class="lg:hidden text-xs text-[hsl(25_15%_42%)] text-center">🚴 Livraison à convenir directement avec le restaurant.</p>

            <?php if (empty($plats)): ?>
            <div class="rounded-2xl bg-[hsl(36_50%_98%)] p-8 text-center">
                <p class="text-[hsl(25_15%_42%)]">Aucun plat disponible pour le moment.</p>
            </div>
            <?php else: ?>
                <?php foreach ($plats_par_categorie as $categorie => $plats_cat): ?>
                <div>
                    <h2 class="font-display text-xl font-bold text-[hsl(20_30%_14%)] mb-4">
                        <?php echo htmlspecialchars($categorie); ?>
                    </h2>
                    <div class="space-y-3">
                        <?php foreach ($plats_cat as $plat): ?>
                        <div class="flex gap-3 rounded-2xl bg-[hsl(36_50%_98%)] p-4 shadow-card hover:shadow-soft transition-shadow" id="plat-<?php echo $plat['id']; ?>">
                            <!-- Photo du plat -->
                            <div class="h-24 w-24 shrink-0 overflow-hidden rounded-xl">
                                <?php echo afficherImagePlat(
                                    $plat['photo'] ?? null,
                                    $plat['nom'],
                                    'h-full w-full object-cover'
                                ); ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <h3 class="font-display font-bold text-[hsl(20_30%_14%)] leading-tight">
                                            <?php echo htmlspecialchars($plat['nom']); ?>
                                            <?php if ($plat['est_populaire']): ?>
                                            <span class="ml-1 text-xs font-medium text-[hsl(14_72%_46%)]">⭐</span>
                                            <?php endif; ?>
                                        </h3>
                                        <p class="mt-0.5 text-xs text-[hsl(25_15%_42%)] line-clamp-2">
                                            <?php echo htmlspecialchars($plat['description']); ?>
                                        </p>
                                    </div>
                                    <span class="font-bold text-[hsl(14_72%_46%)] shrink-0 text-sm">
                                        <?php echo number_format($plat['prix'], 0, ',', ' '); ?> F
                                    </span>
                                </div>
                                <a href="panier.php?add=<?php echo $plat['id']; ?>"
                                   class="btn-ajouter mt-2 inline-flex h-10 items-center gap-2 rounded-lg bg-[hsl(14_72%_46%)] px-4 text-white text-sm font-medium hover:bg-[hsl(14_72%_40%)] transition-colors <?php echo !$isOpen ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''; ?>"
                                   data-restaurant-id="<?php echo $restaurant_id; ?>"
                                   data-restaurant-nom="<?php echo htmlspecialchars($restaurant['nom'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Ajouter
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Avis -->
            <?php if (!empty($avis)): ?>
            <div class="pt-8 border-t border-[hsl(30_25%_86%)]">
                <h2 class="font-display text-xl font-bold text-[hsl(20_30%_14%)] mb-4">Avis clients</h2>
                <div class="space-y-4">
                    <?php foreach ($avis as $a): ?>
                    <div class="rounded-2xl bg-[hsl(36_50%_98%)] p-4 shadow-card">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="flex text-[hsl(38_92%_52%)]">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <svg class="w-4 h-4 <?php echo $i <= $a['note'] ? 'fill-current' : 'text-gray-300 fill-current'; ?>" viewBox="0 0 24 24">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                                <?php endfor; ?>
                            </div>
                            <span class="text-sm font-medium text-[hsl(20_30%_14%)]">
                                <?php echo htmlspecialchars($a['prenom']); ?>
                            </span>
                            <span class="text-xs text-[hsl(25_15%_42%)]">
                                <?php echo date('d/m/Y', strtotime($a['created_at'])); ?>
                            </span>
                        </div>
                        <p class="text-sm text-[hsl(25_15%_42%)]"><?php echo htmlspecialchars($a['commentaire']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div class="lg:sticky lg:top-24 h-fit space-y-4">
            <!-- Info commande -->
            <div class="rounded-2xl bg-[hsl(14_72%_46%)]/5 border border-[hsl(14_72%_46%)]/20 p-4">
                <h3 class="font-display font-bold text-[hsl(20_30%_14%)] mb-3">Votre commande</h3>
                <p class="text-sm text-[hsl(25_15%_42%)] mb-3">
                    Sélectionnez vos plats pour commencer votre commande.
                </p>
                <div class="text-xs text-[hsl(25_15%_42%)]">
                    Commande minimum : <?php echo number_format($restaurant['commande_minimum'], 0, ',', ' '); ?> F
                </div>
            </div>
            
            <!-- Info livraison -->
            <div class="rounded-2xl bg-[hsl(36_50%_98%)] p-4 shadow-soft">
                <h4 class="font-semibold text-[hsl(20_30%_14%)] mb-3">Livraison</h4>
                <div class="space-y-2 text-sm text-[hsl(25_15%_42%)]">
                    <div class="flex justify-between">
                        <span>Délai</span>
                        <span class="font-medium text-[hsl(20_30%_14%)]">
                            <?php echo $restaurant['delai_livraison_min']; ?>-<?php echo $restaurant['delai_livraison_max']; ?> min
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span>Min. commande</span>
                        <span class="font-medium text-[hsl(20_30%_14%)]">
                            <?php echo number_format($restaurant['commande_minimum'], 0, ',', ' '); ?> F
                        </span>
                    </div>
                </div>
                <p class="mt-3 text-xs text-[hsl(25_15%_42%)]">🚴 Frais de livraison à convenir directement avec le restaurant.</p>
                <div class="mt-3 pt-3 border-t border-[hsl(30_25%_86%)] text-xs text-[hsl(25_15%_42%)]">
                    📍 <?php echo htmlspecialchars($restaurant['adresse']); ?>, <?php echo htmlspecialchars($restaurant['quartier']); ?>
                </div>
            </div>

            <?php if (!empty($restaurant['latitude']) && !empty($restaurant['longitude'])): ?>
            <!-- Carte Leaflet -->
            <div class="rounded-2xl overflow-hidden shadow-soft border border-[hsl(30_25%_86%)]">
                <div id="map-restaurant" style="height:220px;"></div>
                <div class="bg-[hsl(36_50%_98%)] px-4 py-3 flex items-center gap-2 text-xs text-[hsl(25_15%_42%)]">
                    <svg class="h-3.5 w-3.5 text-[hsl(14_72%_46%)] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <?php echo htmlspecialchars($restaurant['adresse'] . ', ' . $restaurant['quartier']); ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</section>

<?php if (!empty($restaurant['latitude']) && !empty($restaurant['longitude'])): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    const lat = <?php echo floatval($restaurant['latitude']); ?>;
    const lng = <?php echo floatval($restaurant['longitude']); ?>;
    const nom = <?php echo json_encode($restaurant['nom']); ?>;

    const map = L.map('map-restaurant', {
        zoomControl: true,
        scrollWheelZoom: false,
        dragging: true,
    }).setView([lat, lng], 16);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>'
    }).addTo(map);

    // Marqueur personnalisé rouge
    const icon = L.divIcon({
        html: '<div style="width:18px;height:18px;background:#c0392b;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.35);"></div>',
        iconSize: [18, 18],
        iconAnchor: [9, 9],
        className: '',
    });

    L.marker([lat, lng], { icon })
        .addTo(map)
        .bindPopup('<strong>' + nom + '</strong>')
        .openPopup();
})();
</script>
<?php endif; ?>

<!-- Modale confirmation changement de restaurant -->
<div id="modal-restaurant" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
    <div class="w-full max-w-sm rounded-3xl bg-white p-6 shadow-xl">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-100 mx-auto">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h3 class="mt-4 text-center font-display text-lg font-bold text-[hsl(20_30%_14%)]">Changer de restaurant ?</h3>
        <p class="mt-2 text-center text-sm text-[hsl(25_15%_42%)]" id="modal-message"></p>
        <div class="mt-6 flex gap-3">
            <button id="modal-annuler" class="flex-1 rounded-2xl border border-[hsl(30_25%_86%)] py-2.5 text-sm font-medium text-[hsl(20_30%_14%)] hover:bg-[hsl(36_78%_92%)] transition-colors">
                Annuler
            </button>
            <a id="modal-confirmer" href="#" class="flex-1 rounded-2xl bg-[hsl(14_72%_46%)] py-2.5 text-center text-sm font-medium text-white hover:bg-[hsl(14_72%_40%)] transition-colors">
                Confirmer
            </a>
        </div>
    </div>
</div>

<script>
(function () {
    const cartRestaurantId  = <?php echo $cart_restaurant_id; ?>;
    const cartRestaurantNom = <?php echo json_encode($cart_restaurant_nom); ?>;
    const modal    = document.getElementById('modal-restaurant');
    const message  = document.getElementById('modal-message');
    const annuler  = document.getElementById('modal-annuler');
    const confirmer = document.getElementById('modal-confirmer');

    document.querySelectorAll('.btn-ajouter').forEach(btn => {
        btn.addEventListener('click', function (e) {
            const restoId = parseInt(this.dataset.restaurantId);
            const restoNom = this.dataset.restaurantNom;
            const href = this.getAttribute('href');

            if (cartRestaurantId > 0 && cartRestaurantId !== restoId) {
                e.preventDefault();
                message.textContent = 'Votre panier contient des plats de "' + cartRestaurantNom + '". Si vous continuez, ils seront supprimés.';
                confirmer.setAttribute('href', href);
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        });
    });

    annuler.addEventListener('click', () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    });

    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    });
})();
</script>

<?php require_once 'includes/footer.php'; ?>
