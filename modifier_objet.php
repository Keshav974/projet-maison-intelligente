<?php
session_start();
require_once 'includes/config_db.php';
require_once 'includes/functions.php';

// --- Vérification de l'accès utilisateur ---
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if ($_SESSION['role'] !== 'complexe' && $_SESSION['role'] !== 'admin') { die("Accès non autorisé."); }

// --- Récupération de l'ID de l'objet à modifier ---
$objet_id = $_GET['id'] ?? null;
if (!$objet_id) { header("Location: objets.php"); exit; }

$errors = [];
$success_message = '';

try {
    // --- Récupération des détails de l'objet ---
    $stmt_objet = $db->prepare("SELECT * FROM objets_connectes WHERE id = :id");
    $stmt_objet->execute([':id' => $objet_id]);
    $objet = $stmt_objet->fetch();
    if (!$objet) { throw new Exception("Objet non trouvé."); }

    // --- Récupération des paramètres actuels de l'objet ---
    $stmt_params = $db->prepare("SELECT param_nom, param_valeur FROM objet_parametres WHERE objet_connecte_id = :id");
    $stmt_params->execute([':id' => $objet_id]);
    $params_actuels_raw = $stmt_params->fetchAll();
    $params_actuels = array_column($params_actuels_raw, 'param_valeur', 'param_nom');

    // --- Traitement du formulaire de modification ---
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Mise à jour de l'état de l'objet
        $nouvel_etat = $_POST['etat'] ?? $objet['etat'];
            if ($nouvel_etat !== $objet['etat']) {
        logActivity(
            $_SESSION['user_id'], 
            'etat_change', // Un nouveau type d'action clair
            $nouvel_etat,   // La description est simplement le nouvel état ('Actif' ou 'Inactif')
            $db, 
            $objet_id
        );
    }
        $stmt_update_etat = $db->prepare("UPDATE objets_connectes SET etat = :etat WHERE id = :id");
        $stmt_update_etat->execute([':etat' => $nouvel_etat, ':id' => $objet_id]);

        // Mise à jour des paramètres
        if (isset($_POST['params'])) {
            foreach ($_POST['params'] as $nom => $valeur) {
                $sql_upsert = "INSERT INTO objet_parametres (objet_connecte_id, param_nom, param_valeur)
                               VALUES (:objet_id, :nom, :valeur)
                               ON CONFLICT (objet_connecte_id, param_nom) DO UPDATE SET param_valeur = :valeur";
                $stmt_upsert = $db->prepare($sql_upsert);
                $stmt_upsert->execute([
                    ':objet_id' => $objet_id,
                    ':nom' => $nom,
                    ':valeur' => trim($valeur)
                ]);
            }
        }
        $success_message = "Objet mis à jour avec succès !";
        $points_ajoutes = 5;
        $stmt_update_points = $db->prepare("UPDATE utilisateurs SET points = points + :points WHERE id = :user_id");
        $stmt_update_points->execute([':points' => $points_ajoutes, ':user_id' => $_SESSION['user_id']]);
logActivity($_SESSION['user_id'], 'modification_objet', "Mise à jour de l'objet '" . htmlspecialchars($objet['nom']) . "'", $db, $objet_id);
        incrementerCompteurAction($_SESSION['user_id'], $db); //
        //updateUserLevel($_SESSION['user_id'], $db); // Mise à jour du niveau de l'utilisateur
        header("Location: modifier_objet.php?id=" . $objet_id . "&status=updated");
        exit;
    }

} catch (Exception $e) {
    die("ERREUR FATALE LORS DE L'INSERTION : " . $e->getMessage());
    $errors[] = $e->getMessage();
}

require_once 'includes/header.php';
?>

<main class="container mt-4">
    <!-- Affichage du titre et des messages de succès ou d'erreur -->
    <h1>Modifier l'Objet : <?php echo htmlspecialchars($objet['nom'] ?? '...'); ?></h1>
    <p>Changez l'état et les paramètres de votre appareil.</p>
    <hr>

    <?php
    if (isset($_GET['status']) && $_GET['status'] == 'updated') { $success_message = "Objet mis à jour avec succès !"; }
    if (!empty($success_message)) { echo '<div class="alert alert-success">' . htmlspecialchars($success_message) . '</div>'; }
    if (!empty($errors)) { /* Affichage des erreurs */ }
    ?>

    <?php if ($objet): ?>
        <!-- Formulaire de modification de l'objet -->
        <form action="modifier_objet.php?id=<?php echo htmlspecialchars($objet_id); ?>" method="post">
            <div class="card">
                <div class="card-header">Paramètres</div>
                <div class="card-body">
                    <!-- Modification de l'état de l'objet -->
                    <div class="mb-3">
                        <label for="etat" class="form-label"><strong>État de l'objet :</strong></label>
                        <select class="form-select" id="etat" name="etat">
                            <option value="Actif" <?php if ($objet['etat'] === 'Actif') echo 'selected'; ?>>Actif</option>
                            <option value="Inactif" <?php if ($objet['etat'] === 'Inactif') echo 'selected'; ?>>Inactif</option>
                        </select>
                    </div>
                    <hr>
                    
                    <!-- Modification des paramètres spécifiques selon le type d'objet -->
                    <?php if ($objet['type'] === 'Thermostat') : ?>
                        <div class="mb-3">
                            <label for="param_temp" class="form-label">Température cible (°C) :</label>
                            <input type="number" class="form-control" name="params[temperature_cible]" id="param_temp" value="<?php echo htmlspecialchars($params_actuels['temperature_cible'] ?? '20'); ?>">
                        </div>
                    <?php elseif ($objet['type'] === 'Éclairage') : ?>
                        <div class="mb-3">
                            <label for="param_intensite" class="form-label">Intensité (%) :</label>
                            <input type="range" class="form-range" min="0" max="100" step="10" name="params[intensite]" id="param_intensite" value="<?php echo htmlspecialchars($params_actuels['intensite'] ?? '80'); ?>">
                        </div>
                         <div class="mb-3">
                            <label for="param_couleur" class="form-label">Couleur :</label>
                            <input type="color" class="form-control form-control-color" name="params[couleur_hex]" id="param_couleur" value="<?php echo htmlspecialchars($params_actuels['couleur_hex'] ?? '#FFFFFF'); ?>">
                        </div>
                    <?php elseif ($objet['type'] === 'Sécurité') : ?>
                         <div class="mb-3">
                            <label for="param_code" class="form-label">Code d'accès (4 chiffres) :</label>
                            <input type="text" class="form-control" name="params[code_acces]" id="param_code" maxlength="4" pattern="\d{4}" value="<?php echo htmlspecialchars($params_actuels['code_acces'] ?? ''); ?>">
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <!-- Boutons pour enregistrer ou retourner à la liste -->
                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                    <a href="objets.php" class="btn btn-secondary">Retour à la liste</a>
                </div>
            </div>
        </form>
    <?php endif; ?>
</main>

<?php require_once 'footer.php'; ?>