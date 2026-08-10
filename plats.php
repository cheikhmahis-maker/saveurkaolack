<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

$pageTitle = 'Plats';

// Initialiser les variables par défaut
$activeCategory = isset($_GET['cat']) ? $_GET['cat'] : 'Tout';
$categories = ['Tout'];
$dishes = [];

// Connexion BDD
try {
    $pdo = getDB();
    assurerSchemaEssai($pdo);

    // Récupérer les catégories (uniquement des restaurants actifs, en essai ou abonnés)
    $stmt = $pdo->query("SELECT DISTINCT p.categorie
                        FROM plats p
                        JOIN restaurants r ON p.restaurant_id = r.id
                        WHERE p.disponible = 1
                          AND r.statut = 'actif'
                          AND (r.essai_debut IS NULL OR DATE_ADD(r.essai_debut, INTERVAL 45 DAY) >= CURDATE() OR (r.abonnement_jusquau IS NOT NULL AND r.abonnement_jusquau >= CURDATE()))
                        ORDER BY p.categorie");
    $categories = array_merge(['Tout'], $stmt->fetchAll(PDO::FETCH_COLUMN));
    
    // Récupérer les plats (uniquement des restaurants actifs, en essai ou abonnés)
    $filtreRestaurant = "r.statut = 'actif'
        AND (r.essai_debut IS NULL OR DATE_ADD(r.essai_debut, INTERVAL 45 DAY) >= CURDATE() OR (r.abonnement_jusquau IS NOT NULL AND r.abonnement_jusquau >= CURDATE()))";

    if ($activeCategory === 'Tout') {
        $stmt = $pdo->query("SELECT p.*, r.nom as restaurant_nom, r.id as restaurant_id
                            FROM plats p
                            JOIN restaurants r ON p.restaurant_id = r.id
                            WHERE p.disponible = 1 AND {$filtreRestaurant}
                            ORDER BY p.categorie, p.nom");
    } else {
        $stmt = $pdo->prepare("SELECT p.*, r.nom as restaurant_nom, r.id as restaurant_id
                            FROM plats p
                            JOIN restaurants r ON p.restaurant_id = r.id
                            WHERE p.disponible = 1 AND p.categorie = ? AND {$filtreRestaurant}
                            ORDER BY p.nom");
        $stmt->execute([$activeCategory]);
    }
    $dishes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $categories = ['Tout'];
    $dishes = [];
}

require_once 'includes/header.php';
?>

<section class="bg-[hsl(36_78%_92%)]/40">
    <div class="container mx-auto px-4 max-w-7xl py-12 md:py-16">
        <div class="text-xs font-semibold uppercase tracking-widest text-[hsl(14_72%_46%)]">Carte</div>
        <h1 class="mt-2 font-display text-4xl font-bold text-[hsl(20_30%_14%)] md:text-5xl">
            Tous les plats
        </h1>
        <p class="mt-3 max-w-xl text-[hsl(25_15%_42%)]">
            Une sélection des meilleures spécialités sénégalaises de Kaolack.
        </p>

        <div class="mt-6 flex flex-wrap gap-2">
            <?php foreach ($categories as $c): 
                $isActive = $c === $activeCategory;
            ?>
            <a href="?cat=<?php echo urlencode($c); ?>" 
               class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors <?php echo $isActive ? 'bg-[hsl(14_72%_46%)] text-[hsl(38_60%_97%)]' : 'border border-[hsl(30_25%_86%)] text-[hsl(20_30%_14%)] hover:bg-[hsl(36_30%_92%)]'; ?>">
                <?php echo $c; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="container mx-auto px-4 max-w-7xl py-12">
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($dishes as $d): ?>
        <div class="group flex overflow-hidden rounded-2xl border border-[hsl(30_25%_86%)]/60 bg-[hsl(36_50%_98%)] transition-all hover:-translate-y-0.5 hover:shadow-warm">
            <div class="relative h-32 w-32 shrink-0 overflow-hidden">
                <?php echo afficherImagePlat($d['photo'] ?? null, $d['nom'], 'h-full w-full object-cover transition-transform duration-500 group-hover:scale-110'); ?>
            </div>
            <div class="flex flex-1 flex-col justify-between p-3">
                <div>
                    <span class="inline-block mb-1.5 px-2 py-0.5 bg-[hsl(38_92%_52%)] text-[hsl(20_50%_14%)] rounded-md text-xs font-medium">
                        <?php echo $d['categorie']; ?>
                    </span>
                    <div class="font-display text-base font-bold text-[hsl(20_30%_14%)]">
                        <?php echo htmlspecialchars($d['nom']); ?>
                    </div>
                    <div class="text-xs text-[hsl(25_15%_42%)]"><?php echo htmlspecialchars($d['restaurant_nom']); ?></div>
                </div>
                <div class="mt-2 flex items-center justify-between">
                    <div class="font-display text-lg font-bold text-[hsl(14_72%_46%)]">
                        <?php echo number_format($d['prix'], 0, ',', ' '); ?> F
                    </div>
                    <a href="panier.php?add=<?php echo $d['id']; ?>" class="h-9 w-9 inline-flex items-center justify-center rounded-xl bg-[hsl(14_72%_46%)] text-[hsl(38_60%_97%)] shadow-warm hover:bg-[hsl(14_72%_40%)] transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($dishes)): ?>
    <div class="py-16 text-center text-[hsl(25_15%_42%)]">
        Aucun plat dans cette catégorie.
    </div>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>
