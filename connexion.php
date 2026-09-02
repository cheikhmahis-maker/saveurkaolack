<?php
/**
 * CONNEXION.PHP - Authentification Saveur Kaolack
 */

// Démarrer la session en premier
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

// Si déjà connecté, rediriger selon le rôle
if (!empty($_SESSION['id'])) {
    switch ($_SESSION['role']) {
        case 'client':
            header('Location: index.php');
            break;
        case 'restaurant':
            header('Location: dashboard_resto.php');
            break;
        case 'admin':
            header('Location: admin/index.php');
            break;
        default:
            header('Location: index.php');
    }
    exit();
}

$pageTitle = 'Connexion';
$pageNoIndex = true;
$erreur = '';
$succes = '';

// Gérer le paramètre de redirection depuis l'URL
$redirectAutorise = ['panier' => 'panier.php', 'checkout' => 'checkout.php'];
if (!empty($_GET['redirect']) && isset($redirectAutorise[$_GET['redirect']])) {
    $_SESSION['redirect_after_login'] = $redirectAutorise[$_GET['redirect']];
}

// Déterminer l'onglet actif (connexion ou inscription)
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'signin';

// ============================================================
// TRAITEMENT DU FORMULAIRE DE CONNEXION
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'connexion') {
    $email    = isset($_POST['email'])    ? trim($_POST['email'])  : '';
    $password = isset($_POST['password']) ? $_POST['password']     : '';
    $ip       = $_SERVER['REMOTE_ADDR']  ?? 'unknown';

    $maxAttempts = 5;
    $lockoutTime = 15 * 60; // 15 minutes en secondes

    if (empty($email) || empty($password)) {
        $erreur = 'Veuillez remplir tous les champs.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Adresse email invalide.';
    } elseif (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erreur = 'Erreur de sécurité : session invalide. Veuillez réessayer.';
    } else {
        try {
            $pdo = getDB();

            // Nettoyer les anciennes tentatives (plus de 15 min)
            $pdo->prepare("DELETE FROM login_attempts WHERE created_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE)")
                ->execute();

            // Compter les tentatives récentes pour cette IP + email
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND email = ?");
            $stmt->execute([$ip, $email]);
            $nbTentatives = (int) $stmt->fetchColumn();

            if ($nbTentatives >= $maxAttempts) {
                // Calculer le temps restant
                $stmt = $pdo->prepare("SELECT TIMESTAMPDIFF(SECOND, MIN(created_at), NOW()) FROM login_attempts WHERE ip = ? AND email = ?");
                $stmt->execute([$ip, $email]);
                $secondesEcoulees = (int) $stmt->fetchColumn();
                $minutesRestantes = ceil(($lockoutTime - $secondesEcoulees) / 60);
                $erreur = "Trop de tentatives échouées. Réessayez dans {$minutesRestantes} minute(s).";
            } else {
                $stmt = $pdo->prepare("SELECT id, nom, prenom, email, password, role FROM utilisateurs WHERE email = :email LIMIT 1");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    // Connexion réussie — effacer les tentatives en BDD
                    $pdo->prepare("DELETE FROM login_attempts WHERE ip = ? AND email = ?")
                        ->execute([$ip, $email]);

                    $_SESSION['id']                = $user['id'];
                    $_SESSION['nom']               = $user['nom'];
                    $_SESSION['prenom']            = $user['prenom'];
                    $_SESSION['email']             = $user['email'];
                    $_SESSION['role']              = $user['role'];
                    $_SESSION['derniere_activite'] = time();

                    session_regenerate_id(true);

                    if (!empty($_SESSION['redirect_after_login'])) {
                        $redirect = $_SESSION['redirect_after_login'];
                        unset($_SESSION['redirect_after_login']);
                        header('Location: ' . $redirect);
                        exit();
                    }

                    switch ($user['role']) {
                        case 'client':     header('Location: index.php');          break;
                        case 'restaurant': header('Location: dashboard_resto.php'); break;
                        case 'admin':      header('Location: admin/index.php');     break;
                        default:           header('Location: index.php');
                    }
                    exit();

                } else {
                    // Mauvais mot de passe — enregistrer la tentative en BDD
                    $pdo->prepare("INSERT INTO login_attempts (ip, email) VALUES (?, ?)")
                        ->execute([$ip, $email]);

                    $restantes = $maxAttempts - ($nbTentatives + 1);
                    if ($restantes > 0) {
                        $erreur = "Email ou mot de passe incorrect. Il vous reste {$restantes} tentative(s).";
                    } else {
                        $erreur = "Trop de tentatives échouées. Réessayez dans 15 minutes.";
                    }
                }
            }

        } catch (PDOException $e) {
            error_log('Erreur connexion: ' . $e->getMessage());
            $erreur = 'Une erreur technique est survenue. Veuillez réessayer.';
        }
    }
}

