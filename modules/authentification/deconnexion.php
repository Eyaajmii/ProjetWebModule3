<?php
/**
 * Page de déconnexion
 * Détruit la session et redirige vers la page de connexion
 */

session_start();

// Détruire toutes les variables de session
$_SESSION = array();

// Détruire le cookie de session si existant
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Détruire la session
session_destroy();

// Rediriger vers la page de connexion des étudiants
header('Location: ../etudiants/test_connexion.php');
exit();
