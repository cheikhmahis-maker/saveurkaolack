<?php
/**
 * AIDE_WAVE.PHP - Guide de configuration Wave pour les restaurateurs
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['id']) || $_SESSION['role'] !== 'restaurant') {
    header('Location: connexion.php');
    exit();
}

// Vérifier si Wave est déjà configuré
$wave_configure = false;
try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT wave_api_key FROM restaurants WHERE utilisateur_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['id']]);
    $row  = $stmt->fetch(PDO::FETCH_ASSOC);
    $wave_configure = !empty($row['wave_api_key']);
} catch (PDOException $e) {}

$pageTitle = 'Configurer Wave - Saveur Kaolack';
require_once 'includes/header.php';
?>

<div class="container mx-auto px-4 max-w-3xl py-10">

    <a href="dashboard_resto.php" class="inline-flex items-center gap-2 text-sm text-[hsl(25_15%_42%)] hover:text-[hsl(14_72%_46%)] mb-6">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Retour au dashboard
    </a>

    <!-- En-tête -->
    <div class="rounded-3xl bg-gradient-to-br from-[hsl(200_80%_40%)] to-[hsl(200_80%_25%)] p-8 text-white mb-8">
        <div class="flex items-center gap-4">
            <div class="h-16 w-16 rounded-2xl bg-white/20 flex items-center justify-center text-4xl shrink-0">
                🌊
            </div>
            <div>
                <h1 class="font-display text-2xl font-bold">Activer le paiement Wave</h1>
                <p class="text-white/80 mt-1">
                    Permettez à vos clients de payer par Wave directement sur votre compte.
                </p>
            </div>
        </div>

        <?php if ($wave_configure): ?>
        <div class="mt-6 flex items-center gap-3 rounded-2xl bg-white/20 px-4 py-3">
            <svg class="h-6 w-6 text-green-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="font-medium">Wave est déjà activé sur votre restaurant. Les clients peuvent payer par Wave.</span>
        </div>
        <?php else: ?>
        <div class="mt-6 flex items-center gap-3 rounded-2xl bg-white/20 px-4 py-3">
            <svg class="h-6 w-6 text-orange-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span class="font-medium">Wave n'est pas encore activé. Suivez les 3 étapes ci-dessous.</span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Étapes -->
    <div class="space-y-6">

        <!-- Étape 1 -->
        <div class="rounded-2xl border border-[hsl(30_25%_86%)] bg-[hsl(36_50%_98%)] overflow-hidden">
            <div class="flex items-center gap-4 bg-[hsl(200_80%_40%)]/10 px-6 py-4 border-b border-[hsl(30_25%_86%)]">
                <div class="h-10 w-10 rounded-full bg-[hsl(200_80%_40%)] text-white font-bold text-lg flex items-center justify-center shrink-0">1</div>
                <div>
                    <h2 class="font-bold text-[hsl(20_30%_14%)]">Avoir un compte Wave Business</h2>
                    <p class="text-sm text-[hsl(25_15%_42%)]">Votre compte Wave personnel ne suffit pas — il faut un compte marchand</p>
                </div>
            </div>
            <div class="px-6 py-5 space-y-3">
                <p class="text-sm text-[hsl(25_15%_42%)]">
                    Si vous avez déjà un <strong>compte Wave Business / Marchand</strong>, passez directement à l'étape 2.
                </p>
                <div class="rounded-xl bg-blue-50 border border-blue-200 p-4">
                    <p class="text-sm font-medium text-blue-800 mb-2">Comment ouvrir un compte Wave Business :</p>
                    <ol class="text-sm text-blue-700 space-y-1.5 list-decimal list-inside">
                        <li>Appelez Wave au <strong>33 867 00 00</strong> ou rendez-vous dans une agence Wave à Kaolack</li>
                        <li>Dites que vous voulez un <strong>compte marchand</strong> pour recevoir des paiements en ligne</li>
                        <li>Apportez votre <strong>CNI</strong> et le <strong>NINEA</strong> de votre restaurant (ou registre de commerce)</li>
                        <li>Wave activera votre compte sous 24 à 48h</li>
                    </ol>
                </div>
                <div class="flex items-start gap-2 text-sm text-[hsl(25_15%_42%)]">
                    <svg class="h-4 w-4 text-orange-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Si vous n'avez pas de NINEA, Wave peut quand même ouvrir un compte avec votre CNI selon la taille de votre activité.</span>
                </div>
            </div>
        </div>

        <!-- Étape 2 -->
        <div class="rounded-2xl border border-[hsl(30_25%_86%)] bg-[hsl(36_50%_98%)] overflow-hidden">
            <div class="flex items-center gap-4 bg-[hsl(200_80%_40%)]/10 px-6 py-4 border-b border-[hsl(30_25%_86%)]">
                <div class="h-10 w-10 rounded-full bg-[hsl(200_80%_40%)] text-white font-bold text-lg flex items-center justify-center shrink-0">2</div>
                <div>
                    <h2 class="font-bold text-[hsl(20_30%_14%)]">Obtenir votre clé API Wave</h2>
                    <p class="text-sm text-[hsl(25_15%_42%)]">C'est un code secret que seul vous connaissez</p>
                </div>
            </div>
            <div class="px-6 py-5 space-y-4">
                <ol class="space-y-4">
                    <li class="flex gap-4">
                        <div class="h-7 w-7 rounded-full bg-[hsl(36_78%_92%)] text-[hsl(14_72%_46%)] font-bold text-sm flex items-center justify-center shrink-0 mt-0.5">1</div>
                        <div>
                            <p class="font-medium text-[hsl(20_30%_14%)] text-sm">Ouvrez votre navigateur (Chrome, Firefox...)</p>
                            <p class="text-xs text-[hsl(25_15%_42%)] mt-0.5">Sur votre ordinateur ou téléphone</p>
                        </div>
                    </li>
                    <li class="flex gap-4">
                        <div class="h-7 w-7 rounded-full bg-[hsl(36_78%_92%)] text-[hsl(14_72%_46%)] font-bold text-sm flex items-center justify-center shrink-0 mt-0.5">2</div>
                        <div>
                            <p class="font-medium text-[hsl(20_30%_14%)] text-sm">Allez sur le site des développeurs Wave</p>
                            <div class="mt-1.5 inline-flex items-center gap-2 rounded-lg bg-[hsl(20_30%_14%)] px-3 py-1.5">
                                <svg class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                                </svg>
                                <span class="text-white font-mono text-xs">developer.wave.com</span>
                            </div>
                        </div>
                    </li>
                    <li class="flex gap-4">
                        <div class="h-7 w-7 rounded-full bg-[hsl(36_78%_92%)] text-[hsl(14_72%_46%)] font-bold text-sm flex items-center justify-center shrink-0 mt-0.5">3</div>
                        <div>
                            <p class="font-medium text-[hsl(20_30%_14%)] text-sm">Connectez-vous avec votre numéro Wave Business</p>
                            <p class="text-xs text-[hsl(25_15%_42%)] mt-0.5">Le même numéro que votre compte marchand Wave</p>
                        </div>
                    </li>
                    <li class="flex gap-4">
                        <div class="h-7 w-7 rounded-full bg-[hsl(36_78%_92%)] text-[hsl(14_72%_46%)] font-bold text-sm flex items-center justify-center shrink-0 mt-0.5">4</div>
                        <div>
                            <p class="font-medium text-[hsl(20_30%_14%)] text-sm">Cliquez sur <strong>"API Keys"</strong> dans le menu</p>
                            <p class="text-xs text-[hsl(25_15%_42%)] mt-0.5">Ou "Clés API" si le site est en français</p>
                        </div>
                    </li>
                    <li class="flex gap-4">
                        <div class="h-7 w-7 rounded-full bg-[hsl(36_78%_92%)] text-[hsl(14_72%_46%)] font-bold text-sm flex items-center justify-center shrink-0 mt-0.5">5</div>
                        <div>
                            <p class="font-medium text-[hsl(20_30%_14%)] text-sm">Copiez votre clé API</p>
                            <div class="mt-2 rounded-lg bg-[hsl(20_30%_14%)]/5 border border-[hsl(30_25%_86%)] px-3 py-2">
                                <p class="font-mono text-xs text-[hsl(25_15%_42%)]">wave_sn_prod_AbCdEfGhIjKl...</p>
                            </div>
                            <p class="text-xs text-[hsl(25_15%_42%)] mt-1">La clé commence toujours par <strong>wave_sn_prod_</strong></p>
                        </div>
                    </li>
                </ol>

                <div class="rounded-xl bg-red-50 border border-red-200 p-4">
                    <div class="flex items-start gap-2">
                        <svg class="h-5 w-5 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-red-800">Gardez cette clé secrète !</p>
                            <p class="text-xs text-red-600 mt-0.5">Ne la partagez jamais par téléphone, WhatsApp ou email. Ne la donnez qu'ici sur Saveur Kaolack.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Étape 3 -->
        <div class="rounded-2xl border-2 <?php echo $wave_configure ? 'border-green-400 bg-green-50' : 'border-[hsl(14_72%_46%)] bg-[hsl(36_50%_98%)]'; ?> overflow-hidden">
            <div class="flex items-center gap-4 <?php echo $wave_configure ? 'bg-green-100' : 'bg-[hsl(14_72%_46%)]/10'; ?> px-6 py-4 border-b <?php echo $wave_configure ? 'border-green-200' : 'border-[hsl(30_25%_86%)]'; ?>">
                <div class="h-10 w-10 rounded-full <?php echo $wave_configure ? 'bg-green-500' : 'bg-[hsl(14_72%_46%)]'; ?> text-white font-bold text-lg flex items-center justify-center shrink-0">
                    <?php echo $wave_configure ? '✓' : '3'; ?>
                </div>
                <div>
                    <h2 class="font-bold text-[hsl(20_30%_14%)]">
                        <?php echo $wave_configure ? 'Wave est activé !' : 'Coller la clé dans votre dashboard'; ?>
                    </h2>
                    <p class="text-sm text-[hsl(25_15%_42%)]">
                        <?php echo $wave_configure ? 'Vos clients peuvent déjà payer par Wave.' : 'Dernière étape — 30 secondes'; ?>
                    </p>
                </div>
            </div>
            <div class="px-6 py-5 space-y-4">
                <?php if (!$wave_configure): ?>
                <ol class="space-y-3">
                    <li class="flex gap-3 text-sm text-[hsl(20_30%_14%)]">
                        <span class="font-bold text-[hsl(14_72%_46%)] shrink-0">1.</span>
                        <span>Restez connecté sur Saveur Kaolack (ne fermez pas cet onglet)</span>
                    </li>
                    <li class="flex gap-3 text-sm text-[hsl(20_30%_14%)]">
                        <span class="font-bold text-[hsl(14_72%_46%)] shrink-0">2.</span>
                        <span>Allez sur <strong>"Modifier mes infos"</strong> dans le menu de gauche</span>
                    </li>
                    <li class="flex gap-3 text-sm text-[hsl(20_30%_14%)]">
                        <span class="font-bold text-[hsl(14_72%_46%)] shrink-0">3.</span>
                        <span>Faites défiler jusqu'à la section <strong>"Paiement Wave"</strong></span>
                    </li>
                    <li class="flex gap-3 text-sm text-[hsl(20_30%_14%)]">
                        <span class="font-bold text-[hsl(14_72%_46%)] shrink-0">4.</span>
                        <span>Collez votre clé API dans le champ et cliquez <strong>"Enregistrer"</strong></span>
                    </li>
                </ol>
                <a href="modifier_restaurant.php"
                   class="mt-2 flex items-center justify-center gap-2 rounded-2xl bg-[hsl(14_72%_46%)] px-6 py-3 text-white font-semibold hover:bg-[hsl(14_72%_40%)] transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Aller dans "Modifier mes infos" →
                </a>
                <?php else: ?>
                <div class="flex items-center gap-3 text-green-700">
                    <svg class="h-6 w-6 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-medium">Votre clé Wave est enregistrée. Le paiement Wave est actif pour vos clients.</p>
                </div>
                <a href="modifier_restaurant.php"
                   class="mt-2 inline-flex items-center gap-2 rounded-xl border border-green-300 bg-white px-4 py-2 text-sm text-green-700 font-medium hover:bg-green-50 transition-colors">
                    Modifier ma clé Wave
                </a>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- FAQ -->
    <div class="mt-10">
        <h2 class="font-display text-xl font-bold text-[hsl(20_30%_14%)] mb-4">Questions fréquentes</h2>
        <div class="space-y-3">

            <details class="rounded-xl border border-[hsl(30_25%_86%)] bg-[hsl(36_50%_98%)] group">
                <summary class="flex cursor-pointer items-center justify-between px-5 py-4 font-medium text-[hsl(20_30%_14%)]">
                    L'argent va directement sur mon compte Wave ?
                    <svg class="h-5 w-5 text-[hsl(25_15%_42%)] transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="border-t border-[hsl(30_25%_86%)] px-5 py-4 text-sm text-[hsl(25_15%_42%)]">
                    Oui. Quand un client paie par Wave, l'argent va <strong>directement sur votre compte Wave Business</strong>. Saveur Kaolack ne touche pas à l'argent — c'est un paiement de votre client vers vous.
                </div>
            </details>

            <details class="rounded-xl border border-[hsl(30_25%_86%)] bg-[hsl(36_50%_98%)] group">
                <summary class="flex cursor-pointer items-center justify-between px-5 py-4 font-medium text-[hsl(20_30%_14%)]">
                    Est-ce que c'est obligatoire d'activer Wave ?
                    <svg class="h-5 w-5 text-[hsl(25_15%_42%)] transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="border-t border-[hsl(30_25%_86%)] px-5 py-4 text-sm text-[hsl(25_15%_42%)]">
                    Non. Si vous ne configurez pas Wave, vos clients peuvent quand même commander et payer <strong>à la livraison en espèces</strong>. Wave est une option supplémentaire pour attirer plus de clients.
                </div>
            </details>

            <details class="rounded-xl border border-[hsl(30_25%_86%)] bg-[hsl(36_50%_98%)] group">
                <summary class="flex cursor-pointer items-center justify-between px-5 py-4 font-medium text-[hsl(20_30%_14%)]">
                    Ma clé est-elle visible par Saveur Kaolack ?
                    <svg class="h-5 w-5 text-[hsl(25_15%_42%)] transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="border-t border-[hsl(30_25%_86%)] px-5 py-4 text-sm text-[hsl(25_15%_42%)]">
                    Votre clé est stockée de façon sécurisée et n'est jamais affichée à l'écran. Elle est utilisée uniquement de façon automatique pour initier les paiements Wave de vos clients.
                </div>
            </details>

            <details class="rounded-xl border border-[hsl(30_25%_86%)] bg-[hsl(36_50%_98%)] group">
                <summary class="flex cursor-pointer items-center justify-between px-5 py-4 font-medium text-[hsl(20_30%_14%)]">
                    J'ai perdu ma clé ou elle ne fonctionne plus, que faire ?
                    <svg class="h-5 w-5 text-[hsl(25_15%_42%)] transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="border-t border-[hsl(30_25%_86%)] px-5 py-4 text-sm text-[hsl(25_15%_42%)]">
                    Retournez sur <strong>developer.wave.com</strong>, générez une nouvelle clé API, puis collez-la dans <strong>"Modifier mes infos"</strong> sur Saveur Kaolack. L'ancienne clé sera remplacée automatiquement.
                </div>
            </details>

        </div>
    </div>

    <!-- Contact -->
    <div class="mt-8 rounded-2xl bg-[hsl(36_78%_92%)]/40 border border-[hsl(30_25%_86%)] p-6 text-center">
        <p class="font-medium text-[hsl(20_30%_14%)]">Besoin d'aide pour configurer Wave ?</p>
        <p class="text-sm text-[hsl(25_15%_42%)] mt-1">Contactez l'équipe Saveur Kaolack</p>
        <a href="tel:<?php echo SITE_TEL; ?>"
           class="mt-3 inline-flex items-center gap-2 rounded-xl bg-[hsl(14_72%_46%)] px-5 py-2.5 text-white font-medium hover:bg-[hsl(14_72%_40%)] transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.948V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 5V5z"/>
            </svg>
            <?php echo SITE_TEL; ?>
        </a>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>
