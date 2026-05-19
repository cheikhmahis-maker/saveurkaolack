<?php
/**
 * CHANGER MOT DE PASSE - Page de changement de mot de passe pour restaurant
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/fonctions.php';

// Vérifier connexion (tous rôles : client, restaurant, admin)
if (empty($_SESSION['id']) || empty($_SESSION['role'])) {
    header('Location: connexion.php');
    exit();
}

$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'];
$table = ($user_role === 'client') ? 'utilisateurs' : (($user_role === 'restaurant') ? 'restaurants' : 'admins');

$message = '';
$erreur = '';

// Connexion BDD
try {
    $pdo = new PDO('mysql:host=localhost;dbname=saveur_kaolack;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // TRAITEMENT DU FORMULAIRE
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ancien_mdp = $_POST['ancien_mdp'] ?? '';
        $nouveau_mdp = $_POST['nouveau_mdp'] ?? '';
        $confirmation_mdp = $_POST['confirmation_mdp'] ?? '';
        
        // Vérifier que les champs sont remplis
        if (empty($ancien_mdp) || empty($nouveau_mdp) || empty($confirmation_mdp)) {
            $erreur = "Tous les champs sont obligatoires.";
        }
        // Vérifier que le nouveau mot de passe fait au moins 6 caractères
        elseif (strlen($nouveau_mdp) < 6) {
            $erreur = "Le nouveau mot de passe doit faire au moins 6 caractères.";
        }
        // Vérifier que les mots de passe correspondent
        elseif ($nouveau_mdp !== $confirmation_mdp) {
            $erreur = "La confirmation ne correspond pas au nouveau mot de passe.";
        }
        else {
            // Vérifier l'ancien mot de passe (dans la bonne table selon le rôle)
            $stmt = $pdo->prepare("SELECT password FROM {$table} WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $erreur = "Utilisateur non trouvé.";
            }
            // Si le mot de passe est en clair (ancien format) ou hashé
            elseif (!password_verify($ancien_mdp, $user['password']) && $ancien_mdp !== $user['password']) {
                $erreur = "L'ancien mot de passe est incorrect.";
            }
            else {
                // Hasher le nouveau mot de passe
                $hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);
                
                // Mettre à jour dans la bonne table
                $stmt = $pdo->prepare("UPDATE {$table} SET password = ? WHERE id = ?");
                $stmt->execute([$hash, $user_id]);
                
                $message = "Mot de passe changé avec succès !";
            }
        }
    }
    
} catch (PDOException $e) {
    $erreur = 'Erreur : ' . $e->getMessage();
}

$pageTitle = 'Changer mon mot de passe';
require_once 'includes/header.php';
?>

<section class="container mx-auto px-4 max-w-md py-12">
    <div class="text-center mb-8">
        <div class="mx-auto h-16 w-16 rounded-full bg-[hsl(14_72%_46%)]/10 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-[hsl(14_72%_46%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <h1 class="font-display text-2xl font-bold text-[hsl(20_30%_14%)]">Changer mon mot de passe</h1>
        <p class="text-[hsl(25_15%_42%)]">Sécurisez votre compte restaurant</p>
    </div>

    <div class="rounded-2xl bg-[hsl(36_50%_98%)] border border-[hsl(30_25%_86%)] p-6 shadow-soft">
        <?php if ($message): ?>
        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-700">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <?php if ($erreur): ?>
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-700">
            <?php echo $erreur; ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Ancien mot de passe</label>
                <input type="password" name="ancien_mdp" required class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Nouveau mot de passe</label>
                <input type="password" name="nouveau_mdp" required minlength="6" class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none">
                <p class="text-xs text-gray-500 mt-1">Minimum 6 caractères</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Confirmer le nouveau mot de passe</label>
                <input type="password" name="confirmation_mdp" required class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none">
            </div>
            
            <button type="submit" class="w-full rounded-xl bg-[hsl(14_72%_46%)] px-4 py-2 font-medium text-white hover:bg-[hsl(14_72%_40%)] transition-colors">
                🔒 Changer mon mot de passe
            </button>
        </form>
        
        <div class="mt-6 pt-4 border-t border-[hsl(30_25%_86%)] text-center">
            <a href="dashboard_resto.php" class="text-[hsl(14_72%_46%)] hover:underline text-sm">← Retour au dashboard</a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
