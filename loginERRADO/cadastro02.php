<?php
	require("BDconn.php");
	require("cryp2graph2.php");
	
	$email = $_POST['email'];
    $nickname = $_POST['nickname'];
    $senha = $_POST['senha'];

    $senha_cripto = FazSenha($email, $senha);
    $sql_user = "INSERT INTO user(email, nickname, senha, errosLoginUser) VALUES (?,?,?,0)";
    $stmt = mysqli_prepare($BDconn, $sql_user);
    mysqli_stmt_bind_param($stmt, "sss", $email, $nickname, $senha_cripto);
    if (!mysqli_stmt_execute($stmt)) {
        ob_clean();
        header("Location: cadastro01.php?erro=1");
    }
    mysqli_stmt_close($stmt);
    ob_clean();
    header("Location: login01.php");
?>