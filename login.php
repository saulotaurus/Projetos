<?php

session_start();

// Função para registrar erros em um arquivo de log
function registrarErro($mensagem) {
    $dataHoraAtual = new DateTime('now', new DateTimeZone('America/Santiago'));
    $dataHoraFormatada = $dataHoraAtual->format('Y-m-d H:i:s');
    $arquivoLog = "erros.log";
    $mensagemLog = "{$dataHoraFormatada} - {$mensagem}\n";
    file_put_contents($arquivoLog, $mensagemLog, FILE_APPEND);
}

if (empty($_POST["Login"]) || empty($_POST["Password"])) {
    header("Location: form.php");
    exit();
}

$login = trim($_POST["Login"]);
$senha = $_POST["Password"];

// Conectar ao banco de dados
$conn = new mysqli("localhost", "root", "admin", "sistema_db");

if ($conn->connect_error) {
    $erroConexao = "Falha na conexão: " . $conn->connect_error;
    registrarErro($erroConexao);
    die($erroConexao);
}

$stmt = $conn->prepare("SELECT username, email, password, cargo FROM login WHERE email = ?");

if ($stmt === false) {
    $erroPreparo = "Erro ao preparar a consulta: " . $conn->error;
    registrarErro($erroPreparo);
    die($erroPreparo);
}

$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();

if ($result === false) {
    $erroConsulta = "Erro na consulta: " . $stmt->error;
    registrarErro($erroConsulta);
    die($erroConsulta);
}

if ($result->num_rows > 0) {
    $dados = $result->fetch_assoc();

    if (password_verify($senha, $dados["password"])) {
        // Armazenar nome, e-mail e cargo na sessão
        $_SESSION["nome"] = $dados["username"];
        $_SESSION["email"] = $dados["email"];
        $_SESSION["cargo"] = $dados["cargo"];

        // Redirecionamento dependendo do cargo
        switch ($_SESSION["cargo"]) {
            case "admin":
                header("Location: pesquisas.php");
                exit();

            case "gestor":
                header("Location: pesquisas.php");
                exit();

            case "colaborador":
                header("Location: pesquisas.php");
                exit();

            default:
                echo "<h1>Acesso Negado</h1>";
                echo "<p>Você não tem permissão para acessar esta página.</p>";
                break;
        }

        exit();
    } else {
        $erroSenha = "Senha incorreta para o usuário: " . $login;
        registrarErro($erroSenha);
    }
} else {
    $erroUsuario = "Usuário não encontrado: " . $login;
    registrarErro($erroUsuario);
}

$_SESSION["naoautenticado"] = true;
header("Location: form.php");
exit();

$stmt->close();
$conn->close();

?>
