<?php
session_start();

// Importe seu arquivo de conexão aqui
// require_once '../conexao.php'; 
require_once __DIR__ . "/../../config.php";
$pdo = getDB();

// Verifica se o usuário está logado (assumindo que $_SESSION['user'] guarda o email do usuário)
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$emailSessao = $_SESSION['user'];

// 1. RECEBER OS DADOS
$nickname = isset($_POST['nickname']) ? trim($_POST['nickname']) : '';
$caminhoFotoDB = 'img/user-profile-default.jpg'; // Caminho padrão caso o usuário não envie foto

// ==========================================
// 2. PROCESSAR A FOTO DE PERFIL
// ==========================================
if (isset($_FILES['profilePic']) && $_FILES['profilePic']['error'] === UPLOAD_ERR_OK) {
    $foto = $_FILES['profilePic'];
    $extensao = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));
    $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];

    if (in_array($extensao, $extensoesPermitidas)) {
        // Cria um nome único para a imagem
        $novoNomeFoto = uniqid() . '_' . time() . '.' . $extensao;

        // dirname(__DIR__, 2) pega o caminho absoluto deste arquivo e volta 2 pastas cravadas.
        // Fica algo como: C:/xampp/htdocs/seu_projeto/img/
        $pastaDestino = dirname(__DIR__, 2) . '/img/uploads/profile/';

        // Se a pasta 'img' não existir na raiz, o PHP cria ela agora mesmo
        if (!is_dir($pastaDestino)) {
            mkdir($pastaDestino, 0777, true);
        }

        $destinoFisico = $pastaDestino . $novoNomeFoto;

        // Tenta mover o arquivo
        if (move_uploaded_file($foto['tmp_name'], $destinoFisico)) {
            $caminhoFotoDB = 'img/uploads/profile/' . $novoNomeFoto;
        } else {
            // Se ainda assim der erro, o código vai parar e te mostrar exatamente qual caminho ele tentou usar
            die("Erro fatal: Não foi possível salvar a imagem no caminho: " . $destinoFisico);
        }
    }
}

// ==========================================
// 3. PROCESSAR O NICKNAME
// ==========================================
if (!empty($nickname)) {
    // Verifica se o nickname digitado já existe no banco
    $stmt = $pdo->prepare("SELECT nomeDeUsuario FROM user WHERE nomeDeUsuario = :nickname");
    $stmt->bindValue(':nickname', $nickname);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // Se já existe, redireciona de volta para a tela de cadastro com o erro
        header("Location: cadastro03.php?erro=nickname_exists");
        exit();
    }
} else {
    // Se o usuário não preencheu o nickname, gera um automático baseado no nome
    // Busca o nome do usuário no banco (Ajuste as colunas 'nome' e 'sobrenome' se necessário)
    $stmt = $pdo->prepare("SELECT nome, sobrenome FROM user WHERE email = :email");
    $stmt->bindValue(':email', $emailSessao);
    $stmt->execute();
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Junta nome e sobrenome, tudo em minúsculo e tira os espaços
    $nomeBase = strtolower(trim($userData['nome'] . $userData['sobrenome']));
    $nomeBase = preg_replace('/[^a-z0-9]/', '', $nomeBase); // Limpa caracteres especiais

    $nickname = $nomeBase;
    $nicknameUnico = false;

    // Loop que garante que o nickname automático será único
    while (!$nicknameUnico) {
        $stmtCheck = $pdo->prepare("SELECT email FROM user WHERE nomeDeUsuario = :nickname");
        $stmtCheck->bindValue(':nickname', $nickname);
        $stmtCheck->execute();

        if ($stmtCheck->rowCount() == 0) {
            $nicknameUnico = true; // Encontrou um nome livre!
        } else {
            // Se já existir, adiciona um número aleatório no final e testa de novo
            $nickname = $nomeBase . rand(10, 9999);
        }
    }
}

// ==========================================
// 4. SALVAR TUDO NO BANCO DE DADOS
// ==========================================
// (Assumindo que a coluna da foto se chama 'fotoPerfil'. Ajuste se for diferente)
$stmtUpdate = $pdo->prepare("UPDATE user SET nomeDeUsuario = :nickname, fotoPerfil = :foto WHERE email = :email");
$stmtUpdate->bindValue(':nickname', $nickname);
$stmtUpdate->bindValue(':foto', $caminhoFotoDB);
$stmtUpdate->bindValue(':email', $emailSessao);

if ($stmtUpdate->execute()) {
    // Sucesso! Redireciona para a home do usuário
    header("Location: ../../userScreen/home-user.php");
    exit();
} else {
    echo "Ocorreu um erro ao atualizar o perfil. Tente novamente.";
}
