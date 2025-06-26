<?php

session_start();
require_once 'includes/config_db.php';
require_once 'includes/functions.php';

// Protection de la page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// --- Récupération des données pour le tableau de bord ---
$user_id = $_SESSION['user_id'];
$stats = [
    'objets_count' => 0,
    'points' => 0,
    'niveau' => 'débutant'
];

$zonesData = [
    'securite' => ['objets' => [], 'actifs' => 0],
    'temperature' => ['objets' => [], 'actifs' => 0],
    'eclairage' => ['objets' => [], 'actifs' => 0]
];

try {
    // On récupère les dernières infos de l'utilisateur (points, niveau)
    $stmt_user = $db->prepare("SELECT points, niveau FROM utilisateurs WHERE id = :id");
    $stmt_user->execute([':id' => $user_id]);
    $user_data = $stmt_user->fetch();
    if ($user_data) {
        $stats['points'] = $user_data['points'];
        $stats['niveau'] = $user_data['niveau'];
    }

    // On compte le nombre d'objets appartenant à l'utilisateur
    $stmt_objets = $db->prepare("SELECT COUNT(*) FROM objets_connectes WHERE utilisateur_id = :id");
    $stmt_objets->execute([':id' => $user_id]);
    $stats['objets_count'] = $stmt_objets->fetchColumn();

    $stmt_objets = $db->prepare(
        "SELECT id, type, nom, description, etat, marque 
         FROM objets_connectes"
    );
    $stmt_objets->execute();
    $objets_bruts = $stmt_objets->fetchAll(PDO::FETCH_ASSOC);
    $objets_complets = []; // Le tableau qui contiendra le résultat final combiné
    $objets_ids = [];      // Juste la liste des IDs

    if ($objets_bruts) {
        // On remplit nos deux tableaux
        foreach ($objets_bruts as $objet) {
            $objets_ids[] = $objet['id']; // Ajoute l'id à la liste
            $objet['parametres'] = []; // Crée une sous-case vide pour les futurs paramètres
            $objets_complets[$objet['id']] = $objet; // Stocke l'objet complet, avec l'ID comme clé
        }

        if (!empty($objets_ids)) {
            $placeholders = implode(',', array_fill(0, count($objets_ids), '?'));

            $stmt_params = $db->prepare(
                "SELECT objet_connecte_id, param_nom, param_valeur 
                 FROM objet_parametres 
                 WHERE objet_connecte_id IN ($placeholders)"
            );
            $stmt_params->execute($objets_ids);
            $tous_les_parametres = $stmt_params->fetchAll(PDO::FETCH_ASSOC);

            // On attache chaque paramètre à son objet parent
            foreach ($tous_les_parametres as $param) {
                $objets_complets[$param['objet_connecte_id']]['parametres'][] = [
                    'nom' => $param['param_nom'],
                    'valeur' => $param['param_valeur']
                ];
            }
        }

        foreach ($objets_complets as $objet) {
            switch ($type = $objet['type']) {
                case 'Sécurité':
                    $zonesData['securite']['objets'][] = $objet;
                    if (strtolower($objet['etat']) === 'actif') {
                        $zonesData['securite']['actifs']++;
                    }
                    break;

                case 'Thermostat':
                    $zonesData['temperature']['objets'][] = $objet;
                    if (strtolower($objet['etat']) === 'actif') {
                        $zonesData['temperature']['actifs']++;
                    }
                    break;

                case 'Éclairage':
                    $zonesData['eclairage']['objets'][] = $objet;
                    if (strtolower($objet['etat']) === 'actif') {
                        $zonesData['eclairage']['actifs']++;
                    }
                    break;
            }
        }
    }
} catch (PDOException $e) {
    // Gestion d'erreur silencieuse pour ne pas casser la page
    error_log("Erreur de BDD sur le tableau de bord : " . $e->getMessage());
}

$zones_json = json_encode($zonesData);

require_once 'includes/header.php';
?>

