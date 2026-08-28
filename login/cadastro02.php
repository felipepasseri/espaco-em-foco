<?php
class Criar
{
    private $db;

    public function __construct($conn)
    {
        $this->db = $conn;
    }

    public function cadastro($emailLogin, $nomeLogin, $sobrenomeLogin, $passwordLogin)
    {
        try {
            $this->db->beginTransaction();

            $sql1 = "INSERT INTO user(email, nome, sobrenome, senha)
                        VALUES (?, ?, ?, ?)";
            $stmt1 = $this->db->prepare($sql1);
            $stmt1->execute([$emailLogin, $nomeLogin, $sobrenomeLogin, $passwordLogin]);

            $sql2 = "INSERT INTO userlevel(emailLevel, userlevel)
                        VALUES (?, 1)";
            $stmt2 = $this->db->prepare($sql2);
            $stmt2->execute([$emailLogin]);

            $sql3 = "INSERT INTO userpoints(emailPoints, userpoints)
                        VALUES (?, 0)";
            $stmt3 = $this->db->prepare($sql3);
            $stmt3->execute([$emailLogin]);

            $sql4 = "INSERT INTO userroles(emailRoles, codTypeRoles)
                        VALUES (?, 0)";
            $stmt4 = $this->db->prepare($sql4);
            $stmt4->execute([$emailLogin]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

require_once '../config.php';

session_start();

$emailSign = $_POST['emailSign'];
$nameSign = $_POST['nameSign'];
$lastNameSign = $_POST['lastNameSign'];
$passwordSign = $_POST['passwordSign'];

$senha_cripto = password_hash($passwordSign, PASSWORD_BCRYPT);

$pdo = getDB();

require_once 'enviar_email.php';

$criar = new Criar($pdo);
$criou = $criar->cadastro($emailSign, $nameSign, $lastNameSign, $senha_cripto);

if ($criou) {
    // Cadastro ok, tenta enviar o e-mail de verificação
    $enviouEmail = enviarEmailConfirmacao($pdo, $emailSign);
    
    $_SESSION['email_verificacao'] = $emailSign; // Mantém a sessão do email para reenvios, etc.
    
    if ($enviouEmail) {
        header("Location: finalizarCadastro/cadastro03.php?sucesso=1");
    } else {
        header("Location: finalizarCadastro/cadastro03.php?erro_email=1");
    }
    exit;
} else {
    ob_clean();
    header("Location: login.php?errocad=1");
}
