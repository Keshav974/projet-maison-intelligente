<?php
session_start();

// Protéger la page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'config_db.php';

// On vérifie si le drapeau 'consultation_points_awarded' n'a PAS encore été mis dans la session.
if (!isset($_SESSION['consultation_points_awarded'])) {
    try {
        $points_a_ajouter = 1;

        $sql_update_points = "UPDATE utilisateurs SET points = points + :points WHERE id = :user_id";

        $stmt_update_points = $db->prepare($sql_update_points);

        $stmt_update_points->bindParam(':points', $points_a_ajouter, PDO::PARAM_INT);
        $stmt_update_points->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);

        $stmt_update_points->execute();
        require_once 'functions.php';
        updateUserLevel($utilisateur['id'], $db);
        // Une fois les points attribués, on met le flag dans la session à 'true'
        // pour ne pas redonner de points lors du prochain chargement de la page.
        $_SESSION['consultation_points_awarded'] = true;

    } catch (PDOException $e) {
        error_log("Erreur de base de données lors de l'attribution des points : " . $e->getMessage());
    }
}

// On vérifie si l'utilisateur a le rôle d'administrateur ou de complexe pour pouvoir supprimer un objet.

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_objet_a_supprimer'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        try {
            $id_a_supprimer = $_POST['id_objet_a_supprimer'];
            
            $sql_delete = "DELETE FROM objets_connectes WHERE id = :id";
            $stmt_delete = $db->prepare($sql_delete);
            $stmt_delete->bindParam(':id', $id_a_supprimer, PDO::PARAM_INT);
            $stmt_delete->execute();

            // Redirection vers la même page pour voir la liste à jour
            header("Location: objets.php?status=deleted");
            exit;

        } catch (PDOException $e) {
            header("Location: objets.php?status=error");
            exit;
        }
    }
}


// Récupérer les valeurs des filtres depuis l'URL (méthode GET)
$type_filtre = $_GET['type'] ?? '';
$etat_filtre = $_GET['etat'] ?? '';
$recherche_filtre = $_GET['recherche'] ?? '';

// Requête SQL de base
$sql = "SELECT id, nom, description, type, etat, marque FROM objets_connectes";

$conditions = [];
$params = [];

if (!empty($type_filtre)) {
    $conditions[] = "type = :type";
    $params[':type'] = $type_filtre;
}

if (!empty($etat_filtre)) {
    $conditions[] = "etat = :etat";
    $params[':etat'] = $etat_filtre;
}

if (!empty($recherche_filtre)) {
    $conditions[] = "(nom ILIKE :recherche OR description ILIKE :recherche)";
    $params[':recherche'] = '%' . $recherche_filtre . '%';
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY nom ASC";

// Initialisation le tableau des objets et le message d'erreur
$objets = [];
$error_message = '';

try {
    // Préparer et exécuter la requête SQL dynamique
    $stmt = $db->prepare($sql);
    $stmt->execute($params); // Passer le tableau de paramètres à execute()
    
    $objets = $stmt->fetchAll();

} catch (PDOException $e) {
    $error_message = "Une erreur est survenue lors de la récupération des objets.";
}


require_once 'header.php';
?>

<main class="container mt-4">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Liste des Objets Connectés</h1>
        <?php // Affiche le bouton "Ajouter un objet" si l'utilisateur a le bon rôle
        if (isset($_SESSION['role']) && ($_SESSION['role'] === 'complexe' || $_SESSION['role'] === 'admin')) : ?>
            <a href="ajouter_objet.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Ajouter un objet
            </a>
        <?php endif; ?>
    </div>
    <hr>

    <?php 
    // Gère les messages de statut après une action (ex: suppression)
    if (isset($_GET['status']) && $_GET['status'] == 'deleted') {
        echo '<div class="alert alert-success">L\'objet a été supprimé avec succès.</div>';
    }
    if (isset($_GET['status']) && $_GET['status'] == 'error') {
        echo '<div class="alert alert-danger">Une erreur est survenue lors de la suppression de l\'objet.</div>';
    }
    // Affiche un message d'erreur si la récupération initiale des objets a échoué
    if (isset($error_message) && !empty($error_message)) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
    }
    ?>

    <?php
    // Récupérer dynamiquement les options pour les filtres depuis la base de données
    // Types
    $types = [];
    try {
        $stmt_types = $db->query("SELECT DISTINCT type FROM objets_connectes ORDER BY type ASC");
        $types = $stmt_types->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $types = [];
    }

    // États
    $etats = [];
    try {
        $stmt_etats = $db->query("SELECT DISTINCT etat FROM objets_connectes ORDER BY etat ASC");
        $etats = $stmt_etats->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $etats = [];
    }

    // Marques
    $marques = [];
    try {
        $stmt_marques = $db->query("SELECT DISTINCT marque FROM objets_connectes ORDER BY marque ASC");
        $marques = $stmt_marques->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $marques = [];
    }

    // Récupérer la valeur du filtre marque
    $marque_filtre = $_GET['marque'] ?? '';
    ?>
    <div class="card bg-light mb-4">
        <div class="card-body">
            <form action="objets.php" method="get" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <label for="recherche" class="visually-hidden">Rechercher</label>
                    <input type="text" class="form-control" id="recherche" name="recherche" placeholder="Rechercher par nom ou description..." value="<?php echo htmlspecialchars($recherche_filtre); ?>">
                </div>
                <div class="col-md-2">
                    <label for="type" class="visually-hidden">Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">-- Tous les types --</option>
                        <?php foreach ($types as $type_option): ?>
                            <option value="<?php echo htmlspecialchars($type_option); ?>" <?php if ($type_filtre === $type_option) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($type_option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="etat" class="visually-hidden">État</label>
                    <select class="form-select" id="etat" name="etat">
                        <option value="">-- Tous les états --</option>
                        <?php foreach ($etats as $etat_option): ?>
                            <option value="<?php echo htmlspecialchars($etat_option); ?>" <?php if ($etat_filtre === $etat_option) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($etat_option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="marque" class="visually-hidden">Marque</label>
                    <select class="form-select" id="marque" name="marque">
                        <option value="">-- Toutes les marques --</option>
                        <?php foreach ($marques as $marque_option): ?>
                            <option value="<?php echo htmlspecialchars($marque_option); ?>" <?php if ($marque_filtre === $marque_option) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($marque_option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <?php if (!empty($objets)) : ?>
            <?php foreach ($objets as $objet) : ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($objet['nom']); ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($objet['type']); ?></h6>
                            <p class="card-text flex-grow-1"><?php echo htmlspecialchars($objet['description']); ?></p>
                            <p class="card-text"><small class="text-muted">Marque : <?php echo htmlspecialchars($objet['marque']); ?></small></p>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                             <span>
                                État : 
                                <?php if ($objet['etat'] === 'Actif') : ?>
                                    <span class="badge bg-success">Actif</span>
                                <?php else : ?>
                                    <span class="badge bg-secondary">Inactif</span>
                                <?php endif; ?>
                             </span>

                            <?php 

            if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') : 
                            ?>
                                <form action="objets.php" method="post" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet objet ?');">
                                    <input type="hidden" name="id_objet_a_supprimer" value="<?php echo $objet['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" title="Supprimer l'objet">
                                        <i class="bi bi-trash-fill"></i> Supprimer
                                    </button>
                                </form>
                            <?php 
                            endif; 
                            ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="col">
                <div class="alert alert-warning">Aucun objet correspondant à vos critères de recherche n'a été trouvé.</div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
require_once 'footer.php';
?>