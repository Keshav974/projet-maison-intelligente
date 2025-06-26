<?php
session_start();

// Vérification de la connexion de l'utilisateur
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'includes/config_db.php';
// --- DÉBUT DU TRAITEMENT DES SUPPRESSIONS (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CAS 1 : L'admin supprime définitivement un objet
    if (isset($_POST['id_objet_a_supprimer']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        try {
            $stmt_delete = $db->prepare("DELETE FROM objets_connectes WHERE id = :id");
            $stmt_delete->execute([':id' => $_POST['id_objet_a_supprimer']]);
            header("Location: objets.php?status=deleted");
            exit;
        } catch (PDOException $e) {
            header("Location: objets.php?status=error_delete");
            exit;
        }
    }

    // CAS 2 : Un utilisateur complexe demande la suppression
    if (isset($_POST['id_objet_demande_suppression']) && isset($_SESSION['role']) && $_SESSION['role'] === 'complexe') {
        try {
            $stmt_request = $db->prepare("UPDATE objets_connectes SET demande_suppression = TRUE WHERE id = :id");
            $stmt_request->execute([':id' => $_POST['id_objet_demande_suppression']]);
            header("Location: objets.php?status=request_sent");
            exit;
        } catch (PDOException $e) {
            header("Location: objets.php?status=error_request");
            exit;
        }
    }
}
// --- FIN DU TRAITEMENT DES SUPPRESSIONS ---
// Attribution de points à l'utilisateur lors de la première consultation de la page
if (!isset($_SESSION['consultation_points_awarded'])) {
    try {
        $points_a_ajouter = 1;
        $sql_update_points = "UPDATE utilisateurs SET points = points + :points WHERE id = :user_id";
        $stmt_update_points = $db->prepare($sql_update_points);
        $stmt_update_points->bindParam(':points', $points_a_ajouter, PDO::PARAM_INT);
        $stmt_update_points->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt_update_points->execute();
        require_once 'includes/functions.php';
        //updateUserLevel($utilisateur['id'], $db);
        $_SESSION['consultation_points_awarded'] = true;
    } catch (PDOException $e) {
        error_log("Erreur de base de données lors de l'attribution des points : " . $e->getMessage());
    }
}

// Suppression d'un objet connecté par un administrateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_objet_a_supprimer'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        try {
            $id_a_supprimer = $_POST['id_objet_a_supprimer'];
            $sql_delete = "DELETE FROM objets_connectes WHERE id = :id";
            $stmt_delete = $db->prepare($sql_delete);
            $stmt_delete->bindParam(':id', $id_a_supprimer, PDO::PARAM_INT);
            $stmt_delete->execute();
            header("Location: objets.php?status=deleted");
            exit;
        } catch (PDOException $e) {
            header("Location: objets.php?status=error");
            exit;
        }
    }
}

// Construction de la requête SQL pour récupérer les objets connectés selon les filtres
$type_filtre = $_GET['type'] ?? '';
$etat_filtre = $_GET['etat'] ?? '';
$recherche_filtre = $_GET['recherche'] ?? '';
$sql = "SELECT id, nom, description, type, etat, marque, demande_suppression FROM objets_connectes";
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

// Exécution de la requête pour récupérer les objets connectés
$objets = [];
$error_message = '';
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params); 
    $objets = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Une erreur est survenue lors de la récupération des objets.";
}

// Récupération des paramètres associés à chaque objet connecté
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $objets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($objets)) {
        $objet_ids = array_column($objets, 'id');
        $placeholders = implode(',', array_fill(0, count($objet_ids), '?'));
        $sql_params = "SELECT objet_connecte_id, param_nom, param_valeur 
                       FROM objet_parametres 
                       WHERE objet_connecte_id IN ($placeholders)";
        $stmt_params = $db->prepare($sql_params);
        $stmt_params->execute($objet_ids);
        $all_params = $stmt_params->fetchAll();
        foreach ($all_params as $param) {
            $parametres_par_objet[$param['objet_connecte_id']][$param['param_nom']] = $param['param_valeur'];
        }
    }
} catch (PDOException $e) {
    $error_message = "Une erreur est survenue lors de la récupération des données.";
}

require_once 'includes/header.php';
?>

