<?php
/**
 * REINITIALISER_MDP.PHP - Réinitialisation du mot de passe
 */

session_start();
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

$pageTitle = 'Réinitialiser le mot de passe';
$erreur = '';
$succes = '';
$token = $_GET['token'] ?? '';
$email = '';
$tokenValide = false;

// Vérifier le token
if (!empty($token)) {
    try {
        $pdo = getDB();
        
        // Chercher le token non expiré
        $stmt = $pdo->prepare("
            SELECT email FROM password_resets 
            WHERE token = ? AND expires_at > NOW() 
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $email = $result['email'];
            $tokenValide = true;
        } else {
            $erreur = 'Ce lien de réinitialisation est invalide ou a expiré. Veuillez faire une nouvelle demande.';
        }
    } catch (PDOException $e) {
        $erreur = 'Une erreur est survenue. Veuillez réessayer.';
    }
} else {
    $erreur = 'Aucun token de réinitialisation fourni.';
}

// Traitement du nouveau mot de passe
if ($tokenValide && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    // Validation
    if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erreur = 'Requête invalide. Veuillez réessayer.';
    } elseif (strlen($password) < 8) {
        $erreur = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif ($password !== $passwordConfirm) {
        $erreur = 'Les mots de passe ne correspondent pas.';
    } else {
        try {
            // Hasher le nouveau mot de passe
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Mettre à jour le mot de passe
            $stmt = $pdo->prepare("UPDATE utilisateurs SET password = ? WHERE email = ? LIMIT 1");
            $stmt->execute([$hash, $email]);
            
            if ($stmt->rowCount() > 0) {
                // Supprimer le token utilisé
                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                $stmt->execute([$email]);
                
                $succes = 'Votre mot de passe a été réinitialisé avec succès !';
            } else {
                $erreur = 'Impossible de mettre à jour le mot de passe.';
            }
        } catch (PDOException $e) {
            $erreur = 'Une erreur est survenue lors de la mise à jour.';
        }
    }
}

require_once 'includes/header.php';
?>

<section class="bg-[hsl(36_78%_92%)]/40">
    <div class="container mx-auto px-4 max-w-7xl py-12">
        <div class="text-xs font-semibold uppercase tracking-widest text-[hsl(14_72%_46%)]">Sécurité</div>
        <h1 class="mt-2 font-display text-4xl font-bold text-[hsl(20_30%_14%)] md:text-5xl">
            Nouveau mot de passe
        </h1>
    </div>
</section>

<section class="container mx-auto px-4 max-w-7xl py-12">
    <div class="mx-auto max-w-md">
        <div class="rounded-3xl bg-[hsl(36_50%_98%)] p-8 shadow-soft">
            
            <?php if ($succes): ?>
                <div class="mb-6 rounded-xl bg-green-50 p-4 text-green-800">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-medium">Succès !</span>
                    </div>
                    <p class="mt-2 text-sm"><?php echo htmlspecialchars($succes); ?></p>
                    <a href="connexion.php" class="mt-4 inline-block text-[hsl(14_72%_46%)] hover:underline">
                        Se connecter avec le nouveau mot de passe →
                    </a>
                </div>
            <?php elseif ($tokenValide): ?>
                <p class="mb-6 text-[hsl(25_15%_42%)]">
                    Créez un nouveau mot de passe sécurisé pour votre compte <strong><?php echo htmlspecialchars($email); ?></strong>.
                </p>
                
                <?php if ($erreur): ?>
                    <div class="mb-4 rounded-xl bg-red-50 p-4 text-red-800">
                        <?php echo htmlspecialchars($erreur); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo genererTokenCSRF(); ?>">
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-[hsl(20_30%_14%)]">
                            Nouveau mot de passe
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required
                               minlength="8"
                               class="mt-1.5 h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20"
                               placeholder="••••••••">
                        <p class="mt-1 text-xs text-[hsl(25_15%_42%)]">Minimum 8 caractères</p>
                    </div>

                    <div>
                        <label for="password_confirm" class="block text-sm font-medium text-[hsl(20_30%_14%)]">
                            Confirmer le mot de passe
                        </label>
                        <input type="password"
                               id="password_confirm"
                               name="password_confirm"
                               required
                               minlength="8"
                               class="mt-1.5 h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20" 
                               placeholder="••••••••">
                    </div>
                    
                    <button type="submit" 
                            class="h-12 w-full rounded-xl bg-gradient-warm text-[hsl(38_60%_97%)] font-semibold shadow-warm hover:opacity-90 transition-opacity">
                        RÉINITIALISER LE MOT DE PASSE
                    </button>
                </form>
            <?php else: ?>
                <div class="rounded-xl bg-red-50 p-4 text-red-800">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-medium">Lien invalide</span>
                    </div>
                    <p class="mt-2 text-sm"><?php echo htmlspecialchars($erreur); ?></p>
                    <a href="mot_de_passe_oublie.php" class="mt-4 inline-block text-[hsl(14_72%_46%)] hover:underline">
                        Faire une nouvelle demande →
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
