<?php
session_start(); //🔒 Inicia a sessão para controle de autenticação e permissões

// Garante que o carrinho exista na sessão, mesmo que vazio
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

// Conta os itens no carrinho após garantir que ele exista
$itens_no_carrinho = count($_SESSION['carrinho']);

include 'admin/banco.php'; // 💾 Conexão com o banco de dados

// --- NOVA LÓGICA DE FILTRO E BUSCA ---
$id_categoria_selecionada = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$termo_busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

// --- BUSCA TODAS AS CATEGORIAS PARA O MENU DO FILTRO ---
$categorias_filtro = [];
$result_cats = $conn->query("SELECT id, nome FROM categorias ORDER BY nome ASC");
if ($result_cats) {
    while ($row = $result_cats->fetch_assoc()) {
        $categorias_filtro[] = $row;
    }
}

// --- BUSCA OS PRODUTOS, APLICANDO FILTRO E/OU BUSCA ---
$sql_produtos = "SELECT id, nome, preco, capa_imagem FROM produtos WHERE 1=1";
$params = [];
$types = '';

// Adiciona filtro de categoria se selecionado
if ($id_categoria_selecionada > 0) {
    $sql_produtos .= " AND id_categoria = ?";
    $params[] = $id_categoria_selecionada;
    $types .= 'i';
}

// Adiciona filtro de busca se um termo foi digitado
if (!empty($termo_busca)) {
    $sql_produtos .= " AND (nome LIKE ? OR descricao LIKE ?)";
    $like_termo = "%" . $termo_busca . "%";
    $params[] = $like_termo;
    $params[] = $like_termo;
    $types .= 'ss';
}

$sql_produtos .= " ORDER BY nome ASC";

$stmt = $conn->prepare($sql_produtos);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result_produtos = $stmt->get_result();
$produtos = [];
while ($row = $result_produtos->fetch_assoc()) {
    $produtos[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todos os Produtos - Lenna Digitais</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/lista_produtos.css">
    <link href="img/icones/produto.png" rel="icon" type="image/png">
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
                    <input type="text" name="busca" placeholder="Buscar..." value="<?php echo htmlspecialchars($termo_busca); ?>">
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

<main>
    <div class="container">
        <h2 class="page-title">Todos os Produtos</h2>

        <div class="filter-container">
            <form action="lista_produtos.php" method="get" class="category-filter-form">
                <input type="hidden" name="busca" value="<?php echo htmlspecialchars($termo_busca); ?>">
                <label for="categoria-select">Filtrar por Categoria:</label>
                <div class="select-wrapper">
                    <select name="categoria" id="categoria-select" onchange="this.form.submit()">
                        <option value="">Todas as Categorias</option>
                        <?php foreach ($categorias_filtro as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php if ($id_categoria_selecionada == $cat['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($cat['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <noscript><button type="submit">Filtrar</button></noscript>
            </form>
        </div>

        <?php if (!empty($termo_busca)): ?>
            <p class="search-results-info">Resultados da busca por: <strong>"<?php echo htmlspecialchars($termo_busca); ?>"</strong></p>
        <?php endif; ?>

        <div class="product-grid">
            <?php if (!empty($produtos)): ?>
                <?php foreach ($produtos as $produto): ?>
                    <div class="product-card">
                        <a href="detalhe_produto.php?id=<?php echo $produto['id']; ?>">
                            <img src="<?php echo htmlspecialchars($produto['capa_imagem']); ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>">
                            <h3><?php echo htmlspecialchars($produto['nome']); ?></h3>
                            <p class="price">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></p>
                        </a>
                        <form action="carrinho.php" method="POST">
                            <input type="hidden" name="id_produto" value="<?php echo $produto['id']; ?>">
                            <input type="hidden" name="quantidade" value="1">
                            <button type="submit" name="adicionar_carrinho" class="btn-add-cart">Adicionar ao carrinho</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-results-message">Nenhum produto encontrado para os filtros selecionados.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<footer>
    <div class="container footer-container">
        <div class="copyright-text">
            <p>Lenna Digitais, &copy; <?php echo date('Y'); ?></p>
        </div>
        
        <div class="developer-credit">
            <a href="https://www.romeutech.shop" target="_blank" rel="noopener noreferrer">
                <img src="img/romeutech.png" alt="Desenvolvido por RomeuTech">
            </a>
            <p class="developer-text">Desenvolvido por RomeuTech</p>
        </div>
    </div>
</footer>
<script>
const hamburgerButton = document.getElementById('hamburger-menu');
const mainNavList = document.getElementById('main-nav-list');

hamburgerButton.addEventListener('click', function() {
    mainNavList.classList.toggle('mobile-open');
    const isExpanded = this.getAttribute('aria-expanded') === 'true';
    this.setAttribute('aria-expanded', !isExpanded);
});
</script>

</body>
</html>

