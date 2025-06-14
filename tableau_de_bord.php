<?php
session_start();

// Vérification de la connexion de l'utilisateur
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'header.php';
?>

<main class="container mt-4">
    <h1>Tableau de Bord</h1>
    <hr>

    <!-- Message de bienvenue personnalisé -->
    <div class="alert alert-info">
        Bonjour et bienvenue sur votre tableau de bord, <strong><?php echo htmlspecialchars($_SESSION['pseudo']); ?></strong> !
    </div>

    <!-- Description de l'espace utilisateur -->
    <p>Depuis cet espace, vous pourrez bientôt gérer votre maison intelligente.</p>

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
                        <i class="bi bi-shield-lock-fill"></i> Module d'Administration : Lister les Utilisateurs
                    </a>
                    <a href="admin_ajouter_catalogue.php" class="list-group-item list-group-item-action list-group-item-info">
                        <i class="bi bi-journal-plus"></i> Admin : Ajouter un appareil au Catalogue
                    </a>
            <?php
                endif;
            endif;
            ?>
        </div>
    </div>

    <div class="mt-4">
        <!-- Affichage du rôle actuel de l'utilisateur -->
        <p>Votre statut actuel :</p>
        <ul>
            <li>Rôle : <strong><?php echo htmlspecialchars($_SESSION['role']); ?></strong></li>
        </ul>
    </div>

    <div class="mt-5">
        <!-- Bouton de déconnexion -->
        <a href="deconnexion.php" class="btn btn-danger">
            Se déconnecter
        </a>
    </div>
</main>

<?php
require_once 'footer.php';
?>