<?php

function verificarUsuario($email)
{
    require_once __DIR__ . '/../config.php';
    try {
        $pdo = getDB();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare('SELECT codTypeRoles FROM userRoles WHERE emailRoles = :email');
        $stmt->execute(['email' => $email]);
        $userRole = $stmt->fetch();
        return $userRole;
    } catch (PDOException $e) {
        echo 'Erro: ' . $e->getMessage();
    }
}
