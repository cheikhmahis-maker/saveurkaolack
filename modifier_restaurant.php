<?php
/**
 * MODIFIER RESTAURANT - Modification des infos restaurant
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

if (empty($_SESSION['id']) || $_SESSION['role'] !== 'restaurant') {
    header('Location: connexion.php');
    exit();
}

$erreur = '';
$succes = '';

try {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE utilisateur_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['id']]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$restaurant) {
        header('Location: dashboard_resto.php');
        exit();
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Vérifier le token CSRF
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            $erreur = "Erreur de sécurité : session invalide. Veuillez réessayer.";
        } else {
        $telephone = trim($_POST['telephone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $heure_ouverture = $_POST['heure_ouverture'] ?? '08:00';
        $heure_fermeture = $_POST['heure_fermeture'] ?? '22:00';
        $frais_livraison = intval($_POST['frais_livraison'] ?? 1000);
        
        $stmt = $pdo->prepare("
            UPDATE restaurants 
            SET telephone = ?, email = ?, adresse = ?, heure_ouverture = ?, heure_fermeture = ?, frais_livraison = ?
            WHERE id = ?
        ");
        $stmt->execute([$telephone, $email, $adresse, $heure_ouverture, $heure_fermeture, $frais_livraison, $restaurant['id']]);
        
        $succes = 'Informations mises à jour !';
        
        // Recharger
        $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ? LIMIT 1");
        $stmt->execute([$restaurant['id']]);
        $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    
} catch (PDOException $e) {
    $erreur = 'Erreur : ' . $e->getMessage();
}

$pageTitle = 'Modifier - ' . $restaurant['nom'];
require_once 'includes/header.php';
?>

<section class="container mx-auto px-4 max-w-2xl py-8">
    <div class="mb-6">
        <a href="dashboard_resto.php" class="text-sm text-[hsl(25_15%_42%)] hover:text-[hsl(14_72%_46%)]">← Retour au dashboard</a>
    </div>

    <h1 class="font-display text-2xl font-bold text-[hsl(20_30%_14%)] mb-6">Modifier mes infos</h1>

    <?php if ($erreur): ?>
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-700"><?php echo $erreur; ?></div>
    <?php endif; ?>
    
    <?php if ($succes): ?>
    <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-700"><?php echo $succes; ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4 rounded-2xl bg-[hsl(36_50%_98%)] border border-[hsl(30_25%_86%)] p-6 shadow-soft">
        <?php echo champTokenCSRF(); ?>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Téléphone</label>
                <input type="tel" name="telephone" value="<?php echo htmlspecialchars($restaurant['telephone'] ?? ''); ?>"
                       class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20">
            </div>
            <div>
                <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($restaurant['email'] ?? ''); ?>"
                       class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Adresse</label>
            <input type="text" name="adresse" value="<?php echo htmlspecialchars($restaurant['adresse'] ?? ''); ?>"
                   class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20">
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Ouverture</label>
                <input type="time" name="heure_ouverture" value="<?php echo $restaurant['heure_ouverture']; ?>"
                       class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20">
            </div>
            <div>
                <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Fermeture</label>
                <input type="time" name="heure_fermeture" value="<?php echo $restaurant['heure_fermeture']; ?>"
                       class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20">
            </div>
            <div>
                <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Frais livraison (F)</label>
                <input type="number" name="frais_livraison" value="<?php echo $restaurant['frais_livraison']; ?>" min="0"
                       class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20">
            </div>
        </div>

        <div class="flex gap-3 pt-4">
            <button type="submit" class="flex-1 rounded-xl bg-[hsl(14_72%_46%)] px-6 py-3 text-white font-medium hover:bg-[hsl(14_72%_40%)] transition-colors">
                Enregistrer
            </button>
            <a href="dashboard_resto.php" class="rounded-xl border border-[hsl(30_25%_86%)] px-6 py-3 text-[hsl(25_15%_42%)] font-medium hover:bg-[hsl(36_30%_92%)] transition-colors">
                Annuler
            </a>
        </div>
    </form>
</section>

<?php require_once 'includes/footer.php'; ?>
