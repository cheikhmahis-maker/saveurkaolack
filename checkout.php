<?php
/**
 * CHECKOUT.PHP - Formulaire de commande
 * Saveur Kaolack
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

$pageTitle = 'Finaliser la commande';

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier que le panier n'est pas vide
if (empty($_SESSION['cart'])) {
    header('Location: panier.php');
    exit();
}

$items = $_SESSION['cart'];

// Récupérer les infos du restaurant depuis le premier article
$restaurantId = null;
$restaurantNom = null;
foreach ($items as $item) {
    if (isset($item['restaurant_id'])) {
        $restaurantId = $item['restaurant_id'];
        $restaurantNom = $item['restaurant_nom'];
        break;
    }
}

// Récupérer les infos du restaurant depuis la BDD
try {
    $pdo = getDB();
    
    $restaurant = null;
    $fraisLivraison = 1000;
    if ($restaurantId) {
        $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ? LIMIT 1");
        $stmt->execute([$restaurantId]);
        $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
        $fraisLivraison = $restaurant ? $restaurant['frais_livraison'] : 1000;
    }
} catch (PDOException $e) {
    $restaurant = null;
    $fraisLivraison = 1000;
}

// OBLIGATOIRE : Connexion client requise pour commander
if (empty($_SESSION['id']) || $_SESSION['role'] !== 'client') {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: inscription.php?message=connectez_vous_pour_commander');
    exit();
}

// Mode membre actif
$mode = 'membre';

// Calculer les totaux
$subtotal = 0;
$nbArticles = 0;
foreach ($items as $item) {
    $subtotal += $item['prix'] * $item['quantite'];
    $nbArticles += $item['quantite'];
}
$delivery = $fraisLivraison;
$total = $subtotal + $delivery;

// Générer un token CSRF pour la sécurité
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

require_once 'includes/header.php';
?>

<section class="bg-[hsl(36_78%_92%)]/40">
    <div class="container mx-auto px-4 max-w-7xl py-12">
        <div class="text-xs font-semibold uppercase tracking-widest text-[hsl(14_72%_46%)]">Commande</div>
        <h1 class="mt-2 font-display text-4xl font-bold text-[hsl(20_30%_14%)] md:text-5xl">
            Finaliser votre commande
        </h1>
    </div>
</section>

<section class="container mx-auto px-4 max-w-7xl py-12">
    <div class="grid gap-8 lg:grid-cols-[1fr_380px]">
        <!-- Formulaire de commande -->
        <div class="space-y-6">
            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- FORMULAIRE DE COMMANDE - Client connecté               -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <div class="rounded-3xl bg-[hsl(36_50%_98%)] p-6 shadow-soft md:p-8">
                <h2 class="font-display text-xl font-bold text-[hsl(20_30%_14%)]">
                    Vos informations de livraison
                </h2>
                <p class="mt-1 text-sm text-[hsl(25_15%_42%)]">
                    Remplissez ces informations — votre commande arrive dans 20 à 40 minutes.
                </p>
                
                <form method="POST" action="traiter_commande.php" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="mode" value="membre">
                    <input type="hidden" name="restaurant_id" value="<?php echo $restaurantId; ?>">
                    <input type="hidden" name="prenom" value="<?php echo htmlspecialchars($_SESSION['prenom']); ?>">
                    <input type="hidden" name="nom" value="<?php echo htmlspecialchars($_SESSION['nom']); ?>">
                    
                    <!-- Nom affiché (lecture seule) -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-[hsl(20_30%_14%)]">Prénom</label>
                            <input type="text" 
                                   value="<?php echo htmlspecialchars($_SESSION['prenom']); ?>" 
                                   readonly
                                   class="mt-1.5 h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] bg-[hsl(36_30%_92%)]/30 px-4 text-[hsl(25_15%_42%)]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[hsl(20_30%_14%)]">Nom</label>
                            <input type="text" 
                                   value="<?php echo htmlspecialchars($_SESSION['nom']); ?>" 
                                   readonly
                                   class="mt-1.5 h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] bg-[hsl(36_30%_92%)]/30 px-4 text-[hsl(25_15%_42%)]">
                        </div>
                    </div>
                    
                    <!-- Email depuis la session (hidden) -->
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>">
                    
                    <!-- Téléphone -->
                    <div>
                        <label for="telephone" class="block text-sm font-medium text-[hsl(20_30%_14%)]">
                            Votre téléphone <span class="text-red-500">*</span>
                            <span class="text-xs text-[hsl(25_15%_42%)]">(ex: 77 XXX XX XX)</span>
                        </label>
                        <div class="relative mt-1.5">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[hsl(25_15%_42%)]">+221</span>
                            <input type="tel" 
                                   id="telephone" 
                                   name="telephone" 
                                   required
                                   maxlength="12"
                                   class="h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] pl-14 pr-4 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20" 
                                   placeholder="77 XXX XX XX">
                        </div>
                        <p class="mt-1 text-xs text-[hsl(25_15%_42%)]">
                            Pour vous contacter en cas de problème de livraison
                        </p>
                    </div>
                    
                    <!-- Adresse de livraison -->
                    <div>
                        <label for="adresse" class="block text-sm font-medium text-[hsl(20_30%_14%)]">
                            Adresse de livraison à Kaolack <span class="text-red-500">*</span>
                        </label>
                        <textarea id="adresse" 
                                  name="adresse" 
                                  required
                                  rows="2"
                                  class="mt-1.5 w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-3 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20" 
                                  placeholder="Quartier, rue, maison, point de repère..."></textarea>
                    </div>
                    
                    <!-- Quartier optionnel -->
                    <div>
                        <label for="quartier" class="block text-sm font-medium text-[hsl(20_30%_14%)]">
                            Quartier <span class="text-xs text-[hsl(25_15%_42%)]">(optionnel)</span>
                        </label>
                        <input type="text" 
                               id="quartier" 
                               name="quartier"
                               class="mt-1.5 h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20" 
                               placeholder="Ex: Médina, Liberté 6, Ndar">
                    </div>
                    
                    <!-- Mode de paiement -->
                    <div class="pt-2">
                        <label class="block text-sm font-medium text-[hsl(20_30%_14%)]">
                            Mode de paiement
                        </label>
                        <div class="mt-3 space-y-3">
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-[hsl(30_25%_86%)] p-3 hover:bg-[hsl(36_78%_92%)]/30 has-[:checked]:border-[hsl(14_72%_46%)] has-[:checked]:bg-[hsl(14_72%_46%)]/5">
                                <input type="radio" name="paiement" value="espece" checked class="h-4 w-4 text-[hsl(14_72%_46%)]">
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl">💵</span>
                                    <div>
                                        <div class="font-medium text-[hsl(20_30%_14%)]">Paiement à la livraison</div>
                                        <div class="text-xs text-[hsl(25_15%_42%)]">Cash uniquement</div>
                                    </div>
                                </div>
                            </label>
                            
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-[hsl(30_25%_86%)] p-3 opacity-60">
                                <input type="radio" name="paiement" value="orange_money" disabled class="h-4 w-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl">📱</span>
                                    <div>
                                        <div class="font-medium text-[hsl(20_30%_14%)]">Orange Money</div>
                                        <div class="text-xs text-[hsl(25_15%_42%)]">Bientôt disponible</div>
                                    </div>
                                </div>
                            </label>
                            
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-[hsl(30_25%_86%)] p-3 opacity-60">
                                <input type="radio" name="paiement" value="wave" disabled class="h-4 w-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl">🌊</span>
                                    <div>
                                        <div class="font-medium text-[hsl(20_30%_14%)]">Wave</div>
                                        <div class="text-xs text-[hsl(25_15%_42%)]">Bientôt disponible</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Bouton commander -->
                    <button type="submit" 
                            class="mt-6 h-14 w-full rounded-2xl bg-gradient-warm text-[hsl(38_60%_97%)] text-lg font-semibold shadow-warm hover:opacity-90 transition-opacity">
                        COMMANDER MAINTENANT
                    </button>
                    
                    <!-- Message rassurant -->
                    <p class="text-center text-sm text-[hsl(25_15%_42%)]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mr-1 inline h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Votre commande sera prête dans 20 à 40 minutes.
                    </p>
                </form>
            </div>
            
            <!-- Lien vers profil -->
            <div class="text-center">
                <p class="text-sm text-[hsl(25_15%_42%)]">
                    <a href="profil_client.php" class="font-semibold text-[hsl(14_72%_46%)] hover:underline">
                        📋 Voir mes commandes précédentes
                    </a>
                </p>
            </div>
            
        </div>
        
        <!-- Récapitulatif de la commande -->
        <div class="h-fit rounded-3xl bg-[hsl(36_50%_98%)] p-6 shadow-warm lg:sticky lg:top-24">
            <h3 class="font-display text-lg font-bold text-[hsl(20_30%_14%)]">Votre commande</h3>
            
            <!-- Info Restaurant -->
            <?php if ($restaurant): ?>
            <div class="mt-3 rounded-xl bg-[hsl(14_72%_46%)]/5 p-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-gradient-warm flex items-center justify-center text-sm">
                        <?php echo $restaurant['icone'] ?? '🍽️'; ?>
                    </div>
                    <div>
                        <div class="text-xs text-[hsl(25_15%_42%)]">Chez</div>
                        <div class="font-medium text-[hsl(20_30%_14%)]"><?php echo htmlspecialchars($restaurant['nom']); ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Articles -->
            <div class="mt-4 space-y-3">
                <?php foreach ($items as $platId => $item): ?>
                <div class="flex items-center gap-3">
                    <?php echo afficherImagePlat($item['photo'] ?? null, $item['nom'], 'h-12 w-12 rounded-lg object-cover'); ?>
                    <div class="flex-1 min-w-0">
                        <div class="truncate text-sm font-medium"><?php echo htmlspecialchars($item['nom']); ?></div>
                        <div class="text-xs text-[hsl(25_15%_42%)]">x<?php echo $item['quantite']; ?></div>
                    </div>
                    <div class="text-sm font-semibold">
                        <?php echo number_format($item['prix'] * $item['quantite'], 0, ',', ' '); ?> F
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="my-4 h-px bg-[hsl(30_25%_86%)]"></div>
            
            <!-- Totaux -->
            <div class="space-y-2 text-sm">
                <div class="flex justify-between text-[hsl(25_15%_42%)]">
                    <span>Sous-total (<?php echo $nbArticles; ?> article<?php echo $nbArticles > 1 ? 's' : ''; ?>)</span>
                    <span class="text-[hsl(20_30%_14%)]"><?php echo number_format($subtotal, 0, ',', ' '); ?> F</span>
                </div>
                <div class="flex justify-between text-[hsl(25_15%_42%)]">
                    <span>Livraison</span>
                    <span class="<?php echo $delivery > 0 ? 'text-[hsl(20_30%_14%)]' : 'text-green-600 font-medium'; ?>">
                        <?php echo $delivery > 0 ? number_format($delivery, 0, ',', ' ') . ' F' : 'Gratuite'; ?>
                    </span>
                </div>
            </div>
            
            <div class="my-4 h-px bg-[hsl(30_25%_86%)]"></div>
            
            <div class="flex items-baseline justify-between">
                <span class="font-semibold text-[hsl(20_30%_14%)]">Total</span>
                <span class="font-display text-2xl font-bold text-[hsl(14_72%_46%)]">
                    <?php echo number_format($total, 0, ',', ' '); ?> F
                </span>
            </div>
            
            <!-- Info livraison -->
            <div class="mt-6 rounded-xl bg-[hsl(14_72%_46%)]/5 p-4">
                <div class="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-[hsl(14_72%_46%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <div class="text-sm">
                        <div class="font-medium text-[hsl(20_30%_14%)]">Livraison rapide</div>
                        <div class="text-[hsl(25_15%_42%)]">
                            <?php echo $restaurant ? $restaurant['delai_livraison_min'] . '-' . $restaurant['delai_livraison_max'] : '20-40'; ?> min à Kaolack
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
