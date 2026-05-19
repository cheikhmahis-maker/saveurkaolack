<?php
/**
 * CONFIRMATION.PHP - Affiche le numéro de tracking après commande
 */

require_once 'includes/config.php';
require_once 'includes/fonctions.php';

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier qu'il y a une commande récente
if (empty($_SESSION['dernier_numero_tracking'])) {
    header('Location: index.php');
    exit();
}

$numero_tracking = $_SESSION['dernier_numero_tracking'];

// Nettoyer la session (mais garder le numéro pour cette page)
$pageTitle = 'Commande Confirmée - Saveur Kaolack';

// Récupérer le statut de l'email
$email_envoye = $_SESSION['email_confirmation_envoye'] ?? false;
$email_client = $_SESSION['email_client'] ?? '';

require_once 'includes/header.php';
?>

<section class="bg-gradient-warm py-16">
    <div class="container mx-auto px-4 max-w-2xl text-center">
        <div class="mx-auto h-20 w-20 rounded-full bg-white/20 flex items-center justify-center mb-6">
            <svg class="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="font-display text-3xl font-bold text-white mb-2">Commande Confirmée !</h1>
        <p class="text-white/80">Votre commande a été reçue avec succès</p>
    </div>
</section>

<section class="container mx-auto px-4 max-w-2xl py-12">
    <div class="rounded-3xl bg-[hsl(36_50%_98%)] border border-[hsl(30_25%_86%)] p-8 shadow-soft text-center">
        
        <!-- Numéro de tracking -->
        <div class="mb-8">
            <p class="text-sm text-[hsl(25_15%_42%)] mb-2">Votre numéro de suivi</p>
            <div class="inline-flex items-center gap-3 bg-[hsl(14_72%_46%)]/10 rounded-2xl px-6 py-4 border-2 border-dashed border-[hsl(14_72%_46%)]/30">
                <span class="font-display text-2xl font-bold text-[hsl(14_72%_46%)] tracking-wider" id="tracking-number">
                    <?php echo htmlspecialchars($numero_tracking); ?>
                </span>
                <button onclick="copierCode()" class="p-2 rounded-lg bg-[hsl(14_72%_46%)] text-white hover:bg-[hsl(14_72%_40%)] transition-colors" title="Copier le code">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </button>
            </div>
            <p id="copie-message" class="text-sm text-green-600 mt-2 hidden">✓ Code copié !</p>
        </div>

        <!-- Message email -->
        <?php if ($email_envoye && !empty($email_client)): ?>
        <div class="bg-green-50 border border-green-200 rounded-2xl p-4 mb-6">
            <div class="flex items-start gap-3">
                <svg class="h-6 w-6 text-green-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <div class="text-left">
                    <p class="text-green-800 font-medium">✅ Email envoyé !</p>
                    <p class="text-green-700 text-sm mt-1">
                        Un email de confirmation a été envoyé à <strong><?php echo htmlspecialchars($email_client); ?></strong>.<br>
                        Vérifiez votre boîte de réception (et vos spams).
                    </p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 mb-6">
            <div class="flex items-start gap-3">
                <svg class="h-6 w-6 text-blue-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-left">
                    <p class="text-blue-800 font-medium">💡 Retrouvez votre commande dans votre profil</p>
                    <p class="text-blue-700 text-sm mt-1">
                        Votre numéro de commande est enregistré.<br>
                        <strong>Connectez-vous à votre profil</strong> pour voir toutes vos commandes.<br>
                        <a href="profil_client.php" class="underline text-blue-800">Voir mon profil →</a>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Instructions -->
        <div class="bg-[hsl(36_78%_92%)]/50 rounded-2xl p-6 mb-8">
            <h2 class="font-bold text-[hsl(20_30%_14%)] mb-3">Comment suivre votre commande ?</h2>
            <ol class="text-left text-sm text-[hsl(25_15%_42%)] space-y-2">
                <li class="flex items-start gap-2">
                    <span class="bg-[hsl(14_72%_46%)] text-white rounded-full h-5 w-5 flex items-center justify-center text-xs shrink-0 mt-0.5">1</span>
                    <span>Copiez le code ci-dessus (cliquez sur l'icône)</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="bg-[hsl(14_72%_46%)] text-white rounded-full h-5 w-5 flex items-center justify-center text-xs shrink-0 mt-0.5">2</span>
                    <span>Allez sur la page <strong>"Suivi de commande"</strong></span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="bg-[hsl(14_72%_46%)] text-white rounded-full h-5 w-5 flex items-center justify-center text-xs shrink-0 mt-0.5">3</span>
                    <span>Collez le code pour voir l'état de votre livraison</span>
                </li>
            </ol>
        </div>

        <!-- Information annulation -->
        <div class="rounded-2xl border border-orange-200 bg-orange-50 p-4 mb-8">
            <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-left">
                    <p class="text-sm font-medium text-orange-800">Vous pouvez annuler votre commande</p>
                    <p class="text-xs text-orange-600 mt-1">Tant que votre commande est <strong>"En attente"</strong>, vous pouvez l'annuler depuis la page de suivi. Une fois que le restaurant commence la préparation, l'annulation n'est plus possible.</p>
                </div>
            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="suivi.php" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[hsl(14_72%_46%)] px-6 py-3 text-white font-medium hover:bg-[hsl(14_72%_40%)] transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Suivre ma commande
            </a>
            <a href="index.php" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-[hsl(30_25%_86%)] px-6 py-3 text-[hsl(20_30%_14%)] font-medium hover:bg-[hsl(36_78%_92%)]/50 transition-colors">
                Retour à l'accueil
            </a>
        </div>
    </div>
</section>

<script>
function copierCode() {
    const code = document.getElementById('tracking-number').innerText.trim();
    navigator.clipboard.writeText(code).then(function() {
        const message = document.getElementById('copie-message');
        message.classList.remove('hidden');
        setTimeout(function() {
            message.classList.add('hidden');
        }, 3000);
    }).catch(function(err) {
        // Fallback pour les anciens navigateurs
        const textArea = document.createElement('textarea');
        textArea.value = code;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        
        const message = document.getElementById('copie-message');
        message.classList.remove('hidden');
        setTimeout(function() {
            message.classList.add('hidden');
        }, 3000);
    });
}
</script>

<?php 
// Nettoyer les variables de session après affichage
unset($_SESSION['dernier_numero_tracking']);
unset($_SESSION['derniere_commande_id']);
require_once 'includes/footer.php'; 
?>
