<?php
/**
 * SUIVI.PHP - Suivi de commande avec numéro de tracking
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';
require_once 'includes/geo.php';

$pageTitle = 'Suivi de commande';
$pageNoIndex = true;

// Récupérer le numéro de tracking (depuis URL ou cookie)
$token = isset($_GET['token']) ? trim($_GET['token']) : (isset($_GET['code']) ? trim($_GET['code']) : '');

// Si un code est fourni, le sauvegarder dans un cookie (7 jours)
if (!empty($token)) {
    setcookie('dernier_code_suivi', $token, time() + (7 * 24 * 60 * 60), '/');
}
// Sinon, essayer de récupérer le dernier code du cookie
elseif (isset($_COOKIE['dernier_code_suivi'])) {
    $token = $_COOKIE['dernier_code_suivi'];
}

$commande = null;
$erreur = '';

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$suiviAutorise = true;

if (!empty($token)) {
    try {
        $pdo = getDB();

        if (!suiviTentativeAutorisee($pdo, $ip)) {
            $suiviAutorise = false;
            $erreur = "Trop de tentatives. Veuillez réessayer dans quelques minutes.";
        } else {
        assurerSchemaAvis($pdo);

        // Chercher la commande par son numéro
        $stmt = $pdo->prepare("
            SELECT c.*, r.nom as restaurant_nom, r.telephone as restaurant_telephone
            FROM commandes c
            LEFT JOIN restaurants r ON c.restaurant_id = r.id
            WHERE c.numero_tracking = ?
            LIMIT 1
        ");
        $stmt->execute([strtoupper($token)]);
        $commande = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$commande) {
            $erreur = "Aucune commande trouvée avec ce code.";
            // Effacer le cookie si la commande n'existe plus
            setcookie('dernier_code_suivi', '', time() - 3600, '/');
        }
        // Si la commande est livrée ou annulée, effacer le cookie
        elseif (in_array($commande['statut'], ['livree', 'annulee'])) {
            setcookie('dernier_code_suivi', '', time() - 3600, '/');
        }
        }

    } catch (PDOException $e) {
        $erreur = "Erreur de connexion à la base de données.";
    }
}

// Traitement de l'annulation
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['annuler_commande']) && !empty($token) && $suiviAutorise) {
    // Vérifier le token CSRF
    if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erreur = "Erreur de sécurité : session invalide. Veuillez réessayer.";
    } else {
    try {
        $pdo = getDB();

        // Verifier le statut actuel et le mode de paiement
        $stmt = $pdo->prepare("SELECT statut, mode_paiement FROM commandes WHERE numero_tracking = ? LIMIT 1");
        $stmt->execute([strtoupper($token)]);
        $commande_check = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($commande_check && $commande_check['statut'] === 'en_attente') {
            $stmt = $pdo->prepare("UPDATE commandes SET statut = 'annulee' WHERE numero_tracking = ? AND statut = 'en_attente'");
            $stmt->execute([strtoupper($token)]);

            if ($stmt->rowCount() > 0) {
                $message = "Votre commande a été annulée avec succès.";
                // Recharger les donnees de la commande
                $stmt = $pdo->prepare("
                    SELECT c.*, r.nom as restaurant_nom, r.telephone as restaurant_telephone
                    FROM commandes c
                    LEFT JOIN restaurants r ON c.restaurant_id = r.id
                    WHERE c.numero_tracking = ?
                    LIMIT 1
                ");
                $stmt->execute([strtoupper($token)]);
                $commande = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $erreur = "Impossible d'annuler cette commande.";
            }
        } elseif ($commande_check && $commande_check['mode_paiement'] === 'wave' && in_array($commande_check['statut'], ['confirmee', 'en_preparation', 'en_route'])) {
            // Commande Wave déjà payée : le remboursement doit passer par le support
            $erreur = "Cette commande a été payée par Wave. Pour demander un remboursement, contactez-nous au " . SITE_TEL . " en précisant votre numéro de commande.";
        } else {
            $erreur = "Cette commande ne peut plus être annulée (déjà en préparation ou livrée).";
        }
    } catch (PDOException $e) {
        $erreur = "Erreur lors de l'annulation.";
    }
    }
}

// Traitement de l'avis apres livraison
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['laisser_avis']) && !empty($token) && $suiviAutorise) {
    // Vérifier le token CSRF
    if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erreur = "Erreur de sécurité : session invalide. Veuillez réessayer.";
    } else {
    try {
        $pdo = getDB();
        
        // Verifier que la commande est livree
        $stmt = $pdo->prepare("SELECT id, restaurant_id, statut FROM commandes WHERE numero_tracking = ? LIMIT 1");
        $stmt->execute([strtoupper($token)]);
        $commande_check = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($commande_check && $commande_check['statut'] === 'livree') {
            $note = intval($_POST['note'] ?? 0);
            $commentaire = trim($_POST['commentaire'] ?? '');

            if ($note >= 1 && $note <= 5) {
                // Vérifier qu'un avis n'a pas déjà été laissé pour cette commande
                $existe = $pdo->prepare("SELECT id FROM avis WHERE commande_id = ? LIMIT 1");
                $existe->execute([$commande_check['id']]);
                if ($existe->fetch()) {
                    $erreur = "Vous avez déjà laissé un avis pour cette commande.";
                } else {
                    $utilisateur_id = $_SESSION['id'] ?? null;
                    $stmt = $pdo->prepare("INSERT INTO avis (commande_id, utilisateur_id, restaurant_id, note, commentaire, statut) VALUES (?, ?, ?, ?, ?, 'en_attente')");
                    $stmt->execute([$commande_check['id'], $utilisateur_id, $commande_check['restaurant_id'], $note, $commentaire]);
                    $message = "Merci pour votre avis !";
                }
            } else {
                $erreur = "Veuillez sélectionner une note entre 1 et 5.";
            }
        } else {
            $erreur = "Vous ne pouvez laisser un avis que pour une commande livrée.";
        }
    } catch (PDOException $e) {
        $erreur = "Erreur lors de l'enregistrement de l'avis.";
    }
    }
}

require_once 'includes/header.php';
?>

<section class="bg-[hsl(36_78%_92%)]/40">
    <div class="container mx-auto px-4 max-w-7xl py-12 md:py-16">
        <div class="text-xs font-semibold uppercase tracking-widest text-[hsl(14_72%_46%)]">Suivi</div>
        <h1 class="mt-2 font-display text-4xl font-bold text-[hsl(20_30%_14%)] md:text-5xl">
            Suivre ma commande
        </h1>
        <p class="mt-3 max-w-xl text-[hsl(25_15%_42%)]">
            Saisissez le code de suivi reçu après votre commande pour voir où en est votre livraison.
        </p>
    </div>
</section>

<section class="container mx-auto px-4 max-w-7xl py-12">
    <div class="mx-auto max-w-xl rounded-3xl bg-[hsl(36_50%_98%)] p-6 shadow-soft md:p-8">
        <form method="get" action="" id="suivi-form">
            <label class="block text-sm font-medium text-[hsl(20_30%_14%)]">Code de suivi</label>
            <div class="mt-2 flex gap-2">
                <div class="relative flex-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[hsl(25_15%_42%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="token" value="<?php echo htmlspecialchars($token); ?>" placeholder="Ex : SK-240421-A3B7"
                           class="h-12 w-full rounded-2xl border border-[hsl(30_25%_86%)] pl-12 pr-4 uppercase focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20">
                </div>
                <button type="submit" class="h-12 rounded-2xl px-6 bg-gradient-warm text-[hsl(38_60%_97%)] font-medium shadow-warm hover:opacity-90 transition-opacity">
                    Suivre
                </button>
            </div>
        </form>

        <?php if (!empty($erreur)): ?>
        <div class="mt-6 rounded-2xl bg-red-50 border border-red-200 p-4 text-center">
            <p class="text-red-600"><?php echo htmlspecialchars($erreur); ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
        <div class="mt-6 rounded-2xl bg-green-50 border border-green-200 p-4 text-center">
            <p class="text-green-600"><?php echo htmlspecialchars($message); ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($token) && !$commande && empty($erreur)): ?>
        <!-- Message quand on a un code sauvegardé mais pas encore recherché -->
        <div class="mt-6 rounded-2xl bg-blue-50 border border-blue-200 p-4">
            <p class="text-blue-800 font-medium text-center mb-3">
                📦 Vous avez une commande en cours
            </p>
            <div class="flex gap-3 justify-center">
                <button type="submit" form="suivi-form" class="px-4 py-2 bg-[hsl(14_72%_46%)] text-white rounded-xl hover:bg-[hsl(14_72%_40%)] transition-colors">
                    Continuer le suivi
                </button>
                <a href="index.php" class="px-4 py-2 border border-[hsl(30_25%_86%)] text-[hsl(20_30%_14%)] rounded-xl hover:bg-[hsl(36_78%_92%)] transition-colors">
                    Nouvelle commande
                </a>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($commande): ?>
        <div class="mt-8 space-y-4">
            <div class="flex items-center justify-between rounded-2xl bg-[hsl(36_78%_92%)]/50 p-4">
                <div>
                    <div class="text-xs uppercase text-[hsl(25_15%_42%)]">Commande</div>
                    <div class="font-display text-lg font-bold text-[hsl(14_72%_46%)]">
                        <?php echo htmlspecialchars($commande['numero_tracking']); ?>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-xs uppercase text-[hsl(25_15%_42%)]">Restaurant</div>
                    <div class="font-semibold text-[hsl(20_30%_14%)]">
                        <?php echo htmlspecialchars($commande['restaurant_nom'] ?? 'Inconnu'); ?>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <span class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium
                    <?php 
                    switch($commande['statut']) {
                        case 'livree': echo 'bg-green-100 text-green-700'; break;
                        case 'en_route': echo 'bg-blue-100 text-blue-700'; break;
                        case 'en_preparation': echo 'bg-yellow-100 text-yellow-700'; break;
                        case 'annulee': echo 'bg-red-100 text-red-700'; break;
                        default: echo 'bg-orange-100 text-orange-700';
                    }
                    ?>">
                    <span class="h-2 w-2 rounded-full bg-current animate-pulse"></span>
                    <?php 
                    $labels = [
                        'en_attente' => 'En attente',
                        'en_preparation' => 'En préparation',
                        'en_route' => 'En livraison',
                        'livree' => 'Livrée',
                        'annulee' => 'Annulée'
                    ];
                    echo $labels[$commande['statut']] ?? $commande['statut'];
                    ?>
                </span>
            </div>

            <?php 
            // Définir les étapes selon le statut
            $statut = $commande['statut'];
            $steps = [
                ['label' => 'Commande reçue', 'done' => true],
                ['label' => 'En préparation', 'done' => in_array($statut, ['en_preparation', 'en_route', 'livree'])],
                ['label' => 'En livraison', 'done' => in_array($statut, ['en_route', 'livree'])],
                ['label' => 'Livrée', 'done' => $statut === 'livree'],
            ];
            if ($statut === 'annulee') {
                $steps = [['label' => 'Commande annulée', 'done' => false, 'cancelled' => true]];
            }
            ?>

            <div class="space-y-3">
                <?php foreach ($steps as $i => $s): ?>
                <div class="flex items-center gap-4 rounded-2xl border p-4 transition
                    <?php echo isset($s['cancelled']) ? 'border-red-300 bg-red-50' : ($s['done'] ? 'border-[hsl(142_38%_32%)]/30 bg-[hsl(142_38%_32%)]/5' : 'border-[hsl(30_25%_86%)] bg-[hsl(36_50%_98%)] opacity-60'); ?>">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl
                        <?php echo isset($s['cancelled']) ? 'bg-red-500 text-white' : ($s['done'] ? 'bg-gradient-warm text-[hsl(38_60%_97%)] shadow-warm' : 'bg-[hsl(36_30%_92%)] text-[hsl(25_15%_42%)]'); ?>">
                        <?php if ($s['done']): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <?php elseif (isset($s['cancelled'])): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <?php else: ?>
                        <span class="text-sm font-bold"><?php echo $i + 1; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <div class="font-semibold text-[hsl(20_30%_14%)]"><?php echo $s['label']; ?></div>
                        <div class="text-xs text-[hsl(25_15%_42%)]">
                            <?php 
                            if (isset($s['cancelled'])) echo 'Contactez le restaurant';
                            elseif ($s['done']) echo 'Terminé';
                            else echo 'En attente';
                            ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="rounded-2xl border border-dashed border-[hsl(30_25%_86%)] p-4 text-center text-sm text-[hsl(25_15%_42%)]">
                Commande passée le <span class="font-semibold text-[hsl(20_30%_14%)]"><?php echo date('d/m/Y à H:i', strtotime($commande['created_at'])); ?></span>
            </div>

            <?php if ($commande['statut'] === 'annulee' && !empty($commande['raison_annulation'])): ?>
            <!-- Raison d'annulation par le restaurant -->
            <div class="rounded-2xl border border-red-200 bg-red-50 p-4">
                <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-red-800">Commande annulée par le restaurant</p>
                        <p class="text-xs text-red-600 mt-1">Raison : <?php echo htmlspecialchars($commande['raison_annulation']); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($commande['statut'] === 'en_attente'): ?>
            <!-- Bouton d'annulation -->
            <div class="rounded-2xl border border-orange-200 bg-orange-50 p-4">
                <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm text-orange-800 font-medium">Vous pouvez annuler cette commande</p>
                        <p class="text-xs text-orange-600 mt-1">Tant que le restaurant n'a pas commencé la préparation, vous pouvez annuler.</p>
                        <form method="post" action="" class="mt-3" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette commande ?');">
                            <?php echo champTokenCSRF(); ?>
                            <input type="hidden" name="annuler_commande" value="1">
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-red-500 px-4 py-2 text-sm font-medium text-white hover:bg-red-600 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Annuler la commande
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php elseif (in_array($commande['statut'], ['en_preparation', 'en_route'])): ?>
            <!-- Message : ne peut plus annuler -->
            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm text-gray-700 font-medium">Commande en cours</p>
                        <p class="text-xs text-gray-500 mt-1">Le restaurant a déjà commencé la préparation. La commande ne peut plus être annulée.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($commande['restaurant_telephone']): ?>
            <div class="text-center">
                <a href="tel:<?php echo htmlspecialchars(formatTelLien($commande['restaurant_telephone'])); ?>" class="inline-flex items-center gap-2 text-[hsl(14_72%_46%)] hover:underline">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    Contacter le restaurant
                </a>
            </div>
            <?php endif; ?>
            
            <?php if ($commande['statut'] === 'livree'): ?>
            <!-- Formulaire d'avis pour commande livree -->
            <div id="avis" class="rounded-2xl border border-green-200 bg-green-50 p-4 mt-4">
                <h3 class="font-semibold text-green-800 mb-3 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                    Laissez votre avis
                </h3>
                <p class="text-sm text-green-600 mb-3">Votre commande est livrée. Partagez votre expérience !</p>
                <form method="post" action="" class="space-y-3" id="avis-form">
                    <?php echo champTokenCSRF(); ?>
                    <input type="hidden" name="laisser_avis" value="1">
                    <div>
                        <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-2">Note</label>
                        <div class="flex gap-1" id="star-group">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label class="star-label cursor-pointer" data-val="<?php echo $i; ?>">
                                <input type="radio" name="note" value="<?php echo $i; ?>" class="hidden">
                                <span class="text-3xl text-gray-300 transition-colors select-none">★</span>
                            </label>
                            <?php endfor; ?>
                        </div>
                        <p id="avis-note-erreur" class="hidden text-sm text-red-600 mt-1">Veuillez sélectionner une note (touchez une étoile).</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Commentaire (optionnel)</label>
                        <textarea name="commentaire" rows="3" class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-3 py-2 text-sm focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20" placeholder="Décrivez votre expérience..."></textarea>
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-[hsl(14_72%_46%)] px-4 py-2 text-sm font-medium text-white hover:bg-[hsl(14_72%_40%)] transition-colors">
                        Envoyer mon avis
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
(function () {
    const labels  = document.querySelectorAll('.star-label');
    const spans   = [...labels].map(l => l.querySelector('span'));
    let selected  = 0;

    function coloriser(jusqu) {
        spans.forEach((s, i) => {
            s.classList.toggle('text-yellow-400', i < jusqu);
            s.classList.toggle('text-gray-300',   i >= jusqu);
        });
    }

    labels.forEach((lbl, idx) => {
        lbl.addEventListener('mouseenter', () => coloriser(idx + 1));
        lbl.addEventListener('mouseleave', () => coloriser(selected));
        lbl.addEventListener('click', () => {
            selected = idx + 1;
            coloriser(selected);
            const erreurNote = document.getElementById('avis-note-erreur');
            if (erreurNote) erreurNote.classList.add('hidden');
        });
    });

    const avisForm = document.getElementById('avis-form');
    if (avisForm) {
        avisForm.addEventListener('submit', (e) => {
            if (selected < 1) {
                e.preventDefault();
                const erreurNote = document.getElementById('avis-note-erreur');
                if (erreurNote) erreurNote.classList.remove('hidden');
                document.getElementById('star-group').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }
})();

<?php if ($commande && !in_array($commande['statut'], ['livree', 'annulee'])): ?>
    setTimeout(() => location.reload(), 30000);
<?php endif; ?>
</script>
<?php require_once 'includes/footer.php'; ?>
