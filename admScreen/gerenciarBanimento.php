<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Usuário não autenticado.']);
    exit;
}

require_once '../config.php';
require_once '../login/verify-user.php';

try {
    $pdo = getDB();

    $userroles = verificarUsuario($_SESSION['user']);
    if (!$userroles || $userroles['codTypeRoles'] != 1) {
        throw new Exception('Acesso restrito a administradores.');
    }

    $acao = $_POST['acao'] ?? '';

    // ==========================================
    // REVOGAR BANIMENTO (Desbanir)
    // ==========================================
    if ($acao === 'revogar') {
        $idBan = $_POST['id'] ?? null;
        if (!$idBan) {
            throw new Exception('ID do banimento não informado.');
        }

        $stmt = $pdo->prepare("
            UPDATE banimentos 
            SET ativo = 0, 
                data_revogacao = NOW()
            WHERE id = ? AND ativo = 1
        ");
        $stmt->execute([$idBan]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Banimento não encontrado ou já se encontra inativo.');
        }

        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Banimento revogado com sucesso. O usuário recuperou o acesso ao sistema.'
        ]);
        exit;
    }

    // ==========================================
    // APLICAR BANIMENTO MANUAL
    // ==========================================
    if ($acao === 'aplicar_manual') {
        $identificador = trim($_POST['identificador'] ?? '');
        $tipoBanimento = $_POST['tipo_banimento'] ?? '';
        $motivo = trim($_POST['motivo'] ?? '');
        $dataFimRaw = $_POST['data_fim'] ?? null;

        if (empty($identificador)) {
            throw new Exception('Informe o e-mail ou @nomeDeUsuario do usuário.');
        }

        if (!in_array($tipoBanimento, ['permanente', 'temporario'])) {
            throw new Exception('Tipo de banimento inválido.');
        }

        if (empty($motivo)) {
            throw new Exception('O motivo do banimento é obrigatório.');
        }

        // Localiza usuário por email ou por nomeDeUsuario
        $identificadorLimpo = ltrim($identificador, '@');
        $stmtUser = $pdo->prepare("
            SELECT email, nome, nomeDeUsuario 
            FROM user 
            WHERE email = ? OR nomeDeUsuario = ?
            LIMIT 1
        ");
        $stmtUser->execute([$identificador, $identificadorLimpo]);
        $targetUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$targetUser) {
            throw new Exception("Usuário '{$identificador}' não encontrado.");
        }

        // Não permite banir outro administrador
        $stmtTargetRole = $pdo->prepare("SELECT codTypeRoles FROM userroles WHERE emailRoles = ?");
        $stmtTargetRole->execute([$targetUser['email']]);
        $targetRole = $stmtTargetRole->fetchColumn();
        if ($targetRole == 1) {
            throw new Exception('Não é permitido banir uma conta de administrador.');
        }

        $dataFimSql = null;
        if ($tipoBanimento === 'temporario') {
            if (empty($dataFimRaw)) {
                throw new Exception('Informe a data de término do banimento temporário.');
            }
            $timestampFim = strtotime($dataFimRaw);
            if (!$timestampFim || $timestampFim <= time()) {
                throw new Exception('A data de término deve ser uma data futura.');
            }
            $dataFimSql = date('Y-m-d H:i:s', $timestampFim);
        }

        // Insere o banimento
        $stmtInsert = $pdo->prepare("
            INSERT INTO banimentos (email, tipo_banimento, motivo, admin_responsavel, id_denuncia, data_inicio, data_fim, ativo)
            VALUES (?, ?, ?, ?, NULL, NOW(), ?, 1)
        ");
        $stmtInsert->execute([
            $targetUser['email'],
            $tipoBanimento,
            $motivo,
            $_SESSION['user'],
            $dataFimSql
        ]);

        $tipoTexto = ($tipoBanimento === 'permanente') ? 'permanente' : 'temporário';
        echo json_encode([
            'sucesso' => true,
            'mensagem' => "Banimento {$tipoTexto} aplicado com sucesso a @{$targetUser['nomeDeUsuario']} ({$targetUser['email']})."
        ]);
        exit;
    }

    throw new Exception('Ação não reconhecida.');

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'erro' => $e->getMessage()
    ]);
}

