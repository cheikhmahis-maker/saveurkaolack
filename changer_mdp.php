<?php
/**
 * CHANGER MOT DE PASSE / EMAIL - Page de gestion du compte (client, restaurant, admin)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

// Vérifier connexion (tous rôles : client, restaurant, admin)
if (empty($_SESSION['id']) || empty($_SESSION['role'])) {
    header('Location: connexion.php');
    exit();
}

$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'];
// Client, restaurant et admin partagent tous la même table utilisateurs (voir connexion.php)
$table = 'utilisateurs';

// Lien de retour et libellés adaptés au rôle connecté
$retourUrl = match ($user_role) {
    'admin'      => 'admin/index.php',
    'restaurant' => 'dashboard_resto.php',
    default      => 'profil_client.php',
};
$sousTitre = match ($user_role) {
    'admin'      => 'Sécurisez votre compte administrateur',
    'restaurant' => 'Sécurisez votre compte restaurant',
    default      => 'Sécurisez votre compte',
};

$message = '';
$erreur = '';
$email_actuel = $_SESSION['email'] ?? '';

// Connexion BDD
try {
    $pdo = getDB();

    // Récupérer l'email actuel depuis la BDD (source de vérité)
    $stmt = $pdo->prepare("SELECT email FROM {$table} WHERE id = ?");
    $stmt->execute([$user_id]);
    $userRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($userRow) {
        $email_actuel = $userRow['email'];
    }

    // TRAITEMENT DU FORMULAIRE
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ancien_mdp        = $_POST['ancien_mdp'] ?? '';
        $nouvel_email      = trim($_POST['email'] ?? $email_actuel);
        $nouveau_mdp       = $_POST['nouveau_mdp'] ?? '';
        $confirmation_mdp  = $_POST['confirmation_mdp'] ?? '';

        $emailChange = ($nouvel_email !== '' && $nouvel_email !== $email_actuel);
        $mdpChange   = ($nouveau_mdp !== '' || $confirmation_mdp !== '');

        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            $erreur = "Erreur de sécurité : session invalide. Veuillez réessayer.";
        } elseif (empty($ancien_mdp)) {
            $erreur = "Entrez votre mot de passe actuel pour confirmer les changements.";
        } elseif (!$emailChange && !$mdpChange) {
            $erreur = "Vous n'avez rien modifié.";
        } elseif ($emailChange && !filter_var($nouvel_email, FILTER_VALIDATE_EMAIL)) {
            $erreur = "L'adresse email n'est pas valide.";
        } elseif ($mdpChange && strlen($nouveau_mdp) < 6) {
            $erreur = "Le nouveau mot de passe doit faire au moins 6 caractères.";
        } elseif ($mdpChange && $nouveau_mdp !== $confirmation_mdp) {
            $erreur = "La confirmation ne correspond pas au nouveau mot de passe.";
        } else {
            // Vérifier le mot de passe actuel
            $stmt = $pdo->prepare("SELECT password FROM {$table} WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $erreur = "Utilisateur non trouvé.";
            } elseif (!password_verify($ancien_mdp, $user['password']) && $ancien_mdp !== $user['password']) {
                $erreur = "Le mot de passe actuel est incorrect.";
            } elseif ($emailChange && (function () use ($pdo, $nouvel_email, $user_id, $table) {
                $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE email = ? AND id != ? LIMIT 1");
                $stmt->execute([$nouvel_email, $user_id]);
                return (bool) $stmt->fetch();
            })()) {
                $erreur = "Cette adresse email est déjà utilisée par un autre compte.";
            } else {
                $champs = [];
                $valeurs = [];

                if ($emailChange) {
                    $champs[]  = 'email = ?';
                    $valeurs[] = $nouvel_email;
                }
                if ($mdpChange) {
                    $champs[]  = 'password = ?';
                    $valeurs[] = password_hash($nouveau_mdp, PASSWORD_DEFAULT);
                }
                $valeurs[] = $user_id;

                $stmt = $pdo->prepare("UPDATE {$table} SET " . implode(', ', $champs) . " WHERE id = ?");
                $stmt->execute($valeurs);

                if ($emailChange) {
                    $_SESSION['email'] = $nouvel_email;
                    $email_actuel = $nouvel_email;
                }

                $message = $emailChange && $mdpChange
                    ? "Email et mot de passe mis à jour avec succès !"
                    : ($emailChange ? "Email mis à jour avec succès !" : "Mot de passe changé avec succès !");
            }
        }
    }

} catch (PDOException $e) {
    error_log('Erreur changer_mdp: ' . $e->getMessage());
    $erreur = 'Une erreur technique est survenue. Veuillez réessayer.';
}

$pageTitle = 'Mon compte';
require_once 'includes/header.php';
?>

<section class="container mx-auto px-4 max-w-md py-12">
    <div class="text-center mb-8">
        <div class="mx-auto h-16 w-16 rounded-full bg-[hsl(14_72%_46%)]/10 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-[hsl(14_72%_46%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <h1 class="font-display text-2xl font-bold text-[hsl(20_30%_14%)]">Mon compte</h1>
        <p class="text-[hsl(25_15%_42%)]"><?php echo htmlspecialchars($sousTitre); ?></p>
    </div>

    <div class="rounded-2xl bg-[hsl(36_50%_98%)] border border-[hsl(30_25%_86%)] p-6 shadow-soft">
        <?php if ($message): ?>
        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-700">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <?php if ($erreur): ?>
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-700">
            <?php echo htmlspecialchars($erreur); ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <?php echo champTokenCSRF(); ?>

            <div>
                <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email_actuel); ?>" required class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none">
            </div>

            <div class="pt-2 border-t border-[hsl(30_25%_86%)]">
                <p class="text-xs uppercase tracking-wide text-[hsl(25_15%_42%)] mt-4 mb-1">Nouveau mot de passe (optionnel)</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Nouveau mot de passe</label>
                <input type="password" name="nouveau_mdp" minlength="6" class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none">
                <p class="text-xs text-gray-500 mt-1">Laissez vide pour garder le même mot de passe. Minimum 6 caractères sinon.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Confirmer le nouveau mot de passe</label>
                <input type="password" name="confirmation_mdp" class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none">
            </div>

            <div class="pt-2 border-t border-[hsl(30_25%_86%)]">
                <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1 mt-4">Mot de passe actuel <span class="text-red-500">*</span></label>
                <input type="password" name="ancien_mdp" required class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none">
                <p class="text-xs text-gray-500 mt-1">Obligatoire pour confirmer tout changement.</p>
            </div>

            <button type="submit" class="w-full rounded-xl bg-[hsl(14_72%_46%)] px-4 py-2 font-medium text-white hover:bg-[hsl(14_72%_40%)] transition-colors">
                🔒 Enregistrer les modifications
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-[hsl(30_25%_86%)] text-center">
            <a href="<?php echo htmlspecialchars($retourUrl); ?>" class="text-[hsl(14_72%_46%)] hover:underline text-sm">← Retour au dashboard</a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