// ============================================================
// TRAITEMENT DU FORMULAIRE D'INSCRIPTION
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'inscription') {
    // Récupération et nettoyage
    $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
    $prenom = isset($_POST['prenom']) ? trim($_POST['prenom']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $telephone = isset($_POST['telephone']) ? trim($_POST['telephone']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
    $role = isset($_POST['role']) ? $_POST['role'] : 'client'; // Par défaut client
    
    // Validation
    if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
        $erreur = 'Veuillez remplir tous les champs obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Adresse email invalide.';
    } elseif (!empty($telephone) && !telephoneValide($telephone)) {
        $erreur = 'Le numéro de téléphone n\'est pas valide. Exemple : +221 77 123 45 67';
    } elseif (strlen($password) < 8) {
        $erreur = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif ($password !== $password_confirm) {
        $erreur = 'Les mots de passe ne correspondent pas.';
    } elseif (!in_array($role, ['client', 'restaurant'])) {
        $erreur = 'Type de compte invalide.';
    } elseif (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erreur = 'Erreur de sécurité : session invalide. Veuillez réessayer.';
    } else {
        try {
            $pdo = getDB();
            
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $erreur = 'Cette adresse email est déjà utilisée.';
            } else {
                // Hachage sécurisé du mot de passe
                $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                
                // Insertion du nouvel utilisateur
                $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, email, telephone, password, role, created_at) VALUES (:nom, :prenom, :email, :telephone, :password, :role, NOW())");
                $stmt->execute([
                    ':nom' => $nom,
                    ':prenom' => $prenom,
                    ':email' => $email,
                    ':telephone' => $telephone,
                    ':password' => $password_hash,
                    ':role' => $role
                ]);
                
                // Succès - basculer vers l'onglet connexion
                $succes = 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
                $activeTab = 'signin';
            }
            
        } catch (PDOException $e) {
            error_log('Erreur inscription: ' . $e->getMessage());
            $erreur = 'Une erreur technique est survenue. Veuillez réessayer.';
        }
    }
}

// Vérification des messages de la session (timeout, déconnexion)
if (isset($_GET['timeout'])) {
    $erreur = 'Votre session a expiré pour inactivité. Veuillez vous reconnecter.';
}
if (isset($_GET['deconnexion'])) {
    $succes = 'Vous avez été déconnecté avec succès.';
}
if (isset($_GET['message']) && $_GET['message'] === 'connectez_vous_pour_commander') {
    $succes = '🛒 Connectez-vous pour finaliser votre commande et suivre vos achats dans votre profil !';
}

require_once 'includes/header.php';
?>

