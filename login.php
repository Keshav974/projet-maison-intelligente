<?php

session_start();
require_once 'includes/config_db.php';

$errors = [];
$identifiant = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifiant = isset($_POST['identifiant']) ? trim($_POST['identifiant']) : '';
    $mot_de_passe_login = isset($_POST['mot_de_passe_login']) ? $_POST['mot_de_passe_login'] : '';

    if (empty($identifiant)) {
        $errors[] = "Le pseudonyme ou l'email est requis.";
    }
    if (empty($mot_de_passe_login)) {
        $errors[] = "Le mot de passe est requis.";
    }

    if (empty($errors)) {
        try {
            $sql = "SELECT id, pseudo, email, mot_de_passe_hash, role, compte_valide 
                    FROM utilisateurs 
                    WHERE pseudo = :identifiant OR email = :identifiant";
            
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':identifiant', $identifiant);
            $stmt->execute();
            
            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($utilisateur && password_verify($mot_de_passe_login, $utilisateur['mot_de_passe_hash'])) {
                if ($utilisateur['compte_valide'] || $utilisateur['jeton_validation'] === NULL) {
                    $sql_update_points = "UPDATE utilisateurs SET points = points + 1 WHERE id = :user_id";
                    $stmt_update_points = $db->prepare($sql_update_points);
                    $stmt_update_points->bindParam(':user_id', $utilisateur['id'], PDO::PARAM_INT);
                    require_once 'includes/functions.php';
                    $stmt_update_points->execute();

                    try {
                        $stmt_update_logins = $db->prepare("UPDATE utilisateurs SET nombre_connexions = nombre_connexions + 1 WHERE id = :id");
                        $stmt_update_logins->execute([':id' => $utilisateur['id']]);
                    } catch (PDOException $e) {
                        error_log("Erreur de mise à jour du compteur de connexions: " . $e->getMessage());
                    }

                    logActivity($utilisateur['id'], 'connexion', 'Connexion réussie', $db);

                    $_SESSION['user_id'] = $utilisateur['id'];
                    $_SESSION['pseudo'] = $utilisateur['pseudo'];
                    $_SESSION['role'] = $utilisateur['role'];

                    header("Location: tableau_de_bord.php");
                    exit;
                } else {
                    $errors[] = "Votre compte n'est pas encore validé. Veuillez vérifier votre email pour le lien de validation.";
                }
            } else {
                $errors[] = "Identifiant ou mot de passe incorrect.";
            }

        } catch (PDOException $e) {
            error_log("Erreur de base de données lors de la connexion : " . $e->getMessage());
            $errors[] = "Une erreur technique est survenue. Veuillez réessayer plus tard.";
        }
    }
}
?>

<?php require_once 'includes/header.php'; ?>

<main class="container mt-4">

    <h2>Connexion</h2>

    <?php
    if (!empty($errors)) {
        echo '<div class="alert alert-danger"><strong>Erreur(s) :</strong><ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul></div>';
    }
    ?>

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

<?php require_once 'footer.php'; ?>
