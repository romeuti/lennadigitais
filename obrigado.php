<?php
session_start();

// Garante que o carrinho exista na sessão, mesmo que vazio
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

// Conta os itens no carrinho após garantir que ele exista
$itens_no_carrinho = count($_SESSION['carrinho']);

// Pega o ID do pedido da sessão para exibir ao cliente
if (!isset($_SESSION['id_pedido_finalizado'])) {
    // Se não houver pedido finalizado, redireciona para o início
    header('Location: index.php');
    exit;
}

$id_pedido = $_SESSION['id_pedido_finalizado'];
// Limpa a variável da sessão para não mostrar de novo
unset($_SESSION['id_pedido_finalizado']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Recebido!</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/obrigado.css">
</head>
<body>

<header class="site-header">
    <div class="header-top-bar">
        <div class="container">
            <div class="social-icons">
                <a href="https://instagram.com" target="_blank"><img src="img/instagram.png" alt="Instagram"></a>
                <a href="https://facebook.com" target="_blank"><img src="img/facebook.png" alt="Facebook"></a>
            </div>
            <div class="user-links">
                <a href="#">Meus pedidos</a>
                <a href="#">♥ Lista de desejos</a>
            </div>
        </div>
    </div>
    <div class="header-main-container">
        <div class="container header-main">
            <div class="logo">
                <a href="index.php">
                    <img src="img/lenna.png" alt="Logo Lenna Personalizados">
                </a>
            </div>
            <div class="search-container">
                <form action="lista_produtos.php" method="get">
                    <input type="text" name="busca" placeholder="Buscar...">
                    <button type="submit">🔍</button>
                </form>
            </div>
            <div class="header-actions">
                <div class="user-login">
                    <?php if (isset($_SESSION['cliente_logado']) && $_SESSION['cliente_logado'] === true): ?>
                        <a href="perfil_cliente.php"> <span>👤</span>
                            <div>
                                Olá, <strong><?php echo htmlspecialchars($_SESSION['cliente_nome']); ?></strong><br>
                                <a href="logout_cliente.php">Sair</a>
                            </div>
                        </a>
                    <?php else: ?>
                        <a href="clientes.php">
                            <span>👤</span>
                            <div>
                                Olá, faça seu login<br>
                                <strong>ou cadastre-se</strong>
                            </div>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="cart-icon">
                    <a href="carrinho.php">
                        <span>🛒</span>
                        <div class="cart-count" <?php if ($itens_no_carrinho == 0) echo 'style="display: none;"'; ?>>
                            <?php echo $itens_no_carrinho; ?>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </nav>
</header>

    <main>
        <div class="container obrigado-container">
            <h1>Obrigado pelo seu Pedido!</h1>
            <p>Seu pedido de número <strong>#<?php echo $id_pedido; ?></strong> foi recebido e está aguardando a finalização.</p>
            <p>Em breve entraremos em contato para as próximas etapas.</p>
            <div class="secondary-actions">
                <a href="index.php" class="btn-primary">Voltar para a Loja</a>
            </div>
        </div>
    </main>

<footer>
    <p>Lenna Digitais, &copy; <?php echo date('Y'); ?></p>
</footer>

</body>
</html>

