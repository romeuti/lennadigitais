<?php
session_start(); //

// Garante que o carrinho exista na sessão, mesmo que vazio
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

// LÓGICA DO CONTADOR: Conta os itens após garantir que ele exista
$itens_no_carrinho = count($_SESSION['carrinho']);

include 'admin/banco.php'; //💾 Conexão com o banco de dados

// Lógica para adicionar item ao carrinho (vindo da página do produto)
if (isset($_POST['adicionar_carrinho'])) {
    $id_produto = (int)$_POST['id_produto'];
    $quantidade = (int)$_POST['quantidade'];

    if ($quantidade > 0) {
        if (isset($_SESSION['carrinho'][$id_produto])) {
            $_SESSION['carrinho'][$id_produto] += $quantidade;
        } else {
            $_SESSION['carrinho'][$id_produto] = $quantidade;
        }
    }
    header('Location: carrinho.php');
    exit;
}

// Lógica para remover item do carrinho
if (isset($_GET['remover'])) {
    $id_produto = (int)$_GET['remover'];
    if (isset($_SESSION['carrinho'][$id_produto])) {
        unset($_SESSION['carrinho'][$id_produto]);
    }
    header('Location: carrinho.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrinho de Compras</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/carrinho.css">
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
    </div>
    <nav class="main-navigation">
        <div class="container">
            <ul>
                <li><a href="index.php">🏡 Loja</a></li>
                <li><a href="lista_produtos.php">🛍️ Produtos</a></li>
                <li><a href="contato.php">📞 Contato</a></li>
                <li><a href="admin/gerenciar.php">⚙️ Gerenciar</a></li>
            </ul>
        </div>
    </nav>
</header>

<main>
<div class="container">
    <h1 class="page-title">Carrinho de Compras</h1>

    <?php if (!empty($_SESSION['carrinho'])): ?>
        <div class="cart-content"> <div class="cart-items">
                <table class="tabela-carrinho">
                    <thead>
                        <tr>
                            <th colspan="2">Produto</th>
                            <th>Preço</th>
                            <th>Quantidade</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_carrinho = 0;
                        $ids = implode(',', array_keys($_SESSION['carrinho']));
                        $sql = "SELECT id, nome, preco, capa_imagem FROM produtos WHERE id IN ($ids)";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($produto = $result->fetch_assoc()) {
                                $id = $produto['id'];
                                $quantidade = $_SESSION['carrinho'][$id];
                                $subtotal = $produto['preco'] * $quantidade;
                                $total_carrinho += $subtotal;
                        ?>
                        <tr>
                            <td class="product-image"><img src="<?php echo htmlspecialchars($produto['capa_imagem']); ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>"></td>
                            <td class="product-name"><?php echo htmlspecialchars($produto['nome']); ?></td>
                            <td>R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></td>
                            <td><?php echo $quantidade; ?></td>
                            <td>R$ <?php echo number_format($subtotal, 2, ',', '.'); ?></td>
                            <td><a href="carrinho.php?remover=<?php echo $id; ?>" class="remove-link" title="Remover Item">&times;</a></td>
                        </tr>
                        <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
                <div class="cart-total">
                    <strong>Total do Pedido:</strong>
                    <span>R$ <?php echo number_format($total_carrinho, 2, ',', '.'); ?></span>
                </div>
            </div>

            <div class="checkout-actions">
                <a href="cadastro_ou_login.php" class="btn-checkout">Finalizar Compra com PIX</a>
            </div>
        </div>
    <?php else: ?>
        <div class="cart-empty">
            <h2>Seu carrinho está vazio.</h2>
            <p>Adicione produtos à sua cesta para continuar.</p>
            <a href="lista_produtos.php" class="btn-primary" id="botao-ver-produtos">Ver Produtos</a>
        </div>
    <?php endif; ?>
</div>
</main>

<footer>
    <p>Lenna Personalizados, &copy; <?php echo date('Y'); ?></p>
</footer>

</body>
</html>

