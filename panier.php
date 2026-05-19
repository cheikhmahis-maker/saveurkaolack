<?php
/**
 * PANIER.PHP - Panier d'achat simple
 * Saveur Kaolack
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

$pageTitle = 'Mon Panier';

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialiser le panier
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Connexion BDD pour récupérer les infos des plats
$pdo = getDB();

// Ajouter un plat au panier
if (isset($_GET['add'])) {
    $platId = (int)$_GET['add'];
    $stmt = $pdo->prepare("SELECT p.*, r.nom as restaurant_nom, r.id as restaurant_id 
                          FROM plats p 
                          JOIN restaurants r ON p.restaurant_id = r.id 
                          WHERE p.id = ?");
    $stmt->execute([$platId]);
    $plat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($plat) {
        // Vérifier si le panier contient déjà des articles d'un autre restaurant
        $restaurantActuel = null;
        if (!empty($_SESSION['cart'])) {
            $firstItem = reset($_SESSION['cart']);
            $restaurantActuel = $firstItem['restaurant_id'] ?? null;
        }
        
        // Si restaurant différent et panier non vide
        if ($restaurantActuel && $restaurantActuel != $plat['restaurant_id']) {
            // Stocker le message pour affichage
            $_SESSION['flash_message'] = [
                'type' => 'info',
                'titre' => '📢 Changement de restaurant',
                'message' => 'Votre panier contenait des articles de "' . $firstItem['restaurant_nom'] . '".<br>' .
                            'Vous avez ajouté un plat de "' . $plat['restaurant_nom'] . '".<br>' .
                            'Votre panier précédent a été remplacé.'
            ];
            // Vider le panier et ajouter le nouveau plat
            $_SESSION['cart'] = [];
        }
        
        if (isset($_SESSION['cart'][$platId])) {
            $_SESSION['cart'][$platId]['quantite']++;
        } else {
            $_SESSION['cart'][$platId] = [
                'id' => $platId,
                'nom' => $plat['nom'],
                'prix' => $plat['prix'],
                'photo' => $plat['photo'] ?? null,
                'restaurant_nom' => $plat['restaurant_nom'],
                'restaurant_id' => $plat['restaurant_id'],
                'quantite' => 1
            ];
        }
    }
    header('Location: panier.php');
    exit;
}

// Modifier quantité
if (isset($_GET['update']) && isset($_GET['qty'])) {
    $platId = (int)$_GET['update'];
    $quantite = (int)$_GET['qty'];
    if ($quantite <= 0) {
        unset($_SESSION['cart'][$platId]);
    } else {
        $_SESSION['cart'][$platId]['quantite'] = $quantite;
    }
    header('Location: panier.php');
    exit;
}

// Supprimer un plat
if (isset($_GET['remove'])) {
    $platId = (int)$_GET['remove'];
    unset($_SESSION['cart'][$platId]);
    header('Location: panier.php');
    exit;
}

// Vider le panier
if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    header('Location: panier.php');
    exit;
}

// Calculer les totaux
$items = $_SESSION['cart'];
$subtotal = 0;
$nbArticles = 0;
$restaurantNom = null;
$restaurantId = null;

foreach ($items as $item) {
    $subtotal += $item['prix'] * $item['quantite'];
    $nbArticles += $item['quantite'];
    if (!$restaurantNom && isset($item['restaurant_nom'])) {
        $restaurantNom = $item['restaurant_nom'];
        $restaurantId = $item['restaurant_id'];
    }
}

$delivery = !empty($items) ? 1000 : 0;
$total = $subtotal + $delivery;

// Récupérer le restaurant pour le minimum de commande
$restaurant = null;
if ($restaurantId) {
    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ?");
    $stmt->execute([$restaurantId]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
}

require_once 'includes/header.php';

// Afficher le message flash si présent
$flashMessage = null;
if (isset($_SESSION['flash_message'])) {
    $flashMessage = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
?>

<section class="bg-[hsl(36_78%_92%)]/40">
    <div class="container mx-auto px-4 max-w-7xl py-12">
        <div class="text-xs font-semibold uppercase tracking-widest text-[hsl(14_72%_46%)]">Votre panier</div>
        <h1 class="mt-2 font-display text-4xl font-bold text-[hsl(20_30%_14%)] md:text-5xl">
            Validation de la commande
        </h1>
    </div>
</section>

<?php if ($flashMessage): ?>
<!-- Message Flash -->
<section class="container mx-auto px-4 max-w-7xl -mt-6 mb-6">
    <div class="rounded-2xl bg-blue-50 border-2 border-blue-200 p-6 shadow-sm">
        <div class="flex items-start gap-4">
            <div class="shrink-0">
                <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="font-display text-lg font-bold text-blue-900 mb-2">
                    <?php echo $flashMessage['titre']; ?>
                </h3>
                <p class="text-blue-800 leading-relaxed">
                    <?php echo $flashMessage['message']; ?>
                </p>
                <div class="mt-4 flex gap-3">
                    <a href="restaurants.php" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Retour aux restaurants
                    </a>
                    <button onclick="this.closest('section').style.display='none'" class="inline-flex items-center gap-2 rounded-xl border border-blue-300 px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100 transition-colors">
                        Fermer ce message
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="container mx-auto px-4 max-w-7xl py-12">
    <?php if (empty($items)): ?>
    <!-- PANIER VIDE -->
    <div class="mx-auto max-w-md rounded-3xl bg-[hsl(36_50%_98%)] p-10 text-center shadow-soft">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-[hsl(36_78%_92%)] text-[hsl(14_72%_46%)]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
        </div>
        <h2 class="font-display text-2xl font-bold text-[hsl(20_30%_14%)]">Votre panier est vide</h2>
        <p class="mt-2 text-sm text-[hsl(25_15%_42%)]">
            Découvrez nos restaurants et composez votre commande.
        </p>
        <a href="/saveur-php/restaurants.php" class="mt-6 inline-flex h-12 items-center justify-center rounded-2xl px-6 bg-gradient-warm text-[hsl(38_60%_97%)] font-medium shadow-warm hover:opacity-90 transition-opacity">
            Voir les restaurants
        </a>
    </div>
    <?php else: ?>
    <!-- PANIER AVEC ARTICLES -->
    <div class="grid gap-8 lg:grid-cols-[1fr_380px]">
        <!-- Articles -->
        <div class="space-y-3">
            <!-- Info Restaurant -->
            <?php if ($restaurantNom): ?>
            <div class="rounded-2xl bg-[hsl(14_72%_46%)]/5 border border-[hsl(14_72%_46%)]/20 p-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-full bg-[hsl(14_72%_46%)]/10 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[hsl(14_72%_46%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs text-[hsl(25_15%_42%)]">Restaurant</div>
                        <div class="font-semibold text-[hsl(20_30%_14%)]"><?php echo htmlspecialchars($restaurantNom); ?></div>
                    </div>
                    <a href="/saveur-php/restaurant.php?id=<?php echo $restaurantId; ?>" class="ml-auto text-sm text-[hsl(14_72%_46%)] hover:underline">
                        Voir le menu →
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php foreach ($items as $platId => $item): ?>
            <div class="flex items-center gap-4 rounded-2xl bg-[hsl(36_50%_98%)] p-3 shadow-card">
                <?php echo afficherImagePlat($item['photo'] ?? null, $item['nom'], 'h-20 w-20 shrink-0 rounded-xl object-cover'); ?>
                <div class="flex-1 min-w-0">
                    <div class="font-display font-bold text-[hsl(20_30%_14%)]"><?php echo htmlspecialchars($item['nom']); ?></div>
                    <div class="text-xs text-[hsl(25_15%_42%)]"><?php echo htmlspecialchars($restaurantNom ?? ''); ?></div>
                    <div class="mt-1 font-semibold text-[hsl(14_72%_46%)]">
                        <?php echo number_format($item['prix'], 0, ',', ' '); ?> F
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="?update=<?php echo $platId; ?>&qty=<?php echo $item['quantite'] - 1; ?>" 
                       class="h-8 w-8 inline-flex items-center justify-center rounded-lg border border-[hsl(30_25%_86%)] hover:bg-[hsl(36_30%_92%)] transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                        </svg>
                    </a>
                    <span class="w-6 text-center font-semibold"><?php echo $item['quantite']; ?></span>
                    <a href="?update=<?php echo $platId; ?>&qty=<?php echo $item['quantite'] + 1; ?>" 
                       class="h-8 w-8 inline-flex items-center justify-center rounded-lg border border-[hsl(30_25%_86%)] hover:bg-[hsl(36_30%_92%)] transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </a>
                    <a href="?remove=<?php echo $platId; ?>" 
                       class="ml-1 h-8 w-8 inline-flex items-center justify-center rounded-lg text-[hsl(0_75%_48%)] hover:bg-[hsl(0_75%_48%)]/10 transition-colors"
                       onclick="return confirm('Supprimer cet article ?')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Rappel restaurant unique -->
            <div class="mt-4 rounded-xl bg-[#FFF3CD] border border-[#ffc107] p-4">
                <div class="flex items-start gap-2">
                    <span class="text-lg">⚠️</span>
                    <div class="text-sm">
                        <p class="font-semibold text-[#856404]">Ce panier est réservé à <?php echo htmlspecialchars($restaurantNom); ?></p>
                        <p class="text-[#856404]/80 mt-1">Pour commander ailleurs, videz d'abord ce panier.</p>
                    </div>
                </div>
            </div>
            
            <!-- Info commande minimum -->
            <?php if ($restaurant && $subtotal < $restaurant['commande_minimum']): ?>
            <div class="mt-4 rounded-xl bg-[hsl(0_75%_48%)]/5 border border-[hsl(0_75%_48%)]/20 p-4">
                <div class="flex items-center gap-2 text-[hsl(0_75%_48%)]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <p class="text-sm font-medium">
                        Minimum de commande : <?php echo number_format($restaurant['commande_minimum'], 0, ',', ' '); ?> F
                    </p>
                </div>
                <p class="text-xs text-[hsl(25_15%_42%)] mt-2">
                    Il vous manque <?php echo number_format($restaurant['commande_minimum'] - $subtotal, 0, ',', ' '); ?> F pour valider
                </p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Récap -->
        <div class="h-fit rounded-3xl bg-[hsl(36_50%_98%)] p-6 shadow-warm lg:sticky lg:top-24">
            <h3 class="font-display text-lg font-bold text-[hsl(20_30%_14%)]">Récapitulatif</h3>
            <div class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between text-[hsl(25_15%_42%)]">
                    <span>Sous-total (<?php echo $nbArticles; ?> article<?php echo $nbArticles > 1 ? 's' : ''; ?>)</span>
                    <span class="text-[hsl(20_30%_14%)]"><?php echo number_format($subtotal, 0, ',', ' '); ?> F</span>
                </div>
                <div class="flex justify-between text-[hsl(25_15%_42%)]">
                    <span>Livraison</span>
                    <span class="text-[hsl(20_30%_14%)]"><?php echo number_format($delivery, 0, ',', ' '); ?> F</span>
                </div>
            </div>
            <div class="my-4 h-px bg-[hsl(30_25%_86%)]"></div>
            <div class="flex items-baseline justify-between">
                <span class="font-semibold text-[hsl(20_30%_14%)]">Total</span>
                <span class="font-display text-2xl font-bold text-[hsl(14_72%_46%)]">
                    <?php echo number_format($total, 0, ',', ' '); ?> F
                </span>
            </div>
            <!-- Bouton de commande : Connexion obligatoire -->
            <div class="mt-6 space-y-3">
                <?php $minAtteint = !$restaurant || $subtotal >= $restaurant['commande_minimum']; ?>
                
                <?php if (!empty($_SESSION['id']) && $_SESSION['role'] === 'client'): ?>
                    <!-- Client connecté : bouton commander direct -->
                    <?php if ($minAtteint): ?>
                    <a href="checkout.php" 
                       class="flex h-12 w-full items-center justify-center rounded-2xl bg-gradient-warm text-[hsl(38_60%_97%)] text-base font-medium shadow-warm hover:opacity-90 transition-opacity">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Commander maintenant
                    </a>
                    <p class="text-center text-xs text-green-600">
                        ✓ Connecté en tant que <?php echo htmlspecialchars($_SESSION['prenom']); ?>
                    </p>
                    <?php else: ?>
                    <button disabled
                            class="flex h-12 w-full items-center justify-center rounded-2xl bg-[hsl(30_25%_86%)] text-[hsl(25_15%_42%)] text-base font-medium cursor-not-allowed">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Minimum : <?php echo number_format($restaurant['commande_minimum'], 0, ',', ' '); ?> F
                    </button>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Pas connecté : connexion obligatoire -->
                    <?php if ($minAtteint): ?>
                    <a href="connexion.php?redirect=panier&message=connectez_vous_pour_commander" 
                       class="flex h-12 w-full items-center justify-center rounded-2xl bg-gradient-warm text-[hsl(38_60%_97%)] text-base font-medium shadow-warm hover:opacity-90 transition-opacity">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        Se connecter pour commander
                    </a>
                    <?php else: ?>
                    <button disabled
                            class="flex h-12 w-full items-center justify-center rounded-2xl bg-[hsl(30_25%_86%)] text-[hsl(25_15%_42%)] text-base font-medium cursor-not-allowed">
                        Minimum : <?php echo number_format($restaurant['commande_minimum'], 0, ',', ' '); ?> F
                    </button>
                    <?php endif; ?>
                    
                    <p class="text-center text-xs text-[hsl(25_15%_42%)]">
                        <span class="text-[hsl(14_72%_46%)]">✓</span> Créez un compte pour suivre vos commandes<br>
                        <a href="connexion.php?tab=signup&redirect=panier" class="text-[hsl(14_72%_46%)] hover:underline">Créer un compte</a>
                    </p>
                <?php endif; ?>
            </div>
            
            <div class="mt-4 space-y-2 text-center">
                <a href="restaurants.php" 
                   class="block text-sm text-[hsl(14_72%_46%)] hover:underline"
                   onclick="return confirm('Vider le panier pour choisir un autre restaurant ?')">
                    Choisir un autre restaurant
                </a>
                <a href="?clear=1" 
                   class="block text-sm text-[hsl(0_75%_48%)] hover:underline">
                    Vider le panier
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>
