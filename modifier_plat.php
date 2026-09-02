<?php
/**
 * MODIFIER PLAT - Modification d'un plat
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

// Vérifier connexion restaurant
if (empty($_SESSION['id']) || $_SESSION['role'] !== 'restaurant') {
    header('Location: connexion.php');
    exit();
}

$erreur = '';
$succes = '';
$plat = null;

try {
    $pdo = getDB();
    
    // Récupérer le restaurant
    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE utilisateur_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['id']]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$restaurant) {
        header('Location: dashboard_resto.php');
        exit();
    }
    
    $plat_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    // Récupérer le plat
    $stmt = $pdo->prepare("SELECT * FROM plats WHERE id = ? AND restaurant_id = ? LIMIT 1");
    $stmt->execute([$plat_id, $restaurant['id']]);
    $plat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plat) {
        header('Location: dashboard_resto.php');
        exit();
    }
    
    // Traitement du formulaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Vérifier le token CSRF
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            $erreur = "Erreur de sécurité : session invalide. Veuillez réessayer.";
        } elseif (!restaurantEnRegle($restaurant)) {
            $erreur = "Votre essai gratuit est terminé. Contactez l'administrateur pour réactiver votre compte.";
        } else {
        $nom = trim($_POST['nom'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $prix = floatval($_POST['prix'] ?? 0);
        $categorie = trim($_POST['categorie'] ?? '');
        $disponible = isset($_POST['disponible']) ? 1 : 0;
        
        if (empty($nom) || $prix <= 0) {
            $erreur = 'Le nom et le prix sont obligatoires.';
        } else {
            // Gérer l'upload de nouvelle photo
            $photo = $plat['photo'];
            if (!empty($_FILES['photo']['tmp_name']) && $_FILES['photo']['error'] === 0) {
                $resultat = uploadImageSecurise($_FILES['photo'], 'uploads/plats/', 1200, 900, 2097152);
                if (isset($resultat['filename'])) {
                    if ($photo && file_exists('uploads/plats/' . $photo)) {
                        unlink('uploads/plats/' . $photo);
                    }
                    $photo = $resultat['filename'];
                } else {
                    $erreur = "Erreur upload photo : " . ($resultat['error'] ?? 'Erreur inconnue');
                }
            }

            if (empty($erreur)) {
            $stmt = $pdo->prepare("
                UPDATE plats
                SET nom = ?, description = ?, prix = ?, categorie = ?, photo = ?, disponible = ?
                WHERE id = ? AND restaurant_id = ?
            ");
            $stmt->execute([$nom, $description, $prix, $categorie, $photo, $disponible, $plat_id, $restaurant['id']]);
            }
            
            $succes = 'Plat modifié avec succès !';
            
            // Recharger le plat
            $stmt = $pdo->prepare("SELECT * FROM plats WHERE id = ? LIMIT 1");
            $stmt->execute([$plat_id]);
            $plat = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        }
    }
    
} catch (PDOException $e) {
    $erreur = messageErreurBDD($e, 'modifier_plat.php');
}

$pageTitle = 'Modifier - ' . ($plat['nom'] ?? 'Plat');
require_once 'includes/header.php';
?>

<section class="container mx-auto px-4 max-w-2xl py-8">
    <div class="mb-6">
        <a href="dashboard_resto.php" class="text-sm text-[hsl(25_15%_42%)] hover:text-[hsl(14_72%_46%)]">← Retour au dashboard</a>
    </div>

    <h1 class="font-display text-2xl font-bold text-[hsl(20_30%_14%)] mb-6">Modifier le plat</h1>

    <?php if ($erreur): ?>
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-700"><?php echo $erreur; ?></div>
    <?php endif; ?>
    
    <?php if ($succes): ?>
    <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-700"><?php echo $succes; ?></div>
    <?php endif; ?>

    <?php if ($plat): ?>
    <form method="POST" enctype="multipart/form-data" class="space-y-4 rounded-2xl bg-[hsl(36_50%_98%)] border border-[hsl(30_25%_86%)] p-6 shadow-soft">
        <?php echo champTokenCSRF(); ?>
        <div>
            <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Nom du plat *</label>
            <input type="text" name="nom" value="<?php echo htmlspecialchars($plat['nom']); ?>" required 
                   class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20">
        </div>

        <div>
            <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Description</label>
            <textarea name="description" rows="3"
                      class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20"><?php echo htmlspecialchars($plat['description'] ?? ''); ?></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Prix (FCFA) *</label>
                <input type="number" name="prix" value="<?php echo $plat['prix']; ?>" required min="1"
                       class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20">
            </div>
            <div>
                <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Catégorie</label>
                <select name="categorie" class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20">
                    <option value="" <?php echo empty($plat['categorie']) ? 'selected' : ''; ?>>-- Choisir --</option>
                    <option value="Entrée" <?php echo $plat['categorie'] === 'Entrée' ? 'selected' : ''; ?>>Entrée</option>
                    <option value="Plat principal" <?php echo $plat['categorie'] === 'Plat principal' ? 'selected' : ''; ?>>Plat principal</option>
                    <option value="Dessert" <?php echo $plat['categorie'] === 'Dessert' ? 'selected' : ''; ?>>Dessert</option>
                    <option value="Boisson" <?php echo $plat['categorie'] === 'Boisson' ? 'selected' : ''; ?>>Boisson</option>
                    <option value="Accompagnement" <?php echo $plat['categorie'] === 'Accompagnement' ? 'selected' : ''; ?>>Accompagnement</option>
                </select>
            </div>
        </div>

        <!-- Photo -->
        <div>
            <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Photo du plat</label>
            <?php if (!empty($plat['photo'])): ?>
            <div class="mb-2">
                <img src="uploads/plats/<?php echo htmlspecialchars($plat['photo']); ?>"
                     alt="Photo actuelle"
                     class="h-24 w-24 rounded-xl object-cover border border-[hsl(30_25%_86%)]">
                <p class="text-xs text-[hsl(25_15%_42%)] mt-1">Photo actuelle — choisissez un fichier pour la remplacer</p>
            </div>
            <?php endif; ?>
            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"
                   class="w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-[hsl(14_72%_46%)] file:text-white hover:file:bg-[hsl(14_72%_40%)]">
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="disponible" id="disponible" <?php echo $plat['disponible'] ? 'checked' : ''; ?> class="h-4 w-4 rounded border-[hsl(30_25%_86%)] text-[hsl(14_72%_46%)] focus:ring-[hsl(14_72%_46%)]">
            <label for="disponible" class="text-sm text-[hsl(20_30%_14%)]">Disponible à la vente</label>
        </div>

        <div class="flex gap-3 pt-4">
            <button type="submit" class="flex-1 rounded-xl bg-[hsl(14_72%_46%)] px-6 py-3 text-white font-medium hover:bg-[hsl(14_72%_40%)] transition-colors">
                Enregistrer les modifications
            </button>
            <a href="dashboard_resto.php" class="rounded-xl border border-[hsl(30_25%_86%)] px-6 py-3 text-[hsl(25_15%_42%)] font-medium hover:bg-[hsl(36_30%_92%)] transition-colors">
                Annuler
            </a>
        </div>
    </form>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>
