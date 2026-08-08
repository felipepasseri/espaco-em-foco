<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit();
}

require_once __DIR__ . '/../config.php';

$email = $_SESSION['user'];

try {
    $pdo = getDB();

    // Pega username
    $stmtMe = $pdo->prepare("SELECT nomeDeUsuario FROM user WHERE email = :email");
    $stmtMe->execute(['email' => $email]);
    $myUsername = $stmtMe->fetchColumn();

    if (!$myUsername) {
        echo json_encode(['success' => false, 'error' => 'Usuário não encontrado']);
        exit();
    }

    $topic = $_POST['topic'] ?? null;
    $titulo = trim($_POST['titulo'] ?? '');
    $conteudo = $_POST['conteudo'] ?? '';

    if (!$topic || !$titulo || !$conteudo) {
        echo json_encode(['success' => false, 'error' => 'Todos os campos são obrigatórios']);
        exit();
    }

    // Validação
    $topicsValidos = ['Planetas', 'Estrelas', 'Galáxias', 'Cosmologia', 'Outros'];
    if (!in_array($topic, $topicsValidos)) {
        echo json_encode(['success' => false, 'error' => 'Tópico inválido']);
        exit();
    }

    $pdo->beginTransaction();

    // Insere o post (avaliacao_adm = 'Em Analise' por padrão)
    $stmtPost = $pdo->prepare("
        INSERT INTO postagens_forum (nome_usuario_post, topic_post, titulo_post, desc_post, likes_post, deslikes_post, reposts)
        VALUES (:username, :topic, :titulo, :conteudo, 0, 0, 0)
    ");
    $stmtPost->execute([
        'username' => $myUsername,
        'topic' => $topic,
        'titulo' => $titulo,
        'conteudo' => $conteudo
    ]);
    $postId = $pdo->lastInsertId();

    // Upload de imagens (até 5)
    $uploadDir = __DIR__ . '/../img/uploads/forum/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (isset($_FILES['imagens'])) {
        $files = $_FILES['imagens'];
        $maxFiles = min(5, count($files['name']));

        for ($i = 0; $i < $maxFiles; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (!in_array(strtolower($ext), $allowedExts)) {
                    continue;
                }

                // Nome aleatório
                $randomName = uniqid('', true) . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $destPath = $uploadDir . $randomName;

                if (move_uploaded_file($files['tmp_name'][$i], $destPath)) {
                    $dbPath = 'img/uploads/forum/' . $randomName;
                    $stmtImg = $pdo->prepare("INSERT INTO imgs_post (id_post, img_caminho) VALUES (:postId, :caminho)");
                    $stmtImg->execute(['postId' => $postId, 'caminho' => $dbPath]);
                }
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'postId' => $postId]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Erro interno']);
}
