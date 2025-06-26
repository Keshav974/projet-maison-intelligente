<?php
require_once 'includes/config_db.php';

$message_utilisateur = '';

// 1. Vérifier si un jeton est présent dans l'URL
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];

    try {
        // 2. Chercher un utilisateur avec ce jeton qui n'est pas encore validé
        $sql_check = "SELECT id FROM utilisateurs WHERE jeton_validation = :token AND compte_valide = FALSE";
        $stmt_check = $db->prepare($sql_check);
        $stmt_check->execute([':token' => $token]);
        $user = $stmt_check->fetch();

        if ($user) {
            // 3. Si l'utilisateur est trouvé, on valide son compte
            $sql_update = "UPDATE utilisateurs SET compte_valide = TRUE, jeton_validation = NULL WHERE id = :id";
            $stmt_update = $db->prepare($sql_update);
            $stmt_update->execute([':id' => $user['id']]);

            $message_utilisateur = '<div class="alert alert-success">Votre compte a été validé avec succès ! Vous pouvez maintenant vous connecter.</div>';

        } else {
            // Le jeton est invalide ou le compte est déjà validé
            $message_utilisateur = '<div class="alert alert-danger">Ce lien de validation est invalide ou a déjà été utilisé.</div>';
        }

    } catch (PDOException $e) {
        $message_utilisateur = '<div class="alert alert-danger">Une erreur technique est survenue. Veuillez réessayer plus tard.</div>';
    }
} else {
    // Aucun jeton fourni
    $message_utilisateur = '<div class="alert alert-warning">Aucun jeton de validation fourni.</div>';
}

// Affichage de la page pour l'utilisateur
require_once 'includes/header.php';
?>

<main class="container mt-5">
    <div class="row">
        <div class="col-md-8 offset-md-2 text-center">
            <h1>Validation de votre compte</h1>
            <hr>
            <?php echo $message_utilisateur; ?>
            <a href="login.php" class="btn btn-primary mt-3">Aller à la page de connexion</a>
        </div>
    </div>
</main>

<?php
require_once 'footer.php';
?>