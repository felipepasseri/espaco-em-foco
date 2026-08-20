<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit();
}

require_once __DIR__ . '/../config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['id_pergunta']) || !isset($data['resposta_dada']) || !isset($data['tipo'])) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit();
}

$id_pergunta = (int)$data['id_pergunta'];
$resposta_dada = $data['resposta_dada'];
$tipo = $data['tipo'];

if (!isset($_SESSION['quiz_atual']) || !is_array($_SESSION['quiz_atual']['respostas'])) {
    echo json_encode(['success' => false, 'error' => 'Sessão de quiz inválida. Reinicie a página.']);
    exit();
}

try {
    $pdo = getDB();
    $is_correct = false;

    if ($tipo === 'multipla_escolha') {
        // resposta_dada é o id da alternativa
        $stmt = $pdo->prepare("SELECT is_correct FROM quiz_resposta WHERE id = :resposta_id AND id_pergunta = :pergunta_id AND tipo_pergunta = 'multipla_escolha'");
        $stmt->execute(['resposta_id' => $resposta_dada, 'pergunta_id' => $id_pergunta]);
        $alt = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($alt && $alt['is_correct'] == 1) {
            $is_correct = true;
        }
    } else if ($tipo === 'lacuna') {
        // Em lacuna, as respostas certas são salvas em resposta_lacuna
        // Para simplificar, comparamos ignorando case, já que lacunas podem ser digitadas com letras maiusculas
        $stmt = $pdo->prepare("SELECT resposta_lacuna FROM quiz_resposta WHERE id_pergunta = :pergunta_id AND tipo_pergunta = 'lacuna'");
        $stmt->execute(['pergunta_id' => $id_pergunta]);
        $lacunas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $respostasEsperadas = [];
        foreach ($lacunas as $lacuna) {
            $respostasEsperadas[] = mb_strtolower(trim($lacuna['resposta_lacuna']), 'UTF-8');
        }

        // Se o usuário digitou exatamente (case-insensitive) o que era esperado (nesta v1 simplificada para 1 lacuna ou múltiplas separadas por vírgula)
        // Como o JS enviará a resposta do(s) input(s), se houver múltiplos inputs, o front envia formatado, ou envia um array.
        // Assumiremos que o front envia as respostas separadas por vírgula se houver mais de uma, e comparamos.
        // Para ser robusto, dividimos por vírgula:
        $respUsuarioArr = explode(',', mb_strtolower(trim($resposta_dada), 'UTF-8'));
        $respUsuarioArr = array_map('trim', $respUsuarioArr);

        // Verifica se todas as esperadas foram respondidas corretamente na mesma ordem
        if ($respostasEsperadas === $respUsuarioArr) {
            $is_correct = true;
        }
    }

    // Salva na sessão
    // A chave é o id da pergunta, para não ter duplicações caso ele envie a mesma pergunta duas vezes
    $_SESSION['quiz_atual']['respostas'][$id_pergunta] = [
        'id_pergunta' => $id_pergunta,
        'resposta_dada' => $resposta_dada,
        'is_correct' => $is_correct
    ];

    echo json_encode(['success' => true, 'is_correct' => $is_correct]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro interno na validação.']);
}
