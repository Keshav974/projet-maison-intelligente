<?php

session_start(); // Démarrage de la session pour gérer les données utilisateur
require_once 'includes/config_db.php'; // Inclusion du fichier de configuration de la base de données

$errors = []; // Tableau pour stocker les erreurs
$identifiant = ""; // Variable pour stocker l'identifiant soumis par l'utilisateur

// Vérification si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifiant = isset($_POST['identifiant']) ? trim($_POST['identifiant']) : ''; // Récupération de l'identifiant
    $mot_de_passe_login = isset($_POST['mot_de_passe_login']) ? $_POST['mot_de_passe_login'] : ''; // Récupération du mot de passe

    // Validation des champs du formulaire
    if (empty($identifiant)) {
        $errors[] = "Le pseudonyme ou l'email est requis.";
    }
    if (empty($mot_de_passe_login)) {
        $errors[] = "Le mot de passe est requis.";
    }

    // Si aucune erreur, tentative de connexion à la base de données
    if (empty($errors)) {
        try {
            // Requête pour récupérer l'utilisateur par pseudonyme ou email
            $sql = "SELECT id, pseudo, email, mot_de_passe_hash, role, compte_valide 
                    FROM utilisateurs 
                    WHERE pseudo = :identifiant OR email = :identifiant";
            
            $stmt = $db->prepare($sql); // Préparation de la requête
            $stmt->bindParam(':identifiant', $identifiant); // Liaison des paramètres
            $stmt->execute(); // Exécution de la requête
            
            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC); // Récupération des résultats

            // Vérification du mot de passe et mise à jour des points si connexion réussie
            if ($utilisateur && password_verify($mot_de_passe_login, $utilisateur['mot_de_passe_hash'])) {
                if ($user['compte_valide'] || $user['jeton_validation'] === NULL) {
                $sql_update_points = "UPDATE utilisateurs SET points = points + 1 WHERE id = :user_id"; // Requête pour ajouter des points
                $stmt_update_points = $db->prepare($sql_update_points); // Préparation de la requête
                $stmt_update_points->bindParam(':user_id', $utilisateur['id'], PDO::PARAM_INT); // Liaison des paramètres
                require_once 'includes/functions.php'; // Inclusion des fonctions supplémentaires
                $stmt_update_points->execute(); // Exécution de la mise à jour
                        try {
            $stmt_update_logins = $db->prepare("UPDATE utilisateurs SET nombre_connexions = nombre_connexions + 1 WHERE id = :id");
            $stmt_update_logins->execute([':id' => $user['id']]);
        } catch (PDOException $e) {
            // On ne bloque pas la connexion si le compteur échoue
            error_log("Erreur de mise à jour du compteur de connexions: " . $e->getMessage());
        }
                //updateUserLevel($utilisateur['id'], $db); // Mise à jour du niveau utilisateur
                logActivity($utilisateur['id'], 'connexion', 'Connexion réussie', $db);
                // Enregistrement des informations utilisateur en session
                $_SESSION['user_id'] = $utilisateur['id'];
                $_SESSION['pseudo'] = $utilisateur['pseudo'];
                $_SESSION['role'] = $utilisateur['role'];

                // Redirection vers le tableau de bord
                header("Location: tableau_de_bord.php");
                exit;
                } else {
                    $errors[] = "Votre compte n'est pas encore validé. Veuillez vérifier votre email pour le lien de validation."; // Erreur si le compte n'est pas validé
                }
            } else {
                $errors[] = "Identifiant ou mot de passe incorrect."; // Erreur si identifiant ou mot de passe incorrect
            }

        } catch (PDOException $e) {
            error_log("Erreur de base de données lors de la connexion : " . $e->getMessage()); // Log des erreurs de base de données
            $errors[] = "Une erreur technique est survenue. Veuillez réessayer plus tard."; // Message d'erreur technique
        }
    }
}
?>

<?php require_once 'includes/header.php'; // Inclusion de l'en-tête ?>

<main class="container mt-4">

    <h2>Connexion</h2>

    <?php
    // Affichage des erreurs de validation ou de connexion

    if (!empty($errors)) {
        echo '<div class="alert alert-danger"><strong>Erreur(s) :</strong><ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul></div>';
    }
    ?>

    <!-- Formulaire de connexion -->
    <form action="login.php" method="post" class="needs-validation" novalidate>
        <div class="mb-3">
            <label for="identifiant" class="form-label">Pseudonyme ou Email :</label>
            <input type="text" class="form-control" id="identifiant" name="identifiant" value="<?php echo htmlspecialchars($identifiant); ?>" required>
            <div class="invalid-feedback">
                Veuillez fournir votre pseudonyme ou email.
            </div>
        </div>
        <div class="mb-3">
            <label for="mot_de_passe_login" class="form-label">Mot de passe :</label>
            <input type="password" class="form-control" id="mot_de_passe_login" name="mot_de_passe_login" required>
            <div class="invalid-feedback">
                Veuillez fournir votre mot de passe.
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Se connecter</button>
    </form>
    <p class="mt-3">Pas encore de compte ? <a href="inscription.php">Inscrivez-vous ici !</a></p>
</main>

<?php require_once 'footer.php'; // Inclusion du pied de page ?>
