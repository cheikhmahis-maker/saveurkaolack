<?php
/**
 * PROFIL - Gestion du profil restaurant (photo bannière, infos)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

// Vérifier connexion et rôle restaurant
if (empty($_SESSION['id']) || $_SESSION['role'] !== 'restaurant') {
    header('Location: connexion.php');
    exit();
}

$message = '';
$erreur = '';

// Connexion BDD
try {
    $pdo = getDB();
    
    // Récupérer le restaurant
    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE utilisateur_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['id']]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$restaurant) {
        $erreur = "Aucun restaurant associé à votre compte.";
    } else {
        $restaurant_id = $restaurant['id'];
        
        // TRAITEMENT DU FORMULAIRE
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Vérifier le token CSRF
            if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
                $erreur = "Erreur de sécurité : session invalide. Veuillez réessayer.";
            } elseif (!restaurantEnRegle($restaurant)) {
                $erreur = "Votre essai gratuit est terminé. Contactez l'administrateur pour réactiver votre compte.";
            } else {
            $nom = trim($_POST['nom'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $adresse = trim($_POST['adresse'] ?? '');
            $quartier = trim($_POST['quartier'] ?? '');
            $telephone = trim($_POST['telephone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $heure_ouverture = $_POST['heure_ouverture'] ?? '08:00';
            $heure_fermeture = $_POST['heure_fermeture'] ?? '22:00';
            $commande_minimum = intval($_POST['commande_minimum'] ?? 3000);

            if (!telephoneValide($telephone)) {
                $erreur = "Le numéro de téléphone n'est pas valide. Exemple : +221 77 123 45 67";
            } else {

            // Gérer l'upload de la photo bannière (redimensionnée/compressée, comme les photos de plats)
            $photo_banniere = $restaurant['photo_banniere'] ?? null;
            if (!empty($_FILES['photo_banniere']['tmp_name']) && $_FILES['photo_banniere']['error'] === 0) {
                $resultat = uploadImageSecurise($_FILES['photo_banniere'], BANNIERES_PATH, 1600, 900, 2097152);
                if ($resultat['success']) {
                    if ($photo_banniere && file_exists(BANNIERES_PATH . $photo_banniere)) {
                        unlink(BANNIERES_PATH . $photo_banniere);
                    }
                    $photo_banniere = $resultat['filename'];
                } else {
                    $erreur = "Erreur upload bannière : " . $resultat['error'];
                }
            }

            // Gérer l'upload du logo (redimensionné/compressé)
            $photo_logo = $restaurant['logo'] ?? null;
            if (empty($erreur) && !empty($_FILES['photo_logo']['tmp_name']) && $_FILES['photo_logo']['error'] === 0) {
                $resultat = uploadImageSecurise($_FILES['photo_logo'], LOGOS_PATH, 500, 500, 2097152);
                if ($resultat['success']) {
                    if ($photo_logo && file_exists(LOGOS_PATH . $photo_logo)) {
                        unlink(LOGOS_PATH . $photo_logo);
                    }
                    $photo_logo = $resultat['filename'];
                } else {
                    $erreur = "Erreur upload logo : " . $resultat['error'];
                }
            }

            if (empty($erreur)) {
            
            // Mettre à jour les infos
            $stmt = $pdo->prepare("UPDATE restaurants SET 
                nom = ?, description = ?, adresse = ?, quartier = ?,
                telephone = ?, email = ?, heure_ouverture = ?, heure_fermeture = ?,
                commande_minimum = ?, photo_banniere = ?, logo = ?
                WHERE id = ? AND utilisateur_id = ?");
            
            $stmt->execute([
                $nom, $description, $adresse, $quartier,
                $telephone, $email, $heure_ouverture, $heure_fermeture,
                $commande_minimum, $photo_banniere, $photo_logo,
                $restaurant_id, $_SESSION['id']
            ]);
            
            $message = "Profil mis à jour avec succès !";
            
            // Recharger les données
            $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE utilisateur_id = ? LIMIT 1");
            $stmt->execute([$_SESSION['id']]);
            $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            }
            }
        }
    }

} catch (PDOException $e) {
    $erreur = messageErreurBDD($e, 'profil.php');
}

$pageTitle = 'Mon Profil - Paramètres du restaurant';
require_once 'includes/header.php';
?>

<section class="container mx-auto px-4 max-w-4xl py-8">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="font-display text-3xl font-bold text-[hsl(20_30%_14%)]">Mon Profil</h1>
            <p class="text-[hsl(25_15%_42%)]">Gérez les informations et l'apparence de votre restaurant</p>
        </div>
        <a href="dashboard_resto.php" class="text-[hsl(14_72%_46%)] hover:underline">← Retour au dashboard</a>
    </div>

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

    <?php if ($restaurant): ?>
    <div class="rounded-2xl bg-[hsl(36_50%_98%)] border border-[hsl(30_25%_86%)] p-6 shadow-soft">
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <?php echo champTokenCSRF(); ?>
            
            <!-- Photos -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Photo bannière -->
                <div>
                    <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-2">Photo de bannière</label>
                    <div class="mb-3 rounded-xl overflow-hidden bg-gray-100 aspect-video">
                        <?php if (!empty($restaurant['photo_banniere'])): ?>
                        <?php echo afficherBanniere($restaurant['photo_banniere'], 'Bannière', 'w-full h-full object-cover'); ?>
                        <?php else: ?>
                        <div class="h-full flex items-center justify-center text-gray-400">
                            <div class="text-center">
                                <svg class="h-12 w-12 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-sm">Aucune bannière</span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="photo_banniere" accept="image/*" class="w-full text-sm">
                    <p class="text-xs text-gray-500 mt-1">Format recommandé : 1200 x 400 pixels</p>
                </div>
                
                <!-- Logo -->
                <div>
                    <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-2">Logo du restaurant</label>
                    <div class="mb-3 rounded-xl overflow-hidden bg-gray-100 aspect-square max-w-[200px]">
                        <?php if (!empty($restaurant['logo'])): ?>
                        <?php echo afficherLogo($restaurant['logo'], 'Logo', 'w-full h-full object-cover'); ?>
                        <?php else: ?>
                        <div class="h-full flex items-center justify-center text-gray-400">
                            <div class="text-center">
                                <svg class="h-10 w-10 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <span class="text-sm">Aucun logo</span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="photo_logo" accept="image/*" class="w-full text-sm">
                    <p class="text-xs text-gray-500 mt-1">Format carré recommandé</p>
                </div>
            </div>
            
            <!-- Informations générales -->
            <div class="border-t border-[hsl(30_25%_86%)] pt-6">
                <h2 class="font-display text-xl font-bold text-[hsl(20_30%_14%)] mb-4">Informations générales</h2>
                
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Nom du restaurant *</label>
                        <input type="text" name="nom" value="<?php echo htmlspecialchars($restaurant['nom']); ?>" required class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Description</label>
                        <textarea name="description" rows="3" class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none"><?php echo htmlspecialchars($restaurant['description']); ?></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Adresse</label>
                        <input type="text" name="adresse" value="<?php echo htmlspecialchars($restaurant['adresse']); ?>" class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Quartier</label>
                        <input type="text" name="quartier" value="<?php echo htmlspecialchars($restaurant['quartier']); ?>" class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Téléphone</label>
                        <input type="tel" name="telephone" pattern="[\d\s\-\+]{7,17}" title="Entrez un numéro valide (ex: +221 77 123 45 67)" placeholder="+221 77 000 00 00" value="<?php echo htmlspecialchars($restaurant['telephone']); ?>" class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($restaurant['email']); ?>" class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none">
                    </div>
                </div>
            </div>
            
            <!-- Horaires -->
            <div class="border-t border-[hsl(30_25%_86%)] pt-6">
                <h2 class="font-display text-xl font-bold text-[hsl(20_30%_14%)] mb-4">Horaires d'ouverture</h2>
                
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Ouverture</label>
                        <input type="time" name="heure_ouverture" value="<?php echo $restaurant['heure_ouverture'] ?? '08:00'; ?>" class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Fermeture</label>
                        <input type="time" name="heure_fermeture" value="<?php echo $restaurant['heure_fermeture'] ?? '22:00'; ?>" class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none">
                    </div>
                </div>
            </div>
            
            <!-- Commande -->
            <div class="border-t border-[hsl(30_25%_86%)] pt-6">
                <h2 class="font-display text-xl font-bold text-[hsl(20_30%_14%)] mb-4">Paramètres de commande</h2>
                <p class="text-xs text-[hsl(25_15%_42%)] mb-4">🚴 Les frais de livraison ne sont plus gérés sur le site : convenez-en directement avec vos clients.</p>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Commande minimum (FCFA)</label>
                        <input type="number" name="commande_minimum" value="<?php echo $restaurant['commande_minimum'] ?? 3000; ?>" min="0" class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Boutons -->
            <div class="border-t border-[hsl(30_25%_86%)] pt-6 flex items-center justify-between">
                <button type="submit" class="rounded-xl bg-[hsl(14_72%_46%)] px-6 py-2 font-medium text-white hover:bg-[hsl(14_72%_40%)] transition-colors">
                    💾 Sauvegarder les modifications
                </button>
                <a href="changer_mdp.php" class="text-[hsl(14_72%_46%)] hover:underline text-sm">
                    🔒 Changer mon mot de passe →
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>
