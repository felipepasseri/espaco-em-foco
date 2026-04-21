<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Espaço em Foco Login</title>
	</head>
		<script type="text/javascript">
			function Verificar(){
				erro="";
				if(document.form.email.value==""){
					erro+="Preencha o email\n";
				}
				if(document.form.senha.value==""){
					erro+="Preencha a senha";
				}
				if(erro==""){
					return true;
				}
				else{
					alert(erro);
					return false;
				}
			}
		</script>
	</head>
	<body>
		<div id="conteiner">
			<?php
				if (isset($_GET['erro'])) {
				    echo "<p style='color:red;'>Email ou senha incorretos!</p>";
				}
			?>
			<h1>Logar</h1>
			<form name="form" onsubmit="return Verificar()" method="POST" action="login02.php">
				<input type="text" id="email" name="email" placeholder="email"><br><br>
				<input type="password" id="senha" name="senha" placeholder="Senha"><br>
				<a href="cadastro01.php">Cadastro</a><br><br>
				<input type="submit" id="enviar" name="enviar"><br>
			</form>
		</div>
	</body>
	</html>