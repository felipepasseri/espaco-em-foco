<?php
    class Auth {

        private $db;

        public function __construct($conn) {
            $this->db = $conn;
        }

        public function verificar($emailSubmit) {

            $sql = "SELECT email FROM user WHERE email = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$emailSubmit]);

            $dados = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$dados) {
                return false;
            }
            else{
                return true;
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: mudarsenha.php');
        exit;
    }
    require_once 'cryp2graph2.php';
    require_once '../config.php';
    session_start();

    $email = trim($_POST['emailSign'] ?? '');

    if ($email === '') {
        header("Location: login.php"); // deu algum erro brutal e volta pro login
        exit;
    }

    $conn = getDB();

    $auth = new Auth($conn);
    $user = $auth->verificar($email, $senha);

    if ($user) { // Tudo certo, continuar para mudarsenha03.php
        header("Location: mudarsenha03.php");
        exit;
    }
    else { // Não existe email no BD
        header("Location: mudarsenha.php?erro=1");
        exit;
    }
?>