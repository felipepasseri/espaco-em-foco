<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit();
}

require_once __DIR__ . '/../config.php';

$email = $_SESSION['user'];

try {
    $pdo = getDB();

    // Pega username
    $stmtMe = $pdo->prepare("SELECT nomeDeUsuario FROM user WHERE email = :email");
    $stmtMe->execute(['email' => $email]);
    $myUsername = $stmtMe->fetchColumn();

    if (!$myUsername) {
        echo json_encode(['success' => false, 'error' => 'Usuário não encontrado']);
        exit();
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
        exit();
    }

    $topicId = $data['topic'] ?? null;
    $titulo = trim($data['titulo'] ?? '');
    $conteudo = trim($data['conteudo'] ?? '');
    $perguntas = $data['perguntas'] ?? [];

    if (!$topicId || !$titulo || !$conteudo) {
        echo json_encode(['success' => false, 'error' => 'Todos os campos do artigo são obrigatórios']);
        exit();
    }

    // Inicia transação
    $pdo->beginTransaction();

    // 1. Inserir o artigo
    $stmtArtigo = $pdo->prepare("
        INSERT INTO artigo (id_topic, titulo, conteudo, avaliacao_adm)
        VALUES (:topicId, :titulo, :conteudo, 'Em Analise')
    ");
    $stmtArtigo->execute([
        'topicId' => $topicId,
        'titulo' => $titulo,
        'conteudo' => $conteudo
    ]);
    
    $idArtigo = $pdo->lastInsertId();

    // Dicionário de XP
    $xp_valores = [
        'facil' => 200,
        'medio' => 500,
        'dificil' => 720
    ];

    // 2. Inserir as perguntas e respostas
    if (is_array($perguntas)) {
        foreach ($perguntas as $pergunta) {
            $textoPergunta = $pergunta['titulo'] ?? '';
            $tipoPergunta = $pergunta['tipo'] ?? ''; // 'multipla_escolha' ou 'lacuna'
            $dificuldade = $pergunta['dificuldade'] ?? 'facil';
            
            // Garantir que a dificuldade seja válida
            if (!array_key_exists($dificuldade, $xp_valores)) {
                $dificuldade = 'facil';
            }
            $xp_recompensa = $xp_valores[$dificuldade];

            $stmtPergunta = $pdo->prepare("
                INSERT INTO quiz_pergunta (id_artigo, texto_pergunta, tipo, dificuldade, xp_recompensa)
                VALUES (:idArtigo, :texto, :tipo, :dificuldade, :xp)
            ");
            $stmtPergunta->execute([
                'idArtigo' => $idArtigo,
                'texto' => $textoPergunta,
                'tipo' => $tipoPergunta,
                'dificuldade' => $dificuldade,
                'xp' => $xp_recompensa
            ]);
            
            $idPergunta = $pdo->lastInsertId();

            if ($tipoPergunta === 'multipla_escolha' && isset($pergunta['alternativas']) && is_array($pergunta['alternativas'])) {
                foreach ($pergunta['alternativas'] as $index => $textoAlternativa) {
                    $isCorrect = (isset($pergunta['resposta_correta']) && $pergunta['resposta_correta'] == $index) ? 1 : 0;
                    
                    $stmtResposta = $pdo->prepare("
                        INSERT INTO quiz_resposta (id_pergunta, tipo_pergunta, texto_alternativa, is_correct)
                        VALUES (:idPergunta, 'multipla_escolha', :textoAlternativa, :isCorrect)
                    ");
                    $stmtResposta->execute([
                        'idPergunta' => $idPergunta,
                        'textoAlternativa' => $textoAlternativa,
                        'isCorrect' => $isCorrect
                    ]);
                }
            } elseif ($tipoPergunta === 'lacuna' && isset($pergunta['lacunas']) && is_array($pergunta['lacunas'])) {
                foreach ($pergunta['lacunas'] as $textoLacuna) {
                    $stmtResposta = $pdo->prepare("
                        INSERT INTO quiz_resposta (id_pergunta, tipo_pergunta, resposta_lacuna, is_correct)
                        VALUES (:idPergunta, 'lacuna', :respostaLacuna, 1)
                    ");
                    $stmtResposta->execute([
                        'idPergunta' => $idPergunta,
                        'respostaLacuna' => $textoLacuna
                    ]);
                }
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => 'Erro interno ao salvar.']);
}
