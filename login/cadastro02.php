<?php
	class Database {
        private $conn;

        public function __construct() {
            require("BDconn.php");
            $this->conn = $BDconn;
        }

        public function getConnection() {
            return $this->conn;
        }
    }

    class Criar {
        private $db;

        public function __construct($conn) {
            $this->db = $conn;
        }

        public function cadastro($emailLogin, $nomeLogin, $sobrenomeLogin, $passwordLogin) {
            $sql_user = "INSERT INTO user(email, nome, sobrenome, senha, errosLogin) VALUES (?,?,?,?,0)";
            $stmt = mysqli_prepare($this->db, $sql_user);
            mysqli_stmt_bind_param($stmt, "ssss", $emailLogin, $nomeLogin, $sobrenomeLogin, $passwordLogin);

            if (!mysqli_stmt_execute($stmt)) {
                return false;
            }
            else{
                return true;
            }
            mysqli_stmt_close($stmt);
        }
    }
    session_start();
    $emailSign = $_POST['emailSign'];
    $nameSign = $_POST['nameSign'];
    $lastNameSign = $_POST['lastNameSign'];
    $passwordSign = $_POST['passwordSign'];

    require("cryp2graph2.php");
    $senha_cripto = FazSenha($emailSign, $passwordSign);

    $db = new Database();
    $criar = new Criar($db->getConnection());

    $criou = $criar->cadastro($emailSign,$nameSign,$lastNameSign, $senha_cripto);

    if ($criou) {
        $_SESSION['user'] = $emailSign;
        header("Location: ../index.html");
    } else {
        echo "Login já existe";
    }
?>