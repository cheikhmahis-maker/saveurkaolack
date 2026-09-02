<?php
/**
 * PROFIL CLIENT - Page de profil avec historique des commandes
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

// Vérifier connexion client
if (empty($_SESSION['id']) || $_SESSION['role'] !== 'client') {
    $_SESSION['redirect_after_login'] = 'profil_client.php';
    header('Location: connexion.php');
    exit();
}

$client_id = $_SESSION['id'];
$client_nom = $_SESSION['prenom'] . ' ' . $_SESSION['nom'];
$client_email = $_SESSION['email'];

// Connexion BDD
$commandes = [];
$total_commandes = 0;
$commandes_en_cours = [];
$commandes_livrees = [];

try {
    $pdo = getDB();

    // Lier les anciennes commandes sans client_id (mode invité → connecté)
    // Séparé du SELECT pour qu'une erreur ici ne bloque pas l'affichage
    try {
        $stmt_update = $pdo->prepare("
            UPDATE commandes
            SET client_id = ?
            WHERE client_id IS NULL AND client_info LIKE ?
        ");
        $stmt_update->execute([$client_id, '%"email":"' . $client_email . '"%']);
    } catch (PDOException $e) {
        // La colonne client_info peut ne pas exister — ignorer silencieusement
    }

    // Récupérer les commandes : par client_id OU par email dans client_info
    $stmt = $pdo->prepare("
        SELECT c.*, r.nom as restaurant_nom, r.logo AS photo_logo
        FROM commandes c
        LEFT JOIN restaurants r ON c.restaurant_id = r.id
        WHERE c.client_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$client_id]);
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Si aucune commande par client_id, chercher par email dans client_info
    if (empty($commandes)) {
        try {
            $stmt2 = $pdo->prepare("
                SELECT c.*, r.nom as restaurant_nom, r.logo AS photo_logo
                FROM commandes c
                LEFT JOIN restaurants r ON c.restaurant_id = r.id
                WHERE c.client_info LIKE ?
                ORDER BY c.created_at DESC
            ");
            $stmt2->execute(['%"email":"' . $client_email . '"%']);
            $commandes = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // client_info absent — pas de fallback
        }
    }

    // Statistiques
    $total_commandes = count($commandes);
    $commandes_en_cours = array_filter($commandes, fn($c) => !in_array($c['statut'], ['livree', 'annulee']));
    $commandes_livrees  = array_filter($commandes, fn($c) => $c['statut'] === 'livree');

} catch (PDOException $e) {
    $erreur = messageErreurBDD($e, 'profil_client.php');
}

// Fonction pour le badge de statut
function getStatutBadge($statut) {
    $badges = [
        'en_attente' => ['⏳', 'En attente', 'bg-yellow-100 text-yellow-800'],
        'confirmee' => ['✅', 'Confirmée', 'bg-blue-100 text-blue-800'],
        'en_preparation' => ['👨‍🍳', 'En préparation', 'bg-orange-100 text-orange-800'],
        'en_route' => ['🛵', 'En route', 'bg-purple-100 text-purple-800'],
        'livree' => ['🎉', 'Livrée', 'bg-green-100 text-green-800'],
        'annulee' => ['❌', 'Annulée', 'bg-red-100 text-red-800'],
    ];
    return $badges[$statut] ?? ['❓', $statut, 'bg-gray-100 text-gray-800'];
}


$pageTitle = 'Mon Profil - Mes Commandes';
require_once 'includes/header.php';
?>

<section class="bg-gradient-warm py-12">
    <div class="container mx-auto px-4 max-w-4xl">
        <div class="flex items-center gap-4">
            <div class="h-16 w-16 rounded-full bg-white/20 flex items-center justify-center text-3xl">
                👤
            </div>
            <div class="text-white">
                <h1 class="font-display text-2xl font-bold"><?php echo htmlspecialchars($client_nom); ?></h1>
                <p class="text-white/80"><?php echo htmlspecialchars($client_email); ?></p>
            </div>
        </div>
    </div>
</section>

<section class="container mx-auto px-4 max-w-4xl py-8">
    
    <!-- Statistiques -->
    <div class="grid grid-cols-3 gap-3 mb-8">
        <div class="bg-white rounded-xl p-3 shadow-sm border border-[hsl(30_25%_86%)] text-center">
            <div class="text-2xl font-bold text-[hsl(14_72%_46%)]"><?php echo $total_commandes; ?></div>
            <div class="text-xs text-[hsl(25_15%_42%)] mt-0.5 leading-tight">Total</div>
        </div>
        <div class="bg-white rounded-xl p-3 shadow-sm border border-[hsl(30_25%_86%)] text-center">
            <div class="text-2xl font-bold text-orange-600"><?php echo count($commandes_en_cours); ?></div>
            <div class="text-xs text-[hsl(25_15%_42%)] mt-0.5 leading-tight">En cours</div>
        </div>
        <div class="bg-white rounded-xl p-3 shadow-sm border border-[hsl(30_25%_86%)] text-center">
            <div class="text-2xl font-bold text-green-600"><?php echo count($commandes_livrees); ?></div>
            <div class="text-xs text-[hsl(25_15%_42%)] mt-0.5 leading-tight">Livrées</div>
        </div>
    </div>
    
    <!-- Liste des commandes -->
    <div class="bg-white rounded-2xl shadow-soft border border-[hsl(30_25%_86%)] overflow-hidden">
        <div class="p-6 border-b border-[hsl(30_25%_86%)] bg-gradient-to-r from-[hsl(14_72%_46%)]/5 to-transparent">
            <h2 class="font-display text-xl font-bold text-[hsl(20_30%_14%)] flex items-center gap-2">
                📋 Mes commandes
                <span class="text-sm font-normal text-[hsl(25_15%_42%)]">(<?php echo $total_commandes; ?>)</span>
            </h2>
            <p class="text-sm text-[hsl(25_15%_42%)] mt-1">
                Retrouvez ici toutes vos commandes avec leur date, code et statut
            </p>
        </div>
        
        <?php if (empty($commandes)): ?>
        <div class="p-8 text-center">
            <div class="text-6xl mb-4">🛒</div>
            <p class="text-[hsl(25_15%_42%)] mb-4">Vous n'avez pas encore passé de commande</p>
            <a href="plats.php" class="inline-block bg-[hsl(14_72%_46%)] text-white px-6 py-2 rounded-xl hover:bg-[hsl(14_72%_40%)]">
                Commander maintenant
            </a>
        </div>
        <?php else: ?>
        <div class="divide-y divide-[hsl(30_25%_86%)]">
            <?php foreach ($commandes as $commande): 
                $badge = getStatutBadge($commande['statut']);
                $date = date('d/m/Y à H:i', strtotime($commande['created_at']));
                $numero = $commande['numero'] ?? $commande['numero_tracking'] ?? 'N/A';
            ?>
            <div class="p-4 hover:bg-[hsl(36_50%_98%)] transition-colors">
                <div class="flex items-start gap-4">
                    <!-- Logo restaurant -->
                    <div class="h-12 w-12 rounded-xl bg-gray-100 flex-shrink-0 overflow-hidden">
                        <?php if (!empty($commande['photo_logo'])): ?>
                        <img src="uploads/restaurants/<?php echo htmlspecialchars($commande['photo_logo']); ?>" 
                             alt="" class="h-full w-full object-cover">
                        <?php else: ?>
                        <div class="h-full w-full flex items-center justify-center text-xl">🍽️</div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Info commande -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="font-bold text-[hsl(20_30%_14%)]">
                                <?php echo htmlspecialchars($commande['restaurant_nom'] ?? 'Restaurant'); ?>
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?php echo $badge[2]; ?>">
                                <?php echo $badge[0] . ' ' . $badge[1]; ?>
                            </span>
                        </div>
                        
                        <div class="text-sm space-y-1.5">
                            <div class="flex items-center gap-2 text-[hsl(25_15%_42%)]">
                                <span>📅</span>
                                <span><?php echo $date; ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span>🏷️</span>
                                <span class="font-mono text-[hsl(14_72%_46%)] font-bold text-base">
                                    <?php echo htmlspecialchars($numero); ?>
                                </span>
                            </div>
                            <div class="flex items-center gap-2 text-[hsl(25_15%_42%)]">
                                <span>💰</span>
                                <span class="font-medium"><?php echo number_format($commande['total'], 0, ',', ' '); ?> FCFA</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex flex-col gap-2">
                        <a href="suivi.php?code=<?php echo htmlspecialchars($numero); ?>" 
                           class="text-xs bg-[hsl(14_72%_46%)] text-white px-3 py-1.5 rounded-lg hover:bg-[hsl(14_72%_40%)] text-center">
                            Suivre
                        </a>
                        
                        <?php if ($commande['statut'] === 'livree'): ?>
                        <a href="suivi.php?code=<?php echo htmlspecialchars($numero); ?>#avis" 
                           class="text-xs border border-[hsl(30_25%_86%)] text-[hsl(20_30%_14%)] px-3 py-1.5 rounded-lg hover:bg-[hsl(36_78%_92%)] text-center">
                            ⭐ Avis
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Actions rapides -->
    <div class="mt-6 flex gap-4">
        <a href="changer_mdp.php" class="flex-1 bg-white rounded-xl p-4 border border-[hsl(30_25%_86%)] hover:border-[hsl(14_72%_46%)] transition-colors text-center">
            <div class="text-2xl mb-1">🔒</div>
            <div class="text-sm font-medium">Changer mot de passe</div>
        </a>
        <a href="plats.php" class="flex-1 bg-white rounded-xl p-4 border border-[hsl(30_25%_86%)] hover:border-[hsl(14_72%_46%)] transition-colors text-center">
            <div class="text-2xl mb-1">🛒</div>
            <div class="text-sm font-medium">Nouvelle commande</div>
        </a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
