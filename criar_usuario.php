<?php
session_start();


// Verifica se o usuário tem permissão para acessar a página (somente admin e gestor)
if (!isset($_SESSION["cargo"]) || ($_SESSION["cargo"] != "admin" && $_SESSION["cargo"] != "gestor")) {
    header("Location: form.php");
    exit();
}

// Inclui o arquivo com a função de verificação do dígito do RUT
require_once 'verificador_11.php';

// Função para registrar erros em um arquivo de log
function registrarErro($mensagem) {
    $dataHoraAtual = new DateTime('now', new DateTimeZone('America/Santiago'));
    $dataHoraFormatada = $dataHoraAtual->format('Y-m-d H:i:s');
    $arquivoLog = "erros.log";
    $mensagemLog = "{$dataHoraFormatada} - {$mensagem}\n";
    file_put_contents($arquivoLog, $mensagemLog, FILE_APPEND);
}

// Processa o formulário se for uma requisição POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rut = trim($_POST["rut"]);
    $email = trim($_POST["email"]);
    $username = trim($_POST["nome"]); // Corrigi de 'username' para 'nome'
    $senha = trim($_POST["senha"]);
    $cargo = trim($_POST["cargo"]);

    // Validação: Todos os campos são obrigatórios
if (empty($rut) || empty($email) || empty($username) || empty($senha) || empty($cargo)) {
    echo "Erro: Todos os campos são obrigatórios.";
    registrarErro("Tentativa de inserção com campos vazios.");
    exit();
}

        // Validação do e-mail
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "E-mail inválido!";
    exit();
}

    // Validação do RUT
if (strlen($rut) > 9) {
    echo "Erro: O RUT deve ter no máximo 9 caracteres.";
    registrarErro("Tentativa de inserção com RUT inválido: {$rut}");
    exit();
}
    
    // Separa o número do RUT e o dígito verificador
    $rut_sem_dv = substr($rut, 0, -1); // Remove o último caractere (dígito verificador)
    $digito_verificador = substr($rut, -1); // Pega o último caractere (dígito verificador)

    // Calcula o dígito verificador esperado
    $digito_calculado = verificador($rut_sem_dv, 11);

    // Compara o dígito verificador calculado com o fornecido
if ($digito_verificador != $digito_calculado) {
    echo "Erro: RUT inválido. O dígito verificador não corresponde.";
    registrarErro("Tentativa de inserção com RUT inválido: {$rut}");
    exit();
}
    
    // Limita o RUT a 10 caracteres
if (strlen($rut) > 9) {
    echo "Erro: O RUT deve ter no máximo 9 caracteres.";
    registrarErro("Tentativa de inserção com RUT inválido: {$rut}");
    exit();

}
    // Criptografar senha
    $senhaHash = password_hash($senha, PASSWORD_BCRYPT);

    // Conectar ao banco de dados
    $conn = new mysqli("localhost", "root", "admin", "sistema_db");

    if ($conn->connect_error) {
        registrarErro("Erro na conexão: " . $conn->connect_error);
        die("Erro na conexão com o banco de dados.");
    }

    // Verificar se o e-mail já existe
    $stmt = $conn->prepare("SELECT rut FROM login WHERE email = ?");
    if ($stmt === false) {
        registrarErro("Erro ao preparar a consulta de verificação: " . $conn->error);
        die("Erro ao preparar consulta.");
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        echo "Erro: Este e-mail já está registrado.";
        registrarErro("Tentativa de cadastro com e-mail já existente: {$email}");
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close(); // Fecha a verificação antes de continuar

    // Inserir novo usuário no banco
    $stmt = $conn->prepare("INSERT INTO login (rut, email, username, password, cargo) VALUES (?, ?, ?, ?, ?)");
    if ($stmt === false) {
        registrarErro("Erro ao preparar a inserção: " . $conn->error);
        die("Erro ao preparar a inserção.");
    }

    $stmt->bind_param("issss", $rut, $email, $username, $senhaHash, $cargo);

    if ($stmt->execute()) {
        echo "Usuário cadastrado com sucesso!";
    } else {
        $erro = "Erro ao cadastrar usuário: " . $stmt->error;
        registrarErro($erro);
        echo $erro;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Novo Usuário</title>
    <link rel="stylesheet" href="style2.css">
</head>
<body>

<h2>Criar Novo Usuário</h2>
<form method="post">
    <label for="nome">Nome:</label>
    <input type="text" name="nome" id="nome" required><br>

    <label for="rut">RUT:</label>
    <input type="text" name="rut" id="rut" maxlength="9" required><br>

    <label for="email">E-mail:</label>
    <input type="email" name="email" id="email" required><br>

    <label for="senha">Senha:</label>
    <input type="password" name="senha" id="senha" required><br>

    <label for="cargo">Cargo:</label>
    <select name="cargo" id="cargo" required>
        <option value="admin">Administrador</option>
        <option value="gestor">Gestor</option>
        <option value="colaborador">Colaborador</option>
    </select><br>

    <button type="submit">Criar Usuário</button>
</form>


<form action="pesquisas.php" method="post">
    <button type="submit" class="btn btn-danger">Voltar</button>
</form>

</body>
</html>