<section class="container mx-auto px-4 max-w-7xl flex items-center justify-center py-12 md:py-20">
    <div class="w-full max-w-md rounded-3xl bg-[hsl(36_50%_98%)] p-6 shadow-warm md:p-8">
        <div class="mb-6 flex flex-col items-center text-center">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-warm shadow-warm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-[hsl(38_60%_97%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2a9 9 0 00-9 9v1a3 3 0 003 3h12a3 3 0 003-3v-1a9 9 0 00-9-9zM12 2v6m0 0l-3-3m3 3l3-3" />
                </svg>
            </div>
            <h1 class="mt-4 font-display text-2xl font-bold text-[hsl(20_30%_14%)]">
                Bienvenue
            </h1>
            <p class="mt-1 text-sm text-[hsl(25_15%_42%)]">
                Espace restaurateur & client
            </p>
        </div>

        <!-- Tabs -->
        <div class="grid w-full grid-cols-2 rounded-xl bg-[hsl(36_78%_92%)]/50 p-1">
            <a href="?tab=signin" class="rounded-lg px-3 py-2 text-center text-sm font-medium transition-colors <?php echo $activeTab === 'signin' ? 'bg-[hsl(36_50%_98%)] text-[hsl(20_30%_14%)] shadow-sm' : 'text-[hsl(25_15%_42%)] hover:text-[hsl(20_30%_14%)]'; ?>">
                Connexion
            </a>
            <a href="?tab=signup" class="rounded-lg px-3 py-2 text-center text-sm font-medium transition-colors <?php echo $activeTab === 'signup' ? 'bg-[hsl(36_50%_98%)] text-[hsl(20_30%_14%)] shadow-sm' : 'text-[hsl(25_15%_42%)] hover:text-[hsl(20_30%_14%)]'; ?>">
                Inscription
            </a>
        </div>

        <!-- Messages d'erreur ou succès -->
        <?php if (!empty($erreur)): ?>
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <?php echo htmlspecialchars($erreur); ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($succes)): ?>
        <div class="mt-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            <?php echo htmlspecialchars($succes); ?>
        </div>
        <?php endif; ?>

        <?php if ($activeTab === 'signin'): ?>
        <!-- CONNEXION -->
        <form method="POST" action="" class="mt-6 space-y-4">
            <?php echo champTokenCSRF(); ?>
            <input type="hidden" name="action" value="connexion">
            <div>
                <label for="email-in" class="block text-sm font-medium text-[hsl(20_30%_14%)]">Email</label>
                <div class="relative mt-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[hsl(25_15%_42%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <input type="email" name="email" id="email-in" required class="h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] pl-10 pr-4 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20" placeholder="vous@exemple.sn">
                </div>
            </div>
            <div>
                <div class="flex items-center justify-between">
                    <label for="pwd-in" class="block text-sm font-medium text-[hsl(20_30%_14%)]">Mot de passe</label>
                    <a href="mot_de_passe_oublie.php" class="text-xs font-medium text-[hsl(14_72%_46%)] hover:underline">Mot de passe oublié ?</a>
                </div>
                <div class="relative mt-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[hsl(25_15%_42%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <input type="password" name="password" id="pwd-in" required class="h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] pl-10 pr-4 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20" placeholder="••••••••">
                </div>
            </div>
            <button type="submit" class="h-12 w-full rounded-2xl bg-gradient-warm text-[hsl(38_60%_97%)] text-base font-medium shadow-warm hover:opacity-90 transition-opacity">
                Se connecter
            </button>
        </form>
        <?php else: ?>
        <!-- INSCRIPTION -->
        <form method="POST" action="" class="mt-6 space-y-4">
            <?php echo champTokenCSRF(); ?>
            <input type="hidden" name="action" value="inscription">
            
            <!-- Type de compte -->
            <div>
                <label class="block text-sm font-medium text-[hsl(20_30%_14%)]">Type de compte *</label>
                <div class="mt-1.5 grid grid-cols-2 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="role" value="client" checked class="peer sr-only">
                        <div class="rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 text-center text-sm peer-checked:border-[hsl(14_72%_46%)] peer-checked:bg-[hsl(14_72%_46%)] peer-checked:text-white">
                            Client
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="role" value="restaurant" class="peer sr-only">
                        <div class="rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 text-center text-sm peer-checked:border-[hsl(14_72%_46%)] peer-checked:bg-[hsl(14_72%_46%)] peer-checked:text-white">
                            Restaurateur
                        </div>
                    </label>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="prenom-up" class="block text-sm font-medium text-[hsl(20_30%_14%)]">Prénom *</label>
                    <input type="text" name="prenom" id="prenom-up" required class="mt-1.5 h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20" placeholder="Aïssatou">
                </div>
                <div>
                    <label for="nom-up" class="block text-sm font-medium text-[hsl(20_30%_14%)]">Nom *</label>
                    <input type="text" name="nom" id="nom-up" required class="mt-1.5 h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20" placeholder="Diop">
                </div>
            </div>
            <div>
                <label for="phone-up" class="block text-sm font-medium text-[hsl(20_30%_14%)]">Téléphone</label>
                <div class="relative mt-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[hsl(25_15%_42%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <input type="tel" name="telephone" id="phone-up" pattern="[\d\s\-\+]{7,17}" title="Entrez un numéro valide (ex: +221 77 123 45 67)" class="h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] pl-10 pr-4 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20" placeholder="+221 77 000 00 00">
                </div>
            </div>
            <div>
                <label for="email-up" class="block text-sm font-medium text-[hsl(20_30%_14%)]">Email</label>
                <div class="relative mt-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[hsl(25_15%_42%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <input type="email" name="email" id="email-up" required class="h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] pl-10 pr-4 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20" placeholder="vous@exemple.sn">
                </div>
            </div>
            <div>
                <label for="pwd-up" class="block text-sm font-medium text-[hsl(20_30%_14%)]">Mot de passe</label>
                <div class="relative mt-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[hsl(25_15%_42%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <input type="password" name="password" id="pwd-up" required minlength="8" class="h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] pl-10 pr-4 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20" placeholder="Au moins 8 caractères">
                </div>
            </div>
            <div>
                <label for="pwd-confirm" class="block text-sm font-medium text-[hsl(20_30%_14%)]">Confirmer le mot de passe *</label>
                <div class="relative mt-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[hsl(25_15%_42%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <input type="password" name="password_confirm" id="pwd-confirm" required minlength="8" class="h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] pl-10 pr-4 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20" placeholder="Répétez votre mot de passe">
                </div>
            </div>
            <button type="submit" class="h-12 w-full rounded-2xl bg-gradient-warm text-[hsl(38_60%_97%)] text-base font-medium shadow-warm hover:opacity-90 transition-opacity">
                Créer mon compte
            </button>
        </form>
        <?php endif; ?>

        <p class="mt-6 text-center text-xs text-[hsl(25_15%_42%)]">
            Vous êtes restaurateur ? <a href="<?php echo BASE_URL; ?>partenaire.php" class="font-semibold text-[hsl(14_72%_46%)] hover:underline">Inscrire mon restaurant</a>
        </p>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
