<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}
require_once __DIR__ . '/../login/verify-user.php';
$userRoles = verificarUsuario($_SESSION['user']);
if ($userRoles['codTypeRoles'] == 0) {
    header("Location: ../userScreen/home-user.php");
    exit;
}

require_once '../config.php';
try {
    $pdo = getDB();
    $sqlBusca = "SELECT * FROM denuncias WHERE status='Em Analise'";
    $stmtBusca = $pdo->prepare($sqlBusca);
    $stmtBusca->execute();

    $denuncias = $stmtBusca->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['erro'] = $e->getMessage();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css" />
    <link rel="stylesheet" href="styleadm.css" />
    <script src="../scripts/abrirDenuncia.js" type="module" defer></script>
    <title>Document</title>
</head>

<body>
    <header id="main-header">
        <?php include __DIR__ . "/../navBar.php"; ?>
    </header>
    <div id="buttonCima">
        <li>
            <a href="AdicionaCard/adicionaCard.php" class="button"><span>Tópicos</span></a>
        </li>
    </div>
    <div id="denuncias">
        <?php if (empty($denuncias)): ?>

            <p>Nenhuma denúncia encontrada.</p>

        <?php else: ?>

            <?php foreach ($denuncias as $denuncia): ?>

                <div class="denuncia">
                    <h2>Denúncia #<?= htmlspecialchars($denuncia['id']) ?></h2>

                    <p>
                        <strong>Denunciado:</strong>
                        <?= htmlspecialchars($denuncia['nome_usuario_alvo']) ?>
                    </p>

                    <p>
                        <strong>Categoria:</strong>
                        <?= htmlspecialchars($denuncia['categoria_denuncia']) ?>
                    </p>

                    <p>
                        <strong>Motivo:</strong>
                        <?= htmlspecialchars($denuncia['motivo']) ?>
                    </p>

                    <p>
                        <strong>Status:</strong>
                        <?= htmlspecialchars($denuncia['status']) ?>
                    </p>
                    <button 
                        class="button analisar-denuncia"
                        data-id="<?= htmlspecialchars($denuncia['id']) ?>">
                        Analisar
                    </button>
                </div>
                <br>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>

    <div id="modalDenuncia" class="modal">
        <div class="modal-conteudo">
            <div class="modal-header">
                <h2>Analisar denúncia #<span id="modal-id"></span></h2>
                <button id="fecharModal" class="fechar-modal">
                    &times;
                </button>
            </div>

            <div class="modal-body">
                <div class="info-denuncia">
                    <div class="campo">
                        <strong>Denunciante</strong>
                        <p id="modal-denunciante"></p>
                    </div>

                    <div class="campo">
                        <strong>Usuário denunciado</strong>
                        <p id="modal-denunciado"></p>
                    </div>

                    <div class="campo">
                        <strong>Categoria</strong>
                        <p id="modal-categoria"></p>
                    </div>

                    <div class="campo">
                        <strong>Motivo</strong>
                        <p id="modal-motivo"></p>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button id="recusarDenuncia" class="btn-recusar">
                    Recusar denúncia
                </button>

                <button id="resolverDenuncia" class="btn-resolver">
                    Resolver denúncia
                </button>
            </div>
        </div>
    </div>
</body>

</html>