<?php
session_start();
require_once 'includes/config_db.php';

$niveaux_config = [
    'débutant' => [
        'points_requis' => 0,
        'role_associe' => 'simple'
    ],
    'intermédiaire' => [
        'points_requis' => 20,
        'role_associe' => 'simple'
    ],
    'avancé' => [
        'points_requis' => 50,
        'role_associe' => 'complexe' // Débloque le module Gestion
    ],
    'expert' => [
        'points_requis' => 100,
        'role_associe' => 'admin' // Débloque le module Administration
    ]
];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['level_up_submit'])) {
    $nouveau_niveau_choisi = $_POST['nouveau_niveau'] ?? '';
    $user_id = $_SESSION['user_id'];

    // On récupère les données actuelles de l'utilisateur pour vérifier
    $stmt_user = $db->prepare("SELECT points, niveau FROM utilisateurs WHERE id = :id");
    $stmt_user->execute([':id' => $user_id]);
    $user_actuel = $stmt_user->fetch();

    // Vérification de sécurité
    if (
        $user_actuel && // L'utilisateur existe
        isset($niveaux_config[$nouveau_niveau_choisi]) && // Le niveau demandé existe dans notre config
        $user_actuel['points'] >= $niveaux_config[$nouveau_niveau_choisi]['points_requis'] && // L'utilisateur a assez de points
        $niveaux_config[$nouveau_niveau_choisi]['points_requis'] > $niveaux_config[$user_actuel['niveau']]['points_requis'] // On ne peut que monter, pas descendre
    ) {
        // Tout est bon, on met à jour le niveau et le rôle
        $nouveau_role = $niveaux_config[$nouveau_niveau_choisi]['role_associe'];

        $sql_update = "UPDATE utilisateurs SET niveau = :niveau, role = :role WHERE id = :id";
        $stmt_update = $db->prepare($sql_update);
        $stmt_update->execute([
            ':niveau' => $nouveau_niveau_choisi,
            ':role' => $nouveau_role,
            ':id' => $user_id
        ]);

        // On met à jour la session avec le nouveau rôle
        $_SESSION['role'] = $nouveau_role;

        // On redirige avec un message de succès
        header("Location: profil.php?status=levelup_success");
        exit;
    } else {
        // Redirection avec une erreur si la tentative est invalide
        header("Location: profil.php?status=levelup_error");
        exit;
    }
}

// Vérification de la connexion de l'utilisateur
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$errors = [];
$success_message = '';

