<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../index.php");
    exit();
}

require_once __DIR__ . '/../../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailAtual = $_SESSION['user'];

    // Dados recebidos
    $novoNome = trim($_POST['nome']);
    $novoSobrenome = trim($_POST['sobrenome']);
    $novoUsername = trim($_POST['username']);
    $novoEmail = trim($_POST['email']);
    $senhaAtualDigitada = $_POST['senhaAtual'];
    $novaSenha = $_POST['senha'];

    try {
        $pdo = getDB();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 1. VERIFICAÇÃO DE SEGURANÇA (A Senha Atual está certa?)
        $stmtVerifica = $pdo->prepare("SELECT senha FROM user WHERE email = :email");
        $stmtVerifica->execute(['email' => $emailAtual]);
        $userDB = $stmtVerifica->fetch(PDO::FETCH_ASSOC);

        if (!$userDB || !password_verify($senhaAtualDigitada, $userDB['senha'])) {
            // Se a senha estiver errada, devolve pra tela com erro
            header("Location: editar-perfil.php?erro=senha");
            exit();
        }

        // 2. Verifica se o novo email já existe
        if ($novoEmail !== $emailAtual) {
            $stmtEmail = $pdo->prepare("SELECT email FROM user WHERE email = :email");
            $stmtEmail->execute(['email' => $novoEmail]);
            if ($stmtEmail->rowCount() > 0) {
                header("Location: editar-perfil.php?erro=email");
                exit();
            }
        }

        // 3. Inicia o pacote de atualização
        $sql = "UPDATE user SET nome = :nome, sobrenome = :sobrenome, nomeDeUsuario = :username, email = :email";
        $params = [
            'nome' => $novoNome,
            'sobrenome' => $novoSobrenome,
            'username' => $novoUsername,
            'email' => $novoEmail,
            'emailAtual' => $emailAtual
        ];

        // Se ele digitou uma nova senha, criptografa e adiciona ao pacote
        if (!empty($novaSenha)) {
            $sql .= ", senha = :senha";
            $params['senha'] = password_hash($novaSenha, PASSWORD_DEFAULT);
        }

        // 4. UPLOAD DE IMAGENS
        $uploadDir = __DIR__ . '/../../img/uploads/banner/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        function processarUpload($fileArray, $prefixo)
        {
            global $uploadDir;
            if ($fileArray['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($fileArray['name'], PATHINFO_EXTENSION);
                $nomeUnico = $prefixo . '_' . uniqid() . '.' . $ext;
                $caminhoCompleto = $uploadDir . $nomeUnico;

                if (move_uploaded_file($fileArray['tmp_name'], $caminhoCompleto)) {
                    return 'img/uploads/banner/' . $nomeUnico;
                }
            }
            return false;
        }

        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $caminhoFoto = processarUpload($_FILES['foto'], 'avatar');
            if ($caminhoFoto) {
                $sql .= ", fotoPerfil = :foto";
                $params['foto'] = $caminhoFoto;
            }
        }

        if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
            $caminhoBanner = processarUpload($_FILES['banner'], 'banner');
            if ($caminhoBanner) {
                $sql .= ", bannerPerfil = :banner";
                $params['banner'] = $caminhoBanner;
            }
        }

        // 5. Executa a Query
        $sql .= " WHERE email = :emailAtual";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Mantém o usuário logado com o email novo caso ele tenha mudado
        $_SESSION['user'] = $novoEmail;

        header("Location: ../home-user.php?status=success");
        exit();
    } catch (PDOException $e) {
        die("Erro interno do servidor: " . $e->getMessage());
    }
}