<main class="container mt-4">
    <!-- Affichage du titre et bouton d'ajout d'objet pour les utilisateurs autorisés -->
    <div class="d-flex justify-content-between align-items-center">
        <h1>Liste des Objets Connectés</h1>
        <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'complexe' || $_SESSION['role'] === 'admin')) : ?>
            <a href="ajouter_objet.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Ajouter un objet
            </a>
        <?php endif; ?>
    </div>
    <hr>

    <!-- Affichage des messages de statut (succès, erreur, etc.) -->
    <?php 
    if (isset($_GET['status']) && $_GET['status'] == 'deleted') {
        echo '<div class="alert alert-success">L\'objet a été supprimé avec succès.</div>';
    }
    if (isset($_GET['status']) && $_GET['status'] == 'error') {
        echo '<div class="alert alert-danger">Une erreur est survenue lors de la suppression de l\'objet.</div>';
    }
    if (isset($error_message) && !empty($error_message)) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
    }
    ?>

    <?php
    // Récupération des options de filtres (types, états, marques) depuis la base de données
    $types = [];
    try {
        $stmt_types = $db->query("SELECT DISTINCT type FROM objets_connectes ORDER BY type ASC");
        $types = $stmt_types->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $types = [];
    }
    $etats = [];
    try {
        $stmt_etats = $db->query("SELECT DISTINCT etat FROM objets_connectes ORDER BY etat ASC");
        $etats = $stmt_etats->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $etats = [];
    }
    $marques = [];
    try {
        $stmt_marques = $db->query("SELECT DISTINCT marque FROM objets_connectes ORDER BY marque ASC");
        $marques = $stmt_marques->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $marques = [];
    }
    $marque_filtre = $_GET['marque'] ?? '';
    ?>
    <!-- Formulaire de filtres de recherche -->
    <div class="card bg-light mb-4">
        <div class="card-body">
            <form action="objets.php" method="get" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <input type="text" class="form-control" id="recherche" name="recherche" placeholder="Rechercher par nom ou description..." value="<?php echo htmlspecialchars($recherche_filtre); ?>">
                </div>
                <div class="col-md-2">
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

    <!-- Affichage de la liste des objets connectés -->
    <div class="row">
        <?php if (!empty($objets)) : ?>
            <?php foreach ($objets as $objet) : ?>
                <div class="col-md-6 col-lg-4 mb-4 d-flex">
                    <div class="card h-100 w-100 d-flex flex-column">
                        <div class="card-body flex-grow-1">
                            <h5 class="card-title"><?php echo htmlspecialchars($objet['nom']); ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($objet['type']); ?></h6>
                            <!-- Affichage des paramètres de l'objet si actif -->
                            <?php if ($objet['etat'] === 'Actif' && isset($parametres_par_objet[$objet['id']])) : ?>
                                <div class="mt-3 mb-3 p-2 bg-light rounded small">
                                    <ul>
                                        <?php foreach ($parametres_par_objet[$objet['id']] as $nom_param => $valeur_param) : ?>
                                            <li>
                                                <?php
                                                $label = htmlspecialchars(ucfirst(str_replace('_', ' ', $nom_param)));
                                                $valeur = htmlspecialchars($valeur_param);
                                                $unite = '';
                                                if ($nom_param === 'temperature_cible') {
                                                    $unite = ' °C';
                                                } elseif ($nom_param === 'intensite') {
                                                    $unite = ' %';
                                                }
                                                echo $label . ' : <strong>' . $valeur . $unite . '</strong>';
                                                ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            <p class="card-text"><small class="text-muted">Marque : <?php echo htmlspecialchars($objet['marque']); ?></small></p>
                            <p class="card-text flex-grow-1"><?php echo htmlspecialchars($objet['description']); ?></p>
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
                            <div> 
                                
                                <!-- Bouton de modification pour les utilisateurs autorisés -->
                                <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'complexe' || $_SESSION['role'] === 'admin')) : ?>
                                    <a href="modifier_objet.php?id=<?php echo $objet['id']; ?>" class="btn btn-primary btn-sm" title="Modifier">
                                        <i class="bi bi-pencil-fill"></i> Modifier
                                    </a>
                                <?php endif; ?>
                                
                                <!-- Bouton de demande de suppression pour les utilisateurs complexes -->
                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'complexe') : ?>
                                    <form action="objets.php" method="post" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir demander la suppression de cet objet ?');">
                                        <input type="hidden" name="id_objet_demande_suppression" value="<?php echo $objet['id']; ?>">
                                        <button type="submit" class="btn btn-warning btn-sm" title="Demander la suppression">
                                            <i class="bi bi-exclamation-triangle"></i> Demander suppression
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <!-- Bouton de suppression pour les administrateurs -->
                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') : ?>
                                    <?php if ($objet['demande_suppression']) : ?>
                                        <span class="badge bg-warning me-2" title="Demande de suppression en attente">
                                            <i class="bi bi-exclamation-circle"></i> Suppression demandée
                                        </span>
                                    <?php endif; ?>
                                    <form action="objets.php" method="post" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet objet ?');">
                                        <input type="hidden" name="id_objet_a_supprimer" value="<?php echo $objet['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Supprimer">
                                            <i class="bi bi-trash-fill"></i> Supprimer
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <!-- Message si aucun objet trouvé -->
            <div class="col">
                <div class="alert alert-warning">Aucun objet correspondant à vos critères de recherche n'a été trouvé.</div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
require_once 'footer.php';
?>
