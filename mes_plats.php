<?php
/**
 * MES PLATS - Gestion complète du menu par le restaurateur
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
        
        // TRAITEMENT DES ACTIONS
        // Ajouter un plat
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            if ($_POST['action'] === 'ajouter') {
                // Vérification CSRF
                if (!isset($_POST['csrf_token']) || !verifierTokenCSRF($_POST['csrf_token'])) {
                    die('Erreur de sécurité : Token CSRF invalide.');
                }
                
                $nom = trim($_POST['nom'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $prix = floatval($_POST['prix'] ?? 0);
                $categorie = trim($_POST['categorie'] ?? '');
                $disponible = isset($_POST['disponible']) ? 1 : 0;
                
                // Gérer l'upload de photo de maniere securisee
                $photo = null;
                if (!empty($_FILES['photo']['tmp_name']) && $_FILES['photo']['error'] === 0) {
                    $resultat = uploadImageSecurise($_FILES['photo'], 'uploads/plats/', 1200, 900, 2097152);
                    if ($resultat['success']) {
                        $photo = $resultat['filename'];
                    } else {
                        $erreur = "Erreur upload photo : " . $resultat['error'];
                    }
                }
                
                if (empty($erreur) && !empty($nom) && $prix > 0) {
                    $stmt = $pdo->prepare("INSERT INTO plats (nom, description, prix, categorie, photo, restaurant_id, disponible) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$nom, $description, $prix, $categorie, $photo, $restaurant_id, $disponible]);
                    $message = "Plat ajouté avec succès !";
                } elseif (empty($erreur)) {
                    $erreur = "Le nom et le prix sont obligatoires.";
                }
            }
            
            // Modifier un plat
            elseif ($_POST['action'] === 'modifier' && isset($_POST['plat_id'])) {
                // Vérification CSRF
                if (!isset($_POST['csrf_token']) || !verifierTokenCSRF($_POST['csrf_token'])) {
                    die('Erreur de sécurité : Token CSRF invalide.');
                }
                
                $plat_id = intval($_POST['plat_id']);
                $nom = trim($_POST['nom'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $prix = floatval($_POST['prix'] ?? 0);
                $categorie = trim($_POST['categorie'] ?? '');
                $disponible = isset($_POST['disponible']) ? 1 : 0;
                
                // Vérifier que le plat appartient bien à ce restaurant
                $stmt = $pdo->prepare("SELECT id, photo FROM plats WHERE id = ? AND restaurant_id = ?");
                $stmt->execute([$plat_id, $restaurant_id]);
                $plat = $stmt->fetch();
                
                if ($plat) {
                    // Gérer l'upload de nouvelle photo de maniere securisee
                    $photo = $plat['photo'];
                    if (!empty($_FILES['photo']['tmp_name']) && $_FILES['photo']['error'] === 0) {
                        $resultat = uploadImageSecurise($_FILES['photo'], 'uploads/plats/', 1200, 900, 2097152);
                        if ($resultat['success']) {
                            // Supprimer l'ancienne photo si existe
                            if ($photo && file_exists('uploads/plats/' . $photo)) {
                                unlink('uploads/plats/' . $photo);
                            }
                            $photo = $resultat['filename'];
                        } else {
                            $erreur = "Erreur upload photo : " . $resultat['error'];
                        }
                    }
                    
                    if (empty($erreur) && !empty($nom) && $prix > 0) {
                        $stmt = $pdo->prepare("UPDATE plats SET nom = ?, description = ?, prix = ?, categorie = ?, photo = ?, disponible = ? WHERE id = ?");
                        $stmt->execute([$nom, $description, $prix, $categorie, $photo, $disponible, $plat_id]);
                        $message = "Plat modifié avec succès !";
                    } elseif (empty($erreur)) {
                        $erreur = "Le nom et le prix sont obligatoires.";
                    }
                }
            }
            
            // Supprimer un plat
            elseif ($_POST['action'] === 'supprimer' && isset($_POST['plat_id'])) {
                // Vérification CSRF
                if (!isset($_POST['csrf_token']) || !verifierTokenCSRF($_POST['csrf_token'])) {
                    die('Erreur de sécurité : Token CSRF invalide.');
                }
                
                $plat_id = intval($_POST['plat_id']);
                
                // Vérifier propriété et supprimer photo
                $stmt = $pdo->prepare("SELECT photo FROM plats WHERE id = ? AND restaurant_id = ?");
                $stmt->execute([$plat_id, $restaurant_id]);
                $plat = $stmt->fetch();
                
                if ($plat) {
                    if ($plat['photo'] && file_exists('uploads/plats/' . $plat['photo'])) {
                        unlink('uploads/plats/' . $plat['photo']);
                    }
                    $stmt = $pdo->prepare("DELETE FROM plats WHERE id = ? AND restaurant_id = ?");
                    $stmt->execute([$plat_id, $restaurant_id]);
                    $message = "Plat supprimé avec succès !";
                }
            }
        }
        
        // Récupérer tous les plats du restaurant
        $stmt = $pdo->prepare("SELECT * FROM plats WHERE restaurant_id = ? ORDER BY categorie, nom");
        $stmt->execute([$restaurant_id]);
        $plats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer les catégories disponibles
        $categories = $pdo->query("SELECT nom FROM categories ORDER BY ordre_affichage")->fetchAll(PDO::FETCH_COLUMN);
    }
    
} catch (PDOException $e) {
    $erreur = 'Erreur : ' . $e->getMessage();
}

$pageTitle = 'Mes Plats - Gestion du menu';
require_once 'includes/header.php';
?>

<section class="container mx-auto px-4 max-w-6xl py-8">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="font-display text-3xl font-bold text-[hsl(20_30%_14%)]">Mes Plats</h1>
            <p class="text-[hsl(25_15%_42%)]">Gérez votre menu : ajoutez, modifiez ou supprimez des plats</p>
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
    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Formulaire d'ajout -->
        <div class="lg:col-span-1">
            <div class="rounded-2xl bg-[hsl(36_50%_98%)] border border-[hsl(30_25%_86%)] p-6 shadow-soft sticky top-4">
                <h2 class="font-display text-xl font-bold text-[hsl(20_30%_14%)] mb-4">Ajouter un plat</h2>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <?php echo champTokenCSRF(); ?>
                    <input type="hidden" name="action" value="ajouter">
                    
                    <div>
                        <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Nom du plat *</label>
                        <input type="text" name="nom" required class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Description</label>
                        <textarea name="description" rows="3" class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Prix (FCFA) *</label>
                        <input type="number" name="prix" required min="1" class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Catégorie</label>
                        <select name="categorie" class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none">
                            <option value="">-- Choisir --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Photo</label>
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-[hsl(14_72%_46%)] file:text-white hover:file:bg-[hsl(14_72%_40%)]">
                        <p class="text-xs text-[hsl(25_15%_42%)] mt-1">JPG, PNG ou WebP - Max 2 Mo</p>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="disponible" id="disponible" checked class="h-4 w-4 rounded border-gray-300">
                        <label for="disponible" class="text-sm text-[hsl(20_30%_14%)]">Disponible immédiatement</label>
                    </div>
                    
                    <button type="submit" class="w-full rounded-xl bg-[hsl(14_72%_46%)] px-4 py-2 font-medium text-white hover:bg-[hsl(14_72%_40%)] transition-colors">
                        + Ajouter le plat
                    </button>
                </form>
            </div>
        </div>

        <!-- Liste des plats -->
        <div class="lg:col-span-2">
            <div class="rounded-2xl bg-[hsl(36_50%_98%)] border border-[hsl(30_25%_86%)] p-6 shadow-soft">
                <h2 class="font-display text-xl font-bold text-[hsl(20_30%_14%)] mb-4">
                    Mon menu (<?php echo count($plats); ?> plats)
                </h2>
                
                <?php if (!empty($plats)): ?>
                <div class="space-y-4">
                    <?php foreach ($plats as $plat): ?>
                    <div class="rounded-xl bg-white border border-[hsl(30_25%_86%)] overflow-hidden">
                        <div class="p-4">
                            <div class="flex items-start gap-4">
                                <!-- Photo -->
                                <div class="h-20 w-20 shrink-0 overflow-hidden rounded-lg bg-gray-100">
                                    <?php if ($plat['photo']): ?>
                                    <img src="uploads/plats/<?php echo $plat['photo']; ?>" alt="<?php echo htmlspecialchars($plat['nom']); ?>" class="h-full w-full object-cover">
                                    <?php else: ?>
                                    <div class="h-full w-full flex items-center justify-center text-gray-400">
                                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="font-bold text-[hsl(20_30%_14%)]"><?php echo htmlspecialchars($plat['nom']); ?></h3>
                                        <span class="rounded-full px-2 py-0.5 text-xs <?php echo $plat['disponible'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                            <?php echo $plat['disponible'] ? 'Dispo' : 'Indispo'; ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-[hsl(25_15%_42%)] line-clamp-2"><?php echo htmlspecialchars($plat['description']); ?></p>
                                    <div class="mt-2 flex items-center gap-4">
                                        <span class="font-bold text-[hsl(14_72%_46%)]"><?php echo number_format($plat['prix'], 0, ',', ' '); ?> F</span>
                                        <span class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded"><?php echo htmlspecialchars($plat['categorie']); ?></span>
                                    </div>
                                </div>
                                
                                <!-- Actions -->
                                <div class="flex flex-col gap-2">
                                    <div onclick="toggleEditForm(<?php echo $plat['id']; ?>);" class="cursor-pointer p-2 text-[hsl(25_15%_42%)] hover:text-[hsl(14_72%_46%)] hover:bg-[hsl(14_72%_46%)]/10 rounded-lg transition-colors" title="Modifier">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </div>
                                    <form method="POST" class="inline" onsubmit="return confirm('Supprimer ce plat ?');">
                                        <?php echo champTokenCSRF(); ?>
                                        <input type="hidden" name="action" value="supprimer">
                                        <input type="hidden" name="plat_id" value="<?php echo $plat['id']; ?>">
                                        <button type="submit" class="p-2 text-red-500 hover:text-red-700 hover:bg-red-100 rounded-lg transition-colors" title="Supprimer">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Formulaire de modification (caché) -->
                        <div id="edit-<?php echo $plat['id']; ?>" class="hidden border-t border-[hsl(30_25%_86%)] bg-gray-50 p-4">
                            <form method="POST" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2">
                                <?php echo champTokenCSRF(); ?>
                                <input type="hidden" name="action" value="modifier">
                                <input type="hidden" name="plat_id" value="<?php echo $plat['id']; ?>">
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Nom</label>
                                    <input type="text" name="nom" value="<?php echo htmlspecialchars($plat['nom']); ?>" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Prix</label>
                                    <input type="number" name="prix" value="<?php echo $plat['prix']; ?>" required min="1" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                </div>
                                
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                                    <textarea name="description" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><?php echo htmlspecialchars($plat['description']); ?></textarea>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Catégorie</label>
                                    <select name="categorie" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                        <option value="">-- Choisir --</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $plat['categorie'] === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Nouvelle photo</label>
                                    <input type="file" name="photo" accept="image/*" class="w-full text-xs">
                                </div>
                                
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" name="disponible" id="dispo-<?php echo $plat['id']; ?>" <?php echo $plat['disponible'] ? 'checked' : ''; ?> class="h-4 w-4">
                                    <label for="dispo-<?php echo $plat['id']; ?>" class="text-sm">Disponible</label>
                                </div>
                                
                                <div class="flex gap-2">
                                    <button type="submit" class="rounded-lg bg-[hsl(14_72%_46%)] px-4 py-2 text-sm font-medium text-white hover:bg-[hsl(14_72%_40%)]">Sauvegarder</button>
                                    <button type="button" onclick="toggleEditForm(<?php echo $plat['id']; ?>)" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300">Annuler</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-12">
                    <div class="mx-auto h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                        <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </div>
                    <p class="text-[hsl(25_15%_42%)]">Votre menu est vide.</p>
                    <p class="text-sm text-gray-500">Ajoutez votre premier plat avec le formulaire à gauche !</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</section>

<script>
function toggleEditForm(platId) {
    var form = document.getElementById('edit-' + platId);
    if (form) {
        form.classList.toggle('hidden');
    }
    return false;
}
</script>

<?php require_once 'includes/footer.php'; ?>
