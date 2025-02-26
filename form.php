<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
<h1>Bem Vindo ao Sistema</h1>
<form action="login.php" method="post">
    <label for="usuario">Login</label>
    <input type="text" name="Login" id="usuario" required autocomplete="username"> <br>

    <label for="password">Senha</label>
    <input type="password" name="Password" id="senha" required autocomplete="off">

    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <div class="recordar">¿Olvido su contraseña?</div>
    <button type="submit">Enviar</button>
    <div class="registrate">¿Quiere hacer el <a href="#">registro</a>?
    
</form>

</body>