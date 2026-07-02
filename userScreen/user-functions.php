<?php
// Busca todos os dados básicos do usuário de uma só vez

function getUserData($pdo, $email)
{
    $stmt = $pdo->prepare('SELECT nome, sobrenome, nomeDeUsuario, fotoPerfil FROM user WHERE email = :email;');
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
