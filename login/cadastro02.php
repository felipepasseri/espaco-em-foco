<?php
    class Criar {
        private $db;

        public function __construct($conn) {
            $this->db = $conn;
        }

        public function cadastro($emailLogin, $nomeLogin, $sobrenomeLogin, $passwordLogin) {
            try {
                $this->db->beginTransaction();

                $sql1 = "INSERT INTO user(email, nome, sobrenome, senha)
                        VALUES (?, ?, ?, ?)";
                $stmt1 = $this->db->prepare($sql1);
                $stmt1->execute([$emailLogin, $nomeLogin, $sobrenomeLogin, $passwordLogin]);

                $sql2 = "INSERT INTO userLevel(emailLevel, userLevel)
                        VALUES (?, 1)";
                $stmt2 = $this->db->prepare($sql2);
                $stmt2->execute([$emailLogin]);

                $sql3 = "INSERT INTO userPoints(emailPoints, userPoints)
                        VALUES (?, 0)";
                $stmt3 = $this->db->prepare($sql3);
                $stmt3->execute([$emailLogin]);

                $sql4 = "INSERT INTO userRoles(emailRoles, codTypeRoles)
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
    require_once 'cryp2graph2.php';

    session_start();

    $emailSign = $_POST['emailSign'];
    $nameSign = $_POST['nameSign'];
    $lastNameSign = $_POST['lastNameSign'];
    $passwordSign = $_POST['passwordSign'];

    $senha_cripto = FazSenha($emailSign, $passwordSign);

    $pdo = getDB();

    $criar = new Criar($pdo);
    $criou = $criar->cadastro($emailSign, $nameSign, $lastNameSign, $senha_cripto);

    if ($criou) {
        $_SESSION['user'] = $emailSign;
        header("Location: ../index.html");
        exit;
    } else {
        ob_clean();
        header("Location: login.php?errocad=1");
    }
?>