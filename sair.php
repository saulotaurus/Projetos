<?php
session_start();
$_SESSION = []; // Limpa todas as variáveis da sessão
session_destroy(); // Destrói a sessão
setcookie(session_name(), '', time() - 3600, '/'); // Remove o cookie de sessão

header("Location: login.php");
exit();
?>
