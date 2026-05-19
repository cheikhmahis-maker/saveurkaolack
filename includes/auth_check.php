<?php
/**
 * =====================================================
 * AUTH_CHECK.PHP - Exemples d'utilisation des protections
 * Saveur Kaolack - Guide de sécurisation des pages
 * =====================================================
 * 
 * Ce fichier contient des exemples commentés montrant comment
 * protéger les différents types de pages de votre application.
 * 
 * N'INCLUEZ PAS ce fichier dans vos pages - copiez-collez
 * simplement les exemples appropriés selon vos besoins.
 */

// =============================================================================
// EXEMPLE 1 : PAGE CLIENT (ex: profil.php, commandes.php, panier.php)
// =============================================================================
// Ces pages sont réservées aux clients uniquement.
// Les restaurants et admins n'y ont pas accès.
/*
<?php
require_once 'session.php';  // Toujours EN PREMIER
estClient();               // Protection : redirige si pas client
$pageTitle = 'Mon Profil';
require_once 'header.php';

// Le reste de votre code page ici...
// L'utilisateur est garanti d'être un client connecté
?>
*/


// =============================================================================
// EXEMPLE 2 : PAGE RESTAURANT (ex: dashboard_resto.php, menu_gestion.php)
// =============================================================================
// Ces pages sont réservées aux restaurants uniquement.
// Les clients et admins n'y ont pas accès (sauf si vous modifiez).
/*
<?php
require_once 'session.php';   // Toujours EN PREMIER
estRestaurant();              // Protection : redirige si pas restaurant
$pageTitle = 'Dashboard Restaurant';
require_once 'header.php';

// Le reste de votre code page ici...
// L'utilisateur est garanti d'être un restaurant connecté
?>
*/


// =============================================================================
// EXEMPLE 3 : PAGE ADMIN (ex: admin/index.php, admin/utilisateurs.php)
// =============================================================================
// Ces pages sont réservées aux administrateurs uniquement.
// Les clients et restaurants n'y ont pas accès.
/*
<?php
require_once '../session.php';  // Remonter d'un dossier si dans admin/
estAdmin();                     // Protection : redirige si pas admin
$pageTitle = 'Panel Admin';
require_once '../header.php';

// Le reste de votre code page ici...
// L'utilisateur est garanti d'être un admin connecté
?>
*/


// =============================================================================
// EXEMPLE 4 : PAGE MIXTE CLIENT OU ADMIN (ex: voir_commande.php, support.php)
// =============================================================================
// Ces pages acceptent les clients ET les admins.
// Les restaurants n'y ont pas accès.
/*
<?php
require_once 'session.php';    // Toujours EN PREMIER
estClientOuAdmin();            // Protection : redirige si pas client ni admin
$pageTitle = 'Ma Commande';
require_once 'header.php';

// Le reste de votre code page ici...
// L'utilisateur est garanti d'être un client OU un admin connecté
?>
*/


// =============================================================================
// EXEMPLE 5 : PAGE MIXTE RESTAURANT OU ADMIN (ex: gestion_livraison.php)
// =============================================================================
// Ces pages acceptent les restaurants ET les admins.
// Les clients n'y ont pas accès.
/*
<?php
require_once 'session.php';       // Toujours EN PREMIER
estRestaurantOuAdmin();           // Protection : redirige si pas restaurant ni admin
$pageTitle = 'Gestion Livraison';
require_once 'header.php';

// Le reste de votre code page ici...
// L'utilisateur est garanti d'être un restaurant OU un admin connecté
?>
*/


// =============================================================================
// EXEMPLE 6 : PAGE UNIQUEMENT CONNECTÉ (sans restriction de rôle)
// =============================================================================
// Ces pages acceptent tous les utilisateurs connectés, quel que soit leur rôle.
// Les visiteurs non connectés sont redirigés.
/*
<?php
require_once 'session.php';  // Toujours EN PREMIER
estConnecte();               // Protection : redirige si pas connecté
$pageTitle = 'Espace Membre';
require_once 'header.php';

// Le reste de votre code page ici...
// L'utilisateur est garanti d'être connecté (client, restaurant OU admin)

// Vous pouvez ensuite adapter selon le rôle :
if ($_SESSION['role'] === 'client') {
    // Afficher interface client
} elseif ($_SESSION['role'] === 'restaurant') {
    // Afficher interface restaurant
} else {
    // Afficher interface admin
}
?>
*/


