<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma Maison Intelligente</title>
    <!-- Inclusion de Bootstrap pour le style -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <!-- Inclusion du fichier CSS personnalisé -->
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <header>
            <!-- Titre principal de la page -->
            <h1>Bienvenue sur la plateforme de Ma Maison Intelligente</h1>
        </header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <!-- Logo et lien vers la page d'accueil -->
                <a class="navbar-brand" href="index.php">Ma Maison Intelligente</a>
                <!-- Bouton pour afficher/cacher le menu sur les petits écrans -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <!-- Lien vers la page d'accueil -->
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Accueil</a>
                        </li>
                        <!-- Lien vers le catalogue -->
                        <li class="nav-item">
                            <a class="nav-link" href="catalogue.php">Catalogue</a>
                        </li>
                        <?php if (isset($_SESSION['user_id'])) : ?>
                            <!-- Lien vers le tableau de bord (visible uniquement si connecté) -->
                            <li class="nav-item">
                                <a class="nav-link" href="tableau_de_bord.php">Tableau de Bord</a>
                            </li>
                            <!-- Lien vers les objets connectés (visible uniquement si connecté) -->
                            <li class="nav-item">
                                <a class="nav-link" href="objets.php">Objets Connectés</a>
                            </li>
                            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'complexe' || $_SESSION['role'] === 'admin')) : ?>
                                <!-- Lien pour ajouter un objet (visible pour les rôles 'complexe' et 'admin') -->
                                <li class="nav-item">
                                    <a class="nav-link" href="ajouter_objet.php">Ajouter Objet</a>
                                </li>
                            <?php endif; ?>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') : ?>
                                <!-- Lien pour gérer les utilisateurs (visible uniquement pour le rôle 'admin') -->
                                <li class="nav-item">
                                    <a class="nav-link" href="admin_utilisateurs.php">Admin Utilisateurs</a>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                        <?php if (isset($_SESSION['user_id'])) : ?>
                            <!-- Lien vers le profil (visible uniquement si connecté) -->
                            <li class="nav-item">
                                <a class="nav-link" href="profil.php">
                                    <i class="bi bi-person-fill"></i> <?php echo htmlspecialchars($_SESSION['pseudo']); ?>
                                </a>
                            </li>
                            <!-- Lien pour se déconnecter (visible uniquement si connecté) -->
                            <li class="nav-item">
                                <a class="nav-link" href="deconnexion.php">
                                    <i class="bi bi-box-arrow-right"></i> Déconnexion
                                </a>
                            </li>
                        <?php else : ?>
                            <!-- Lien vers la page d'inscription (visible uniquement si non connecté) -->
                            <li class="nav-item">
                                <a class="nav-link" href="inscription.php">Inscription</a>
                            </li>
                            <!-- Lien vers la page de connexion (visible uniquement si non connecté) -->
                            <li class="nav-item">
                                <a class="nav-link" href="login.php">Connexion</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>