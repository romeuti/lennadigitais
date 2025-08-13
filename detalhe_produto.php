<?php
session_start();
include 'admin/banco.php'; // Inclui a conexão com o banco de dados

// Garante que o carrinho exista na sessão
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}
$itens_no_carrinho = count($_SESSION['carrinho']);

// Valida o ID do produto
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: lista_produtos.php');
    exit;
}
$id_produto = (int)$_GET['id'];

// --- LÓGICA PARA SALVAR NOVO FEEDBACK ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_feedback'])) {
    // Apenas clientes logados podem enviar feedback
    if (isset($_SESSION['cliente_logado']) && $_SESSION['cliente_logado'] === true) {
        $nome_cliente = $_SESSION['cliente_nome']; // Pega o nome do cliente da sessão
        $avaliacao = trim($_POST['avaliacao']);
        $nota = (int)$_POST['nota'];

        if (!empty($avaliacao) && $nota >= 1 && $nota <= 5) {
            $stmt_insert_feedback = $conn->prepare(
                "INSERT INTO feedback (id_produto, nome_cliente, avaliacao, nota, status) VALUES (?, ?, ?, ?, 'pendente')"
            );
            $stmt_insert_feedback->bind_param("isss", $id_produto, $nome_cliente, $avaliacao, $nota);
            if ($stmt_insert_feedback->execute()) {
                $_SESSION['feedback_sucesso'] = "Obrigado! Seu feedback foi enviado para análise.";
            } else {
                $_SESSION['feedback_erro'] = "Ocorreu um erro ao enviar seu feedback. Tente novamente.";
            }
            $stmt_insert_feedback->close();
        } else {
            $_SESSION['feedback_erro'] = "Por favor, preencha o texto da avaliação e selecione uma nota.";
        }
        // Redireciona para a mesma página para evitar reenvio do formulário
        header('Location: detalhe_produto.php?id=' . $id_produto);
        exit;
    }
}


// --- BUSCA OS DETALHES DO PRODUTO PRINCIPAL ---
$stmt_produto = $conn->prepare("SELECT p.id, p.nome, p.descricao, p.preco, p.capa_imagem, c.nome as nome_categoria FROM produtos p JOIN categorias c ON p.id_categoria = c.id WHERE p.id = ?");
$stmt_produto->bind_param("i", $id_produto);
$stmt_produto->execute();
$result_produto = $stmt_produto->get_result();

if ($result_produto->num_rows === 0) {
    header('Location: lista_produtos.php');
    exit;
}
$produto = $result_produto->fetch_assoc();
$stmt_produto->close();

// --- BUSCA AS IMAGENS DA GALERIA ---
$stmt_galeria = $conn->prepare("SELECT caminho_imagem FROM produto_imagens WHERE id_produto = ?");
$stmt_galeria->bind_param("i", $id_produto);
$stmt_galeria->execute();
$result_galeria = $stmt_galeria->get_result();
$imagens_galeria = [];
while ($row = $result_galeria->fetch_assoc()) {
    $imagens_galeria[] = $row['caminho_imagem'];
}
$stmt_galeria->close();

// --- BUSCA OS FEEDBACKS APROVADOS PARA ESTE PRODUTO ---
$stmt_feedback = $conn->prepare("SELECT nome_cliente, avaliacao, nota, data_envio FROM feedback WHERE id_produto = ? AND status = 'aprovado' ORDER BY data_envio DESC");
$stmt_feedback->bind_param("i", $id_produto);
$stmt_feedback->execute();
$result_feedback = $stmt_feedback->get_result();
$feedbacks_aprovados = [];
while ($row = $result_feedback->fetch_assoc()) {
    $feedbacks_aprovados[] = $row;
}
$stmt_feedback->close();


