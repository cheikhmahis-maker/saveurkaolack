<?php
/**
 * WAVE_ERROR.PHP - Callback Wave après échec ou annulation du paiement
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$raison          = trim($_GET['raison'] ?? 'echec');
$numero_tracking = trim($_GET['numero'] ?? ($_SESSION['wave_numero_tracking'] ?? ''));

// Annuler la commande Wave en BDD si elle est encore en_attente
if (!empty($numero_tracking) && !in_array($raison, ['introuvable', 'erreur_serveur'])) {
    try {
        $pdo = getDB();
        $pdo->prepare("
            UPDATE commandes
            SET statut = 'annulee', raison_annulation = 'Paiement Wave échoué'
            WHERE numero_tracking = ? AND statut = 'en_attente' AND mode_paiement = 'wave'
        ")->execute([$numero_tracking]);
    } catch (PDOException $e) {
        error_log('wave_error annulation: ' . $e->getMessage());
    }
}

// Nettoyer les variables Wave de la session
unset($_SESSION['wave_api_key'], $_SESSION['wave_numero_tracking'], $_SESSION['wave_commande_id']);

$pageTitle = 'Paiement Wave échoué - Saveur Kaolack';
require_once 'includes/header.php';
?>

<section class="bg-red-50 py-16">
    <div class="container mx-auto px-4 max-w-2xl text-center">
        <div class="mx-auto h-20 w-20 rounded-full bg-red-100 flex items-center justify-center mb-6">
            <svg class="h-10 w-10 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>
        <h1 class="font-display text-3xl font-bold text-red-700 mb-2">Paiement non abouti</h1>
        <p class="text-red-500">
            <?php if ($raison === 'annule'): ?>
                Vous avez annulé le paiement Wave.
            <?php elseif ($raison === 'introuvable'): ?>
                La commande associée est introuvable.
            <?php elseif ($raison === 'erreur_serveur'): ?>
                Une erreur serveur s'est produite. Contactez le support.
            <?php else: ?>
                Le paiement Wave n'a pas pu être validé.
            <?php endif; ?>
        </p>
    </div>
</section>

<section class="container mx-auto px-4 max-w-2xl py-12">
    <div class="rounded-3xl bg-[hsl(36_50%_98%)] border border-[hsl(30_25%_86%)] p-8 shadow-soft">

        <!-- Message commande annulée -->
        <?php if (!empty($numero_tracking) && !in_array($raison, ['introuvable', 'erreur_serveur'])): ?>
        <div class="mb-6 rounded-2xl bg-gray-50 border border-gray-200 p-4">
            <p class="text-sm text-gray-700 font-medium">Commande annulée</p>
            <p class="text-xs text-gray-500 mt-1">
                La commande <strong><?php echo htmlspecialchars($numero_tracking); ?></strong> a été annulée.<br>
                Votre panier est toujours disponible — vous pouvez recommander quand vous voulez.
            </p>
        </div>
        <?php endif; ?>

        <!-- Que faire ? -->
        <h2 class="font-bold text-[hsl(20_30%_14%)] mb-4 text-center">Que souhaitez-vous faire ?</h2>

        <div class="space-y-3">
            <!-- Réessayer depuis le panier -->
            <a href="panier.php"
               class="flex items-center gap-4 rounded-2xl border-2 border-[hsl(14_72%_46%)] bg-[hsl(14_72%_46%)]/5 p-4 hover:bg-[hsl(14_72%_46%)]/10 transition-colors">
                <span class="text-3xl">🛒</span>
                <div>
                    <div class="font-semibold text-[hsl(20_30%_14%)]">Réessayer la commande</div>
                    <div class="text-xs text-[hsl(25_15%_42%)]">Votre panier est intact — choisissez un autre mode de paiement</div>
                </div>
                <svg class="ml-auto h-5 w-5 text-[hsl(14_72%_46%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            <!-- Retour accueil -->
            <a href="index.php"
               class="flex items-center gap-4 rounded-2xl border border-[hsl(30_25%_86%)] p-4 hover:bg-[hsl(36_78%_92%)]/40 transition-colors">
                <span class="text-3xl">🏠</span>
                <div>
                    <div class="font-semibold text-[hsl(20_30%_14%)]">Retour à l'accueil</div>
                    <div class="text-xs text-[hsl(25_15%_42%)]">Revenir à la page principale</div>
                </div>
                <svg class="ml-auto h-5 w-5 text-[hsl(25_15%_42%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <!-- Contact support -->
        <p class="mt-8 text-center text-xs text-[hsl(25_15%_42%)]">
            Besoin d'aide ? Contactez-nous au
            <a href="tel:<?php echo SITE_TEL; ?>" class="font-medium text-[hsl(14_72%_46%)] hover:underline">
                <?php echo SITE_TEL; ?>
            </a>
        </p>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
