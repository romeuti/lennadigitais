<?php
session_start();

// Remove todas as variáveis de sessão relacionadas ao cliente
unset($_SESSION['cliente_logado']);
unset($_SESSION['cliente_id']);
unset($_SESSION['cliente_nome']);

// Redireciona para a página inicial ou de login
header("Location: index.php");
exit;
?>

