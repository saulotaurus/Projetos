<?php
session_start();

if (!isset($_SESSION["cargo"])) {
    header("Location: form.php");
    exit();
}

// Recuperar dados do usuário logado
$emailUsuario = isset($_SESSION["email"]) ? $_SESSION["email"] : "";
$nomeUsuario = isset($_SESSION["nome"]) ? $_SESSION["nome"] : "";

// Verificar se as variáveis estão sendo corretamente definidas
if (empty($emailUsuario) || empty($nomeUsuario)) {
    echo "Erro: Dados do usuário não encontrados na sessão.";
    exit();
}

// Função para registrar erros em um arquivo de log
function registrarErro($mensagem) {
    $dataHoraAtual = new DateTime('now', new DateTimeZone('America/Santiago'));
    $dataHoraFormatada = $dataHoraAtual->format('Y-m-d H:i:s');
    $arquivoLog = "erros.log";
    $mensagemLog = "{$dataHoraFormatada} - {$mensagem}\n";
    file_put_contents($arquivoLog, $mensagemLog, FILE_APPEND);
}

$conn = new mysqli("localhost", "root", "admin", "sistema_db");
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Definir número de itens por página (10, 25 ou 50)
$itens_por_pagina = isset($_POST['itens_por_pagina']) ? $_POST['itens_por_pagina'] : 10;

// Pegar o número da página atual (se não existir, será 1)
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

// Calcular o deslocamento (offset) baseado na página atual
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Se o formulário foi enviado e o filtro não está vazio, realiza a busca com filtro
$campos_validos = ["RUT", "username", "email", "cargo"];
$campo = isset($_POST["campo"]) && in_array($_POST["campo"], $campos_validos) ? $_POST["campo"] : "email";

$filtro = isset($_POST["filtro"]) ? trim($_POST["filtro"]) : "";
$resultados = [];

// Se o filtro estiver vazio, exibe todos os usuários
if (empty($filtro)) {
    $sql = "SELECT RUT, username, email, cargo FROM login ORDER BY username ASC LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $offset, $itens_por_pagina);
} else {
    $sql = "SELECT RUT, username, email, cargo FROM login WHERE $campo LIKE ? LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $parametro = "%$filtro%";
    $stmt->bind_param("sii", $parametro, $offset, $itens_por_pagina);
}

if ($stmt) {
    $stmt->execute();
    $resultados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    die("Erro ao preparar a consulta: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style2.css">
    <title>Pesquisa de Usuários</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        .usuario-logado {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 14px;
            background-color: #f1f1f1;
            padding: 5px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>

<!-- Exibir informações do usuário logado -->
<div class="usuario-logado">
    <p><strong>Bem-vindo, <?php echo htmlspecialchars($nomeUsuario); ?>!</strong></p>
    <p>Email: <?php echo htmlspecialchars($emailUsuario); ?></p>
</div>

<h2>Pesquisar Usuários</h2>

<!-- Botão para criar usuário, visível para admins e gestores -->
<?php if (isset($_SESSION["cargo"]) && ($_SESSION["cargo"] == "admin" || $_SESSION["cargo"] == "gestor")): ?>
    <form action="criar_usuario.php" method="get">
        <button type="submit" class="btn btn-primary">Criar Usuário</button>
    </form>
<?php endif; ?>

<form method="post">
    <label for="campo">Pesquisar por:</label>
    <select name="campo" id="campo">
        <option value="RUT" <?php echo ($campo == "RUT") ? "selected" : ""; ?>>RUT</option>
        <option value="username" <?php echo ($campo == "username") ? "selected" : ""; ?>>Nome</option>
        <option value="email" <?php echo ($campo == "email") ? "selected" : ""; ?>>E-mail</option>
        <option value="cargo" <?php echo ($campo == "cargo") ? "selected" : ""; ?>>Cargo</option>
    </select>

    <input type="text" name="filtro" placeholder="Digite sua pesquisa" value="<?php echo htmlspecialchars($filtro); ?>">
    <button type="submit">Buscar</button>
</form>

<!-- Exibir a tabela com todos os usuários, ou com os resultados da pesquisa -->
<?php if (!empty($resultados)): ?>
    <h3>Resultados:</h3>
    <table>
        <tr>
            <th>RUT</th>
            <th>Nome</th>
            <th>Email</th>
            <th>Cargo</th>
            <th>Ações</th>
        </tr>
        <?php foreach ($resultados as $usuario): ?>
            <tr>
                <td><?php echo htmlspecialchars($usuario["RUT"]); ?></td>
                <td><?php echo htmlspecialchars($usuario["username"]); ?></td>
                <td><?php echo htmlspecialchars($usuario["email"]); ?></td>
                <td><?php echo htmlspecialchars($usuario["cargo"]); ?></td>
                <td>
                    <?php if ($_SESSION["cargo"] == "admin"): ?>
                        <a href="editar_excluir.php?email=<?php echo urlencode($usuario['email']); ?>">Editar</a> |
                        <a href="editar_excluir.php?email=<?php echo urlencode($usuario['email']); ?>" onclick="return confirm('Tem certeza?');">Excluir</a>
                    <?php elseif ($_SESSION["cargo"] == "gestor"): ?>
                        <a href="editar_excluir.php?email=<?php echo urlencode($usuario['email']); ?>">Editar</a>
                    <?php else: ?>
                        <span>Sem permissão</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <form action="pesquisas.php" method="post">
            <button type="submit" class="btn btn-danger">Voltar</button>
        </form>

        <form action="sair.php" method="get">
            <button type="submit" class="btn btn-danger">SAIR</button>
        </form>
<?php elseif ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
    <p>Nenhum resultado encontrado.</p>
<?php endif; ?>



</body>
</html>

<?php $conn->close(); ?>
