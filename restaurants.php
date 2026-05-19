<?php
/**
 * RESTAURANTS.PHP - Liste des restaurants depuis la BDD
 * Saveur Kaolack
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

$pageTitle = 'Restaurants';

// Connexion BDD et récupération des restaurants
try {
    $pdo = getDB();
    
    // Récupérer les catégories pour le filtre
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY ordre_affichage");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Construction de la requête avec filtres
    $where = ["statut = 'actif'"];
    $params = [];
    
    // Recherche texte
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    if (!empty($q)) {
        $where[] = "(nom LIKE :q OR description LIKE :q OR quartier LIKE :q)";
        $params[':q'] = '%' . $q . '%';
    }
    
    // Filtre catégorie
    $cat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
    if ($cat > 0) {
        $where[] = "categorie_id = :cat";
        $params[':cat'] = $cat;
    }
    
    $sql = "SELECT r.*, c.nom as categorie_nom, c.icone 
            FROM restaurants r 
            LEFT JOIN categories c ON r.categorie_id = c.id 
            WHERE " . implode(' AND ', $where) . " 
            ORDER BY est_nouveau DESC, note_moyenne DESC, nom";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $restaurants = [];
    $error = 'Erreur BDD: ' . $e->getMessage();
}

require_once 'includes/header.php';
?>

<section class="bg-[hsl(36_78%_92%)]/40">
    <div class="container mx-auto px-4 max-w-7xl py-12 md:py-16">
        <div class="text-xs font-semibold uppercase tracking-widest text-[hsl(14_72%_46%)]">Tous les restaurants</div>
        <h1 class="mt-2 font-display text-4xl font-bold text-[hsl(20_30%_14%)] md:text-5xl">
            Mangez local à Kaolack
        </h1>
        <p class="mt-3 max-w-xl text-[hsl(25_15%_42%)]">
            Découvrez tous les restaurants partenaires de la ville et commandez en quelques clics.
        </p>
        <!-- Filtres -->
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="?" class="px-4 py-2 rounded-full text-sm font-medium <?php echo empty($cat) ? 'bg-[hsl(14_72%_46%)] text-white' : 'bg-white text-[hsl(25_15%_42%)] hover:bg-[hsl(36_30%_92%)]'; ?> transition-colors">
                Tous
            </a>
            <?php foreach ($categories as $categorie): ?>
            <a href="?cat=<?php echo $categorie['id']; ?>" class="px-4 py-2 rounded-full text-sm font-medium <?php echo $cat == $categorie['id'] ? 'bg-[hsl(14_72%_46%)] text-white' : 'bg-white text-[hsl(25_15%_42%)] hover:bg-[hsl(36_30%_92%)]'; ?> transition-colors">
                <?php echo $categorie['icone'] . ' ' . htmlspecialchars($categorie['nom']); ?>
            </a>
            <?php endforeach; ?>
        </div>
        
        <div class="relative mt-6 max-w-md">
            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[hsl(25_15%_42%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <form method="get" action="">
                <?php if ($cat > 0): ?>
                <input type="hidden" name="cat" value="<?php echo $cat; ?>">
                <?php endif; ?>
                <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Rechercher un restaurant ou un quartier…"
                       class="h-12 w-full rounded-2xl border border-[hsl(30_25%_86%)] bg-[hsl(36_50%_98%)] pl-12 pr-4 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20">
            </form>
        </div>
    </div>
</section>

<section class="container mx-auto px-4 max-w-7xl py-12">
    <?php if (isset($error)): ?>
    <div class="rounded-2xl bg-red-50 border border-red-200 p-8 text-center">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-red-100 text-red-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h3 class="font-display text-xl font-bold text-red-800 mb-2">Problème de base de données</h3>
        <p class="text-red-700 mb-4"><?php echo $error; ?></p>
        <div class="bg-white rounded-xl p-4 text-left text-sm text-[hsl(25_15%_42%)]">
            <p class="font-semibold mb-2">Solutions possibles :</p>
            <ul class="list-disc list-inside space-y-1">
                <li>Vérifiez que MySQL est démarré dans XAMPP</li>
                <li><a href="database/install.php" class="text-[hsl(14_72%_46%)] hover:underline">Cliquez ici pour installer la base de données</a></li>
                <li>Vérifiez les identifiants (root / mot de passe vide)</li>
            </ul>
        </div>
    </div>
    <?php else: ?>
    
    <div class="grid gap-6 md:grid-cols-3">
        <?php foreach ($restaurants as $r):
            // Déterminer si ouvert selon l'heure (utilise la fonction robuste)
            $isOpen = estRestaurantOuvert($r['heure_ouverture'], $r['heure_fermeture']);
            $badgeClass = $isOpen ? 'bg-[hsl(142_38%_32%)] text-[hsl(38_60%_97%)]' : 'bg-[hsl(36_30%_92%)] text-[hsl(25_15%_42%)]';
            $dotClass = $isOpen ? 'bg-[hsl(38_60%_97%)]' : 'bg-[hsl(25_15%_42%)]';
            $btnClass = $isOpen ? 'bg-[hsl(14_72%_46%)] text-[hsl(38_60%_97%)] hover:bg-[hsl(14_72%_40%)]' : 'bg-[hsl(36_30%_92%)] text-[hsl(25_15%_42%)] cursor-not-allowed';
            $btnText = $isOpen ? 'Voir le menu' : 'Fermé';
            
            // Calculer délai formaté
            $delayText = $r['delai_livraison_min'] . '-' . $r['delai_livraison_max'] . ' min';
        ?>
        <div class="group overflow-hidden rounded-3xl bg-[hsl(36_50%_98%)] transition-all duration-300 hover:-translate-y-1 hover:shadow-warm">
            <div class="relative aspect-[4/3] overflow-hidden">
                <?php echo afficherBanniere(
                    $r['photo_banniere'] ?? null,
                    $r['nom'],
                    'h-full w-full object-cover transition-transform duration-500 group-hover:scale-105'
                ); ?>
                <div class="absolute inset-0 bg-gradient-to-t from-[hsl(20_30%_14%)]/70 to-transparent"></div>
                
                <!-- Badge Nouveau -->
                <?php if ($r['est_nouveau']): ?>
                <span class="absolute left-3 top-3 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-[hsl(14_72%_46%)] text-white">
                    ✨ Nouveau
                </span>
                <?php endif; ?>
                
                <!-- Badge Ouvert/Fermé -->
                <span class="absolute <?php echo $r['est_nouveau'] ? 'left-3 top-10' : 'left-3 top-3'; ?> inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium <?php echo $badgeClass; ?>">
                    <span class="h-1.5 w-1.5 rounded-full <?php echo $dotClass; ?>"></span>
                    <?php echo $isOpen ? 'Ouvert' : 'Fermé'; ?>
                </span>
                
                <!-- Note -->
                <div class="absolute right-3 top-3 flex items-center gap-1 rounded-full bg-[hsl(38_44%_96%)]/95 px-2.5 py-1 text-xs font-bold text-[hsl(20_30%_14%)] shadow-soft">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 fill-[hsl(38_92%_52%)] text-[hsl(38_92%_52%)]" viewBox="0 0 24 24" stroke="none">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                    <?php echo number_format($r['note_moyenne'], 1); ?> (<?php echo $r['nb_avis']; ?>)
                </div>
                
                <div class="absolute bottom-4 left-4 right-4 text-[hsl(38_60%_97%)]">
                    <div class="font-display text-xl font-bold"><?php echo htmlspecialchars($r['nom']); ?></div>
                    <div class="text-sm opacity-90">
                        <?php echo ($r['icone'] ?? '🍽️') . ' ' . htmlspecialchars($r['categorie_nom'] ?? 'Restaurant'); ?> • 
                        <?php echo htmlspecialchars($r['quartier']); ?>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between p-4 text-sm text-[hsl(25_15%_42%)]">
                <div class="flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[hsl(14_72%_46%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <?php echo $delayText; ?>
                </div>
                <div class="flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[hsl(14_72%_46%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Min <?php echo number_format($r['commande_minimum'], 0, ',', ' '); ?> F
                </div>
            </div>
            <div class="px-4 pb-4">
                <a href="/saveur-php/restaurant.php?id=<?php echo $r['id']; ?>" 
                   class="block w-full py-2.5 text-center rounded-xl <?php echo $btnClass; ?> transition-colors font-medium">
                    <?php echo $btnText; ?>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($restaurants)): ?>
    <div class="py-16 text-center">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-[hsl(36_78%_92%)] text-[hsl(25_15%_42%)]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h3 class="font-display text-xl font-bold text-[hsl(20_30%_14%)]">Aucun restaurant trouvé</h3>
        <p class="mt-2 text-sm text-[hsl(25_15%_42%)]">
            Essayez une autre recherche ou un autre filtre.
        </p>
        <a href="?" class="mt-4 inline-flex h-11 items-center justify-center rounded-xl px-6 bg-[hsl(14_72%_46%)] text-white font-medium hover:bg-[hsl(14_72%_40%)] transition-colors">
            Voir tous les restaurants
        </a>
    </div>
    <?php endif; ?>
    
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>
