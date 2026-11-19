
<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

function responder(int $status, array $dados): void {
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    /*if (!isset($_SESSION['user'])) {
        responder(401, [
            'sucesso' => false,
            'mensagem' => 'Sua sessão expirou. Entre novamente.'
        ]);
    }

    require_once __DIR__ . '/../login/verify-user.php';

    $userroles = verificarUsuario($_SESSION['user']);

    if (!$userroles || (int)($userroles['codTypeRoles'] ?? 0) === 0) {
        responder(403, [
            'sucesso' => false,
            'mensagem' => 'Você não tem permissão para realizar esta ação.'
        ]);
    }*/

    require_once __DIR__ . '/../config.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responder(405, [
            'sucesso' => false,
            'mensagem' => 'Método não permitido.'
        ]);
    }

    $dados = json_decode(file_get_contents('php://input'), true);

    if (!is_array($dados)) {
        responder(400, [
            'sucesso' => false,
            'mensagem' => 'Dados JSON inválidos.'
        ]);
    }

    $id = filter_var($dados['id'] ?? null, FILTER_VALIDATE_INT);
    $acao = $dados['acao'] ?? '';

    $statusPermitidos = [
        'aceitar' => 'Aprovado',
        'recusar' => 'Reprovado'
    ];

    if (!$id || !isset($statusPermitidos[$acao])) {
        responder(400, [
            'sucesso' => false,
            'mensagem' => 'ID ou ação inválida.'
        ]);
    }

    $pdo = getDB();

    $sql = "
        UPDATE postagens_forum
        SET avaliacao_adm = :novo_status
        WHERE id = :id
          AND avaliacao_adm = 'Em Analise'
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':novo_status' => $statusPermitidos[$acao],
        ':id' => $id
    ]);

    if ($stmt->rowCount() === 0) {
        responder(409, [
            'sucesso' => false,
            'mensagem' => 'Post não encontrado, já analisado ou sem alterações.'
        ]);
    }

    responder(200, [
        'sucesso' => true,
        'mensagem' => $acao === 'aceitar'
            ? 'Post aceito com sucesso!'
            : 'Post recusado com sucesso!'
    ]);

} catch (Throwable $e) {
    error_log(
        'Erro ao atualizar post: ' .
        get_class($e) . ': ' . $e->getMessage()
    );

    responder(500, [
        'sucesso' => false,
        'mensagem' => 'Erro interno ao atualizar o post. Confira o log do PHP.'
    ]);
}
