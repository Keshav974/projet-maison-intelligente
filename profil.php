<?php
session_start();

// Vérifier si l'utilisateur est connecté, sinon le rediriger
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'config_db.php';


$errors = [];
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- LOGIQUE POUR LA MISE À JOUR DU PROFIL (PSEUDO/EMAIL) ---
    if (isset($_POST['update_profile_submit'])) {
        $new_pseudo = trim($_POST['pseudo'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        $user_id = $_SESSION['user_id'];

        if (empty($new_pseudo)) { $errors[] = "Le pseudonyme ne peut pas être vide."; }
        if (empty($new_email)) { $errors[] = "L'email ne peut pas être vide."; }
        elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Le format de l'email est invalide."; }

        if (empty($errors)) {
            try {
                $sql_check = "SELECT id FROM utilisateurs WHERE (pseudo = :pseudo OR email = :email) AND id != :id";
                $stmt_check = $db->prepare($sql_check);
                $stmt_check->execute([':pseudo' => $new_pseudo, ':email' => $new_email, ':id' => $user_id]);
                if ($stmt_check->fetch()) {
                    $errors[] = "Ce pseudonyme ou cet email est déjà utilisé par un autre compte.";
                } else {
                    $sql_update = "UPDATE utilisateurs SET pseudo = :pseudo, email = :email WHERE id = :id";
                    $stmt_update = $db->prepare($sql_update);
                    $stmt_update->execute([':pseudo' => $new_pseudo, ':email' => $new_email, ':id' => $user_id]);
                    $_SESSION['pseudo'] = $new_pseudo;
                    $success_message = "Votre profil (pseudo/email) a été mis à jour avec succès !";
                }
            } catch (PDOException $e) { $errors[] = "Erreur technique lors de la mise à jour."; }
        }
    }


    // --- LOGIQUE POUR LE CHANGEMENT DE MOT DE PASSE ---
    if (isset($_POST['change_password_submit'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_new_password = $_POST['confirm_new_password'] ?? '';
        $user_id = $_SESSION['user_id'];

        // Validation
        if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
            $errors[] = "Tous les champs de mot de passe sont requis.";
        }
        if ($new_password !== $confirm_new_password) {
            $errors[] = "Le nouveau mot de passe et sa confirmation ne correspondent pas.";
        }
        if (strlen($new_password) < 5) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins 5 caractères.";
        }

        if (empty($errors)) {
            try {
                // 1. Récupérer le hash actuel de l'utilisateur
                $sql_fetch_pass = "SELECT mot_de_passe_hash FROM utilisateurs WHERE id = :id";
                $stmt_fetch_pass = $db->prepare($sql_fetch_pass);
                $stmt_fetch_pass->execute([':id' => $user_id]);
                $user_data = $stmt_fetch_pass->fetch();

                // 2. Vérifier si le mot de passe actuel est correct
                if ($user_data && password_verify($current_password, $user_data['mot_de_passe_hash'])) {
                    // 3. Le mot de passe actuel est correct, on peut hacher et mettre à jour le nouveau
                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $sql_update_pass = "UPDATE utilisateurs SET mot_de_passe_hash = :new_hash WHERE id = :id";
                    $stmt_update_pass = $db->prepare($sql_update_pass);
                    $stmt_update_pass->execute([':new_hash' => $new_password_hash, ':id' => $user_id]);

                    $success_message = "Votre mot de passe a été changé avec succès !";
                } else {
                    // Le mot de passe actuel fourni est incorrect
                    $errors[] = "Votre mot de passe actuel est incorrect.";
                }
            } catch (PDOException $e) {
                $errors[] = "Une erreur technique est survenue lors du changement de mot de passe.";
            }
        }
    }
}

// Récupération des données à jour de l'utilisateur pour l'affichage

$utilisateur_actuel = null;
$error_message_display = ''; 

try {
    $sql_fetch = "SELECT id, pseudo, email, role, points, niveau, to_char(date_inscription, 'DD/MM/YYYY à HH24:MI') AS date_inscription_formatee
                  FROM utilisateurs WHERE id = :id";
    $stmt_fetch = $db->prepare($sql_fetch);
    $stmt_fetch->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt_fetch->execute();
    $utilisateur_actuel = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$utilisateur_actuel) {
        header("Location: deconnexion.php");
        exit;
    }
} catch (PDOException $e) {
    $error_message_display = "Une erreur est survenue lors de la récupération de votre profil.";
}

require_once 'header.php';
?>

<main class="container mt-4">
    <h1>Mon Profil</h1>
    <hr>

    <?php
    // Affichage des messages de succès ou d'erreur du formulaire de mise à jour
    if (!empty($success_message)) {
        echo '<div class="alert alert-success">' . htmlspecialchars($success_message) . '</div>';
    }
    if (!empty($errors)) {
        echo '<div class="alert alert-danger"><strong>Erreur(s) :</strong><ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul></div>';
    }

    // On s'assure que les données de l'utilisateur ont bien été récupérées avant de tenter de les afficher
    if (isset($utilisateur_actuel) && $utilisateur_actuel) {
    ?>
        <div class="row">
            <div class="col-lg-8">

                <div class="card mb-4">
                    <div class="card-header">Informations actuelles</div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-4">Pseudonyme :</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($utilisateur_actuel['pseudo']); ?></dd>
                            <dt class="col-sm-4">Email :</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($utilisateur_actuel['email']); ?></dd>
                            <dt class="col-sm-4">Membre depuis le :</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($utilisateur_actuel['date_inscription_formatee']); ?></dd>
                        </dl>
                    </div>
                </div>

                <div class="card mb-4"> <div class="card-header">Modifier mes informations</div>
                    <div class="card-body">
                        <form action="profil.php" method="post">
                            <div class="mb-3">
                                <label for="pseudo" class="form-label">Nouveau Pseudonyme :</label>
                                <input type="text" class="form-control" id="pseudo" name="pseudo" value="<?php echo htmlspecialchars($utilisateur_actuel['pseudo']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Nouvel Email :</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($utilisateur_actuel['email']); ?>" required>
                            </div>
                            <button type="submit" name='update_profile_submit' class="btn btn-primary">Mettre à jour le profil</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Changer le mot de passe</div>
                    <div class="card-body">
                        <form action="profil.php" method="post">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Mot de passe actuel :</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Nouveau mot de passe :</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_new_password" class="form-label">Confirmez le nouveau mot de passe :</label>
                                <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required>
                            </div>
                            <button type="submit" name="change_password_submit" class="btn btn-warning">Changer le mot de passe</button>
                        </form>
                    </div>
                </div>

            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">Statut et Progression</div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <strong>Rôle :</strong>
                            <span class="badge bg-primary fs-6 rounded-pill"><?php echo htmlspecialchars(ucfirst($utilisateur_actuel['role'])); ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <strong>Niveau :</strong>
                            <span class="badge bg-info fs-6 rounded-pill"><?php echo htmlspecialchars(ucfirst($utilisateur_actuel['niveau'])); ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>Points :</strong>
                            <span class="badge bg-warning text-dark fs-6 rounded-pill"><?php echo htmlspecialchars($utilisateur_actuel['points']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            </div>
    <?php
    } else {
        // Ce message s'affiche si la récupération des données a échoué
        echo '<div class="alert alert-danger">' . htmlspecialchars($error_message_display) . '</div>';
    }
    ?>
</main>

<?php
require_once 'footer.php';
?>