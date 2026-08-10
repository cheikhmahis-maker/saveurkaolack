<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';
require_once 'includes/notifications.php';
$pageTitle = 'Devenir partenaire';

// Données des avantages partenaire
$benefits = [
    ['icon' => 'users', 'title' => 'Nouveaux clients', 'text' => 'Touchez des milliers de clients à Kaolack qui commandent chaque jour.'],
    ['icon' => 'trending-up', 'title' => 'Croissance garantie', 'text' => 'Augmentez votre chiffre d\'affaires sans investir en livreurs.'],
    ['icon' => 'star', 'title' => 'Service simplifié', 'text' => 'Gérez vos commandes et votre menu en ligne facilement.']
];

$message = '';
$erreur = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom         = trim($_POST['resto']    ?? '');
    $proprietaire = trim($_POST['owner']   ?? '');
    $telephone   = trim($_POST['phone']    ?? '');
    $email       = trim($_POST['email']    ?? '');
    $adresse     = trim($_POST['addr']     ?? '');
    $quartier    = trim($_POST['quartier'] ?? 'Centre ville');
    $description = trim($_POST['msg']      ?? '');

    // Validation
    if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erreur = "Erreur de sécurité. Veuillez réessayer.";
    } elseif (empty($nom) || empty($proprietaire) || empty($telephone) || empty($email) || empty($adresse)) {
        $erreur = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse email n'est pas valide.";
    } else {
        try {
            $pdo = getDB();
            
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT id FROM restaurants WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $erreur = "Un restaurant avec cet email existe déjà.";
            } else {
                // Insérer le restaurant en attente
                $stmt = $pdo->prepare("
                    INSERT INTO restaurants (nom, description, adresse, quartier, telephone, email, statut, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'en_attente', NOW())
                ");
                $stmt->execute([
                    $nom,
                    $description,
                    $adresse,
                    $quartier,
                    $telephone,
                    $email
                ]);
                
                $message = "Demande envoyée avec succès ! L'équipe va étudier votre demande.";
                envoyerEmailConfirmationPartenaire($nom, $proprietaire, $email);
            }
        } catch (PDOException $e) {
            error_log('Erreur partenaire: ' . $e->getMessage());
            $erreur = 'Une erreur technique est survenue. Veuillez réessayer.';
        }
    }
}

$sent = !empty($message);

require_once 'includes/header.php';
?>

<section class="relative overflow-hidden bg-gradient-warm py-16 md:py-24">
    <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-[hsl(38_60%_97%)]/10 blur-2xl"></div>
    <div class="absolute -bottom-20 -left-10 h-72 w-72 rounded-full bg-[hsl(38_92%_52%)]/20 blur-3xl"></div>
    <div class="container mx-auto px-4 max-w-7xl relative text-[hsl(38_60%_97%)]">
        <span class="inline-flex items-center gap-1.5 mb-4 px-3 py-1 bg-[hsl(38_60%_97%)]/15 text-[hsl(38_60%_97%)] rounded-full text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2a9 9 0 00-9 9v1a3 3 0 003 3h12a3 3 0 003-3v-1a9 9 0 00-9-9zM12 2v6m0 0l-3-3m3 3l3-3" />
            </svg>
            Restaurateurs
        </span>
        <h1 class="font-display text-4xl font-bold leading-tight text-balance md:text-6xl">
            Devenez partenaire Saveur Kaolack.
        </h1>
        <p class="mt-4 max-w-xl text-lg text-[hsl(38_60%_97%)]/85">
            Inscription gratuite, mise en ligne sous 24h, paiement à la livraison.
        </p>
    </div>
</section>

