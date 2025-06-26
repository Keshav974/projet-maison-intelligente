<?php
function updateUserLevel($userId, $db) {
    try {
        // Récupérer les informations actuelles de l'utilisateur (points, niveau, rôle)
        $sql_get = "SELECT points, niveau, role FROM utilisateurs WHERE id = :id";
        $stmt_get = $db->prepare($sql_get);
        $stmt_get->execute([':id' => $userId]);
        $user = $stmt_get->fetch();

        if (!$user) {
            return; // Arrêter si l'utilisateur n'existe pas
        }

        $points = $user['points'];
        $current_level = $user['niveau'];
        $current_role = $user['role'];

        // Déterminer le nouveau niveau et rôle en fonction des points
        $new_level = 'débutant';
        $new_role = 'simple';

        if ($points >= 50) {
            $new_level = 'expert';
            $new_role = 'admin';
        } elseif ($points >= 30) {
            $new_level = 'avancé';
            $new_role = 'complexe';
        } elseif ($points >= 10) {
            $new_level = 'intermédiaire';
            $new_role = 'simple';
        }

        // Mettre à jour la base de données si le niveau ou le rôle a changé
        if ($new_level !== $current_level || $new_role !== $current_role) {
            $sql_update = "UPDATE utilisateurs SET niveau = :niveau, role = :role WHERE id = :id";
            $stmt_update = $db->prepare($sql_update);
            $stmt_update->execute([
                ':niveau' => $new_level,
                ':role' => $new_role,
                ':id' => $userId
            ]);

            // Mettre à jour la session si l'utilisateur connecté est celui modifié
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                $_SESSION['role'] = $new_role;
            }
        }

    } catch (PDOException $e) {
        // Gestion des erreurs silencieuse
    }
}

function logActivity($userId, $typeAction, $description, $db, $objetId = null) {
    try {
        $sql = "INSERT INTO logs_activite (utilisateur_id, type_action, description_action, objet_id) 
                VALUES (:uid, :type, :desc, :oid)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':uid' => $userId,
            ':type' => $typeAction,
            ':desc' => $description,
            ':oid' => $objetId 
        ]);
    } catch (PDOException $e) {
        error_log("Erreur de journalisation: " . $e->getMessage());
    }
}

function incrementerCompteurAction($userId, $db) {
    try {
        $stmt = $db->prepare("UPDATE utilisateurs SET nombre_actions = nombre_actions + 1 WHERE id = :id");
        $stmt->execute([':id' => $userId]);
    } catch (PDOException $e) {
        // Gérer l'erreur silencieusement pour ne pas bloquer l'utilisateur
        error_log('Erreur lors de l\'incrémentation du compteur d\'actions: ' . $e->getMessage());
    }
}
?>


