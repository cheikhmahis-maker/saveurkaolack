<?php
/**
 * Liste des identifiants restaurants pour présentation
 */
try {
    $pdo = new PDO('mysql:host=localhost;dbname=saveur_kaolack;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>IDENTIFIANTS RESTAURANTS</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background: #333; color: white;'>
            <th>ID</th>
            <th>Nom</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Statut</th>
            <th>Mot de passe</th>
          </tr>";
    
    $stmt = $pdo->query("SELECT r.id, r.nom, r.email as email_resto, r.telephone, r.statut, u.email as email_user FROM restaurants r LEFT JOIN utilisateurs u ON r.utilisateur_id = u.id ORDER BY r.id");
    
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $email = $r['email_user'] ?: $r['email_resto'] ?: 'N/A';
        $statut = $r['statut'] ?: 'actif';
        $color = ($statut == 'actif') ? '#90EE90' : (($statut == 'en_attente') ? '#FFD700' : '#FFB6C1');
        
        echo "<tr>";
        echo "<td>" . $r['id'] . "</td>";
        echo "<td><b>" . htmlspecialchars($r['nom']) . "</b></td>";
        echo "<td>" . htmlspecialchars($email) . "</td>";
        echo "<td>" . htmlspecialchars($r['telephone'] ?: 'N/A') . "</td>";
        echo "<td style='background: $color'>" . $statut . "</td>";
        echo "<td><i>Mot de passe hashé - utilisez 'Mot de passe oublié' ou reinitialisez via admin</i></td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "<p><b>Instructions:</b> Les mots de passe sont sécurisés (hashés). Pour la présentation, réinitialisez les mots de passe via la page 'Mot de passe oublié' ou créez de nouveaux comptes.</p>";
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
?>
