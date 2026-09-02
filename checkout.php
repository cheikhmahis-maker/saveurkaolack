<?php
/**
 * CHECKOUT.PHP - Formulaire de commande
 * Saveur Kaolack
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

$pageTitle = 'Finaliser la commande';
$pageNoIndex = true;

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
$restaurant     = null;
$waveDisponible = false;

try {
    $pdo = getDB();
    assurerSchemaEssai($pdo);
    if ($restaurantId) {
        $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ? LIMIT 1");
        $stmt->execute([$restaurantId]);
        $restaurant     = $stmt->fetch(PDO::FETCH_ASSOC);
        $waveDisponible = !empty($restaurant['wave_api_key']);
    }
} catch (PDOException $e) {
    // Variables déjà initialisées à leurs valeurs par défaut ci-dessus
}

// Revalider que le restaurant est toujours disponible (a pu changer depuis l'ajout au panier)
if (!$restaurant || $restaurant['statut'] !== 'actif' || !restaurantEnRegle($restaurant)) {
    $_SESSION['erreur_commande'] = "Ce restaurant n'est plus disponible. Veuillez choisir un autre restaurant.";
    header('Location: panier.php');
    exit();
}

// Mode : membre connecté ou invité (sans compte)
$estConnecte = !empty($_SESSION['id']) && ($_SESSION['role'] ?? '') === 'client';
$mode = $estConnecte ? 'membre' : 'invite';

// Infos pré-remplies
$clientPrenom    = $estConnecte ? ($_SESSION['prenom'] ?? '') : '';
$clientNom       = $estConnecte ? ($_SESSION['nom']    ?? '') : '';
$clientEmail     = $estConnecte ? ($_SESSION['email']  ?? '') : '';
$clientTelephone = '';
$clientAdresse   = '';
$clientQuartier  = '';

if ($estConnecte) {
    try {
        $stmt_client = $pdo->prepare("SELECT telephone, adresse, quartier FROM utilisateurs WHERE id = ? LIMIT 1");
        $stmt_client->execute([$_SESSION['id']]);
        $clientInfos = $stmt_client->fetch(PDO::FETCH_ASSOC);
        if ($clientInfos) {
            $clientTelephone = $clientInfos['telephone'] ?? '';
            $clientAdresse   = $clientInfos['adresse']   ?? '';
            $clientQuartier  = $clientInfos['quartier']  ?? '';
        }
    } catch (PDOException $e) {
        // Silencieux
    }
}

// Calculer les totaux
$subtotal = 0;
$nbArticles = 0;
foreach ($items as $item) {
    $subtotal += $item['prix'] * $item['quantite'];
    $nbArticles += $item['quantite'];
}
// Frais de livraison retirés du site : le restaurant et le client s'arrangent directement.
$total = $subtotal;

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

    <?php if (!empty($_SESSION['erreur_commande'])): ?>
    <div class="mb-6 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-red-700">
        <span class="text-xl shrink-0">⚠️</span>
        <div>
            <div class="font-semibold mb-1">Commande non envoyée</div>
            <div class="text-sm"><?php echo htmlspecialchars($_SESSION['erreur_commande']); ?></div>
        </div>
    </div>
    <?php unset($_SESSION['erreur_commande']); ?>
    <?php endif; ?>

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
                
                <?php if (!$estConnecte): ?>
                <!-- Bannière invité -->
                <div class="mb-4 flex items-center justify-between rounded-xl bg-[hsl(14_72%_46%)]/8 border border-[hsl(14_72%_46%)]/20 px-4 py-3">
                    <p class="text-sm text-[hsl(20_30%_14%)]">Vous avez déjà un compte ?
                        <a href="connexion.php?redirect=checkout" class="font-semibold text-[hsl(14_72%_46%)] hover:underline">Connectez-vous</a>
                        pour aller encore plus vite.
                    </p>
                </div>
                <?php endif; ?>

                <form id="form-commande" method="POST" action="traiter_commande.php" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="mode" value="<?php echo $mode; ?>">
                    <input type="hidden" name="restaurant_id" value="<?php echo $restaurantId; ?>">

                    <!-- Nom complet -->
                    <div>
                        <label class="block text-sm font-medium text-[hsl(20_30%_14%)]">Nom complet <span class="text-red-500">*</span></label>
                        <input type="text" name="prenom" required
                               value="<?php echo htmlspecialchars(trim($clientPrenom . ' ' . $clientNom)); ?>"
                               <?php echo $estConnecte ? 'readonly' : ''; ?>
                               class="mt-1.5 h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 <?php echo $estConnecte ? 'bg-[hsl(36_30%_92%)]/30 text-[hsl(25_15%_42%)]' : ''; ?> focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20"
                               placeholder="Mouhamed mahi">
                        <input type="hidden" name="nom" value="">
                    </div>

                    <!-- Email (optionnel pour invités, fixe pour membres) -->
                    <?php if ($estConnecte): ?>
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($clientEmail); ?>">
                    <?php else: ?>
                    <div>
                        <label class="block text-sm font-medium text-[hsl(20_30%_14%)]">
                            Email <span class="text-red-500">*</span>
                            <span class="text-xs font-normal text-[hsl(25_15%_42%)]"> — votre numéro de suivi sera envoyé ici</span>
                        </label>
                        <input type="email" name="email" required
                               class="mt-1.5 h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20"
                               placeholder="votre@email.com">
                    </div>
                    <?php endif; ?>
                    
                    <!-- Téléphone -->
                    <div>
                        <label for="telephone" class="block text-sm font-medium text-[hsl(20_30%_14%)]">
                            Votre téléphone <span class="text-red-500">*</span>
                        </label>
                        <div class="relative mt-1.5">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[hsl(25_15%_42%)] text-sm">+221</span>
                            <input type="tel"
                                   id="telephone"
                                   name="telephone"
                                   required
                                   maxlength="14"
                                   pattern="[\d\s\-]{7,14}"
                                   title="Entrez un numéro valide (ex: 77 123 45 67)"
                                   value="<?php echo htmlspecialchars($clientTelephone); ?>"
                                   class="h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] pl-14 pr-4 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20"
                                   placeholder="77 XXX XX XX">
                        </div>
                        <?php if (!empty($clientTelephone)): ?>
                        <p class="mt-1 text-xs text-green-600">✓ Récupéré depuis votre profil</p>
                        <?php endif; ?>
                    </div>

                    <!-- Adresse de livraison -->
                    <div>
                        <div class="flex items-center justify-between">
                            <label for="adresse" class="block text-sm font-medium text-[hsl(20_30%_14%)]">
                                Adresse de livraison <span class="text-red-500">*</span>
                            </label>
                            <button type="button" id="btn-geo"
                                    class="flex items-center gap-1.5 rounded-lg bg-[hsl(14_72%_46%)]/10 px-3 py-1.5 text-xs font-semibold text-[hsl(14_72%_46%)] hover:bg-[hsl(14_72%_46%)]/20 transition-colors">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span id="btn-geo-txt">Ma position</span>
                            </button>
                        </div>

                        <!-- Statut géolocalisation -->
                        <div id="geo-status" class="hidden mt-2 flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium"></div>

                        <textarea id="adresse"
                                  name="adresse"
                                  required
                                  rows="2"
                                  class="mt-1.5 w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-3 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20 transition-colors"
                                  placeholder="Quartier, rue, maison, point de repère..."><?php echo htmlspecialchars($clientAdresse); ?></textarea>
                        <?php if (!empty($clientAdresse)): ?>
                        <p class="mt-1 text-xs text-green-600">✓ Récupérée depuis votre profil — modifiable</p>
                        <?php endif; ?>
                    </div>

                    <!-- Quartier -->
                    <div>
                        <label for="quartier" class="block text-sm font-medium text-[hsl(20_30%_14%)]">
                            Quartier <span class="text-xs text-[hsl(25_15%_42%)]">(optionnel)</span>
                        </label>
                        <input type="text"
                               id="quartier"
                               name="quartier"
                               value="<?php echo htmlspecialchars($clientQuartier); ?>"
                               class="mt-1.5 h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20"
                               placeholder="Ex: Médina, Darou, Ndar">
                    </div>

                    <!-- Notes -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-[hsl(20_30%_14%)]">
                            Instructions spéciales <span class="text-xs text-[hsl(25_15%_42%)]">(optionnel)</span>
                        </label>
                        <textarea id="notes"
                                  name="notes"
                                  rows="2"
                                  class="mt-1.5 w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-3 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20"
                                  placeholder="Sans piment, livrer au portail, appeler à l'arrivée..."></textarea>
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
                            
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-[hsl(30_25%_86%)] p-3 <?php echo $waveDisponible ? 'hover:bg-[hsl(36_78%_92%)]/30 has-[:checked]:border-[hsl(14_72%_46%)] has-[:checked]:bg-[hsl(14_72%_46%)]/5' : 'opacity-60'; ?>">
                                <input type="radio" name="paiement" value="wave" <?php echo $waveDisponible ? '' : 'disabled'; ?> class="h-4 w-4 text-[hsl(14_72%_46%)]">
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl">🌊</span>
                                    <div>
                                        <div class="font-medium text-[hsl(20_30%_14%)]">Wave</div>
                                        <div class="text-xs text-[hsl(25_15%_42%)]">
                                            <?php echo $waveDisponible ? 'Paiement mobile sécurisé' : 'Non disponible chez ce restaurant'; ?>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Bouton commander -->
                    <button type="submit" id="btn-commander"
                            class="mt-6 h-14 w-full rounded-2xl bg-gradient-warm text-[hsl(38_60%_97%)] text-lg font-semibold shadow-warm hover:opacity-90 transition-opacity disabled:opacity-60 disabled:cursor-not-allowed">
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
                        <?php echo number_format($item['prix'] * $item['quantite'], 0, ',', ' '); ?> F
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="my-4 h-px bg-[hsl(30_25%_86%)]"></div>
            
            <!-- Totaux -->
            <div class="space-y-2 text-sm">
                <div class="flex justify-between text-[hsl(25_15%_42%)]">
                    <span>Sous-total (<?php echo $nbArticles; ?> article<?php echo $nbArticles > 1 ? 's' : ''; ?>)</span>
                    <span class="text-[hsl(20_30%_14%)]"><?php echo number_format($subtotal, 0, ',', ' '); ?> F</span>
                </div>
                <p class="text-xs text-[hsl(25_15%_42%)]">🚴 Livraison à convenir directement avec le restaurant.</p>
            </div>
            
            <div class="my-4 h-px bg-[hsl(30_25%_86%)]"></div>
            
            <div class="flex items-baseline justify-between">
                <span class="font-semibold text-[hsl(20_30%_14%)]">Total</span>
                <span class="font-display text-2xl font-bold text-[hsl(14_72%_46%)]">
                    <?php echo number_format($total, 0, ',', ' '); ?> F
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

<script>
// Anti-double-soumission : désactive les deux boutons dès le premier clic
(function () {
    var form    = document.getElementById('form-commande');
    var btn     = document.getElementById('btn-commander');
    var btnMob  = document.getElementById('btn-commander-mobile');
    if (form) {
        form.addEventListener('submit', function () {
            if (btn)    { btn.disabled    = true; btn.textContent    = 'Envoi en cours…'; }
            if (btnMob) { btnMob.disabled = true; btnMob.textContent = 'Envoi en cours…'; }
        });
    }
})();

(function () {
    const btn      = document.getElementById('btn-geo');
    const btnTxt   = document.getElementById('btn-geo-txt');
    const status   = document.getElementById('geo-status');
    const adresse  = document.getElementById('adresse');
    const quartier = document.getElementById('quartier');

    if (!btn) return;

    function setStatus(msg, type) {
        const styles = {
            loading : 'bg-blue-50 text-blue-700 border border-blue-200',
            success : 'bg-green-50 text-green-700 border border-green-200',
            error   : 'bg-red-50 text-red-700 border border-red-200',
        };
        status.className = 'mt-2 flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium ' + (styles[type] || '');
        status.innerHTML = msg;
        status.classList.remove('hidden');
    }

    btn.addEventListener('click', function () {
        if (!navigator.geolocation) {
            setStatus('❌ Géolocalisation non supportée par votre navigateur', 'error');
            return;
        }

        btn.disabled = true;
        btnTxt.textContent = 'Localisation…';
        setStatus('<svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Détection de votre position…', 'loading');

        navigator.geolocation.getCurrentPosition(
            function (pos) {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;

                fetch('ajax/geocoder.php?lat=' + lat + '&lng=' + lng)
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.erreur) {
                            setStatus('❌ ' + data.erreur, 'error');
                            btn.disabled = false;
                            btnTxt.textContent = 'Ma position';
                            return;
                        }
                        if (data.adresse) adresse.value = data.adresse;
                        // Le quartier n'est pas rempli automatiquement : les données
                        // OpenStreetMap pour Kaolack sont imprécises pour les quartiers.

                        adresse.classList.add('border-green-400', 'ring-2', 'ring-green-200');
                        setTimeout(function () {
                            adresse.classList.remove('border-green-400', 'ring-2', 'ring-green-200');
                        }, 2000);

                        setStatus('✓ Adresse détectée — saisissez votre quartier manuellement', 'success');
                        btn.disabled = false;
                        btnTxt.textContent = 'Position détectée ✓';
                    })
                    .catch(function () {
                        setStatus('❌ Erreur réseau — saisissez l\'adresse manuellement', 'error');
                        btn.disabled = false;
                        btnTxt.textContent = 'Ma position';
                    });
            },
            function (err) {
                const msgs = {
                    1: 'Permission refusée — autorisez la localisation dans votre navigateur',
                    2: 'Position indisponible',
                    3: 'Délai dépassé — réessayez',
                };
                setStatus('❌ ' + (msgs[err.code] || 'Erreur de géolocalisation'), 'error');
                btn.disabled = false;
                btnTxt.textContent = 'Ma position';
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    });
})();
</script>

<!-- Barre fixe mobile : total + bouton Commander (masquée sur desktop) -->
<div class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-[hsl(30_25%_86%)] px-4 py-3 shadow-[0_-4px_20px_rgba(0,0,0,0.08)]">
    <div class="flex items-center gap-4">
        <div class="shrink-0">
            <div class="text-xs text-[hsl(25_15%_42%)]">Total à payer</div>
            <div class="font-display text-xl font-bold text-[hsl(14_72%_46%)]">
                <?php echo number_format($total, 0, ',', ' '); ?> F
            </div>
        </div>
        <button type="button" id="btn-commander-mobile"
                onclick="document.getElementById('form-commande').requestSubmit()"
                class="flex flex-1 h-12 items-center justify-center rounded-2xl bg-gradient-warm text-[hsl(38_60%_97%)] font-semibold shadow-warm disabled:opacity-60">
            Commander →
        </button>
    </div>
</div>
<!-- Espace pour éviter que la barre fixe cache le bas du formulaire -->
<div class="lg:hidden h-20"></div>

<?php require_once 'includes/footer.php'; ?>