// =============================================================================
// EXEMPLE 7 : PAGE PUBLIQUE AVEC CONTENU ADAPTATIF (ex: index.php)
// =============================================================================
// Ces pages sont accessibles à tout le monde, mais affichent du contenu
// différent selon si l'utilisateur est connecté ou non.
/*
<?php
require_once 'session.php';  // Toujours EN PREMIER (même sur pages publiques)
$pageTitle = 'Accueil';
require_once 'header.php';

// Vérification facultative sans redirection
$utilisateur = getUtilisateurConnecte();

if ($utilisateur) {
    // L'utilisateur est connecté
    echo 'Bonjour ' . htmlspecialchars($utilisateur['prenom']);
    
    // Adaptation selon le rôle (sans bloquer l'accès)
    if (estRole('client')) {
        // Afficher lien vers profil client
    } elseif (estRole('restaurant')) {
        // Afficher lien vers dashboard restaurant
    } elseif (estRole('admin')) {
        // Afficher lien vers panel admin
    }
} else {
    // Visiteur non connecté
    echo 'Bienvenue visiteur !';
}
?>
*/


// =============================================================================
// EXEMPLE 8 : PAGE DE DÉCONNEXION (deconnexion.php)
// =============================================================================
// Page simple qui déconnecte l'utilisateur et redirige.
/*
<?php
require_once 'session.php';  // Toujours EN PREMIER
deconnecter();               // Détruit la session et redirige
// Le code ici ne s'exécute jamais car deconnecter() fait exit()
?>
*/


// =============================================================================
// EXEMPLE 9 : REDIRECTION APRÈS CONNEXION (connexion.php)
// =============================================================================
// Après une connexion réussie, rediriger vers la page précédemment demandée.
/*
<?php
require_once 'session.php';

// ... vérification des identifiants en PDO ...

// Si connexion réussie :
$_SESSION['id'] = $user['id'];
$_SESSION['nom'] = $user['nom'];
$_SESSION['prenom'] = $user['prenom'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];

// IMPORTANT : régénérer la session pour sécurité
regenererSession();

// Redirection vers la page demandée avant connexion, ou page par défaut
if (!empty($_SESSION['redirect_after_login'])) {
    $redirect = $_SESSION['redirect_after_login'];
    unset($_SESSION['redirect_after_login']);
    header('Location: ' . $redirect);
} else {
    // Redirection par défaut selon le rôle
    switch ($_SESSION['role']) {
        case 'client':
            header('Location: index.php');
            break;
        case 'restaurant':
            header('Location: dashboard_resto.php');
            break;
        case 'admin':
            header('Location: admin/index.php');
            break;
        default:
            header('Location: index.php');
    }
}
exit();
?>
*/


// =============================================================================
// RÉCAPITULATIF DES FONCTIONS DISPONIBLES
// =============================================================================
// 
// estConnecte()           → Redirige vers connexion.php si pas connecté
// estClient()             → Redirige si pas le rôle 'client'
// estRestaurant()         → Redirige si pas le rôle 'restaurant'
// estAdmin()              → Redirige si pas le rôle 'admin'
// estClientOuAdmin()      → Redirige si pas 'client' NI 'admin'
// estRestaurantOuAdmin()  → Redirige si pas 'restaurant' NI 'admin'
// regenererSession()      → Renouvelle l'ID de session (appel après connexion)
// deconnecter()           → Détruit la session et redirige
// getUtilisateurConnecte()→ Retourne les infos de l'utilisateur connecté
// estRole($role)          → Vérifie un rôle sans redirection (true/false)
// 
