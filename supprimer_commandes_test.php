<?php
/**
 * Suppression des commandes de test
 * À exécuter puis supprimer
 */

require_once 'includes/config.php';

echo "<h2>Suppression des commandes de test</h2>";

try {
    // Afficher les commandes avant suppression
    $stmt = $pdo->query("SELECT c.*, r.nom as restaurant_nom, u.email as client_email 
                         FROM commandes c 
                         LEFT JOIN restaurants r ON c.restaurant_id = r.id 
                         LEFT JOIN utilisateurs u ON c.client_id = u.id 
                         ORDER BY c.created_at DESC 
                         LIMIT 50");
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Commandes trouvées (" . count($commandes) . ") :</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Tracking</th><th>Client</th><th>Restaurant</th><th>Total</th><th>Date</th></tr>";
    foreach ($commandes as $cmd) {
        echo "<tr>";
        echo "<td>" . $cmd['id'] . "</td>";
        echo "<td>" . $cmd['numero_tracking'] . "</td>";
        echo "<td>" . ($cmd['client_email'] ?? 'Invité') . "</td>";
        echo "<td>" . ($cmd['restaurant_nom'] ?? 'N/A') . "</td>";
        echo "<td>" . number_format($cmd['total'], 0, ',', ' ') . " F</td>";
        echo "<td>" . $cmd['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Compter
    $count = $pdo->query("SELECT COUNT(*) FROM commandes")->fetchColumn();
    echo "<p><strong>Total commandes dans la BDD : $count</strong></p>";
    
    // Formulaire de confirmation
    echo "<hr><h3>Suppression</h3>";
    echo "<form method='post'>";
    echo "<p><label><input type='checkbox' name='confirmer' required> Je confirme la suppression de TOUTES les commandes</label></p>";
    echo "<button type='submit' style='background:red;color:white;padding:10px 20px;border:none;cursor:pointer;'>SUPPRIMER TOUTES LES COMMANDES</button>";
    echo "</form>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer'])) {
        // Supprimer d'abord les détails
        $pdo->exec("DELETE FROM commande_details WHERE commande_id IN (SELECT id FROM commandes)");
        echo "<p>✅ Détails des commandes supprimés</p>";
        
        // Supprimer les commandes
        $pdo->exec("DELETE FROM commandes");
        $deleted = $pdo->rowCount();
        echo "<p>✅ <strong>$deleted commandes supprimées !</strong></p>";
        
        // Réinitialiser l'auto-incrément
        $pdo->exec("ALTER TABLE commandes AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE commande_details AUTO_INCREMENT = 1");
        echo "<p>✅ Auto-incrément réinitialisé</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Erreur : " . $e->getMessage() . "</p>";
}

echo "<hr><p><a href='admin/index.php'>← Retour au dashboard</a></p>";
