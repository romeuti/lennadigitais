<?php
session_start();

// Redireciona se o cliente NÃO estiver logado
if (!isset($_SESSION['cliente_logado']) || $_SESSION['cliente_logado'] !== true || !isset($_SESSION['cliente_id'])) {
    header('Location: clientes.php?erro=login_necessario');
    exit();
}

// Garante que o carrinho exista na sessão para o contador
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}
$itens_no_carrinho = count($_SESSION['carrinho']);

include 'admin/banco.php';

// Verifica se o ID do pedido foi fornecido na URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: perfil_cliente.php?erro=pedido_invalido');
    exit();
}

$id_pedido = (int)$_GET['id'];
$id_cliente_logado = $_SESSION['cliente_id'];

// 1. Buscar detalhes do pedido E verificar se ele pertence ao cliente logado, incluindo novos campos
$sql_pedido = "
    SELECT
        p.id,
        p.nome_cliente,
        p.email_cliente,
        p.whatsapp_cliente,
        p.data_pedido,
        p.status_pedido,
        p.email_produto_enviado,        -- Novo campo
        p.data_envio_email,             -- Novo campo
        SUM(ip.quantidade * ip.preco_unitario) AS valor_total
    FROM
        pedidos p
    JOIN
        itens_pedido ip ON p.id = ip.id_pedido
    WHERE
        p.id = ? AND p.id_cliente = ?
    GROUP BY
        p.id, p.nome_cliente, p.email_cliente, p.whatsapp_cliente, p.data_pedido, p.status_pedido,
        p.email_produto_enviado, p.data_envio_email
";
$stmt_pedido = $conn->prepare($sql_pedido);
$stmt_pedido->bind_param("ii", $id_pedido, $id_cliente_logado);
$stmt_pedido->execute();
$result_pedido = $stmt_pedido->get_result();

if ($result_pedido->num_rows === 0) {
    header('Location: perfil_cliente.php?erro=pedido_nao_encontrado');
    exit();
}
$pedido = $result_pedido->fetch_assoc();
$stmt_pedido->close();

// 2. Buscar itens deste pedido
$sql_itens = "
    SELECT
        ip.quantidade,
        ip.preco_unitario,
        prod.nome AS nome_produto,
        prod.capa_imagem
    FROM
        itens_pedido ip
    JOIN
        produtos prod ON ip.id_produto = prod.id
    WHERE
        ip.id_pedido = ?
";
$stmt_itens = $conn->prepare($sql_itens);
$stmt_itens->bind_param("i", $id_pedido);
$stmt_itens->execute();
$result_itens = $stmt_itens->get_result();
$itens_do_pedido = $result_itens->fetch_all(MYSQLI_ASSOC);
$stmt_itens->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Pedido #<?php echo htmlspecialchars($pedido['id']); ?></title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/perfil_cliente.css"> <link rel="stylesheet" href="css/detalhes_pedido_cliente.css"> <link href="img/icones/pedido.png" rel="icon" type="image/png">
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
                        <a href="perfil_cliente.php">
                            <span>👤</span>
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
    <div class="container order-detail-container">
        <h1>Detalhes do Pedido #<?php echo htmlspecialchars($pedido['id']); ?></h1>

        <div class="order-summary-card">
            <h3>Resumo do Pedido</h3>
            <p><strong>Status:</strong> <span class="status-<?php echo strtolower(str_replace(' ', '-', $pedido['status_pedido'])); ?>"><?php echo htmlspecialchars($pedido['status_pedido']); ?></span></p>
            <p><strong>Data do Pedido:</strong> <?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></p>
            <p><strong>Valor Total:</strong> R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></p>

            <?php if ($pedido['status_pedido'] === 'Concluído'): ?>
                <hr>
                <h4>Entrega do Produto Digital:</h4>
                <?php if ($pedido['email_produto_enviado']): ?>
                    <p class="status-success">✅ Produto Digital ENVIADO para o seu e-mail em: <?php echo date('d/m/Y H:i', strtotime($pedido['data_envio_email'])); ?>.</p>
                    <p>Por favor, verifique sua caixa de entrada e a pasta de spam. Se não encontrar, entre em contato.</p>
                <?php else: ?>
                    <p class="status-pending">⏳ O envio do produto digital ainda está PENDENTE. Você será notificado por e-mail quando for enviado.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="order-items-card">
            <h3>Itens do Pedido</h3>
            <table class="order-items-table">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Preço Unit.</th>
                        <th>Qtd.</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens_do_pedido as $item): ?>
                        <tr>
                            <td data-label="Produto">
                                <img src="<?php echo htmlspecialchars($item['capa_imagem']); ?>" alt="<?php echo htmlspecialchars($item['nome_produto']); ?>">
                                <?php echo htmlspecialchars($item['nome_produto']); ?>
                            </td>
                            <td data-label="Preço Unit.">R$ <?php echo number_format($item['preco_unitario'], 2, ',', '.'); ?></td>
                            <td data-label="Qtd."><?php echo htmlspecialchars($item['quantidade']); ?></td>
                            <td data-label="Subtotal">R$ <?php echo number_format($item['quantidade'] * $item['preco_unitario'], 2, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="customer-info-card">
            <h3>Seus Dados de Contato</h3>
            <p><strong>Nome:</strong> <?php echo htmlspecialchars($pedido['nome_cliente']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($pedido['email_cliente']); ?></p>
            <p><strong>WhatsApp:</strong> <?php echo htmlspecialchars($pedido['whatsapp_cliente']); ?></p>
            <?php if ($pedido['status_pedido'] === 'Aguardando Pagamento'): ?>
                <p class="whatsapp-contact-reminder">
                    <a href="https://wa.me/5573991824641?text=Ol%C3%A1%2C%20estou%20enviando%20o%20comprovante%20do%20meu%20pedido%20%23<?php echo $pedido['id']; ?>." target="_blank" rel="noopener" class="btn btn-whatsapp-comprovante">
                        Enviar Comprovante via WhatsApp
                    </a>
                </p>
            <?php endif; ?>
        </div>

        <div class="secondary-actions">
            <a href="perfil_cliente.php" class="btn btn-secondary">↩️ Voltar para Meus Pedidos</a>
            <a href="index.php" class="btn btn-primary">🏠 Voltar para a Loja</a>
        </div>
    </div>
</main>

<footer>
    <p>Lenna Digitais, &copy; <?php echo date('Y'); ?></p>
</footer>

<script>
// Script para o menu responsivo (hamburguer)
const hamburgerButton = document.getElementById('hamburger-menu');
const mainNavList = document.getElementById('main-nav-list');

if (hamburgerButton && mainNavList) {
    hamburgerButton.addEventListener('click', function() {
        mainNavList.classList.toggle('mobile-open');
        const isExpanded = this.getAttribute('aria-expanded') === 'true';
        this.setAttribute('aria-expanded', !isExpanded);
    });
}
</script>

</body>
</html>

