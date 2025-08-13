<?php
// Inicia a sessão caso ainda não tenha sido iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado.
// Se a sessão 'logado' não existir ou não for true, redireciona para o login.
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    // Redireciona para a página de login que está na mesma pasta
    header('Location: login.php');
    exit; // Garante que o script pare de ser executado após o redirecionamento
}

// Opcional: Controle de tempo de sessão (inativa após 30 minutos)
$tempo_limite = 10 * 60; // 10 minutos em segundos
if (isset($_SESSION['ultimo_acesso']) && (time() - $_SESSION['ultimo_acesso']) > $tempo_limite) {
    // Destroi a sessão e redireciona para o login com uma mensagem
    session_unset();
    session_destroy();
    header('Location: login.php?status=expirado');
    exit;
}

// Atualiza o tempo do último acesso a cada carregamento de página protegida
$_SESSION['ultimo_acesso'] = time();

?>

