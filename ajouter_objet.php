<?php
// Page du module "Gestion" permettant d'ajouter un nouvel objet connecté.
session_start();

// On vérifie d'abord que l'utilisateur est connecté.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Ensuite, on protège la page par rôle : seuls les 'complexe' et 'admin' ont le droit.
if ($_SESSION['role'] !== 'complexe' && $_SESSION['role'] !== 'admin') {
    die("Accès non autorisé. Vous n'avez pas les permissions nécessaires pour accéder à cette page.");
}

// Connexion à la BDD.
require_once 'config_db.php';


$errors = [];
$nom = $description = $type = $marque = $etat = ""; 

// On ne traite le formulaire que s'il a été soumis en méthode POST.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupération des données du formulaire avec une protection contre les clés non définies.
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $marque = trim($_POST['marque'] ?? '');
    $etat = trim($_POST['etat'] ?? 'Inactif');

    // --- Validations de base des champs ---
    if (empty($nom)) {
        $errors[] = "Le nom de l'objet est requis.";
    }
    if (empty($type)) {
        $errors[] = "Le type de l'objet est requis.";
    }

    if (empty($errors)) {
        try {
            $sql = "INSERT INTO objets_connectes (nom, description, type, marque, etat) 
                    VALUES (:nom, :description, :type, :marque, :etat)";
            
            $stmt = $db->prepare($sql);

            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':marque', $marque);
            $stmt->bindParam(':etat', $etat);
            $stmt->execute();
            header("Location: objets.php?status=added");
            exit;

        } catch (PDOException $e) {

            $errors[] = "Une erreur est survenue lors de l'ajout de l'objet.";
        }
    }
}

require_once 'header.php';
?>

<main class="container mt-4">
    <h1>Ajouter un Nouvel Objet Connecté</h1>
    <p>Remplissez le formulaire ci-dessous pour ajouter un nouvel objet à la plateforme.</p>
    <hr>

    <?php
    if (!empty($errors)) {
        echo '<div class="alert alert-danger"><ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul></div>';
    }
    ?>

    <form action="ajouter_objet.php" method="post">
        <div class="mb-3">
            <label for="nom" class="form-label">Nom de l'objet :</label>
            <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($nom); ?>" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description :</label>
            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
        </div>
        <div class="mb-3">
            <label for="type" class="form-label">Type :</label>
            <input type="text" class="form-control" id="type" name="type" value="<?php echo htmlspecialchars($type); ?>" required>
            <small class="form-text text-muted">Exemples : Thermostat, Éclairage, Sécurité, Capteur...</small>
        </div>
        <div class="mb-3">
            <label for="marque" class="form-label">Marque :</label>
            <input type="text" class="form-control" id="marque" name="marque" value="<?php echo htmlspecialchars($marque); ?>">
        </div>
        <div class="mb-3">
            <label for="etat" class="form-label">État :</label>
            <select class="form-select" id="etat" name="etat">
                <option value="Actif" <?php if($etat === 'Actif') echo 'selected'; ?>>Actif</option>
                <option value="Inactif" <?php if($etat === 'Inactif') echo 'selected'; ?>>Inactif</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">Ajouter l'objet</button>
        <a href="objets.php" class="btn btn-secondary">Annuler</a>
    </form>
</main>

<?php

require_once 'footer.php';
?>