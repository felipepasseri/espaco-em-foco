<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Usuário não autenticado.'
    ]);
    exit;
}

require_once '../config.php';
require_once '../login/verify-user.php';
require_once '../email.php';

try {
    $pdo = getDB();

    // Validação de permissão de Administrador
    $userroles = verificarUsuario($_SESSION['user']);
    if (!$userroles || $userroles['codTypeRoles'] != 1) {
        throw new Exception('Acesso restrito a administradores.');
    }

    $id = $_POST['id'] ?? null;
    $acao = $_POST['acao'] ?? null;
    $observacao = trim($_POST['observacao'] ?? '');
    $dataFimRaw = $_POST['data_fim'] ?? null;

    if (!$id) {
        throw new Exception('ID da denúncia não informado.');
    }

    if (!$acao) {
        throw new Exception('Nenhuma ação selecionada.');
    }

    if (empty($observacao)) {
        throw new Exception('A observação do administrador é obrigatória.');
    }

    $acoesPermitidas = [
        'aviso_email',
        'banir_permanente',
        'banir_personalizado'
    ];

    if (!in_array($acao, $acoesPermitidas)) {
        throw new Exception('Ação selecionada é inválida.');
    }

    // Busca a denúncia
    $sql = "SELECT * FROM denuncias WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $denuncia = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$denuncia) {
        throw new Exception('Denúncia não encontrada.');
    }

    $nomeUsuario = $denuncia['nome_usuario_alvo'];

    // Busca o e-mail do usuário alvo
    $sql = "SELECT email, nome FROM user WHERE nomeDeUsuario = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nomeUsuario]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || empty($usuario['email'])) {
        throw new Exception("Usuário alvo '{$nomeUsuario}' não encontrado no sistema.");
    }

    $emailAlvo = $usuario['email'];
    $adminEmail = $_SESSION['user'];

    $mensagem = "Olá, " . $nomeUsuario . ".\n\n";
    $mensagem .= "A equipe do Espaço em Foco analisou uma ocorrência relacionada à sua conta e determinou a seguinte medida administrativa:\n\n";

    $resolucaoAcao = '';

    // ==========================================
    // OPÇÃO 1: AVISO POR E-MAIL (Sem banimento)
    // ==========================================
    if ($acao === 'aviso_email') {
        $resolucaoAcao = 'Aviso por e-mail';
        $mensagem .= "Tipo de Ação: AVISO EDUCATIVO\n";
        $mensagem .= "Este e-mail é uma advertência formal sobre conduta e regras da plataforma. Caso novas infrações ocorram, medidas disciplinares mais severas poderão ser aplicadas.\n\n";
    }

    // ==========================================
    // OPÇÃO 2: BANIMENTO PERMANENTE
    // ==========================================
    if ($acao === 'banir_permanente') {
        $resolucaoAcao = 'Banimento permanente';
        $mensagem .= "Tipo de Ação: BANIMENTO PERMANENTE\n";
        $mensagem .= "Sua conta foi banida permanentemente da nossa plataforma por violação das diretrizes da comunidade. O acesso aos recursos foi revogado por tempo indeterminado.\n\n";

        // Registra banimento permanente
        $sqlBan = "INSERT INTO banimentos 
            (email, tipo_banimento, motivo, admin_responsavel, id_denuncia, data_inicio, data_fim, ativo)
            VALUES (?, 'permanente', ?, ?, ?, NOW(), NULL, 1)";
        $stmtBan = $pdo->prepare($sqlBan);
        $stmtBan->execute([$emailAlvo, $observacao, $adminEmail, $id]);
    }

    // ==========================================
    // OPÇÃO 3: BANIMENTO PERSONALIZADO
    // ==========================================
    if ($acao === 'banir_personalizado') {
        if (empty($dataFimRaw)) {
            throw new Exception('Informe a data e horário de término do banimento.');
        }

        $timestampFim = strtotime($dataFimRaw);
        if (!$timestampFim || $timestampFim <= time()) {
            throw new Exception('A data de término do banimento deve ser uma data futura.');
        }

        $dataFimSql = date('Y-m-d H:i:s', $timestampFim);
        $dataFimFormatada = date('d/m/Y \à\s H:i', $timestampFim);

        $resolucaoAcao = 'Banimento temporário';
        $mensagem .= "Tipo de Ação: BANIMENTO TEMPORÁRIO\n";
        $mensagem .= "Sua conta foi temporariamente suspensa por violação das diretrizes da comunidade. Seu acesso será restabelecido automaticamente em: " . $dataFimFormatada . ".\n\n";

        // Registra banimento personalizado
        $sqlBan = "INSERT INTO banimentos 
            (email, tipo_banimento, motivo, admin_responsavel, id_denuncia, data_inicio, data_fim, ativo)
            VALUES (?, 'temporario', ?, ?, ?, NOW(), ?, 1)";
        $stmtBan = $pdo->prepare($sqlBan);
        $stmtBan->execute([$emailAlvo, $observacao, $adminEmail, $id, $dataFimSql]);
    }

    // Observação do administrador
    $mensagem .= "Justificativa da Administração:\n";
    $mensagem .= $observacao . "\n\n";
    $mensagem .= "Atenciosamente,\nEquipe de Moderação - Espaço em Foco";

    // Atualiza status da denúncia para resolvida/aprovada
    $sqlDenuncia = "UPDATE denuncias
                    SET status = 'Aprovado',
                        resolucao_acao = ?,
                        resolucao_obs = ?,
                        data_resolucao = NOW(),
                        admin_responsavel = ?
                    WHERE id = ?";
    $stmtDenuncia = $pdo->prepare($sqlDenuncia);
    $stmtDenuncia->execute([$resolucaoAcao, $observacao, $adminEmail, $id]);

    // Envio do e-mail com fallback seguro
    $emailStatus = 'E-mail enviado ao usuário.';
    try {
        mandarEmail(
            $nomeUsuario,
            $emailAlvo,
            "Notificação Disciplinar - Espaço em Foco",
            $mensagem
        );
    } catch (Throwable $eMail) {
        error_log("Aviso: Falha ao enviar e-mail disciplinar para {$emailAlvo}: " . $eMail->getMessage());
        $emailStatus = 'Ação registrada, mas o envio do e-mail falhou (servidor de e-mail indisponível).';
    }

    echo json_encode([
        'sucesso' => true,
        'mensagem' => "Denúncia resolvida com sucesso ({$resolucaoAcao}). {$emailStatus}"
    ]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'erro' => $e->getMessage()
    ]);
}
