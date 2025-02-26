<?php
session_start();

// Conectar ao banco de dados
$conn = new mysqli("localhost", "root", "admin", "sistema_db");
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);

    // Verificar se o e-mail existe no banco de dados
    $stmt = $conn->prepare("SELECT * FROM login WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();

        // Gerar um token único para a recuperação de senha
        $token = bin2hex(random_bytes(32));

        // Salvar o token no banco de dados, associado ao usuário
        $stmt = $conn->prepare("UPDATE login SET token_recuperacao = ? WHERE email = ?");
        $stmt->bind_param("ss", $token, $email);
        $stmt->execute();

        // Enviar o e-mail de recuperação com o link contendo o token
        $assunto = "Recuperação de senha";
        $mensagem = "Olá, " . $usuario["username"] . "!<br><br>";
        $mensagem .= "Você solicitou a recuperação de senha. Para criar uma nova senha, clique no link abaixo:<br>";
        $mensagem .= "<a href='http://seusite.com/nova_senha.php?token=" . $token . "'>Recuperar senha</a><br><br>";
        $mensagem .= "Se você não solicitou a recuperação de senha, ignore este e-mail.";

        // Configurar o envio de e-mail (você pode usar bibliotecas como PHPMailer)
        // ...

        // Exibir mensagem de sucesso
        echo "E-mail de recuperação enviado com sucesso!";
    } else {
        echo "Erro: E-mail não encontrado.";
    }

    $stmt->close();
    $conn->close();
}
?>