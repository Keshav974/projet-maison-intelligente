<?php
require_once 'includes/config_db.php'; // Inclusion du fichier de configuration de la base de données
// Initialisation des variables pour gérer les erreurs, les champs du formulaire et les messages de succès
$errors = [];
$pseudo = "";
$email = "";
$success_message = "";

// Vérification si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupération et nettoyage des données du formulaire
    $pseudo = isset($_POST['pseudo']) ? trim($_POST['pseudo']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $mot_de_passe = isset($_POST['mot_de_passe']) ? $_POST['mot_de_passe'] : '';
    $confirmation_mot_de_passe = isset($_POST['confirmation_mot_de_passe']) ? $_POST['confirmation_mot_de_passe'] : '';

        try {
        $stmt_check_auth = $db->prepare("SELECT statut FROM membres_autorises WHERE email = :email");
        $stmt_check_auth->execute([':email' => $email]);
        $membre_autorise = $stmt_check_auth->fetch();

        if (!$membre_autorise) {
            $errors[] = "Votre adresse email n'est pas autorisée à s'inscrire sur cette plateforme. Veuillez contacter un administrateur.";
        } elseif ($membre_autorise['statut'] === 'inscrit') {
            $errors[] = "Cette adresse email a déjà été utilisée pour une inscription.";
        }
    } catch (PDOException $e) {
        $errors[] = "Erreur lors de la vérification de l'autorisation.";
    }

    // Validation des champs du formulaire
    if (empty($pseudo)) {
        $errors[] = "Le pseudonyme est requis.";
    }

    if (empty($email)) {
        $errors[] = "L'email est requis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Le format de l'email est invalide.";
    }

    if (empty($mot_de_passe)) {
        $errors[] = "Le mot de passe est requis.";
    } elseif (strlen($mot_de_passe) < 5) {
        $errors[] = "Le mot de passe doit contenir au moins 5 caractères.";
    }

    if (empty($confirmation_mot_de_passe)) {
        $errors[] = "La confirmation du mot de passe est requise.";
    } elseif ($mot_de_passe !== $confirmation_mot_de_passe) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    // Si aucune erreur, traitement de l'inscription
    if (empty($errors)) {
            try {
                // On démarre une transaction pour s'assurer que les deux mises à jour se font correctement
                $db->beginTransaction();

                // 1. On insère le nouvel utilisateur dans la table 'utilisateurs'
                $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                $stmt_insert = $db->prepare("INSERT INTO utilisateurs (pseudo, email, mot_de_passe_hash) VALUES (:pseudo, :email, :mot_de_passe_hash)");
                $stmt_insert->execute([
                    ':pseudo' => $pseudo,
                    ':email' => $email,
                    ':mot_de_passe_hash' => $hashed_password
                ]);

                // 2. On met à jour le statut dans la table 'membres_autorises'
                $stmt_update_status = $db->prepare("UPDATE membres_autorises SET statut = 'inscrit' WHERE email = :email");
                $stmt_update_status->execute([':email' => $email]);

                // On valide la transaction
                $db->commit();

                // Redirection vers la page de connexion avec un message de succès
                session_start(); // On démarre la session ici pour le message flash
                $_SESSION['success_message'] = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
                header("Location: login.php");
                exit;

        } catch (PDOException $e) {
            // Gestion des erreurs de connexion ou d'exécution de la requête
            $errors[] = "Erreur lors de l'inscription : " . htmlspecialchars($e->getMessage());
        }

        // Message de succès après une inscription réussie
        $success_message = "Inscription réussie (simulation) !<br>" .
                           "Pseudonyme : " . htmlspecialchars($pseudo) . "<br>" .
                           "Email : " . htmlspecialchars($email) . "<br>" .
                           "Bienvenue ! La prochaine étape sera de sauvegarder ces informations.";

        // Réinitialisation des champs du formulaire
        $pseudo = "";
        $email = "";
    }
}
?>

<?php require_once 'includes/header.php'; ?>

<main>
    <?php
    // Affichage du message de succès si l'inscription est réussie
    if (!empty($success_message)) {
        echo '<div class="alert alert-success container mt-3">' . $success_message . '</div>';
    }
    ?>

    <?php
    // Affichage des erreurs si des validations ont échoué
    if (!empty($errors)) {
        echo '<div class="alert alert-danger container mt-3"><strong>Erreur(s) :</strong><ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul></div>';
    }
    ?>

    <div class="row">
        <section class="col-md-6">
            <h2>Inscription</h2>
            <!-- Formulaire d'inscription -->
            <form action="inscription.php" method="post">
                <div class="mb-3">
                    <label for="var_nom" class="form-label">Pseudo</label>
                    <input type="text" class="form-control" id="var_nom" name="pseudo" value="<?php echo htmlspecialchars($pseudo); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="var_email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="var_email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="var_motdepasse" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="var_motdepasse" name="mot_de_passe" required>
                </div>
                <div class="mb-3">
                    <label for="var_confirmation_motdepasse" class="form-label">Confirmation du mot de passe</label>
                    <input type="password" class="form-control" id="var_confirmation_motdepasse" name="confirmation_mot_de_passe" required>
                </div>
                <button type="submit" class="btn btn-primary">S'inscrire</button>
            </form>
        </section>
    </div>
</main>

<?php require_once 'footer.php'; ?>