// Traitement des formulaires soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Bloc de mise à jour du profil (pseudo/email)
    if (isset($_POST['update_profile_submit'])) {
        $new_pseudo = trim($_POST['pseudo'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        $user_id = $_SESSION['user_id'];

        if (empty($new_pseudo)) {
            $errors[] = "Le pseudonyme ne peut pas être vide.";
        }
        if (empty($new_email)) {
            $errors[] = "L'email ne peut pas être vide.";
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Le format de l'email est invalide.";
        }

        if (empty($errors)) {
            try {
                // Vérification de l'unicité du pseudo et de l'email
                $sql_check = "SELECT id FROM utilisateurs WHERE (pseudo = :pseudo OR email = :email) AND id != :id";
                $stmt_check = $db->prepare($sql_check);
                $stmt_check->execute([':pseudo' => $new_pseudo, ':email' => $new_email, ':id' => $user_id]);
                if ($stmt_check->fetch()) {
                    $errors[] = "Ce pseudonyme ou cet email est déjà utilisé par un autre compte.";
                } else {
                    // Mise à jour du pseudo et de l'email
                    $sql_update = "UPDATE utilisateurs SET pseudo = :pseudo, email = :email WHERE id = :id";
                    $stmt_update = $db->prepare($sql_update);
                    $stmt_update->execute([':pseudo' => $new_pseudo, ':email' => $new_email, ':id' => $user_id]);
                    $_SESSION['pseudo'] = $new_pseudo;
                    $success_message = "Votre profil (pseudo/email) a été mis à jour avec succès !";
                }
            } catch (PDOException $e) {
                $errors[] = "Erreur technique lors de la mise à jour.";
            }
        }
    }

    // Bloc de changement de mot de passe
    if (isset($_POST['change_password_submit'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_new_password = $_POST['confirm_new_password'] ?? '';
        $user_id = $_SESSION['user_id'];

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
                // Vérification du mot de passe actuel
                $sql_fetch_pass = "SELECT mot_de_passe_hash FROM utilisateurs WHERE id = :id";
                $stmt_fetch_pass = $db->prepare($sql_fetch_pass);
                $stmt_fetch_pass->execute([':id' => $user_id]);
                $user_data = $stmt_fetch_pass->fetch();

                if ($user_data && password_verify($current_password, $user_data['mot_de_passe_hash'])) {
                    // Mise à jour du mot de passe
                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql_update_pass = "UPDATE utilisateurs SET mot_de_passe_hash = :new_hash WHERE id = :id";
                    $stmt_update_pass = $db->prepare($sql_update_pass);
                    $stmt_update_pass->execute([':new_hash' => $new_password_hash, ':id' => $user_id]);
                    $success_message = "Votre mot de passe a été changé avec succès !";
                } else {
                    $errors[] = "Votre mot de passe actuel est incorrect.";
                }
            } catch (PDOException $e) {
                $errors[] = "Une erreur technique est survenue lors du changement de mot de passe.";
            }
        }
    }
}

// Récupération des données de l'utilisateur pour l'affichage
$utilisateur_actuel = null;
$error_message_display = '';

try {
    $sql_fetch = "SELECT id, pseudo, email, role, points, niveau, to_char(date_inscription, 'DD/MM/YYYY à HH24:MI') AS date_inscription_formatee, nombre_connexions, nombre_actions
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

// Récupérer l'historique d'activité pour l'utilisateur affiché
$logs_activite = [];
try {
    $id_a_chercher = $profil_id ?? $_SESSION['user_id'];

    $stmt_logs = $db->prepare("SELECT type_action, description_action, to_char(date_action, 'DD/MM/YYYY à HH24:MI') AS date_formatee 
                               FROM logs_activite 
                               WHERE utilisateur_id = :id 
                               ORDER BY date_action DESC 
                               LIMIT 10");
    $stmt_logs->execute([':id' => $id_a_chercher]);
    $logs_activite = $stmt_logs->fetchAll();
} catch (PDOException $e) {
    // Gérer l'erreur si nécessaire
}
require_once 'includes/header.php';
?>

<main class="container mt-4">
    <h1>Mon Profil</h1>
    <hr>

    <?php
    // Affichage des messages de succès ou d'erreur
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

    // Affichage des informations de l'utilisateur
    if (isset($utilisateur_actuel) && $utilisateur_actuel) {
    ?>
        <div class="row">
            <div class="col-lg-8">

                <!-- Bloc d'affichage des informations actuelles -->
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
                            <dt class="col-sm-4">Nombre de connexions :</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($utilisateur_actuel['nombre_connexions']); ?></dd>
                            <dt class="col-sm-4">Nombre d'actions :</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($utilisateur_actuel['nombre_actions']); ?></dd>
                        </dl>
                    </div>
                </div>

                <!-- Bloc de mise à jour des informations -->
                <div class="card mb-4">
                    <div class="card-header">Modifier mes informations</div>
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

                <!-- Bloc de changement de mot de passe -->
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

                <!-- Bloc d'historique des activités -->
                <div class="card mt-4">
                    <div class="card-header">
                        Historique des dernières activités
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if (!empty($logs_activite)) : ?>
                            <?php foreach ($logs_activite as $log) : ?>
                                <div class="list-group-item">
                                    <p class="mb-1">
                                        <strong><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $log['type_action']))); ?></strong>
                                    </p>
                                    <small class="text-muted"><?php echo htmlspecialchars($log['date_formatee']); ?></small>
                                    <?php if (!empty($log['description_action'])) : ?>
                                        <p class="mb-0 small fst-italic">
                                            Détail : <?php echo htmlspecialchars($log['description_action']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="list-group-item">Aucune activité enregistrée.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div> <div class="col-lg-4">

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
            </div> <div class="card mt-4">
                <div class="card-header">Évolution</div>
                <div class="card-body">
                    <p>Passez au niveau supérieur pour débloquer de nouvelles fonctionnalités !</p>
                    <ul class="list-group">
                        <?php
                        // On boucle sur notre configuration de niveaux
                        foreach ($niveaux_config as $niveau_nom => $niveau_data) {
                            if ($niveau_data['points_requis'] > $niveaux_config[$utilisateur_actuel['niveau']]['points_requis']) {
                                echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                                if ($utilisateur_actuel['points'] >= $niveau_data['points_requis']) {
                                    echo '<span><strong>' . htmlspecialchars(ucfirst($niveau_nom)) . '</strong> <span class="badge bg-success">' . $niveau_data['points_requis'] . ' pts requis</span></span>';
                                    echo '<form action="profil.php" method="post" class="m-0">';
                                    echo '<input type="hidden" name="nouveau_niveau" value="' . $niveau_nom . '">';
                                    echo '<button type="submit" name="level_up_submit" class="btn btn-primary btn-sm">Choisir</button>';
                                    echo '</form>';
                                } else {
                                    $points_manquants = $niveau_data['points_requis'] - $utilisateur_actuel['points'];
                                    echo '<span>' . htmlspecialchars(ucfirst($niveau_nom)) . ' <span class="badge bg-secondary">' . $niveau_data['points_requis'] . ' pts requis</span></span>';
                                    echo '<span class="badge bg-warning text-dark" title="Il vous manque ' . $points_manquants . ' points">Verrouillé</span>';
                                }
                                echo '</li>';
                            }
                        }
                        ?>
                    </ul>
                </div>
            </div> </div> </div>
        
    <?php
    } else {
        // Message d'erreur si les données de l'utilisateur n'ont pas été récupérées
        echo '<div class="alert alert-danger">' . htmlspecialchars($error_message_display) . '</div>';
    }
    ?>
    
</main>

<?php
require_once 'footer.php';
?>