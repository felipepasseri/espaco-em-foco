<?php

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Usuário não autenticado.'
    ]);
    exit;
}

require_once '../config.php';
require_once '../email.php';

try {

    $pdo = getDB();

    $id = $_POST['id'] ?? null;
    $acao = $_POST['acao'] ?? null;
    $observacao = $_POST['observacao'] ?? null;


    if (!$id) {
        throw new Exception('ID da denúncia não informado.');
    }

    if (!$acao) {
        throw new Exception('Nenhuma ação selecionada.');
    }

    if (!$observacao) {
        throw new Exception('Observação não informada.');
    }


    $acoesPermitidas = [
        'aviso_email',
        'banir_7_dias',
        'banir_permanente'
    ];


    if (!in_array($acao, $acoesPermitidas)) {
        throw new Exception('Ação inválida.');
    }


    /*
     * Busca a denúncia
     */

    $sql = "SELECT * FROM denuncias WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    $denuncia = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$denuncia) {
        throw new Exception('Denúncia não encontrada.');
    }


    /*
     * Nome do usuário denunciado
     */

    $nomeUsuario = $denuncia['nome_usuario_alvo'];


    /*
     * Define a ação que será salva
     */

    switch ($acao) {

        case 'aviso_email':
            $acaoBanco = 'Aviso por email';
            break;

        case 'banir_7_dias':
            $acaoBanco = 'Banido por 7 dias';
            break;

        case 'banir_permanente':
            $acaoBanco = 'Banido permanentemente';
            break;

        default:
            throw new Exception('Ação inválida.');
    }


    /*
     * Marca a denúncia como aprovada/resolvida
     */

    $sql = "UPDATE denuncias
            SET status = 'Aprovado'
            WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);


    /*
     * Busca o e-mail do usuário
     */

    $sql = "SELECT email FROM user WHERE nomeDeUsuario = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nomeUsuario]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario['email']) {
        throw new Exception('Usuário não encontrado.');
    }


    $email = $usuario['email'];


    /*
     * Monta a mensagem
     */

    $mensagem = "Olá, " . $nomeUsuario . ".\n\n";

    $mensagem .= "A equipe do Espaço em Foco veio te informar que, devido a uma má conduta e/ou descumprimento das regras da plataforma, ";


    /*
     * Banimento de 7 dias
     */

    if ($acao === 'banir_7_dias') {

        $dataFim = date('d/m/Y', strtotime('+7 days'));

        $mensagem .= "você foi banido por 7 dias e não poderá utilizar nosso site até o dia "
            . $dataFim
            . ".\n\n";

        $sql = "INSERT INTO banimentos
            (email, tipo_banimento, data_inicio, data_fim)
            VALUES (?, '7_dias', NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY))
            ON DUPLICATE KEY UPDATE
                tipo_banimento = '7_dias',
                data_inicio = NOW(),
                data_fim = DATE_ADD(NOW(), INTERVAL 7 DAY)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
    }


    /*
     * Banimento permanente
     */

    if ($acao === 'banir_permanente') {
        $mensagem .= "você foi banido permanentemente do nosso site e não poderá utilizar ou criar outra conta.\n\n";

        $sql = "INSERT INTO banimentos
            (email, tipo_banimento, data_inicio, data_fim)
            VALUES (?, 'permanente', NOW(), NULL)
            ON DUPLICATE KEY UPDATE
                tipo_banimento = 'permanente',
                data_inicio = NOW(),
                data_fim = NULL";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
    }


    /*
     * Aviso por e-mail
     */

    if ($acao === 'aviso_email') {
        $mensagem .= "este e-mail está sendo enviado como um aviso prévio. Caso sejam recebidas novas denúncias contra você, punições maiores poderão ser aplicadas.\n\n";
    }


    /*
     * Observação do administrador
     */

    $mensagem .= "Observação da equipe:\n";
    $mensagem .= $observacao . "\n\n";

    $mensagem .= "Atenciosamente,\n";
    $mensagem .= "Equipe Espaço em Foco";


    /*
     * Envia o e-mail
     */

    mandarEmail(
        $nomeUsuario,
        $email,
        "Punição Espaço em Foco",
        $mensagem
    );


    /*
     * Retorna resposta para o JavaScript
     */

    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Denúncia resolvida e e-mail enviado com sucesso.'
    ]);


} catch (Throwable $e) {

    http_response_code(400);

    echo json_encode([
        'sucesso' => false,
        'erro' => $e->getMessage()
    ]);
}