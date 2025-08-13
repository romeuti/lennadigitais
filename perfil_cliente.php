<?php
session_start();

// Redireciona se o cliente NÃO estiver logado
if (!isset($_SESSION['cliente_logado']) || $_SESSION['cliente_logado'] !== true || !isset($_SESSION['cliente_id'])) {
    header('Location: clientes.php?erro=login_necessario'); // Redireciona para a página de login/cadastro
    exit();
}

// Garante que o carrinho exista na sessão, mesmo que vazio (para o contador)
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}
$itens_no_carrinho = count($_SESSION['carrinho']);


include 'admin/banco.php'; // Inclui a conexão com o banco de dados

$id_cliente = $_SESSION['cliente_id'];
$nome_cliente_logado = $_SESSION['cliente_nome'];

// Lógica de Paginação para pedidos do cliente
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Garante que a página seja no mínimo 1
$limit = 5; // 5 pedidos por página para o cliente
$offset = ($page - 1) * $limit;

// Obter o total de pedidos para o cliente logado
$total_rows_query = $conn->prepare("SELECT COUNT(*) AS total FROM pedidos WHERE id_cliente = ?");
$total_rows_query->bind_param("i", $id_cliente);
$total_rows_query->execute();
$total_rows = $total_rows_query->get_result()->fetch_assoc()['total'];
$total_rows_query->close();
$total_pages = ceil($total_rows / $limit);

// Buscar pedidos do cliente logado com informações detalhadas
$sql_pedidos_cliente = "
    SELECT
        p.id,
        p.data_pedido,
        p.status_pedido,
        SUM(ip.quantidade * ip.preco_unitario) AS valor_total
    FROM
        pedidos p
    JOIN
        itens_pedido ip ON p.id = ip.id_pedido
    WHERE
        p.id_cliente = ?
    GROUP BY
        p.id, p.data_pedido, p.status_pedido
    ORDER BY
        p.data_pedido DESC
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sql_pedidos_cliente);
$stmt->bind_param("iii", $id_cliente, $limit, $offset);
$stmt->execute();
$result_pedidos = $stmt->get_result();
$pedidos_cliente = $result_pedidos->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Pedidos - <?php echo htmlspecialchars($nome_cliente_logado); ?></title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/perfil_cliente.css"> <link href="img/icones/usuario.png" rel="icon" type="image/png">
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

<main>
    <div class="container profile-container">
        <h1>Olá, <?php echo htmlspecialchars($nome_cliente_logado); ?>!</h1>
        <p>Aqui você pode ver o histórico dos seus pedidos.</p>

        <div class="my-orders-section">
            <h2>Meus Pedidos</h2>
            <div class="table-wrapper">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>ID Pedido</th>
                            <th>Data</th>
                            <th>Valor Total</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pedidos_cliente)): ?>
                            <?php foreach ($pedidos_cliente as $pedido): ?>
                                <tr>
                                    <td data-label="ID Pedido">#<?php echo htmlspecialchars($pedido['id']); ?></td>
                                    <td data-label="Data"><?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></td>
                                    <td data-label="Valor Total">R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></td>
                                    <td data-label="Status" class="status-<?php echo strtolower(str_replace(' ', '-', $pedido['status_pedido'])); ?>">
                                        <?php echo htmlspecialchars($pedido['status_pedido']); ?>
                                    </td>
                                    <td data-label="Ações">
                                        <a href="detalhes_pedido_cliente.php?id=<?php echo $pedido['id']; ?>" class="btn btn-details">Ver Detalhes</a>
                                        <?php if ($pedido['status_pedido'] === 'Aguardando Pagamento'): ?>
                                            <a href="https://wa.me/5573991824641?text=Ol%C3%A1%2C%20estou%20enviando%20o%20comprovante%20do%20meu%20pedido%20%23<?php echo $pedido['id']; ?>." target="_blank" rel="noopener" class="btn btn-whatsapp-comprovante">
                                                Enviar Comprovante
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">Você ainda não possui pedidos.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        </div>

        <div class="profile-actions">
            <a href="logout_cliente.php" class="btn btn-logout">Sair da Conta</a>
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

