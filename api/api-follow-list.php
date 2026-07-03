    <?php
    session_start();
    header('Content-Type: application/json');

    if (!isset($_SESSION['user']) || !isset($_GET['type'])) {
        echo json_encode([]);
        exit();
    }

    require_once __DIR__ . '/../config.php';
    $email = $_SESSION['user'];
    $type = $_GET['type'];

    try {
        $pdo = getDB();

        if ($type === 'followers') {
            $stmt = $pdo->prepare("
            SELECT u.email, u.nome, u.sobrenome, u.nomeDeUsuario, u.fotoPerfil,
                   (SELECT COUNT(*) FROM userFollowers uf2 WHERE uf2.emailFollower = :me AND uf2.emailFollowed = u.email) as segue_de_volta
            FROM userFollowers uf
            JOIN user u ON uf.emailFollower = u.email
            WHERE uf.emailFollowed = :me
        ");
        } else {
            $stmt = $pdo->prepare("
            SELECT u.email, u.nome, u.sobrenome, u.nomeDeUsuario, u.fotoPerfil
            FROM userFollowers uf
            JOIN user u ON uf.emailFollowed = u.email
            WHERE uf.emailFollower = :me
        ");
        }

        $stmt->execute(['me' => $email]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($users);
    } catch (PDOException $e) {
        echo json_encode([]);
    }
    ?>