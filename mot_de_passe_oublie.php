<?php
/**
 * MOT_DE_PASSE_OUBLIE.PHP - Demande de réinitialisation
 */

session_start();
require_once 'includes/config.php';
require_once 'includes/fonctions.php';

$pageTitle = 'Mot de passe oublié';
$erreur = '';
$succes = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Veuillez entrer une adresse email valide.';
    } else {
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=saveur_kaolack;charset=utf8mb4', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Vérifier si l'email existe
            $stmt = $pdo->prepare("SELECT id, nom, prenom FROM utilisateurs WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Générer un token unique
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Sauvegarder le token en base
                $stmt = $pdo->prepare("
                    INSERT INTO password_resets (email, token, expires_at, created_at) 
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE token = ?, expires_at = ?, created_at = NOW()
                ");
                $stmt->execute([$email, $token, $expires, $token, $expires]);
                
                // Envoyer l'email de réinitialisation
                $resetUrl = "http://" . $_SERVER['HTTP_HOST'] . "/saveur-php/reinitialiser_mdp.php?token=" . urlencode($token);
                
                $sujet = "Réinitialisation de votre mot de passe - Saveur Kaolack";
                $messageHtml = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Réinitialisation mot de passe</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
        <h2 style="color: #d35400;">🍽️ Saveur Kaolack</h2>
        <p>Bonjour <strong>{$user['prenom']} {$user['nom']}</strong>,</p>
        <p>Vous avez demandé à réinitialiser votre mot de passe.</p>
        
        <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; margin: 20px 0; text-align: center;">
            <p style="margin-bottom: 20px;">Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe :</p>
            <a href="{$resetUrl}" style="background: #d35400; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
                RÉINITIALISER MON MOT DE PASSE
            </a>
        </div>
        
        <p style="font-size: 12px; color: #666;">
            Ce lien est valable pendant <strong>1 heure</strong>.<br>
            Si vous n'avez pas fait cette demande, ignorez cet email.<br>
            Pour des raisons de sécurité, ne partagez ce lien avec personne.
        </p>
        
        <p style="margin-top: 30px;">Merci,<br><strong>L'équipe Saveur Kaolack</strong></p>
    </div>
</body>
</html>
HTML;
                
                // Envoyer l'email
                $emailEnvoye = envoyerEmailSimple($email, $sujet, $messageHtml);
                
                if ($emailEnvoye) {
                    $succes = '✅ Email envoyé ! Vérifiez votre boîte de réception (et vos spams). Le lien est valable 1 heure.';
                } else {
                    $succes = '⚠️ La demande a été enregistrée mais l\'email n\'a pas pu être envoyé. <br><br>
                    <strong>Cause probable :</strong> La configuration Gmail n\'est pas encore faite.<br>
                    <a href="config_email.php" class="text-[hsl(14_72%_46%)] underline">Voir comment configurer l\'email →</a>';
                }
            } else {
                // Pour la sécurité, ne pas révéler si l'email existe ou pas
                $succes = 'Si cette adresse email existe dans notre système, un email de réinitialisation a été envoyé.';
            }
        } catch (PDOException $e) {
            $erreur = 'Une erreur est survenue. Veuillez réessayer plus tard.';
        }
    }
}

require_once 'includes/header.php';
?>

<section class="bg-[hsl(36_78%_92%)]/40">
    <div class="container mx-auto px-4 max-w-7xl py-12">
        <div class="text-xs font-semibold uppercase tracking-widest text-[hsl(14_72%_46%)]">Aide</div>
        <h1 class="mt-2 font-display text-4xl font-bold text-[hsl(20_30%_14%)] md:text-5xl">
            Mot de passe oublié
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="font-medium">Email envoyé !</span>
                    </div>
                    <p class="mt-2 text-sm"><?php echo htmlspecialchars($succes); ?></p>
                    <a href="connexion.php" class="mt-4 inline-block text-[hsl(14_72%_46%)] hover:underline">
                        Retour à la connexion
                    </a>
                </div>
            <?php else: ?>
                <p class="mb-6 text-[hsl(25_15%_42%)]">
                    Entrez votre adresse email et nous vous enverrons un lien pour réinitialiser votre mot de passe.
                </p>
                
                <?php if ($erreur): ?>
                    <div class="mb-4 rounded-xl bg-red-50 p-4 text-red-800">
                        <?php echo htmlspecialchars($erreur); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo genererTokenCSRF(); ?>">
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-[hsl(20_30%_14%)]">
                            Votre email
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               required
                               class="mt-1.5 h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20" 
                               placeholder="votre@email.com">
                    </div>
                    
                    <button type="submit" 
                            class="h-12 w-full rounded-xl bg-gradient-warm text-[hsl(38_60%_97%)] font-semibold shadow-warm hover:opacity-90 transition-opacity">
                        ENVOYER LE LIEN
                    </button>
                </form>
                
                <div class="mt-6 text-center">
                    <a href="connexion.php" class="text-sm text-[hsl(14_72%_46%)] hover:underline">
                        ← Retour à la connexion
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
