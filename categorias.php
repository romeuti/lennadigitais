<?php 
include 'banco.php'; //💾 Conexão com o banco de dados

$id_categoria_selecionada = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$nome_categoria = "Todas as Categorias";

// Busca todas as categorias para o menu
$categorias = [];
$result_cats = $conn->query("SELECT id, nome FROM categorias ORDER BY nome");
while($row = $result_cats->fetch_assoc()) {
    $categorias[] = $row;
    if ($id_categoria_selecionada == $row['id']) {
        $nome_categoria = $row['nome'];
    }
}
?>

<style>
    /* Estilos específicos para esta página */
    .lista-categorias { list-style: none; padding: 0; margin-bottom: 20px; }
    .lista-categorias li { display: inline-block; margin-right: 15px; }
    .lista-produtos { display: flex; flex-wrap: wrap; gap: 20px; }
    .produto-card { background: #fff; border: 1px solid #ddd; padding: 15px; width: calc(33.333% - 20px); box-sizing: border-box; text-align: center; }
    .produto-card img { max-width: 100%; height: 150px; object-fit: cover; }
    .produto-card h3 { font-size: 18px; }
</style>

<h2><?php echo htmlspecialchars($nome_categoria); ?></h2>

<div class="card">
    <h4>Navegue por:</h4>
    <ul class="lista-categorias">
        <li><a href="categorias.php"><strong>Todas</strong></a></li>
        <?php foreach ($categorias as $cat): ?>
            <li><a href="categorias.php?id=<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nome']); ?></a></li>
        <?php endforeach; ?>
    </ul>
</div>

<div class="lista-produtos">
    <?php
    // Monta a query de produtos baseada na categoria selecionada
    $sql_produtos = "SELECT id, nome, preco, capa_imagem FROM produtos";
    if ($id_categoria_selecionada > 0) {
        $sql_produtos .= " WHERE id_categoria = " . $id_categoria_selecionada;
    }

    $result_prods = $conn->query($sql_produtos);
    if ($result_prods->num_rows > 0) {
        while($prod = $result_prods->fetch_assoc()) {
            echo '<div class="produto-card">';
            echo '<a href="lista_produtos.php?id=' . $prod['id'] . '">';
            if (!empty($prod['capa_imagem'])) {
                echo '<img src="' . htmlspecialchars($prod['capa_imagem']) . '" alt="' . htmlspecialchars($prod['nome']) . '">';
            } else {
                echo '<img src="img/placeholder.png" alt="Sem imagem">'; // Uma imagem padrão
            }
            echo '<h3>' . htmlspecialchars($prod['nome']) . '</h3>';
            echo '<p>R$ ' . number_format($prod['preco'], 2, ',', '.') . '</p>';
            echo '</a>';
            echo '</div>';
        }
    } else {
        echo "<p>Nenhum produto encontrado nesta categoria.</p>";
    }
    ?>
</div>

