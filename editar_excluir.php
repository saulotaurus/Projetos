<?php
session_start();


// Verifica se o usuário está logado e tem permissão para acessar a página
if (!isset($_SESSION["cargo"]) || ($_SESSION["cargo"] != "admin" && $_SESSION["cargo"] != "gestor")) {
    header("Location: form.php");
    exit();
}

// Conectar ao banco de dados
$conn = new mysqli("localhost", "root", "admin", "sistema_db");

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Função para registrar erros em um arquivo de log
function registrarErro($mensagem) {
    $dataHoraAtual = new DateTime('now', new DateTimeZone('America/Santiago'));
    $dataHoraFormatada = $dataHoraAtual->format('Y-m-d H:i:s');
    $arquivoLog = "erros.log";
    $mensagemLog = "{$dataHoraFormatada} - {$mensagem}\n";
    file_put_contents($arquivoLog, $mensagemLog, FILE_APPEND);
}

// Carregar dados do usuário para edição
$usuario = null;
if (isset($_GET["email"])) {
    $email = $_GET["email"];
    $stmt = $conn->prepare("SELECT email, username, cargo FROM login WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();
    } else {
        echo "Usuário não encontrado.";
        exit();
    }
    $stmt->close();
}

// EDITAR USUÁRIO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["editar"])) {
    $email = $_POST["email"];
    $nome = $_POST["nome"];
    $senha = !empty($_POST["senha"]) ? password_hash($_POST["senha"], PASSWORD_BCRYPT) : null;
    $cargo = $_POST["cargo"];

    // Se o usuário for um gestor, ele não pode definir um cargo como "admin"
    if ($_SESSION["cargo"] == "gestor" && $cargo == "admin") {
        echo "Erro: Gestores não podem promover usuários para administrador.";
        exit();
    }

    // Atualizar usuário no banco de dados
    if ($senha) {
        $stmt = $conn->prepare("UPDATE login SET username = ?, password = ?, cargo = ? WHERE email = ?");
        $stmt->bind_param("ssss", $nome, $senha, $cargo, $email);
    } else {
        $stmt = $conn->prepare("UPDATE login SET username = ?, cargo = ? WHERE email = ?");
        $stmt->bind_param("sss", $nome, $cargo, $email);
    }

    if ($stmt->execute()) {
        echo "Usuário atualizado com sucesso!";
    } else {
        $erro = "Erro ao atualizar usuário: " . $stmt->error;
        registrarErro($erro);
        echo $erro;
    }

    $stmt->close();
}

// EXCLUIR USUÁRIO (Apenas Admin pode excluir)
if (isset($_POST["excluir"])) {
    if ($_SESSION["cargo"] == "gestor") {
        echo "Erro: Gestores não podem excluir usuários.";
        exit();
    }

    $email = $_POST["email"];

    $stmt = $conn->prepare("DELETE FROM login WHERE email = ?");
    $stmt->bind_param("s", $email);

    if ($stmt->execute()) {
        echo "Usuário excluído com sucesso!";
    } else {
        $erro = "Erro ao excluir usuário: " . $stmt->error;
        registrarErro($erro);
        echo $erro;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários</title>
    <link rel="stylesheet" href="style2.css">
</head>
<body>

<h2>Gerenciar Usuários</h2>

<!-- Formulário de Edição -->
<form method="post">
    <label for="email">E-mail:</label>
    <input type="email" name="email" id="email" value="<?= $usuario['email'] ?? '' ?>" required ><br>

    <label for="nome">Nome:</label>
    <input type="text" name="nome" id="nome" value="<?= $usuario['username'] ?? '' ?>" required><br>

    <label for="senha">Nova Senha <b>(deixe em branco para manter a atual):</label>
    <input type="password" name="senha" id="senha"><br>

    <label for="cargo">Cargo:</label>
    <select name="cargo" id="cargo" required>
        <option value="gestor" <?= isset($usuario['cargo']) && $usuario['cargo'] == 'gestor' ? 'selected' : '' ?>>Gestor</option>
        <option value="colaborador" <?= isset($usuario['cargo']) && $usuario['cargo'] == 'colaborador' ? 'selected' : '' ?>>Colaborador</option>
        <?php if ($_SESSION["cargo"] == "admin"): ?>
            <option value="admin" <?= isset($usuario['cargo']) && $usuario['cargo'] == 'admin' ? 'selected' : '' ?>>Administrador</option>
        <?php endif; ?>
    </select><br>

    <button type="submit" name="editar">Editar Usuário</button>
</form>

<!-- Formulário de Exclusão (somente admins) -->
<?php if ($_SESSION["cargo"] == "admin"): ?>
    <form method="post">
        <input type="hidden" name="email" value="<?= $usuario['email'] ?? '' ?>">
        <button type="submit" name="excluir">Excluir Usuário</button>
    </form>
<?php endif; ?>

<form action="pesquisas.php" method="post">
    <button type="submit" class="btn btn-danger">Voltar</button>
</form>

</body>
</html>
