<?php
//🔍 Exibe todos os erros do PHP para facilitar a depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); //🔒 Inicia a sessão para controle de autenticação e permissões
include __DIR__ . '/../admin/banco.php'; // 💾 Conexão com o banco de dados

// Garante que o carrinho exista na sessão, mesmo que vazio
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

// Conta os itens no carrinho após garantir que ele exista
$itens_no_carrinho = count($_SESSION['carrinho']);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Lenna Digitais'; ?></title>
    
    <?php if (isset($page_description)): ?>
        <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <?php endif; ?>

    <link rel="stylesheet" href="css/index.css">
    
    <link href="<?php echo isset($page_icon) ? htmlspecialchars($page_icon) : 'img/icones/comercio.png'; ?>" rel="icon" type="image/png">
</head>
<body>
  <video autoplay muted loop class="video-background">
    <source src="img/natureza.mp4" type="video/mp4">
    Seu navegador não suporta vídeos em HTML5.
</video>

<header class="site-header">
    <div class="header-top-bar">
        <div class="container">
            <div class="social-icons">
                <a href="https://www.romeutech.shop/admin/portifolio.php" target="_blank"><img src="img/instagram.png" alt="Instagram"></a>
                <a href="https://www.romeutech.shop" target="_blank"><img src="img/facebook.png" alt="Facebook"></a>
            </div>
            <div class="user-links">
                <a href="perfil_cliente.php">Meus pedidos</a>
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
    </div>
    <nav class="main-navigation">
        <div class="container">
            <button class="hamburger-menu" id="hamburger-menu" aria-label="Abrir menu" aria-expanded="false" aria-controls="main-nav-list">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <ul id="main-nav-list">
                <li><a href="index.php">🏡 Loja</a></li>
                <li><a href="lista_produtos.php">🛍️ Produtos</a></li>
                <li><a href="contato.php">📞 Contato</a></li>
                <li><a href="admin/gerenciar.php">⚙️ Gerenciar</a></li>
            </ul>
        </div>
    </nav>
</header>

