<?php 
// 1. Inclui o cabeçalho completo da página
include 'includes/header.php'; 

// 2. FUNÇÃO PARA BUSCAR PRODUTOS POR CATEGORIA (RESTAURADA)
function getProductsByCategory($conn, $categoryId) {
    $products = [];
    $stmt = $conn->prepare("SELECT id, nome, preco, capa_imagem FROM produtos WHERE id_categoria = ?");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    $stmt->close();
    return $products;
}

// 3. BUSCA OS PRODUTOS DAS CATEGORIAS ESPECÍFICAS DA PÁGINA INICIAL
$toposDeBolo = getProductsByCategory($conn, 1);
$arquivosDeCorte = getProductsByCategory($conn, 2);
$conn->close(); // Fecha a conexão após buscar os produtos
?>

<main>
    <div class="container">
        <section class="category-slider">
            <h2 class="category-title">Topos de Bolo</h2>
            <div class="slider-container">
                <div class="slider-wrapper">
                    <?php if (!empty($toposDeBolo)): ?>
                        <?php foreach ($toposDeBolo as $produto): ?>
                            <div class="product-card">
                                <a href="detalhe_produto.php?id=<?php echo $produto['id']; ?>">
                                    <img src="<?php echo htmlspecialchars($produto['capa_imagem']); ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>">
                                    <h3><?php echo htmlspecialchars($produto['nome']); ?></h3>
                                    <p class="price">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></p>
                                </a>
                                <form class="ajax-add-cart-form" method="POST">
                                    <input type="hidden" name="id_produto" value="<?php echo $produto['id']; ?>">
                                    <button type="submit" class="btn-add-cart">Adicionar ao carrinho</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-products-slider">Nenhum produto encontrado nesta categoria.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="category-slider">
            <h2 class="category-title">Arquivos de Corte</h2>
            <div class="slider-container">
                <div class="slider-wrapper">
                    <?php if (!empty($arquivosDeCorte)): ?>
                        <?php foreach ($arquivosDeCorte as $produto): ?>
                            <div class="product-card">
                                <a href="detalhe_produto.php?id=<?php echo $produto['id']; ?>">
                                    <img src="<?php echo htmlspecialchars($produto['capa_imagem']); ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>">
                                    <h3><?php echo htmlspecialchars($produto['nome']); ?></h3>
                                    <p class="price">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></p>
                                </a>
                                <form class="ajax-add-cart-form" method="POST">
                                    <input type="hidden" name="id_produto" value="<?php echo $produto['id']; ?>">
                                    <button type="submit" class="btn-add-cart">Adicionar ao carrinho</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-products-slider">Nenhum produto encontrado nesta categoria.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</main>

<?php 
// 4. Inclui o rodapé e os scripts
include 'includes/footer.php'; 
?>

