<?php
session_start();
// Define que este arquivo vai responder em formato JSON (para o Javascript ler facilmente)
header('Content-Type: application/json');

// Segurança: Bloqueia se não estiver logado
if (!isset($_SESSION['user'])) {
    echo json_encode(['erro' => 'Usuário não autenticado']);
    exit();
}

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../calcularXp.php'; // Importa para podermos checar se ele upou de nível

$email = $_SESSION['user'];

// Recebe os dados do Javascript (O 'fetch' envia no corpo da requisição)
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['erro' => 'Dados inválidos']);
    exit();
}

$pergunta_id = $data['pergunta_id'];
$artigo_id = $data['artigo_id'];
$resposta_usuario = $data['resposta'];
$tipo = $data['tipo'];

try {
    $pdo = getDB();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $acertou = false;

    // ==========================================
    // 1. VALIDAÇÃO DA RESPOSTA
    // ==========================================
    if ($tipo === 'multipla_escolha') {
        // Busca na tabela de alternativas se a que ele escolheu é a correta
        $stmt = $pdo->prepare("SELECT is_correct FROM quiz_alternativa WHERE id = :resposta_id AND id_pergunta = :pergunta_id");
        $stmt->execute(['resposta_id' => $resposta_usuario, 'pergunta_id' => $pergunta_id]);
        $alt = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($alt && $alt['is_correct'] == 1) {
            $acertou = true;
        }
    } else if ($tipo === 'lacuna') {
        // Busca a palavra-chave no banco para comparar com o que ele digitou
        $stmt = $pdo->prepare("SELECT resposta_esperada FROM quiz_pergunta WHERE id = :pergunta_id");
        $stmt->execute(['pergunta_id' => $pergunta_id]);
        $perg = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($perg) {
            // Remove espaços nas pontas e deixa tudo minúsculo para evitar falsos erros
            $resposta_banco = strtolower(trim($perg['resposta_esperada']));
            $resposta_user = strtolower(trim($resposta_usuario));

            if ($resposta_banco === $resposta_user) {
                $acertou = true;
            }
        }
    }

    // Se errou, já devolve a resposta e encerra o script aqui.
    if (!$acertou) {
        echo json_encode(['acertou' => false]);
        exit();
    }


    // ==========================================
    // 2. VERIFICA SE JÁ FEZ (ANTI-FARM)
    // ==========================================
    $stmtProg = $pdo->prepare("SELECT id_artigo FROM usuario_progresso WHERE email_usuario = :email AND id_artigo = :artigo_id");
    $stmtProg->execute(['email' => $email, 'artigo_id' => $artigo_id]);

    if ($stmtProg->rowCount() > 0) {
        // Se a busca achar 1 linha, ele já fez esse quiz no passado!
        echo json_encode([
            'acertou' => true,
            'xp_ganho' => 0,
            'ja_feito' => true
        ]);
        exit();
    }


    // ==========================================
    // 3. DA O XP, SALVA O PROGRESSO E CALCULA NÍVEL
    // ==========================================
    // Inicia a transação (Ou faz tudo junto, ou não faz nada)
    $pdo->beginTransaction();

    // A. Descobre quanto XP o artigo vale
    $stmtArt = $pdo->prepare("SELECT xp_recompensa FROM artigo WHERE id = :artigo_id");
    $stmtArt->execute(['artigo_id' => $artigo_id]);
    $xp_recompensa = $stmtArt->fetchColumn();

    // B. Salva que ele concluiu hoje
    $stmtInsertProg = $pdo->prepare("INSERT INTO usuario_progresso (email_usuario, id_artigo, data_conclusao) VALUES (:email, :artigo_id, NOW())");
    $stmtInsertProg->execute(['email' => $email, 'artigo_id' => $artigo_id]);

    // C. Atualiza os pontos na tabela dele
    $stmtUpdatePoints = $pdo->prepare("UPDATE userPoints SET userPoints = userPoints + :xp WHERE emailPoints = :email");
    $stmtUpdatePoints->execute(['xp' => $xp_recompensa, 'email' => $email]);

    // D. Lógica de Level Up (Puxa os pontos e nível atuais)
    $stmtGetXP = $pdo->prepare("SELECT userPoints FROM userPoints WHERE emailPoints = :email");
    $stmtGetXP->execute(['email' => $email]);
    $pontosAtuais = $stmtGetXP->fetchColumn();

    $stmtGetLevel = $pdo->prepare("SELECT userLevel FROM userLevel WHERE emailLevel = :email");
    $stmtGetLevel->execute(['email' => $email]);
    $nivelAtual = $stmtGetLevel->fetchColumn();

    $upouDeNivel = false;
    $novoNivel = $nivelAtual;

    // Fica rodando enquanto os pontos atuais forem maiores ou iguais aos pontos necessários para passar de nível
    while ($pontosAtuais >= xpNecessario($novoNivel)) {
        // Subtrai o custo do nível atual do montante de pontos do usuário
        $pontosAtuais -= xpNecessario($novoNivel);

        // Sobe o nível
        $novoNivel++;
        $upouDeNivel = true;
    }

    if ($upouDeNivel) {
        // Atualiza para o novo nível no banco
        $stmtLvlUp = $pdo->prepare("UPDATE userLevel SET userLevel = :novo_nivel WHERE emailLevel = :email");
        $stmtLvlUp->execute(['novo_nivel' => $novoNivel, 'email' => $email]);

        // Em vez de zerar, atualiza os pontos com a diferença que sobrou na variável $pontosAtuais
        $stmtUpdateRestante = $pdo->prepare("UPDATE userPoints SET userPoints = :pontos_restantes WHERE emailPoints = :email");
        $stmtUpdateRestante->execute(['pontos_restantes' => $pontosAtuais, 'email' => $email]);
    }

    // Salva tudo no banco definitivamente!
    $pdo->commit();

    // Devolve o JSON com todas as boas notícias para o Front-end animar a tela
    echo json_encode([
        'acertou' => true,
        'xp_ganho' => $xp_recompensa,
        'ja_feito' => false,
        'upou_de_nivel' => $upouDeNivel,
        'novo_nivel' => $novoNivel
    ]);
} catch (PDOException $e) {
    // Se deu qualquer erro no banco, cancela tudo que foi feito desde o beginTransaction()
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['erro' => 'Erro interno do servidor.', 'detalhe' => $e->getMessage()]);
}
