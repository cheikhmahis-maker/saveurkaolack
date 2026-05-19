<?php
/**
 * VALIDER RESTAURANT - Validation des demandes et création de compte
 */

session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/fonctions.php';

// Vérifier que c'est un admin
if (empty($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../connexion.php');
    exit();
}

$message = '';
$erreur = '';
$identifiants = null;

// Traitement de la validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restaurant_id'])) {
    // Vérifier le token CSRF
    if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erreur = "Erreur de sécurité : session invalide. Veuillez réessayer.";
    } else {
        $restaurant_id = intval($_POST['restaurant_id']);
        $action = $_POST['action'] ?? 'valider'; // 'valider' ou 'refuser'
    
    try {
        $pdo = getDB();
        
        // Récupérer les infos du restaurant
        $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ? AND statut = 'en_attente'");
        $stmt->execute([$restaurant_id]);
        $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$restaurant) {
            $erreur = "Restaurant non trouvé ou déjà traité.";
        } else {
            if ($action === 'refuser') {
                // Refuser la demande
                $stmt = $pdo->prepare("UPDATE restaurants SET statut = 'refuse' WHERE id = ?");
                $stmt->execute([$restaurant_id]);
                $message = "Demande refusée avec succès.";
            } else {
                // Générer un mot de passe aléatoire
                $mot_de_passe = 'Resto' . rand(1000, 9999) . '!';
                $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                
                // Créer l'utilisateur restaurant
                $stmt = $pdo->prepare("
                    INSERT INTO utilisateurs (nom, email, password, role, statut) 
                    VALUES (?, ?, ?, 'restaurant', 'actif')
                ");
                $stmt->execute([
                    $restaurant['nom'],
                    $restaurant['email'],
                    $hash
                ]);
                $utilisateur_id = $pdo->lastInsertId();
                
                // Mettre à jour le restaurant
                $stmt = $pdo->prepare("
                    UPDATE restaurants 
                    SET statut = 'actif', utilisateur_id = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$utilisateur_id, $restaurant_id]);
                
                // Stocker les identifiants pour affichage
                $identifiants = [
                    'nom' => $restaurant['nom'],
                    'email' => $restaurant['email'],
                    'password' => $mot_de_passe
                ];
                
                $message = "Restaurant validé avec succès ! Les identifiants ont été générés.";
            }
        }
    } catch (PDOException $e) {
        $erreur = "Erreur : " . $e->getMessage();
    }
    }
}

// Récupérer la liste des restaurants en attente
try {
    $pdo = getDB();
    
    $stmt = $pdo->query("
        SELECT id, nom, email, telephone, adresse, quartier, description, created_at 
        FROM restaurants 
        WHERE statut = 'en_attente' 
        ORDER BY created_at DESC
    ");
    $demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $erreur = "Erreur de connexion : " . $e->getMessage();
    $demandes = [];
}

$pageTitle = 'Validation des restaurants';
require_once '../includes/header.php';
?>

<section class="container mx-auto px-4 max-w-6xl py-8">
    <div class="mb-6">
        <h1 class="font-display text-3xl font-bold text-[hsl(20_30%_14%)]">Demandes d'inscription</h1>
        <p class="text-[hsl(25_15%_42%)] mt-1">Restaurants en attente de validation</p>
    </div>
    
    <?php if (!empty($message)): ?>
    <div class="mb-6 rounded-2xl bg-green-50 border border-green-200 p-4">
        <p class="text-green-700 font-medium"><?php echo htmlspecialchars($message); ?></p>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($erreur)): ?>
    <div class="mb-6 rounded-2xl bg-red-50 border border-red-200 p-4">
        <p class="text-red-600"><?php echo htmlspecialchars($erreur); ?></p>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($identifiants) && is_array($identifiants)): ?>
    <div class="mb-6 rounded-2xl bg-green-100 border-2 border-green-500 p-6 shadow-lg">
        <h3 class="font-bold text-green-800 mb-3 text-lg">✅ Identifiants créés - À transmettre au restaurant</h3>
        <div class="bg-white rounded-xl p-4 border border-green-300">
            <p class="mb-2"><strong>Restaurant :</strong> <?php echo htmlspecialchars($identifiants['nom']); ?></p>
            <p class="mb-2"><strong>Email de connexion :</strong> <code class="bg-blue-100 px-2 py-1 rounded font-mono"><?php echo htmlspecialchars($identifiants['email']); ?></code></p>
            <p class="mb-2"><strong>Mot de passe :</strong> <code class="bg-orange-100 px-2 py-1 rounded font-mono text-orange-700 font-bold"><?php echo htmlspecialchars($identifiants['password']); ?></code></p>
            <p class="text-sm text-gray-600 mt-3 border-t pt-2">
                ⚠️ <strong>Important :</strong> Copiez ces identifiants et envoyez-les au restaurant par WhatsApp/email.<br>
                La page ne les affichera qu'une seule fois !
            </p>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (empty($demandes)): ?>
    <div class="text-center py-12 bg-[hsl(36_50%_98%)] rounded-2xl">
        <div class="text-6xl mb-4">📭</div>
        <p class="text-[hsl(25_15%_42%)]">Aucune demande en attente.</p>
    </div>
    <?php else: ?>
    <div class="space-y-4">
        <?php foreach ($demandes as $d): ?>
        <div class="bg-white rounded-2xl border border-[hsl(30_25%_86%)] p-6 shadow-soft">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h3 class="font-bold text-lg text-[hsl(20_30%_14%)]"><?php echo htmlspecialchars($d['nom']); ?></h3>
                    <p class="text-[hsl(25_15%_42%)] text-sm">
                        📧 <?php echo htmlspecialchars($d['email']); ?> | 
                        📞 <?php echo htmlspecialchars($d['telephone']); ?> | 
                        📍 <?php echo htmlspecialchars($d['adresse']); ?>, <?php echo htmlspecialchars($d['quartier']); ?>
                    </p>
                    <?php if ($d['description']): ?>
                    <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($d['description']); ?></p>
                    <?php endif; ?>
                    <p class="text-xs text-gray-400 mt-2">Demande reçue le <?php echo date('d/m/Y à H:i', strtotime($d['created_at'])); ?></p>
                </div>
                <div class="flex gap-2">
                    <form method="post" action="" class="flex gap-2">
                        <?php echo champTokenCSRF(); ?>
                        <input type="hidden" name="restaurant_id" value="<?php echo $d['id']; ?>">
                        <input type="hidden" name="action" value="valider">
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors">
                            ✅ Valider
                        </button>
                    </form>
                    <form method="post" action="" class="flex gap-2" onsubmit="return confirm('Refuser cette demande ?');">
                        <?php echo champTokenCSRF(); ?>
                        <input type="hidden" name="restaurant_id" value="<?php echo $d['id']; ?>">
                        <input type="hidden" name="action" value="refuser">
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors">
                            ❌ Refuser
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <div class="mt-6">
        <a href="index.php" class="text-[hsl(14_72%_46%)] hover:underline">← Retour au dashboard</a>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>
