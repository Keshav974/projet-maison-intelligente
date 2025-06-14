<?php
// Toujours démarrer la session au TOUT début du script
// pour pouvoir accéder aux variables de session.
session_start();

// Vérifier si l'utilisateur est connecté.
// Si la variable de session 'user_id' n'existe pas (ce qui signifie que l'utilisateur
// n'est pas passé par login.php avec succès), on le redirige vers la page de connexion.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit; // Il est crucial d'arrêter l'exécution du script après une redirection.
}

// Inclure l'en-tête de la page (qui contient la structure HTML de début, la navbar, etc.)
require_once 'header.php';
?>

<main class="container mt-4">
    <h1>Tableau de Bord</h1>
    <hr>

    <div class="alert alert-info">
        Bonjour et bienvenue sur votre tableau de bord, <strong><?php echo htmlspecialchars($_SESSION['pseudo']); ?></strong> !
    </div>

    <p>Depuis cet espace, vous pourrez bientôt gérer votre maison intelligente.</p>

    <div class="mt-4">
        <h4>Actions rapides :</h4>
        <div class="list-group">
            <a href="objets.php" class="list-group-item list-group-item-action">
                Consulter les objets connectés
            </a>
            <a href="profil.php" class="list-group-item list-group-item-action">
                Voir / Modifier mon Profil
            </a>
            
            <?php
            // Affichage conditionnel des liens basé sur le rôle de l'utilisateur
            // Vérifier si la variable de session 'role' existe avant de l'utiliser
            if (isset($_SESSION['role'])) :
                // Lien pour le module "Gestion"
                if ($_SESSION['role'] === 'complexe' || $_SESSION['role'] === 'admin') :
            ?>
                    <a href="ajouter_objet.php" class="list-group-item list-group-item-action list-group-item-warning">
                        <i class="bi bi-plus-circle-fill"></i> Module de Gestion : Ajouter un Objet
                    </a>
            <?php
                endif;

                // Lien pour le module "Administration"
                if ($_SESSION['role'] === 'admin') :
            ?>
                    <a href="admin_utilisateurs.php" class="list-group-item list-group-item-action list-group-item-danger">
                        <i class="bi bi-shield-lock-fill"></i> Module d'Administration : Lister les Utilisateurs
                    </a>
            <?php
                endif;
            endif;
            ?>
        </div>
    </div>


    <div class="mt-4">
        <p>Votre statut actuel :</p>
        <ul>
            <li>Rôle : <strong><?php echo htmlspecialchars($_SESSION['role']); ?></strong></li>
            </ul>
    </div>

    <div class="mt-5">
        <a href="deconnexion.php" class="btn btn-danger">
             Se déconnecter
        </a>
        </div>

</main>

<?php
// Inclure le pied de page
require_once 'footer.php';
?>