<main class="container mt-4">
    <div class="card mt-4">
        <div class="card-header">
            <h4>Voici votre maison <?php echo htmlspecialchars($_SESSION['pseudo']); ?> </h4>
        </div>
        <div class="card-body">
            <div id="house-plan-container"
                data-zones='<?php echo htmlspecialchars($zones_json, ENT_QUOTES, 'UTF-8'); ?>'>
                <img src="assets/img/plan_maison.png" alt="Plan de la maison" class="img-fluid">

                <div class="interactive-zone" id="zone-securite" data-zone-key="securite">
                    <div class="zone-label">
                        Sécurité
                        <span class="badge rounded-pill bg-dark"><?php echo count($zonesData['securite']['objets']); ?></span>
                    </div>
                </div>

                <div class="interactive-zone" id="zone-temperature" data-zone-key="temperature">
                    <div class="zone-label">
                        Température
                        <span class="badge rounded-pill bg-dark"><?php echo count($zonesData['temperature']['objets']); ?></span>
                    </div>
                </div>

                <div class="interactive-zone" id="zone-eclairage" data-zone-key="eclairage">
                    <div class="zone-label">
                        Éclairage
                        <span class="badge rounded-pill bg-dark"><?php echo count($zonesData['eclairage']['objets']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="object-tooltip"></div>

    <style>
        #house-plan-container {
            position: relative;
            max-width: 800px; /* Adaptez à la taille de votre image */
            margin: auto;
        }

        .interactive-zone {
            position: absolute;
            cursor: pointer;
            transition: background-color 0.3s, border 0.3s;
            border: 2px solid rgba(255, 255, 255, 0.5); /* Bordure blanche semi-transparente */
            border-radius: 10px; /* Bords arrondis */
            background-color: rgba(0, 0, 0, 0.2); /* Fond sombre très léger pour la lisibilité */

            /* Flexbox pour centrer le label parfaitement */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .interactive-zone:hover {
            background-color: rgba(23, 162, 184, 0.4); /* Bleu-vert au survol */
            border-color: #ffffff; /* Bordure blanche pleine au survol */
        }

        /* Style du texte à l'intérieur de la zone */
        .zone-label {
            color: white;
            font-weight: bold;
            font-size: 1.1rem; /* Adaptez la taille si besoin */
            text-align: center;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8); /* Ombre portée pour une lisibilité maximale */

            /* Important: pour que le survol et le clic fonctionnent toujours sur la zone parente */
            pointer-events: none;
        }

        /* Style de la pastille (badge) */
        .zone-label .badge {
            margin-left: 8px; /* Espace entre le texte et la pastille */
            font-size: 1rem; /* Le nombre un peu plus petit que le texte */
        }

        /* --- POSITIONNEMENT DES ZONES --- */
        /* C'est la partie que vous devrez ajuster précisément ! */

        /* Zone "Sécurité" sur le garage */
        #zone-securite {
            top: 48%;
            left: 7%;
            width: 26%;
            height: 40%;
        }

        /* Zone "Température" sur la salle à manger / salon */
        #zone-temperature {
            top: 20%;
            left: 35%;
            width: 30%;
            height: 50%;
        }

        /* Zone "Éclairage" sur les deux chambres */
        #zone-eclairage {
            top: 50%;
            left: 62%;
            width: 35%;
            height: 48%;
        }

        /* Style du tooltip (inchangé) */
        #object-tooltip {
            display: none;
            position: absolute;
            background-color: #333;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            z-index: 1000;
            pointer-events: none;
            max-width: 250px;
        }
    </style>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center h-100">
                <div class="card-body">
                    <h5 class="card-title">Mes Objets Ajoutés</h5>
                    <p class="display-4 fw-bold"><?php echo $stats['objets_count']; ?></p>
                    <a href="objets.php" class="btn btn-primary">Gérer mes objets</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center h-100">
                <div class="card-body">
                    <h5 class="card-title">Mes Points</h5>
                    <p class="display-4 fw-bold"><?php echo $stats['points']; ?></p>
                    <p>Niveau : <span class="badge bg-info"><?php echo htmlspecialchars(ucfirst($stats['niveau'])); ?></span></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center h-100">
                <div class="card-body">
                    <h5 class="card-title">Mon Statut</h5>
                    <p class="display-4 fw-bold"><?php echo htmlspecialchars(ucfirst($_SESSION['role'])); ?></p>
                    <a href="profil.php" class="btn btn-secondary">Voir mon profil</a>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <h4>Actions rapides :</h4>
        <div class="list-group">
            <!-- Lien vers la page des objets connectés -->
            <a href="objets.php" class="list-group-item list-group-item-action">
                Consulter les objets connectés
            </a>
            <!-- Lien vers la page de profil -->
            <a href="profil.php" class="list-group-item list-group-item-action">
                Voir / Modifier mon Profil
            </a>

            <?php
            // Affichage conditionnel des liens en fonction du rôle de l'utilisateur
            if (isset($_SESSION['role'])) :
                // Lien pour ajouter un objet (accessible aux utilisateurs avec rôle 'complexe' ou 'admin')
                if ($_SESSION['role'] === 'complexe' || $_SESSION['role'] === 'admin') :
            ?>
                    <a href="ajouter_objet.php" class="list-group-item list-group-item-action list-group-item-warning">
                        <i class="bi bi-plus-circle-fill"></i> Module de Gestion : Ajouter un Objet
                    </a>
            <?php
                endif;

                // Lien pour les fonctionnalités d'administration (accessible uniquement aux administrateurs)
                if ($_SESSION['role'] === 'admin') :
            ?>
                    <a href="admin_utilisateurs.php" class="list-group-item list-group-item-action list-group-item-danger">
                        <i class="bi bi-shield-lock-fill"></i> Module d'Administration : Gérer les Utilisateurs
                    </a>
                    <a href="admin_ajouter_catalogue.php" class="list-group-item list-group-item-action list-group-item-info">
                        <i class="bi bi-journal-plus"></i> Module d'Administration : Ajouter un appareil au Catalogue
                    </a>
            <?php
                endif;
            endif;
            ?>
        </div>
    </div>

    <div class="mt-5">
        <!-- Bouton de déconnexion -->
        <a href="deconnexion.php" class="btn btn-danger">
            Se déconnecter
        </a>
    </div>
</main>
<script src="assets/js/script.js"></script>

<?php
require_once 'footer.php';
?>