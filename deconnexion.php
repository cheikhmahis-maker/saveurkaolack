<?php
/**
 * DECONNEXION.PHP - Déconnexion
 */

// Démarrer la session si pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vider la session
$_SESSION = [];

// Détruire le cookie de session
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Détruire la session
session_destroy();

// Rediriger vers la connexion
header('Location: connexion.php?deconnexion=1');
exit();
