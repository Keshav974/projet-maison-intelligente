<?php

$errors = [];

$pseudo = "";
$email = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pseudo = isset($_POST['pseudo']) ? trim($_POST['pseudo']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $mot_de_passe = isset($_POST['mot_de_passe']) ? $_POST['mot_de_passe'] : '';
    $confirmation_mot_de_passe = isset($_POST['confirmation_mot_de_passe']) ? $_POST['confirmation_mot_de_passe'] : '';
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
    }

    elseif (strlen($mot_de_passe) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
    }

    if (empty($confirmation_mot_de_passe)) {
        $errors[] = "La confirmation du mot de passe est requise.";
    } elseif ($mot_de_passe !== $confirmation_mot_de_passe) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    if (empty($errors)) {
        // Traitement des données (pour l'instant, simulation)
        // Étape 1 : Hasher le mot de passe (TRÈS IMPORTANT pour la sécurité avant de stocker en BDD)
        // $mot_de_passe_hashe = password_hash($mot_de_passe, PASSWORD_DEFAULT);
        // Nous verrons password_hash() plus en détail avec la base de données.

        // Étape 2 : Enregistrer l'utilisateur en base de données (pas encore implémenté)
        // ... code pour insérer $pseudo, $email, $mot_de_passe_hashe dans la BDD ...

        // Étape 3 : Afficher un message de succès / Rediriger
        // Pour l'instant, nous allons juste préparer un message de succès.
        $success_message = "Inscription réussie (simulation) !<br>" .
                           "Pseudonyme : " . htmlspecialchars($pseudo) . "<br>" .
                           "Email : " . htmlspecialchars($email) . "<br>" .
                           "Bienvenue ! La prochaine étape sera de sauvegarder ces informations.";

        // Réinitialiser les variables des champs pour vider le formulaire après un succès
        $pseudo = "";
        $email = "";
        // Les champs de mot de passe ne sont jamais re-remplis de toute façon.

    }
    // Si le tableau $errors n'est pas vide, les erreurs seront affichées
    // dans la partie HTML de la page grâce au code que vous allez écrire
    // (celui qui parcourt le tableau $errors).
}
?>

<?php require_once 'header.php'; ?>

<main>
    <?php
if (!empty($success_message)) {
    echo '<div class="alert alert-success container mt-3">' . $success_message . '</div>';
}
?>
<?php
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