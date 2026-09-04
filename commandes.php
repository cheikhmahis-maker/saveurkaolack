<?php
/**
 * COMMANDES - Liste des commandes du restaurant
 */

// Démarrer la session en premier
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

if (empty($_SESSION['id']) || $_SESSION['role'] !== 'restaurant') {
    header('Location: connexion.php');
    exit();
}

try {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE utilisateur_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['id']]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$restaurant) {
        header('Location: dashboard_resto.php');
        exit();
    }
    
    // Mise à jour statut si demandé
    if (isset($_POST['commande_id'], $_POST['nouveau_statut'])) {
        // Vérifier le token CSRF
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            $erreur = "Erreur de sécurité : session invalide. Veuillez réessayer.";
        } elseif (!restaurantEnRegle($restaurant)) {
            $erreur = "Votre essai gratuit est terminé. Contactez l'administrateur pour réactiver votre compte.";
        } else {
        $commande_id = intval($_POST['commande_id']);
        $statuts_valides = ['en_attente', 'en_preparation', 'en_route', 'livree', 'annulee'];
        $nouveau_statut = in_array($_POST['nouveau_statut'], $statuts_valides) ? $_POST['nouveau_statut'] : 'en_attente';
        
        // Si annulation, sauvegarder la raison si fournie
        $raison_annulation = isset($_POST['raison_annulation']) ? trim($_POST['raison_annulation']) : null;
        
        if ($nouveau_statut === 'annulee' && $raison_annulation) {
            $stmt = $pdo->prepare("UPDATE commandes SET statut = ?, raison_annulation = ? WHERE id = ? AND restaurant_id = ?");
            $stmt->execute([$nouveau_statut, $raison_annulation, $commande_id, $restaurant['id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE commandes SET statut = ? WHERE id = ? AND restaurant_id = ?");
            $stmt->execute([$nouveau_statut, $commande_id, $restaurant['id']]);
        }
        
        // Rediriger pour eviter le re-envoi du formulaire et afficher le nouveau statut
        header('Location: commandes.php');
        exit();
        }
    }
    
    // Récupérer toutes les commandes avec infos client
    $stmt = $pdo->prepare("
        SELECT c.*
        FROM commandes c
        WHERE c.restaurant_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$restaurant['id']]);
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Traiter les infos client pour chaque commande
    foreach ($commandes as &$cmd) {
        $client_info = json_decode($cmd['client_info'] ?? '{}', true);
        $cmd['client_nom'] = $client_info['nom'] ?? 'Client';
        $cmd['client_prenom'] = $client_info['prenom'] ?? '';
        $cmd['telephone'] = $client_info['telephone'] ?? '';
        $cmd['email'] = $client_info['email'] ?? '';
        $cmd['adresse'] = $client_info['adresse'] ?? '';
        $cmd['notes'] = $client_info['notes'] ?? '';
        
        // Formater le numéro pour WhatsApp (ajouter +221 si nécessaire)
        $tel_whatsapp = $cmd['telephone'];
        if (!empty($tel_whatsapp)) {
            // Supprimer espaces et caractères non numériques sauf +
            $tel_whatsapp = preg_replace('/[^0-9+]/', '', $tel_whatsapp);
            // Si commence par 0, remplacer par +221
            if (substr($tel_whatsapp, 0, 1) === '0') {
                $tel_whatsapp = '+221' . substr($tel_whatsapp, 1);
            }
            // Si ne commence pas par +, ajouter +221
            if (substr($tel_whatsapp, 0, 1) !== '+') {
                $tel_whatsapp = '+221' . $tel_whatsapp;
            }
        }
        $cmd['telephone_whatsapp'] = $tel_whatsapp;
    }
    unset($cmd); // Casser la référence
    
} catch (PDOException $e) {
    $erreur = messageErreurBDD($e, 'commandes.php');
}

$pageTitle = 'Mes commandes - ' . $restaurant['nom'];
$pageNoIndex = true;
require_once 'includes/header.php';
?>

