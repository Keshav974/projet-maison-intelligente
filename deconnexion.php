<?php
session_start(); // Démarre une session PHP

// Réinitialise toutes les variables de session
$_SESSION = array();

if (ini_get("session.use_cookies")) { 
    // Supprime le cookie de session si les cookies sont utilisés
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
if (isset($_SESSION['user_id'])) {
    logActivity($_SESSION['user_id'], 'deconnexion', 'Déconnexion de la plateforme', $db);
}
session_destroy(); // Détruit la session active

header("Location: login.php"); // Redirige l'utilisateur vers la page de connexion
exit; // Termine l'exécution du script
?>