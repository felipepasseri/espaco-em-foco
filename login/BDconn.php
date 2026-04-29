<?php
	ini_set('display_errors',1); // mostra a linha do erro
	mysqli_report(MYSQLI_REPORT_ERROR); // Permite ter erros
	$BDconn=mysqli_connect("localhost","guil4713_espacoemfoco","@Espacoemfoco_Laika2026","guil4713_espacoemfoco");
	if(!$BDconn){
		die("Erro ao conectar");
	}
?>