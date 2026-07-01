<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
}
require_once __DIR__ . '/../login/verify-user.php';
$userRoles = verificarUsuario($_SESSION['user']);
if ($userRoles['codTypeRoles'] == 0) {
    header("Location: ../userScreen/home-user.php");
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css" />
    <title>Document</title>
</head>

<body>
    <header id="main-header">
        <?php include __DIR__ . "/../navBar.php"; ?>
    </header>
</body>

</html>