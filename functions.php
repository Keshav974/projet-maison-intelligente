<?php
/**
 * Met à jour le niveau et le rôle d'un utilisateur en fonction de ses points.
 *
 * @param int $userId L'ID de l'utilisateur à vérifier.
 * @param PDO $db L'objet de connexion à la base de données.
 * @return void
 */
function updateUserLevel($userId, $db) {
    try {
        // 1. Récupérer les points, le niveau et le rôle actuels de l'utilisateur.
        $sql_get = "SELECT points, niveau, role FROM utilisateurs WHERE id = :id";
        $stmt_get = $db->prepare($sql_get);
        $stmt_get->execute([':id' => $userId]);
        $user = $stmt_get->fetch();

        if (!$user) {
            return; // Si l'utilisateur n'est pas trouvé, on ne fait rien.
        }

        $points = $user['points'];
        $current_level = $user['niveau'];
        $current_role = $user['role'];

        // 2. Déterminer le nouveau niveau et rôle en fonction des points.
        $new_level = 'débutant'; // Valeur par défaut
        $new_role = 'simple';   // Valeur par défaut

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

        // 3. Si le niveau ou le rôle a changé, on met à jour la base de données.
        if ($new_level !== $current_level || $new_role !== $current_role) {
            $sql_update = "UPDATE utilisateurs SET niveau = :niveau, role = :role WHERE id = :id";
            $stmt_update = $db->prepare($sql_update);
            $stmt_update->execute([
                ':niveau' => $new_level,
                ':role' => $new_role,
                ':id' => $userId
            ]);


            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                $_SESSION['role'] = $new_role;
            }
        }

    } catch (PDOException $e) {

    }
}
?>