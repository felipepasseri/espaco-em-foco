<?php
	$email=$_POST['email'];
	$senha=$_POST['senha'];
	require("BDconn.php");
	$sql = "SELECT email,senha,errosLoginUser FROM user WHERE email = ?";
	$stmt = mysqli_prepare($BDconn,$sql);
	if(!$stmt){
		die("Não foi possível preparar a consulta!");
	}
	if(!mysqli_stmt_bind_param($stmt,"s",$email)){
		die("Não foi possível vincular parâmetros!");
	}
	if(!mysqli_stmt_execute($stmt)){
		die("Não foi possível executar a busca no Banco de dados!");
	}
	if(!mysqli_stmt_bind_result($stmt,$email, $senhaCAD, $errosLoginUser)){
		die("Não foi possível vincular resultados!");
	}
	$fetch = mysqli_stmt_fetch($stmt);
	if(!$fetch){
	    ob_clean();
		header("Location: login01.php?erro=1");
	}
	if(!mysqli_stmt_close($stmt)){
		echo("Não foi possível efetuar a limpeza da conexão. Avise o setor de TI");
		//manda email
	}
	require("cryp2graph2.php");
	if($fetch==null){
	    ob_clean();
		header("Location: login01.php?erro=1");
	}
	else {
		if( ChecaSenha($senha, $senhaCAD)){
			//Usuário correto
			header("Location: ../index.html");
			if($errosLoginUser > 0){
				$sql2 = "UPDATE user SET errosLoginUser = 0 WHERE email = '$email'";
				$dataset2 = mysqli_query($BDconn,$sql2);
			}
			if(!session_start()){
				echo("Não foi possível iniciar a sessão!");
			}
		} else {
			//Aumenta a contagem de errosLoginPessoa, caso > 2, email automático com nova senha
			$errosLoginUser++;
			$sql3 = "UPDATE user SET errosLoginUser = $errosLoginUser WHERE email = '$email'";
			$dataset3 = mysqli_query($BDconn,$sql3);
			if(!$dataset3){
				echo("Não foi possível atualizar a contagem de erros");
			}
			
			if($errosLoginUser>2){
				//manda email com mandarEmail($nomeDestinatario,$To,$Subject,$Message)
				//FAZER DEPOIS
			}
			ob_clean();
			header("Location: login01.php?erro=1");
		} 
	}
?>