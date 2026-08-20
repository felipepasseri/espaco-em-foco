<?php
// Busca todos os dados básicos do usuário de uma só vez

function getUserData($pdo, $email)
{
    $stmt = $pdo->prepare('SELECT nome, sobrenome, nomeDeUsuario, fotoPerfil, bannerPerfil FROM user WHERE email = :email;');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Já garante a foto padrão caso venha vazia
    if ($user && empty($user['fotoPerfil'])) {
        $user['fotoPerfil'] = '/img/user-profile-default.jpg';
    }

    return $user;
}

// Busca os pontos do usuário
function getUserPoints($pdo, $email)
{
    $stmt = $pdo->prepare('SELECT userPoints FROM userPoints WHERE emailPoints = :email;');
    $stmt->execute(['email' => $email]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['userPoints'] : 0;
}

// Busca o nível do usuário
function getUserLevel($pdo, $email)
{
    $stmt = $pdo->prepare('SELECT userLevel FROM userLevel WHERE emailLevel = :email;');
    $stmt->execute(['email' => $email]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['userLevel'] : 1; // Retorna nível 1 por padrão
}

// Conta quantas pessoas o usuário segue
function getFollowingCount($pdo, $email)
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM userFollowers WHERE emailFollower = :email;');
    $stmt->execute(['email' => $email]);
    return $stmt->fetchColumn();
}

// Conta quantos seguidores o usuário tem
function getFollowersCount($pdo, $email)
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM userFollowers WHERE emailFollowed = :email;');
    $stmt->execute(['email' => $email]);
    return $stmt->fetchColumn();
}
// Verifica se o artigo está em cooldown para o usuário
function getArticleCooldown($pdo, $email, $id_artigo) {
    // 1. Pega total de perguntas do artigo
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM quiz_pergunta WHERE id_artigo = :id");
    $stmtTotal->execute(['id' => $id_artigo]);
    $totalPerguntas = (int)$stmtTotal->fetchColumn();
    if ($totalPerguntas === 0) return false;

    // 2. Busca a última tentativa
    $stmtUltima = $pdo->prepare("
        SELECT data_tentativa 
        FROM usuario_progresso 
        WHERE email_usuario = :email AND id_artigo = :id_artigo 
        ORDER BY data_tentativa DESC 
        LIMIT 1
    ");
    $stmtUltima->execute(['email' => $email, 'id_artigo' => $id_artigo]);
    $ultima = $stmtUltima->fetch(PDO::FETCH_ASSOC);

    if (!$ultima) return false; // Nenhuma tentativa

    $dataTentativa = $ultima['data_tentativa'];

    // 3. Pega acertos na tentativa
    $stmtStats = $pdo->prepare("
        SELECT SUM(CASE WHEN status = 'aprovado' THEN 1 ELSE 0 END) as acertos
        FROM usuario_progresso
        WHERE email_usuario = :email AND id_artigo = :id_artigo AND data_tentativa = :dt
    ");
    $stmtStats->execute(['email' => $email, 'id_artigo' => $id_artigo, 'dt' => $dataTentativa]);
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    $acertos = (int)$stats['acertos'];

    $tempoPassado = time() - strtotime($dataTentativa);

    if ($acertos >= ceil($totalPerguntas / 2)) {
        // Aprovou mas não 100%. Cooldown = 3 dias (259200 segundos)
        $cooldownLimit = 259200; 
    } else {
        // Reprovou. Cooldown = 10 min (600 segundos)
        $cooldownLimit = 600;
    }

    if ($tempoPassado < $cooldownLimit) {
        return strtotime($dataTentativa) + $cooldownLimit; // Retorna timestamp de quando acaba
    }

    return false; // Cooldown já passou
}