<section class="container mx-auto px-4 max-w-7xl py-16">
    <div class="grid gap-6 md:grid-cols-3">
        <?php foreach ($benefits as $b): ?>
        <div class="rounded-3xl border border-[hsl(30_25%_86%)]/60 bg-[hsl(36_50%_98%)] p-6 transition-shadow hover:shadow-soft">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-warm shadow-warm">
                <?php if ($b['icon'] === 'users'): ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[hsl(38_60%_97%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <?php elseif ($b['icon'] === 'trending-up'): ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[hsl(38_60%_97%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[hsl(38_60%_97%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                </svg>
                <?php endif; ?>
            </div>
            <h3 class="font-display text-xl font-bold text-[hsl(20_30%_14%)]"><?php echo $b['title']; ?></h3>
            <p class="mt-2 text-sm text-[hsl(25_15%_42%)]"><?php echo $b['text']; ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="container mx-auto px-4 max-w-7xl pb-20">
    <div class="mx-auto max-w-2xl rounded-3xl bg-[hsl(36_50%_98%)] p-6 shadow-soft md:p-10">
        <?php if (!empty($erreur)): ?>
        <div class="mb-6 rounded-2xl bg-red-50 border border-red-200 p-4">
            <p class="text-red-600 text-center"><?php echo htmlspecialchars($erreur); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($sent): ?>
        <div class="py-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-[hsl(142_38%_32%)]/10 text-[hsl(142_38%_32%)]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h2 class="font-display text-2xl font-bold text-[hsl(20_30%_14%)]">Merci !</h2>
            <p class="mt-2 text-[hsl(25_15%_42%)]">
                <?php echo htmlspecialchars($message); ?><br>
                Nous vous recontactons sous 24h.
            </p>
        </div>
        <?php else: ?>
        <h2 class="font-display text-2xl font-bold text-[hsl(20_30%_14%)]">
            Inscrire mon restaurant
        </h2>
        <p class="mt-1 text-sm text-[hsl(25_15%_42%)]">
            Remplissez ce formulaire, notre équipe revient vers vous rapidement.
        </p>
        <form method="post" action="" class="mt-6 space-y-4">
            <?php echo champTokenCSRF(); ?>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="resto" class="block text-sm font-medium text-[hsl(20_30%_14%)]">Nom du restaurant</label>
                    <input type="text" id="resto" name="resto" required class="mt-1.5 h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20" placeholder="Chez Aïda">
                </div>
                <div>
                    <label for="owner" class="block text-sm font-medium text-[hsl(20_30%_14%)]">Votre nom</label>
                    <input type="text" id="owner" name="owner" required class="mt-1.5 h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20" placeholder="Aïda Diop">
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-[hsl(20_30%_14%)]">Téléphone</label>
                    <input type="tel" id="phone" name="phone" required class="mt-1.5 h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20" placeholder="+221 77 000 00 00">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-[hsl(20_30%_14%)]">Email</label>
                    <input type="email" id="email" name="email" required class="mt-1.5 h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20" placeholder="contact@resto.sn">
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="quartier" class="block text-sm font-medium text-[hsl(20_30%_14%)]">Quartier</label>
                    <input type="text" id="quartier" name="quartier" required class="mt-1.5 h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20" placeholder="Médina">
                </div>
                <div>
                    <label for="addr" class="block text-sm font-medium text-[hsl(20_30%_14%)]">Adresse détaillée</label>
                    <input type="text" id="addr" name="addr" required class="mt-1.5 h-11 w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20" placeholder="En face du marché">
                </div>
            </div>
            <div>
                <label for="msg" class="block text-sm font-medium text-[hsl(20_30%_14%)]">Décrivez votre cuisine</label>
                <textarea id="msg" name="msg" rows="4" class="mt-1.5 w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-3 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20" placeholder="Spécialités, horaires, capacité…"></textarea>
            </div>
            <button type="submit" class="h-12 w-full rounded-2xl bg-gradient-warm text-[hsl(38_60%_97%)] text-base font-medium shadow-warm hover:opacity-90 transition-opacity">
                Envoyer ma demande
            </button>
        </form>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