$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($produto['nome']); ?> - Lenna Personalizados</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/detalhe_produto.css">
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
    <div class="container product-detail-container">
        <div class="product-images">
            <div class="main-image">
                <img src="<?php echo htmlspecialchars($produto['capa_imagem']); ?>" alt="Imagem principal de <?php echo htmlspecialchars($produto['nome']); ?>" id="mainProductImage">
            </div>
            <div class="thumbnail-slider-container">
                <button class="slide-button prev" id="prev-slide">❮</button>
                <div class="thumbnail-track-container">
                    <div class="thumbnail-gallery" id="thumbnail-gallery">
                        <img src="<?php echo htmlspecialchars($produto['capa_imagem']); ?>" alt="Thumbnail 1" class="thumbnail active">
                        <?php foreach ($imagens_galeria as $index => $img_path): ?>
                            <img src="<?php echo htmlspecialchars($img_path); ?>" alt="Thumbnail <?php echo $index + 2; ?>" class="thumbnail">
                        <?php endforeach; ?>
                    </div>
                </div>
                <button class="slide-button next" id="next-slide">❯</button>
            </div>
        </div>

        <div class="product-info">
            <span class="category-tag"><?php echo htmlspecialchars($produto['nome_categoria']); ?></span>
            <h1><?php echo htmlspecialchars($produto['nome']); ?></h1>
            <p class="price">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></p>

            <form action="carrinho.php" method="POST">
                <input type="hidden" name="id_produto" value="<?php echo $produto['id']; ?>">
                <div class="quantity-selector">
                    <label for="quantidade">Quantidade:</label>
                    <input type="number" id="quantidade" name="quantidade" value="1" min="1">
                </div>
                <button type="submit" name="adicionar_carrinho" class="btn-add-cart-main">Adicionar ao Carrinho</button>
            </form>

            <div class="description-section">
                <h2>Descrição do Produto</h2>
                <div class="description-content">
                    <?php echo $produto['descricao']; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container feedback-container">
        <h2>Avaliações dos Clientes</h2>
        
        <div class="feedback-form-wrapper">
            <h3>Deixe sua avaliação</h3>
            <?php if (isset($_SESSION['cliente_logado']) && $_SESSION['cliente_logado'] === true): ?>
                <form action="detalhe_produto.php?id=<?php echo $id_produto; ?>" method="POST">
                    <div class="star-rating">
                        <input type="radio" id="5-stars" name="nota" value="5" /><label for="5-stars" class="star">&#9733;</label>
                        <input type="radio" id="4-stars" name="nota" value="4" /><label for="4-stars" class="star">&#9733;</label>
                        <input type="radio" id="3-stars" name="nota" value="3" /><label for="3-stars" class="star">&#9733;</label>
                        <input type="radio" id="2-stars" name="nota" value="2" /><label for="2-stars" class="star">&#9733;</label>
                        <input type="radio" id="1-star" name="nota" value="1" /><label for="1-star" class="star">&#9733;</label>
                    </div>
                    <textarea name="avaliacao" placeholder="Conte-nos o que você achou deste produto..." required></textarea>
                    <button type="submit" name="enviar_feedback">Enviar Avaliação</button>
                </form>
            <?php else: ?>
                <p class="login-prompt">Você precisa estar <a href="clientes.php">logado</a> para deixar uma avaliação.</p>
            <?php endif; ?>
             <?php
                // Exibe mensagens de sucesso ou erro do feedback
                if (isset($_SESSION['feedback_sucesso'])) {
                    echo '<p class="feedback-message success">' . $_SESSION['feedback_sucesso'] . '</p>';
                    unset($_SESSION['feedback_sucesso']);
                }
                if (isset($_SESSION['feedback_erro'])) {
                    echo '<p class="feedback-message error">' . $_SESSION['feedback_erro'] . '</p>';
                    unset($_SESSION['feedback_erro']);
                }
            ?>
        </div>
        
        <div class="feedback-list">
            <?php if (empty($feedbacks_aprovados)): ?>
                <p>Este produto ainda não tem avaliações. Seja o primeiro a avaliar!</p>
            <?php else: ?>
                <?php foreach($feedbacks_aprovados as $feedback): ?>
                    <div class="feedback-item">
                        <div class="feedback-header">
                            <span class="customer-name"><?php echo htmlspecialchars($feedback['nome_cliente']); ?></span>
                            <div class="static-star-rating">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <span class="star <?php echo ($i < $feedback['nota']) ? 'filled' : ''; ?>">&#9733;</span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p class="feedback-text"><?php echo nl2br(htmlspecialchars($feedback['avaliacao'])); ?></p>
                        <span class="feedback-date"><?php echo (new DateTime($feedback['data_envio']))->format('d/m/Y'); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<footer>
    <p>Lenna Personalizados, &copy; <?php echo date('Y'); ?></p>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mainImage = document.getElementById('mainProductImage');
    const gallery = document.getElementById('thumbnail-gallery');
    const thumbnails = gallery.querySelectorAll('.thumbnail');
    const trackContainer = document.querySelector('.thumbnail-track-container');
    const nextBtn = document.getElementById('next-slide');
    const prevBtn = document.getElementById('prev-slide');

    if (thumbnails.length === 0) return;

    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', function() {
            mainImage.src = this.src;
            thumbnails.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });

    let currentIndex = 0;
    const thumbnailWidth = thumbnails[0].offsetWidth;
    const galleryGap = parseInt(window.getComputedStyle(gallery).gap) || 10;
    const step = thumbnailWidth + galleryGap;

    function updateSlider() {
        const maxScroll = gallery.scrollWidth - trackContainer.clientWidth;
        const scrollAmount = currentIndex * step;
        gallery.style.transform = `translateX(-${Math.min(scrollAmount, maxScroll)}px)`;
        prevBtn.disabled = currentIndex === 0;
        nextBtn.disabled = (currentIndex * step) >= maxScroll - 1;
    }

    nextBtn.addEventListener('click', () => {
        const maxIndex = thumbnails.length - Math.floor(trackContainer.clientWidth / step);
        if (currentIndex < maxIndex) {
            currentIndex++;
            updateSlider();
        }
    });

    prevBtn.addEventListener('click', () => {
        if (currentIndex > 0) {
            currentIndex--;
            updateSlider();
        }
    });
    
    updateSlider();
    window.addEventListener('resize', updateSlider);
});
</script>
</body>
</html>

