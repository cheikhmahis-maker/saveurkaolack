<?php
/**
 * ADMIN/RESTAURANTS.PHP - Gestion des restaurants
 * Saveur Kaolack
 */

session_start();

// Verifier que l'utilisateur est admin
if (empty($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../connexion.php');
    exit();
}

require_once '../includes/config.php';
require_once '../includes/db.php';

// Initialiser variables
$restaurants = [];
$nb_restos_attente = 0;
$nb_restos_actifs = 0;
$erreur = '';
$succes = '';

// Connexion BDD
try {
    $pdo = getDB();
    
    // Compteurs
    $nb_restos_attente = $pdo->query("SELECT COUNT(*) FROM restaurants WHERE statut = 'en_attente'")->fetchColumn();
    $nb_restos_actifs = $pdo->query("SELECT COUNT(*) FROM restaurants WHERE statut = 'actif'")->fetchColumn();
    
    // Recuperer tous les restaurants
    $stmt = $pdo->query("
        SELECT r.id, r.nom, r.adresse, r.quartier, r.telephone, r.email, r.statut, r.created_at,
               '-' as prenom, '-' as nom_user, r.telephone as user_telephone,
               cat.nom as categorie_nom
        FROM restaurants r
        LEFT JOIN categories cat ON r.categorie_id = cat.id
        ORDER BY 
            CASE r.statut 
                WHEN 'en_attente' THEN 1 
                WHEN 'actif' THEN 2 
                ELSE 3 
            END,
            r.created_at DESC
    ");
    $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $erreur = 'Erreur BDD : ' . $e->getMessage();
}

$pageTitle = 'Gestion Restaurants';
require_once '../includes/header.php';

$date_jour = date('d/m/Y');
?>

<!-- CDN Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<div class="min-h-screen bg-[hsl(38_44%_96%)]">
    
    <!-- SIDEBAR -->
    <aside class="fixed left-0 top-0 h-full w-64 bg-[hsl(20_30%_14%)] text-white z-50 overflow-y-auto">
        <div class="p-6 border-b border-white/10">
            <div class="font-display text-xl font-bold text-[hsl(14_72%_46%)]">Saveur Kaolack</div>
            <div class="text-xs text-white/60 mt-1">Panel Administration</div>
        </div>
        
        <nav class="p-4 space-y-1">
            <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                Dashboard
            </a>
            
            <a href="restaurants.php" class="flex items-center justify-between px-4 py-3 rounded-xl bg-[hsl(14_72%_46%)] text-white transition-colors">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Restaurants
                </div>
                <?php if ($nb_restos_attente > 0): ?>
                <span class="animate-pulse bg-orange-500 text-white text-xs px-2 py-0.5 rounded-full"><?php echo $nb_restos_attente; ?></span>
                <?php endif; ?>
            </a>
            
            <a href="commandes.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                Commandes
            </a>
            
            <a href="utilisateurs.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                Clients
            </a>
            
            <a href="plats.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                Plats
            </a>
            
            <div class="pt-4 mt-4 border-t border-white/10">
                <a href="../index.php" target="_blank" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    Voir le site
                </a>
                
                <a href="../deconnexion.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-red-400 hover:bg-red-500/20 transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Déconnexion
                </a>
            </div>
        </nav>
    </aside>
    
    <!-- CONTENU PRINCIPAL -->
    <main class="ml-64">
        
        <!-- TOPBAR -->
        <header class="bg-white border-b border-[hsl(30_25%_86%)] px-8 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="font-display text-2xl font-bold text-[hsl(20_30%_14%)]">Gestion des Restaurants</h1>
                    <p class="text-sm text-[hsl(25_15%_42%)]"><?php echo $date_jour; ?></p>
                </div>
                
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-full bg-[hsl(14_72%_46%)] text-white flex items-center justify-center font-medium">
                        <?php echo substr($_SESSION['prenom'] ?? 'A', 0, 1); ?>
                    </div>
                    <div class="text-sm">
                        <div class="font-medium text-[hsl(20_30%_14%)]"><?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></div>
                        <div class="text-[hsl(25_15%_42%)]">Administrateur</div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- CONTENU -->
        <div class="p-8 space-y-6">
            
            <?php if ($erreur): ?>
            <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl"><?php echo $erreur; ?></div>
            <?php endif; ?>
            
            <?php if ($succes): ?>
            <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-xl"><?php echo $succes; ?></div>
            <?php endif; ?>
            
            <!-- STATS RAPIDES -->
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white rounded-xl border border-[hsl(30_25%_86%)] p-4 shadow-soft text-center">
                    <div class="text-2xl font-bold text-[hsl(14_72%_46%)]"><?php echo count($restaurants); ?></div>
                    <div class="text-sm text-[hsl(25_15%_42%)]">Total restaurants</div>
                </div>
                <div class="bg-white rounded-xl border border-[hsl(30_25%_86%)] p-4 shadow-soft text-center">
                    <div class="text-2xl font-bold text-green-600"><?php echo $nb_restos_actifs; ?></div>
                    <div class="text-sm text-[hsl(25_15%_42%)]">Actifs</div>
                </div>
                <div class="bg-white rounded-xl border border-[hsl(30_25%_86%)] p-4 shadow-soft text-center">
                    <div class="text-2xl font-bold text-orange-500"><?php echo $nb_restos_attente; ?></div>
                    <div class="text-sm text-[hsl(25_15%_42%)]">En attente</div>
                </div>
            </div>
            
            <!-- TABLEAU RESTAURANTS -->
            <div class="bg-white rounded-2xl border border-[hsl(30_25%_86%)] shadow-soft overflow-hidden">
                <div class="px-6 py-4 border-b border-[hsl(30_25%_86%)] flex items-center justify-between">
                    <h3 class="font-semibold text-[hsl(20_30%_14%)]">Liste des restaurants</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-[hsl(36_50%_98%)] text-[hsl(25_15%_42%)]">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">Restaurant</th>
                                <th class="px-4 py-3 text-left font-medium">Gérant</th>
                                <th class="px-4 py-3 text-left font-medium">Contact</th>
                                <th class="px-4 py-3 text-left font-medium">Quartier</th>
                                <th class="px-4 py-3 text-left font-medium">Statut</th>
                                <th class="px-4 py-3 text-left font-medium">Inscrit le</th>
                                <th class="px-4 py-3 text-left font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[hsl(30_25%_86%)]">
                            <?php foreach ($restaurants as $r): ?>
                            <tr class="hover:bg-[hsl(36_50%_98%)]/50" data-restaurant-id="<?php echo $r['id']; ?>">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-[hsl(20_30%_14%)]"><?php echo htmlspecialchars($r['nom']); ?></div>
                                    <?php if ($r['categorie_nom']): ?>
                                    <div class="text-xs text-[hsl(25_15%_42%)]"><?php echo htmlspecialchars($r['categorie_nom']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($r['prenom'] . ' ' . $r['nom_user']); ?></td>
                                <td class="px-4 py-3 text-[hsl(25_15%_42%)]">
                                    <div><?php echo $r['telephone'] ?? $r['user_telephone']; ?></div>
                                    <div class="text-xs"><?php echo $r['email']; ?></div>
                                </td>
                                <td class="px-4 py-3 text-[hsl(25_15%_42%)]"><?php echo htmlspecialchars($r['quartier'] ?? '-'); ?></td>
                                <td class="px-4 py-3">
                                    <?php
                                    $badge_class = match($r['statut']) {
                                        'actif' => 'bg-green-100 text-green-700',
                                        'en_attente' => 'bg-orange-100 text-orange-700 animate-pulse',
                                        'inactif' => 'bg-gray-100 text-gray-600',
                                        'ferme' => 'bg-red-100 text-red-700',
                                        default => 'bg-gray-100 text-gray-600'
                                    };
                                    ?>
                                    <span class="inline-flex px-2 py-1 text-xs font-medium <?php echo $badge_class; ?> rounded-full capitalize"><?php echo str_replace('_', ' ', $r['statut']); ?></span>
                                </td>
                                <td class="px-4 py-3 text-[hsl(25_15%_42%)]"><?php echo date('d/m/Y', strtotime($r['created_at'])); ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <a href="../restaurant.php?id=<?php echo $r['id']; ?>" target="_blank" class="px-3 py-1.5 bg-[hsl(30_25%_86%)] text-[hsl(25_15%_42%)] text-xs font-medium rounded-lg hover:bg-[hsl(36_30%_92%)] transition-colors">
                                            Voir
                                        </a>
                                        <?php if ($r['statut'] === 'en_attente'): ?>
                                        <button onclick="validerRestaurant(<?php echo $r['id']; ?>, 'valider')" class="px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition-colors">
                                            Valider
                                        </button>
                                        <button onclick="validerRestaurant(<?php echo $r['id']; ?>, 'rejeter')" class="px-3 py-1.5 bg-red-100 text-red-600 text-xs font-medium rounded-lg hover:bg-red-200 transition-colors">
                                            Rejeter
                                        </button>
                                        <?php else: ?>
                                        <button onclick="changerStatut(<?php echo $r['id']; ?>, '<?php echo $r['statut'] === 'actif' ? 'inactif' : 'actif'; ?>')" class="px-3 py-1.5 <?php echo $r['statut'] === 'actif' ? 'bg-orange-100 text-orange-600 hover:bg-orange-200' : 'bg-green-100 text-green-600 hover:bg-green-200'; ?> text-xs font-medium rounded-lg transition-colors">
                                            <?php echo $r['statut'] === 'actif' ? '🔒 Suspendre' : '✅ Activer'; ?>
                                        </button>
                                        <button onclick="supprimerRestaurant(<?php echo $r['id']; ?>, '<?php echo htmlspecialchars(addslashes($r['nom'])); ?>')" class="px-3 py-1.5 bg-red-100 text-red-600 text-xs font-medium rounded-lg hover:bg-red-200 transition-colors" title="Supprimer définitivement">
                                            🗑️ Suppr.
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($restaurants)): ?>
                <div class="text-center py-12 text-[hsl(25_15%_42%)]">
                    <svg class="h-12 w-12 mx-auto mb-4 text-[hsl(30_25%_86%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Aucun restaurant enregistré
                </div>
                <?php endif; ?>
            </div>
            
        </div>
    </main>
</div>

<script src="../assets/js/dashboard_admin.js"></script>

<script>
const CSRF_TOKEN = <?php echo json_encode($_SESSION['csrf_token'] ?? ''); ?>;

// ─── CHANGER STATUT RESTAURANT (ACTIVER/SUSPENDRE) ───
function changerStatut(restaurantId, nouveauStatut) {
    const action = nouveauStatut === 'actif' ? 'activer' : 'suspendre';
    const message = action === 'activer'
        ? 'Activer ce restaurant ?'
        : 'Suspendre ce restaurant ? Il ne sera plus visible.';

    if (!confirm(message)) return;

    fetch('ajax/restaurant_action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=${action}&id=${restaurantId}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`
    })
    .then(r => r.text())
    .then(text => {
        console.log('Réponse:', text);
        try {
            const data = JSON.parse(text);
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Erreur : ' + data.message);
            }
        } catch (e) {
            alert('Erreur de réponse du serveur');
            console.error(text);
        }
    })
    .catch(err => {
        alert('Erreur lors de l\'opération');
        console.error(err);
    });
}

// ─── SUPPRIMER UN RESTAURANT ───
function supprimerRestaurant(restaurantId, nomRestaurant) {
    if (!confirm(`Êtes-vous sûr de vouloir supprimer définitivement "${nomRestaurant}" ?\n\nCette action est irréversible !`)) {
        return;
    }
    
    fetch('ajax/restaurant_action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=supprimer&id=${restaurantId}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`
    })
    .then(r => r.text())
    .then(text => {
        console.log('Réponse:', text);
        try {
            const data = JSON.parse(text);
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Erreur : ' + data.message);
            }
        } catch (e) {
            alert('Erreur de réponse du serveur');
            console.error(text);
        }
    })
    .catch(err => {
        alert('Erreur lors de la suppression');
        console.error(err);
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
