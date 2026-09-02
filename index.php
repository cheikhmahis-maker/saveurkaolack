<?php
/**
 * INDEX.PHP - Page d'accueil dynamique
 * Saveur Kaolack
 */

// Démarrer la session en premier
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

$pageTitle = 'Accueil';
$pageDescription = 'Commandez vos plats sénégalais préférés à Kaolack : thiéboudienne, yassa, mafé et plus, livrés depuis les restaurants et cuisines locales de la ville.';

// Récupérer données depuis la BDD
try {
    $pdo = getDB();
    assurerSchemaEssai($pdo);

    // Catégories (avec le nombre de restaurants visibles publiquement dans chacune)
    $stmt = $pdo->query("
        SELECT c.*, (
            SELECT COUNT(*) FROM restaurants r
            WHERE r.categorie_id = c.id
              AND r.statut = 'actif'
              AND (r.essai_debut IS NULL OR DATE_ADD(r.essai_debut, INTERVAL 45 DAY) >= CURDATE() OR (r.abonnement_jusquau IS NOT NULL AND r.abonnement_jusquau >= CURDATE()))
        ) AS nb_restaurants
        FROM categories c
        ORDER BY c.ordre_affichage LIMIT 6
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Restaurants populaires (top 3 par note)
    $stmt = $pdo->query("
        SELECT r.*, c.nom as categorie_nom, c.icone 
        FROM restaurants r
        LEFT JOIN categories c ON r.categorie_id = c.id
        WHERE r.statut = 'actif'
          AND (r.essai_debut IS NULL OR DATE_ADD(r.essai_debut, INTERVAL 45 DAY) >= CURDATE() OR (r.abonnement_jusquau IS NOT NULL AND r.abonnement_jusquau >= CURDATE()))
        ORDER BY r.note_moyenne DESC
        LIMIT 3
    ");
    $restaurants_populaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Plats populaires (1 plat par restaurant, 6 restaurants differents)
    $stmt = $pdo->query("
        SELECT p.id, p.nom, p.prix, p.photo, r.nom as restaurant_nom, r.id as restaurant_id
        FROM (
            SELECT restaurant_id, MIN(id) as first_plat_id
            FROM plats 
            WHERE disponible = 1 
            GROUP BY restaurant_id
        ) AS sub
        JOIN plats p ON p.id = sub.first_plat_id
        JOIN restaurants r ON r.id = p.restaurant_id
        WHERE r.statut = 'actif'
          AND (r.essai_debut IS NULL OR DATE_ADD(r.essai_debut, INTERVAL 45 DAY) >= CURDATE() OR (r.abonnement_jusquau IS NOT NULL AND r.abonnement_jusquau >= CURDATE()))
        ORDER BY RAND()
        LIMIT 6
    ");
    $plats_populaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $categories = [];
    $restaurants_populaires = [];
    $plats_populaires = [];
}

require_once 'includes/header.php';
?>

<!-- HERO -->
<section class="relative overflow-hidden">
    <div class="container mx-auto px-4 max-w-7xl grid gap-10 py-12 md:grid-cols-2 md:items-center md:py-20">
        <div class="space-y-6">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-[hsl(36_78%_92%)] text-[hsl(20_40%_18%)] rounded-full text-sm font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-[hsl(14_72%_46%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                </svg>
                Nouveau à Kaolack
            </span>
            <h1 class="font-display text-4xl font-black leading-[1.05] text-[hsl(20_30%_14%)] text-balance md:text-6xl">
                Le goût de chez nous, 
                <span class="bg-gradient-to-r from-[hsl(14_72%_46%)] to-[hsl(28_88%_56%)] bg-clip-text text-transparent">
                    livré à votre porte.
                </span>
            </h1>
            <p class="max-w-md text-lg leading-relaxed text-[hsl(25_15%_42%)]">
                Commandez vos plats préférés des meilleurs restaurants de Kaolack. 
                Sans inscription, suivi en direct, paiement à la livraison.
            </p>

            <form method="GET" action="<?php echo BASE_URL; ?>restaurants.php" class="flex flex-col gap-3 sm:flex-row">
                <div class="relative flex-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[hsl(14_72%_46%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <input type="text" name="q" placeholder="Votre quartier à Kaolack…"
                           class="h-14 w-full rounded-2xl border border-[hsl(30_25%_86%)] bg-[hsl(36_50%_98%)] pl-12 pr-4 text-sm shadow-card focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20">
                </div>
                <button type="submit" class="h-14 inline-flex items-center justify-center gap-2 rounded-2xl px-7 bg-gradient-warm text-[hsl(38_60%_97%)] text-base font-medium shadow-warm hover:opacity-90 transition-opacity">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Trouver
                </button>
            </form>

            <div class="flex flex-wrap items-center gap-6 pt-2 text-sm text-[hsl(25_15%_42%)]">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-[hsl(142_38%_32%)]/10 text-[hsl(142_38%_32%)]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                        </svg>
                    </div>
                    Livraison 30 min
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-[hsl(142_38%_32%)]/10 text-[hsl(142_38%_32%)]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </div>
                    Sans compte
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-[hsl(142_38%_32%)]/10 text-[hsl(142_38%_32%)]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                    </div>
                    Assistant IA
                </div>
            </div>
        </div>

        <div class="relative">
            <div class="absolute -left-6 -top-6 h-32 w-32 rounded-full bg-[hsl(38_92%_52%)]/30 blur-3xl"></div>
            <div class="absolute -bottom-8 -right-4 h-40 w-40 rounded-full bg-[hsl(14_72%_46%)]/20 blur-3xl"></div>
            <div class="relative overflow-hidden rounded-[2rem] shadow-warm aspect-[4/3]">
                <img src="<?php echo UPLOADS_URL; ?>hero.jpg"
                     alt="Burger gourmet"
                     width="800" height="600"
                     class="h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-[hsl(20_30%_14%)]/40 via-transparent to-transparent"></div>
                <div class="absolute bottom-5 left-5 right-5 flex items-end justify-between gap-3 text-[hsl(38_60%_97%)]">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wider opacity-80">Best-seller</div>
                        <div class="font-display text-2xl font-bold">Burger Classic</div>
                    </div>
                    <div class="rounded-2xl bg-[hsl(38_44%_96%)]/95 px-3 py-2 text-[hsl(20_30%_14%)] shadow-soft">
                        <div class="text-[10px] font-semibold uppercase text-[hsl(25_15%_42%)]">À partir de</div>
                        <div class="font-display text-lg font-bold text-[hsl(14_72%_46%)]">2 500 F</div>
                    </div>
                </div>
            </div>

            <!-- Assistant IA Card (cliquable) -->
            <button onclick="var t=document.getElementById('chatbot-toggle'); if(t) t.click();" class="absolute -bottom-6 -left-4 hidden w-56 rounded-2xl bg-[hsl(36_50%_98%)] p-3 shadow-warm md:block hover:shadow-lg hover:scale-105 transition-all cursor-pointer text-left">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-sun">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[hsl(20_50%_14%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                    </div>
                    <div class="text-xs">
                        <div class="font-semibold text-[hsl(20_30%_14%)]">Assistant IA 🤖</div>
                        <div class="text-[hsl(14_72%_46%)]">"J'ai envie d'épicé…"</div>
                    </div>
                </div>
                <div class="mt-2 text-center">
                    <span class="text-xs bg-[hsl(14_72%_46%)] text-white px-2 py-1 rounded-full">Cliquez pour discuter !</span>
                </div>
            </button>
        </div>
    </div>
</section>

<!-- CATÉGORIES -->
<section class="container mx-auto px-4 max-w-7xl py-16">
    <div class="mb-8 flex items-end justify-between gap-4">
        <div>
            <div class="mb-2 text-xs font-semibold uppercase tracking-widest text-[hsl(14_72%_46%)]">Explorer</div>
            <h2 class="font-display text-3xl font-bold text-[hsl(20_30%_14%)] md:text-4xl">Parcourez par catégorie</h2>
        </div>
        <a href="<?php echo BASE_URL; ?>restaurants.php" class="hidden text-[hsl(14_72%_46%)] hover:underline md:inline-flex items-center gap-1">
            Tout voir
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
        </a>
    </div>

    <?php if (!empty($categories)): ?>
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-6">
        <?php foreach ($categories as $cat): ?>
        <a href="<?php echo BASE_URL; ?>restaurants.php?cat=<?php echo $cat['id']; ?>" 
           class="group flex flex-col items-center gap-3 rounded-2xl border border-[hsl(30_25%_86%)]/60 bg-[hsl(36_50%_98%)] p-4 transition-all duration-300 hover:-translate-y-1 hover:shadow-warm hover:border-[hsl(14_72%_46%)]/30">
            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-[hsl(36_78%_92%)] text-2xl transition-colors group-hover:bg-[hsl(14_72%_46%)]/10">
                <?php
                $icones_cat = [
                    1 => '🍲', // Traditionnel
                    2 => '🍖', // Grillades
                    3 => '🍟', // Fast-Food
                    4 => '🧁', // Patisserie
                    5 => '🧃', // Boissons
                ];
                echo $icones_cat[$cat['id']] ?? '🍽️';
                ?>
            </div>
            <div class="text-center">
                <div class="font-display text-sm font-bold text-[hsl(20_30%_14%)]"><?php echo htmlspecialchars($cat['nom']); ?></div>
                <div class="text-xs text-[hsl(25_15%_42%)]"><?php echo $cat['nb_restaurants'] ?? ''; ?> resto<?php echo ($cat['nb_restaurants'] ?? 0) > 1 ? 's' : ''; ?></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="rounded-2xl bg-[hsl(36_50%_98%)] p-8 text-center">
        <p class="text-[hsl(25_15%_42%)]">Catégories en cours de chargement...</p>
    </div>
    <?php endif; ?>
</section>

<!-- RESTAURANTS -->
<section class="bg-[hsl(36_78%_92%)]/40 py-16">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="mb-8 flex items-end justify-between gap-4">
            <div>
                <div class="mb-2 text-xs font-semibold uppercase tracking-widest text-[hsl(14_72%_46%)]">À Kaolack</div>
                <h2 class="font-display text-3xl font-bold text-[hsl(20_30%_14%)] md:text-4xl">Restaurants ouverts maintenant</h2>
            </div>
            <a href="<?php echo BASE_URL; ?>restaurants.php" class="hidden text-[hsl(14_72%_46%)] hover:underline md:inline-flex items-center gap-1">
                Tous les restos 
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>
        </div>

        <?php if (!empty($restaurants_populaires)): ?>
        <div class="grid gap-6 md:grid-cols-3">
            <?php foreach ($restaurants_populaires as $r):
                $isOpen = estRestaurantOuvert($r['heure_ouverture'], $r['heure_fermeture']);
            ?>
            <a href="<?php echo BASE_URL; ?>restaurant.php?id=<?php echo $r['id']; ?>" class="group overflow-hidden rounded-3xl bg-[hsl(36_50%_98%)] transition-all duration-300 hover:-translate-y-1 hover:shadow-warm">
                <div class="relative aspect-[4/3] overflow-hidden">
                    <?php echo afficherBanniere(
                        $r['photo_banniere'] ?? null,
                        $r['nom'],
                        'h-full w-full object-cover transition-transform duration-500 group-hover:scale-105'
                    ); ?>
                    <div class="absolute inset-0 bg-gradient-to-t from-[hsl(20_30%_14%)]/70 via-[hsl(20_30%_14%)]/10 to-transparent"></div>
                    <?php if ($r['est_nouveau']): ?>
                    <span class="absolute left-3 top-3 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-[hsl(14_72%_46%)] text-white">
                        ✨ Nouveau
                    </span>
                    <?php endif; ?>
                    <span class="absolute <?php echo $r['est_nouveau'] ? 'left-3 top-10' : 'left-3 top-3'; ?> inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium <?php echo $isOpen ? 'bg-[hsl(142_38%_32%)] text-[hsl(38_60%_97%)]' : 'bg-[hsl(36_30%_92%)] text-[hsl(25_15%_42%)]'; ?>">
                        <span class="h-1.5 w-1.5 rounded-full <?php echo $isOpen ? 'bg-[hsl(38_60%_97%)]' : 'bg-[hsl(25_15%_42%)]'; ?>"></span>
                        <?php echo $isOpen ? 'Ouvert' : 'Fermé'; ?>
                    </span>
                    <div class="absolute right-3 top-3 flex items-center gap-1 rounded-full bg-[hsl(38_44%_96%)]/95 px-2.5 py-1 text-xs font-bold text-[hsl(20_30%_14%)] shadow-soft">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 fill-[hsl(38_92%_52%)] text-[hsl(38_92%_52%)]" viewBox="0 0 24 24" stroke="none">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <?php echo number_format($r['note_moyenne'], 1); ?> (<?php echo $r['nb_avis']; ?>)
                    </div>
                    <div class="absolute bottom-4 left-4 right-4 text-[hsl(38_60%_97%)]">
                        <div class="font-display text-xl font-bold"><?php echo htmlspecialchars($r['nom']); ?></div>
                        <div class="text-sm opacity-90"><?php echo htmlspecialchars($r['categorie_nom'] ?? 'Restaurant'); ?> • <?php echo htmlspecialchars($r['quartier']); ?></div>
                    </div>
                </div>
                <div class="flex items-center justify-between p-4 text-sm text-[hsl(25_15%_42%)]">
                    <div class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[hsl(14_72%_46%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <?php echo $r['delai_livraison_min']; ?>-<?php echo $r['delai_livraison_max']; ?> min
                    </div>
                    <div class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[hsl(14_72%_46%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Min <?php echo number_format($r['commande_minimum'], 0, ',', ' '); ?> F
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="rounded-2xl bg-[hsl(36_50%_98%)] p-8 text-center">
            <p class="text-[hsl(25_15%_42%)]">Restaurants en cours de chargement...</p>
            <a href="<?php echo BASE_URL; ?>restaurants.php" class="mt-4 inline-flex h-11 items-center justify-center rounded-xl bg-[hsl(14_72%_46%)] px-6 text-white font-medium hover:bg-[hsl(14_72%_40%)]">
                Voir tous les restaurants
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- PLATS POPULAIRES -->
<section class="container mx-auto px-4 max-w-7xl py-16">
    <div class="mb-8 flex items-end justify-between gap-4">
        <div>
            <div class="mb-2 text-xs font-semibold uppercase tracking-widest text-[hsl(14_72%_46%)]">Faites-vous plaisir</div>
            <h2 class="font-display text-3xl font-bold text-[hsl(20_30%_14%)] md:text-4xl">Nos plats les plus commandés</h2>
        </div>
        <a href="<?php echo BASE_URL; ?>plats.php" class="hidden text-[hsl(14_72%_46%)] hover:underline md:inline-flex items-center gap-1">
            Voir tous les plats
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
        </a>
    </div>

    <?php if (!empty($plats_populaires)): ?>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($plats_populaires as $p): ?>
        <a href="<?php echo BASE_URL; ?>restaurant.php?id=<?php echo $p['restaurant_id']; ?>" class="group flex gap-4 rounded-2xl border border-[hsl(30_25%_86%)]/60 bg-[hsl(36_50%_98%)] p-3 transition-all hover:-translate-y-1 hover:shadow-warm hover:border-[hsl(14_72%_46%)]/30">
            <div class="relative h-24 w-24 shrink-0 overflow-hidden rounded-xl">
                <?php echo afficherImagePlat($p['photo'] ?? null, $p['nom'], 'h-full w-full object-cover transition-transform group-hover:scale-105'); ?>
            </div>
            <div class="flex flex-1 flex-col justify-between py-1">
                <div>
                    <div class="font-display text-base font-bold text-[hsl(20_30%_14%)] line-clamp-1"><?php echo htmlspecialchars($p['nom']); ?></div>
                    <div class="text-xs text-[hsl(25_15%_42%)]"><?php echo htmlspecialchars($p['restaurant_nom']); ?></div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="font-display text-lg font-bold text-[hsl(14_72%_46%)]"><?php echo number_format($p['prix'], 0, ',', ' '); ?> F</div>
                    <span class="inline-flex items-center gap-1 rounded-full bg-[hsl(14_72%_46%)]/10 px-2 py-1 text-xs font-medium text-[hsl(14_72%_46%)]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Commander
                    </span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <div class="mt-6 text-center md:hidden">
        <a href="<?php echo BASE_URL; ?>plats.php" class="inline-flex h-11 items-center justify-center rounded-xl bg-[hsl(14_72%_46%)] px-6 text-sm font-medium text-white hover:bg-[hsl(14_72%_40%)]">
            Voir tous les plats
        </a>
    </div>
    <?php else: ?>
    <div class="rounded-2xl bg-[hsl(36_50%_98%)] p-8 text-center">
        <p class="text-[hsl(25_15%_42%)]">Plats en cours de chargement...</p>
    </div>
    <?php endif; ?>
</section>

<!-- COMMENT ÇA MARCHE -->
<section class="container mx-auto px-4 max-w-7xl py-20">
    <div class="mx-auto mb-12 max-w-2xl text-center">
        <div class="mb-2 text-xs font-semibold uppercase tracking-widest text-[hsl(14_72%_46%)]">Simple et rapide</div>
        <h2 class="font-display text-3xl font-bold text-[hsl(20_30%_14%)] md:text-4xl">Trois étapes pour manger</h2>
    </div>

    <div class="grid gap-6 md:grid-cols-3">
        <?php
        $etapes = [
            ['n' => '01', 'icon' => 'search', 'title' => 'Choisissez', 'text' => 'Parcourez les restaurants ouverts près de vous et composez votre panier.'],
            ['n' => '02', 'icon' => 'phone', 'title' => 'Commandez', 'text' => 'Pas besoin de compte. Un numéro de téléphone suffit pour valider.'],
            ['n' => '03', 'icon' => 'truck', 'title' => 'Suivez', 'text' => 'Recevez un lien unique pour suivre votre commande en temps réel.'],
        ];
        ?>
        <?php foreach ($etapes as $s): ?>
        <div class="relative overflow-hidden rounded-3xl border border-[hsl(30_25%_86%)]/60 bg-[hsl(36_50%_98%)] p-7 transition-shadow hover:shadow-soft">
            <div class="absolute right-5 top-4 font-display text-5xl font-black text-[hsl(36_78%_92%)]"><?php echo $s['n']; ?></div>
            <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-warm shadow-warm">
                <?php if ($s['icon'] === 'search'): ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[hsl(38_60%_97%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <?php elseif ($s['icon'] === 'phone'): ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[hsl(38_60%_97%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[hsl(38_60%_97%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                </svg>
                <?php endif; ?>
            </div>
            <h3 class="mb-2 font-display text-xl font-bold text-[hsl(20_30%_14%)]"><?php echo $s['title']; ?></h3>
            <p class="text-sm leading-relaxed text-[hsl(25_15%_42%)]"><?php echo $s['text']; ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- CTA RESTAURATEURS -->
<section class="container mx-auto px-4 max-w-7xl pb-20">
    <div class="relative overflow-hidden rounded-[2.5rem] bg-gradient-warm p-8 shadow-warm md:p-14">
        <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-[hsl(38_60%_97%)]/10 blur-2xl"></div>
        <div class="absolute -bottom-20 -left-10 h-72 w-72 rounded-full bg-[hsl(38_92%_52%)]/20 blur-3xl"></div>
        <div class="relative grid gap-8 md:grid-cols-2 md:items-center">
            <div class="text-[hsl(38_60%_97%)]">
                <span class="inline-flex items-center gap-1.5 mb-4 px-3 py-1 bg-[hsl(38_60%_97%)]/15 text-[hsl(38_60%_97%)] rounded-full text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2a9 9 0 00-9 9v1a3 3 0 003 3h12a3 3 0 003-3v-1a9 9 0 00-9-9zM12 2v6m0 0l-3-3m3 3l3-3" />
                    </svg>
                    Restaurateurs
                </span>
                <h2 class="font-display text-3xl font-bold leading-tight text-balance md:text-5xl">
                    Faites grandir votre restaurant à Kaolack.
                </h2>
                <p class="mt-4 max-w-md text-base text-[hsl(38_60%_97%)]/85">
                    Rejoignez la première plateforme de commande en ligne 100% locale.
                    Inscription gratuite, mise en ligne sous 24h.
                </p>
            </div>
            <div class="flex flex-col gap-3 md:items-end">
                <a href="<?php echo BASE_URL; ?>partenaire.php" class="h-14 inline-flex items-center justify-center gap-2 rounded-2xl bg-[hsl(38_44%_96%)] px-8 text-base font-semibold text-[hsl(14_72%_46%)] hover:bg-[hsl(38_44%_96%)]/95 transition-colors">
                    Inscrire mon restaurant 
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
                <div class="text-sm text-[hsl(38_60%_97%)]/80">
                    Déjà partenaire ? <a href="<?php echo BASE_URL; ?>connexion.php" class="font-semibold underline underline-offset-4 hover:no-underline">Accéder au tableau de bord</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
