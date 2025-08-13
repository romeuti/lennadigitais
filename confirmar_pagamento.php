<?php
session_start();

// Garante que o carrinho exista na sessão, mesmo que vazio
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

// Conta os itens no carrinho após garantir que ele exista
$itens_no_carrinho = count($_SESSION['carrinho']);

// Verifica se há dados do pedido para confirmação
if (!isset($_SESSION['id_pedido_para_confirmacao']) || !isset($_SESSION['valor_total_pedido'])) {
    header('Location: index.php'); // Redireciona se não houver pedido em andamento
    exit;
}

$id_pedido = $_SESSION['id_pedido_para_confirmacao'];
$valor_total = $_SESSION['valor_total_pedido'];

// Lógica para quando o cliente clica que pagou
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_pagamento'])) {
    // Limpa as variáveis de sessão usadas para esta página assim que lidas e o botão é clicado
    unset($_SESSION['id_pedido_para_confirmacao']);
    unset($_SESSION['valor_total_pedido']);

    // Coloca o ID do pedido na sessão para a página de obrigado
    $_SESSION['id_pedido_finalizado'] = $id_pedido;
    header('Location: obrigado.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Pagamento - Lenna Digitais</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/confirmar_pagamento.css">
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
    <div class="container payment-confirmation-container">
        <h1>Instruções de Pagamento</h1>
        <p>Seu pedido de número <strong>#<?php echo $id_pedido; ?></strong> foi gerado com sucesso. Por favor, siga as instruções abaixo para realizar o pagamento.</p>
        
        <div class="pix-details">
            <h2>Pagamento via PIX</h2>
            
            <p>Escaneie o QR Code abaixo com o aplicativo do seu banco ou use a chave PIX Copia e Cola:</p>
            <div class="qrcode-container">
                <img src="img/qrcode-pix.png" alt="QR Code PIX" class="qrcode-image">
            </div>
            
            <p><strong>Chave PIX (Celular):</strong> (41) 998680237 <span class="pix-key-note">(Copie e Cole)</span></p>
            <p class="total-value"><strong>Valor Total:</strong> R$ <?php echo number_format($valor_total, 2, ',', '.'); ?></p>
            <p>
                **IMPORTANTE:** No aplicativo do banco, digite o valor exato de <strong>R$ <?php echo number_format($valor_total, 2, ',', '.'); ?></strong>.
                Não se esqueça de informar o número do seu pedido (<strong>#<?php echo $id_pedido; ?></strong>) no campo de "Descrição" ou "Mensagem" do PIX, se disponível.
            </p>
            <p>Após efetuar o pagamento, envie o comprovante para nosso WhatsApp, informando o número do seu pedido (<strong>#<?php echo $id_pedido; ?></strong>).</p>
            <p class="whatsapp-contact">
                <a href="https://wa.me/5573991824641?text=Ol%C3%A1%2C%20realizei%20um%20pagamento%20PIX%20do%20pedido%20%23<?php echo $id_pedido; ?>.%20Segue%20o%20comprovante." target="_blank" rel="noopener" class="btn-whatsapp">
                    Enviar Comprovante via WhatsApp
                </a>
            </p>
        </div>

        <form action="confirmar_pagamento.php" method="POST" class="payment-form">
            <input type="hidden" name="confirmar_pagamento" value="1">
            <button type="submit" class="btn-primary-confirm">Já Realizei o Pagamento</button>
            <p class="note">Clique aqui *APÓS* efetuar a transferência PIX.</p>
        </form>
        
        <div class="secondary-actions">
            <a href="index.php" class="btn-secondary">Voltar para a Loja</a>
        </div>
    </div>
</main>

<footer>
    <p>Lenna Digitais, &copy; <?php echo date('Y'); ?></p>
</footer>

</body>
</html>

