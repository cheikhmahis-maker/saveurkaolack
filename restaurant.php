<?php
/**
 * RESTAURANT.PHP - Détail d'un restaurant avec ses plats
 * Saveur Kaolack
 */

require_once 'includes/config.php';
require_once 'includes/fonctions.php';

// Récupérer l'ID du restaurant
$restaurant_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($restaurant_id <= 0) {
    header('Location: restaurants.php');
    exit;
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=saveur_kaolack;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer le restaurant
    $stmt = $pdo->prepare("
        SELECT r.*, c.nom as categorie_nom, c.icone 
        FROM restaurants r 
        LEFT JOIN categories c ON r.categorie_id = c.id 
        WHERE r.id = ? AND r.statut = 'actif'
        LIMIT 1
    ");
    $stmt->execute([$restaurant_id]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
    
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
    
    // TODO: Créer table avis
    // Récupérer les avis (table inexistante - commenté temporairement)
    /*
    $stmt = $pdo->prepare("
        SELECT a.*, u.prenom, u.nom 
        FROM avis a 
        LEFT JOIN utilisateurs u ON a.utilisateur_id = u.id 
        WHERE a.restaurant_id = ? AND a.statut = 'visible' 
        ORDER BY a.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$restaurant_id]);
    $avis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    */
    $avis = [];
    
} catch (PDOException $e) {
    header('Location: restaurants.php');
    exit;
}

$pageTitle = $restaurant['nom'];

// Déterminer si ouvert (utilise la fonction robuste)
$isOpen = estRestaurantOuvert($restaurant['heure_ouverture'], $restaurant['heure_fermeture']);

require_once 'includes/header.php';
?>
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
                                <span class="font-bold text-[hsl(20_30%_14%)]"><?php echo number_format($restaurant['note_moyenne'], 1); ?></span>
                                <span class="text-[hsl(25_15%_42%)]">(<?php echo $restaurant['nb_avis']; ?> avis)</span>
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
                        <div class="flex gap-4 rounded-2xl bg-[hsl(36_50%_98%)] p-4 shadow-card hover:shadow-soft transition-shadow" id="plat-<?php echo $plat['id']; ?>">
                            <!-- Photo du plat -->
                            <div class="h-48 overflow-hidden rounded-t-2xl">
                                <?php echo afficherImagePlat(
                                    $plat['photo'] ?? null, 
                                    $plat['nom'],
                                    'h-full w-full object-cover'
                                ); ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <h3 class="font-display font-bold text-[hsl(20_30%_14%)]">
                                            <?php echo htmlspecialchars($plat['nom']); ?>
                                            <?php if ($plat['est_populaire']): ?>
                                            <span class="ml-2 text-xs font-medium text-[hsl(14_72%_46%)]">⭐ Populaire</span>
                                            <?php endif; ?>
                                        </h3>
                                        <p class="text-sm text-[hsl(25_15%_42%)] line-clamp-2">
                                            <?php echo htmlspecialchars($plat['description']); ?>
                                        </p>
                                    </div>
                                    <span class="font-bold text-[hsl(14_72%_46%)] shrink-0">
                                        <?php echo number_format($plat['prix'], 0, ',', ' '); ?> F
                                    </span>
                                </div>
                                <a href="panier.php?add=<?php echo $plat['id']; ?>" 
                                   class="mt-3 inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[hsl(14_72%_46%)] text-white text-sm font-medium hover:bg-[hsl(14_72%_40%)] transition-colors <?php echo !$isOpen ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''; ?>">
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
                                <?php echo $a['invite_nom'] ?? ($a['prenom'] . ' ' . substr($a['nom'], 0, 1) . '.'); ?>
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
                    Commande minimum : <?php echo number_format($restaurant['commande_minimum'], 0, ',', ' '); ?> F
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
                        <span>Frais</span>
                        <span class="font-medium text-green-600">
                            <?php echo $restaurant['frais_livraison'] > 0 ? number_format($restaurant['frais_livraison'], 0, ',', ' ') . ' F' : 'Gratuit'; ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span>Min. commande</span>
                        <span class="font-medium text-[hsl(20_30%_14%)]">
                            <?php echo number_format($restaurant['commande_minimum'], 0, ',', ' '); ?> F
                        </span>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-[hsl(30_25%_86%)] text-xs text-[hsl(25_15%_42%)]">
                    📍 <?php echo htmlspecialchars($restaurant['adresse']); ?>, <?php echo htmlspecialchars($restaurant['quartier']); ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
