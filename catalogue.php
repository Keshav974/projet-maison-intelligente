<?php
session_start(); // Démarre la session pour gérer l'état de connexion de l'utilisateur
require_once 'config_db.php'; // Inclut la configuration de la base de données

// --- Logique de recherche et de filtrage ---
$recherche = $_GET['recherche'] ?? ''; // Récupère le mot-clé de recherche
$type_filtre = $_GET['type'] ?? ''; // Récupère le filtre de type
$marque_filtre = $_GET['marque'] ?? ''; // Récupère le filtre de marque

// Construction de la requête SQL pour récupérer les objets du catalogue
$sql = "SELECT nom, description, type, marque FROM catalogue_objets";
$conditions = [];
$params = [];

if (!empty($recherche)) {
    $conditions[] = "(nom ILIKE :recherche OR description ILIKE :recherche)";
    $params[':recherche'] = '%' . $recherche . '%';
}
if (!empty($type_filtre)) {
    $conditions[] = "type = :type";
    $params[':type'] = $type_filtre;
}
if (!empty($marque_filtre)) {
    $conditions[] = "marque = :marque";
    $params[':marque'] = $marque_filtre;
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY nom ASC";

try {
    $stmt = $db->prepare($sql); // Prépare la requête SQL
    $stmt->execute($params); // Exécute la requête avec les paramètres
    $catalogue_results = $stmt->fetchAll(); // Récupère les résultats
} catch (PDOException $e) {
    $catalogue_results = [];
    $error_message = "Une erreur est survenue lors du chargement du catalogue."; // Message d'erreur en cas de problème
}

require_once 'header.php'; // Inclut le fichier de l'en-tête

?>

<main class="container mt-4">
    <h1>Catalogue des Appareils Compatibles</h1>
    <p class="lead">
        Explorez la liste des objets que vous pouvez intégrer à votre plateforme de maison intelligente.
    </p>
    <hr>

    <!-- Formulaire de recherche et de filtrage -->
    <div class="card bg-light mb-4">
        <div class="card-body">
            <form action="catalogue.php" method="get">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="recherche" class="form-label">Rechercher par mot-clé</label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="recherche" 
                            name="recherche" 
                            placeholder="ex: Caméra, Lumière..." 
                            value="<?php echo htmlspecialchars($recherche); ?>"
                        >
                    </div>
                    <?php
                    // Récupération des options de type et de marque depuis la base de données
                    $type_options = [];
                    try {
                        $type_stmt = $db->query("SELECT DISTINCT type FROM catalogue_objets ORDER BY type ASC");
                        $type_options = $type_stmt->fetchAll(PDO::FETCH_COLUMN);
                    } catch (PDOException $e) {
                        $type_options = [];
                    }

                    $marque_options = [];
                    try {
                        $marque_stmt = $db->query("SELECT DISTINCT marque FROM catalogue_objets ORDER BY marque ASC");
                        $marque_options = $marque_stmt->fetchAll(PDO::FETCH_COLUMN);
                    } catch (PDOException $e) {
                        $marque_options = [];
                    }
                    ?>
                    <div class="col-md-3">
                        <label for="type" class="form-label">Catégorie</label>
                        <select class="form-select" id="type" name="type">
                            <option value="">Toutes</option>
                            <?php foreach ($type_options as $type) : ?>
                                <option 
                                    value="<?php echo htmlspecialchars($type); ?>" 
                                    <?php if ($type_filtre === $type) echo 'selected'; ?>
                                >
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="marque" class="form-label">Marque</label>
                        <select class="form-select" id="marque" name="marque">
                            <option value="">Toutes</option>
                            <?php foreach ($marque_options as $marque) : ?>
                                <option 
                                    value="<?php echo htmlspecialchars($marque); ?>" 
                                    <?php if ($marque_filtre === $marque) echo 'selected'; ?>
                                >
                                    <?php echo htmlspecialchars($marque); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">OK</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Affichage des résultats du catalogue -->
    <div class="row">
        <?php if (isset($error_message)) : ?>
            <div class="alert alert-danger col-12">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($catalogue_results)) : ?>
            <?php foreach ($catalogue_results as $objet) : ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php echo htmlspecialchars($objet['nom']); ?>
                            </h5>
                            <div class="mb-2">
                                <span class="badge bg-primary">
                                    <?php echo htmlspecialchars($objet['type']); ?>
                                </span>
                                <span class="badge bg-secondary">
                                    <?php echo htmlspecialchars($objet['marque']); ?>
                                </span>
                            </div>
                            <p class="card-text text-muted small">
                                <?php echo htmlspecialchars($objet['description']); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="col">
                <p class="alert alert-warning">
                    Aucun appareil correspondant à vos critères n'a été trouvé dans le catalogue.
                </p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once 'footer.php'; // Inclut le fichier du pied de page ?>