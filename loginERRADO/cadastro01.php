<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Espaço em Foco Cadastro</title>
</head>
	<script type="text/javascript">
			function Verificar(){
				erro="";
				if(document.form.email.value==""){
					erro+="Preencha o email\n";
				}
				if(document.form.nickname.value==""){
					erro+="Preencha o nickname\n";
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
			<h1>Cadastro</h1>
			<?php
				if (isset($_GET['erro'])) {
				    echo "<p style='color:red;'>Já existe uma conta com esse email!</p>";
				}
			?>
			<form name="form" onsubmit="return Verificar()" method="POST" action="cadastro02.php">
				<input type="text" id="email" name="email" placeholder="email"><br><br>
				<input type="text" id="nickname" name="nickname" placeholder="nickname"><br><br>
				<input type="password" id="senha" name="senha" placeholder="Senha"><br><br>
				<input type="submit" id="enviar" name="enviar"><br>
			</form>
		</div>
	</body>
</html>