<section class="container mx-auto px-4 max-w-5xl py-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="dashboard_resto.php" class="text-sm text-[hsl(25_15%_42%)] hover:text-[hsl(14_72%_46%)]">← Retour au dashboard</a>
            <h1 class="font-display text-2xl font-bold text-[hsl(20_30%_14%)] mt-2">Mes commandes</h1>
        </div>
        <div class="text-sm text-[hsl(25_15%_42%)]"><?php echo count($commandes); ?> commande(s)</div>
    </div>

    <?php if (isset($erreur)): ?>
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-700"><?php echo $erreur; ?></div>
    <?php endif; ?>

    <button id="notif-nouvelle-commande" type="button" hidden
            class="mb-4 w-full flex items-center justify-center gap-2 rounded-xl bg-[hsl(14_72%_46%)] px-4 py-3 text-sm font-semibold text-white shadow-warm animate-pulse">
        🔔 <span id="notif-nouvelle-commande-texte">Nouvelle commande reçue</span> — cliquez pour actualiser
    </button>

    <?php if (!empty($commandes)): ?>
    <div class="space-y-4">
        <?php foreach ($commandes as $cmd): ?>
        <div class="rounded-2xl bg-[hsl(36_50%_98%)] border border-[hsl(30_25%_86%)] p-4 shadow-soft">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <div class="flex items-center gap-3">
                    <span class="font-bold text-[hsl(20_30%_14%)]">#<?php echo $cmd['id']; ?></span>
                    <span class="text-sm text-[hsl(25_15%_42%)]"><?php echo date('d/m/Y H:i', strtotime($cmd['created_at'])); ?></span>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-medium <?php 
                    switch($cmd['statut']) {
                        case 'en_attente': echo 'bg-yellow-100 text-yellow-700'; break;
                        case 'en_preparation': echo 'bg-blue-100 text-blue-700'; break;
                        case 'en_route': echo 'bg-purple-100 text-purple-700'; break;
                        case 'livree': echo 'bg-green-100 text-green-700'; break;
                        case 'annulee': echo 'bg-red-100 text-red-700'; break;
                        default: echo 'bg-gray-100 text-gray-700';
                    }
                ?>">
                    <?php 
                    switch($cmd['statut']) {
                        case 'en_attente': echo '⏳ En attente'; break;
                        case 'en_preparation': echo '👨‍🍳 En préparation'; break;
                        case 'en_route': echo '🛵 En livraison'; break;
                        case 'livree': echo '✅ Livrée'; break;
                        case 'annulee': echo '❌ Annulée'; break;
                        default: echo $cmd['statut'];
                    }
                    ?>
                </span>
            </div>
            
            <!-- INFORMATIONS CLIENT COMPLÈTES -->
            <div class="grid gap-4 md:grid-cols-2">
                <!-- Colonne Client -->
                <div class="space-y-2">
                    <div class="text-sm font-medium text-[hsl(14_72%_46%)]">📋 Informations client</div>
                    
                    <!-- Nom -->
                    <div class="font-medium text-[hsl(20_30%_14%)]">
                        <?php echo htmlspecialchars(($cmd['client_prenom'] . ' ' . $cmd['client_nom'])); ?>
                    </div>
                    
                    <!-- Téléphone avec boutons -->
                    <?php if (!empty($cmd['telephone'])): ?>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-sm text-[hsl(25_15%_42%)]">📞 <?php echo htmlspecialchars($cmd['telephone']); ?></span>
                        
                        <!-- Bouton Appeler -->
                        <a href="tel:<?php echo htmlspecialchars(formatTelLien($cmd['telephone'])); ?>"
                           class="inline-flex items-center gap-1 rounded-lg bg-blue-500 px-2 py-1 text-xs font-medium text-white hover:bg-blue-600 transition-colors"
                           title="Appeler le client">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            Appeler
                        </a>
                        
                        <!-- Bouton WhatsApp -->
                        <?php if (!empty($cmd['telephone_whatsapp'])):
                            // Récupérer les détails de la commande
                            $stmt_details = $pdo->prepare("SELECT cd.*, p.nom as plat_nom, p.prix 
                                                          FROM commande_details cd 
                                                          JOIN plats p ON cd.plat_id = p.id 
                                                          WHERE cd.commande_id = ?");
                            $stmt_details->execute([$cmd['id']]);
                            $details_commande = $stmt_details->fetchAll(PDO::FETCH_ASSOC);

                            // Récupérer le nom du restaurant
                            $restaurant_nom = $restaurant['nom'] ?? 'Saveur Kaolack';

                            // Décode les infos client
                            $client_info = json_decode($cmd['client_info'], true);
                            $client_nom = $client_info['nom'] ?? '';
                            $client_adresse = $client_info['adresse'] ?? '';
                            $client_notes = $client_info['notes'] ?? '';

                            // Statut formaté
                            $statut_labels = [
                                'en_attente' => 'En attente de confirmation',
                                'preparation' => 'En préparation',
                                'en_route' => 'En cours de livraison',
                                'livree' => 'Livrée',
                                'annulee' => 'Annulée'
                            ];
                            $statut_affiche = $statut_labels[$cmd['statut']] ?? $cmd['statut'];

                            // Construire le message professionnel
                            $message = "Bonjour " . $cmd['client_prenom'] . " " . $client_nom . ",\n\n";
                            $message .= "Nous vous contactons depuis *" . $restaurant_nom . "* concernant votre commande.\n\n";
                            $message .= "━━━━━━━━━━━━━━━━━━\n";
                            $message .= "📋 *DÉTAILS DE LA COMMANDE*\n";
                            $message .= "━━━━━━━━━━━━━━━━━━\n";
                            $message .= "🆔 N° : *" . $cmd['numero_tracking'] . "*\n";
                            $message .= "📅 Date : " . date('d/m/Y à H:i', strtotime($cmd['created_at'])) . "\n\n";

                            // Liste des plats
                            if (!empty($details_commande)) {
                                foreach ($details_commande as $detail) {
                                    $message .= "• " . $detail['quantite'] . "x " . $detail['plat_nom'];
                                    $message .= " - " . number_format($detail['prix_unitaire'], 0, ',', ' ') . " FCFA\n";
                                }
                            }

                            $message .= "\n━━━━━━━━━━━━━━━━━━\n";
                            $message .= "💰 *TOTAL : " . number_format($cmd['total'], 0, ',', ' ') . " FCFA*\n";
                            $message .= "━━━━━━━━━━━━━━━━━━\n\n";

                            // Adresse
                            if (!empty($client_adresse)) {
                                $message .= "📍 *ADRESSE DE LIVRAISON :*\n";
                                $message .= $client_adresse . "\n\n";
                            }

                            // Notes
                            if (!empty($client_notes)) {
                                $message .= "📝 *NOTES :*\n";
                                $message .= $client_notes . "\n\n";
                            }

                            $message .= "📊 *STATUT ACTUEL :* " . $statut_affiche . "\n\n";
                            $message .= "Nous restons à votre disposition pour toute information complémentaire.\n\n";
                            $message .= "Cordialement,\n";
                            $message .= "*" . $restaurant_nom . "*\n";
                            $message .= "🍽️ Saveur Kaolack";

                            $message_whatsapp = urlencode($message);
                        ?>
                        <a href="https://wa.me/<?php echo htmlspecialchars(substr($cmd['telephone_whatsapp'], 1)); ?>?text=<?php echo $message_whatsapp; ?>"
                           target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-1 rounded-lg bg-green-500 px-2 py-1 text-xs font-medium text-white hover:bg-green-600 transition-colors"
                           title="Envoyer les détails par WhatsApp">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            WhatsApp Pro
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Email -->
                    <?php if (!empty($cmd['email'])): ?>
                    <div class="text-sm text-[hsl(25_15%_42%)]">
                        ✉️ <?php echo htmlspecialchars($cmd['email']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Adresse -->
                    <?php if (!empty($cmd['adresse'])): ?>
                    <div class="text-sm text-[hsl(20_30%_14%)] bg-[hsl(36_78%_92%)]/50 p-2 rounded-lg">
                        <span class="text-[hsl(14_72%_46%)]">📍</span> <?php echo htmlspecialchars($cmd['adresse']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Notes -->
                    <?php if (!empty($cmd['notes'])): ?>
                    <div class="text-sm text-[hsl(25_15%_42%)] italic border-l-2 border-[hsl(14_72%_46%)] pl-2">
                        📝 <?php echo htmlspecialchars($cmd['notes']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Colonne Total -->
                <div>
                    <div class="text-sm text-[hsl(25_15%_42%)]">Total commande</div>
                    <div class="font-display text-xl font-bold text-[hsl(14_72%_46%)]"><?php echo number_format($cmd['total'], 0, ',', ' '); ?> F</div>
                    <div class="mt-1 text-xs text-[hsl(25_15%_42%)]">
                        N°: <?php echo htmlspecialchars($cmd['numero_tracking'] ?? 'N/A'); ?>
                    </div>
                </div>
            </div>
            
            <!-- ACTIONS SELON LE STATUT -->
            <?php if ($cmd['statut'] === 'en_attente'): ?>
                <!-- Commande en attente : boutons VALIDER ou REFUSER -->
                <div class="mt-3 pt-3 border-t border-[hsl(30_25%_86%)]">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-sm font-medium text-[hsl(20_30%_14%)]">Action requise :</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <!-- Bouton VALIDER -->
                        <form method="POST" class="inline" onsubmit="return confirm('Valider cette commande ?');">
                            <?php echo champTokenCSRF(); ?>
                            <input type="hidden" name="commande_id" value="<?php echo $cmd['id']; ?>">
                            <input type="hidden" name="nouveau_statut" value="en_preparation">
                            <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-green-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-600 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Valider
                            </button>
                        </form>
                        
                        <!-- Bouton REFUSER -->
                        <form method="POST" class="inline" onsubmit="return confirm('Refuser cette commande ?');">
                            <?php echo champTokenCSRF(); ?>
                            <input type="hidden" name="commande_id" value="<?php echo $cmd['id']; ?>">
                            <input type="hidden" name="nouveau_statut" value="annulee">
                            <input type="hidden" name="raison_annulation" value="Produit indisponible">
                            <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-red-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-red-600 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Refuser
                            </button>
                        </form>
                    </div>
                    <p class="text-xs text-[hsl(25_15%_42%)] mt-2">Valider = commencer la préparation | Refuser = produit fini/non disponible</p>
                </div>
                
            <?php elseif ($cmd['statut'] !== 'livree' && $cmd['statut'] !== 'annulee'): ?>
                <!-- Commande en cours : menu deroulant pour changer statut -->
                <form method="POST" class="mt-3 flex flex-wrap items-center gap-2 pt-3 border-t border-[hsl(30_25%_86%)]">
                    <?php echo champTokenCSRF(); ?>
                    <input type="hidden" name="commande_id" value="<?php echo $cmd['id']; ?>">
                    <span class="text-sm text-[hsl(25_15%_42%)]">Mettre à jour :</span>
                    <select name="nouveau_statut" class="text-sm rounded-lg border border-[hsl(30_25%_86%)] px-2 py-1 bg-white">
                        <option value="en_preparation" <?php echo $cmd['statut'] === 'en_preparation' ? 'selected' : ''; ?>>👨‍🍳 En préparation</option>
                        <option value="en_route" <?php echo $cmd['statut'] === 'en_route' ? 'selected' : ''; ?>>🛵 En livraison</option>
                        <option value="livree">✅ Livrée</option>
                    </select>
                    <button type="submit" class="text-sm bg-[hsl(14_72%_46%)] text-white px-3 py-1 rounded-lg hover:bg-[hsl(14_72%_40%)] transition-colors">Mettre à jour</button>
                </form>
                
            <?php elseif ($cmd['statut'] === 'annulee'): ?>
                <!-- Commande annulee : afficher la raison si existe -->
                <div class="mt-3 pt-3 border-t border-red-200">
                    <div class="flex items-center gap-2 text-red-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm font-medium">Commande annulée</span>
                    </div>
                    <?php if (!empty($cmd['raison_annulation'])): ?>
                        <p class="text-xs text-red-500 mt-1">Raison : <?php echo htmlspecialchars($cmd['raison_annulation']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="rounded-2xl bg-[hsl(36_50%_98%)] border border-[hsl(30_25%_86%)] p-8 text-center">
        <p class="text-[hsl(25_15%_42%)]">Aucune commande pour le moment</p>
        <p class="text-sm text-[hsl(25_15%_42%)] mt-2">Les commandes apparaîtront ici</p>
    </div>
    <?php endif; ?>
</section>

<script>
(function () {
    // Prévient le restaurant d'une nouvelle commande même en restant sur cette page
    // (le son + badge du tableau de bord ne se déclenche que sur dashboard_resto.php).
    const bouton = document.getElementById('notif-nouvelle-commande');
    const texte  = document.getElementById('notif-nouvelle-commande-texte');
    if (!bouton) return;

    let lastCheck = Math.floor(Date.now() / 1000);

    function jouerSon() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            [0, 0.15, 0.30].forEach(function(delai) {
                const osc  = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.type = 'sine';
                osc.frequency.value = 880;
                gain.gain.setValueAtTime(0.4, ctx.currentTime + delai);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + delai + 0.25);
                osc.start(ctx.currentTime + delai);
                osc.stop(ctx.currentTime + delai + 0.25);
            });
        } catch (e) { /* Navigateur sans Web Audio */ }
    }

    function verifier() {
        fetch('ajax/check_nouvelles_commandes.php?depuis=' + lastCheck)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.nouvelles && data.nouvelles > 0) {
                    jouerSon();
                    texte.textContent = data.nouvelles > 1
                        ? data.nouvelles + ' nouvelles commandes reçues'
                        : 'Nouvelle commande reçue';
                    bouton.hidden = false;
                }
                if (data.timestamp) {
                    lastCheck = data.timestamp;
                }
            })
            .catch(function () { /* silencieux */ });
    }

    bouton.addEventListener('click', function () {
        window.location.reload();
    });

    setInterval(verifier, 30000);
})();
</script>

<?php require_once 'includes/footer.php'; ?>
