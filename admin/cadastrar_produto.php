<?php
//🔍 Exibe todos os erros do PHP para facilitar a depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); //🔒 Inicia a sessão para controle de autenticação e permissões
require_once 'auth.php'; //🔒 Restringe o acesso apenas para administradores logados
include 'banco.php';     // 💾 Conexão com o banco de dados

// Carrega categorias
$categorias = [];
$sql = "SELECT id, nome FROM categorias ORDER BY nome";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
    }
}

// Se for edição, carrega produto e imagens da galeria
$produto = null;
$imagens = [];
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);

    // Dados do produto
    $stmt = $conn->prepare("
        SELECT nome, descricao, preco, id_categoria, capa_imagem
          FROM produtos
         WHERE id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resP = $stmt->get_result();
    if ($resP->num_rows === 1) {
        $produto = $resP->fetch_assoc();
    }
    $stmt->close();

    // Imagens da galeria
    $stmt2 = $conn->prepare("
        SELECT id, caminho_imagem
          FROM produto_imagens
         WHERE id_produto = ?
    ");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $resI = $stmt2->get_result();
    while ($row = $resI->fetch_assoc()) {
        $imagens[] = $row;
    }
    $stmt2->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Produto</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/cadastrar_produto.css">
    <link href="../img/icones/produto.png" rel="icon" type="image/png">

    <script src="https://cdn.tiny.cloud/1/xlck5vxrnz1ac67yhebv1k...ndmn/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
      tinymce.init({
        selector: 'textarea#descricao-produto',
        plugins: 'lists link image table code help wordcount',
        toolbar: 'undo redo | bold italic | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist',
        skin: 'oxide-dark',
        content_css: 'dark',
        language: 'pt_BR',
        language_url: 'https://cdn.tiny.cloud/1/no-api-key/tinymce/7/langs/pt_BR.js'
      });
    </script>
    <script>
      // carrega template via AJAX se editor estiver vazio
      document.addEventListener('DOMContentLoaded', () => {
        const sel = document.getElementById('categoria');
        function carregaModelo(catId) {
          const editor = tinymce.get('descricao-produto');
          if (!editor || editor.getContent({format:'text'}).trim()) return;
          fetch(`get_template.php?categoria=${catId}`)
            .then(r=>r.json())
            .then(j=>{
              if (j.conteudo) editor.setContent(j.conteudo);
            });
        }
        // ao iniciar (edição), tentar carregar
        if (sel.value) carregaModelo(sel.value);
        sel.addEventListener('change', e => carregaModelo(e.target.value));
      });
    </script>
</head>
<body>
<div class="container">

    <div class="top-actions">
        <a href="../index.php">🏠 Início</a>
        <a href="gerenciar.php">↩️ Gerenciar ⚙️</a>
        <a href="gerenciar_produtos.php">↩️ Gerenciar Produtos 📋</a>
        <a href="logout.php">🚪 Desconectar</a>
    </div>

    <h2>🛍️ Cadastrar Novo Produto</h2>

    <?php
        // Bloco que exibe mensagem de sucesso/erro
        if (isset($_SESSION['mensagem_sucesso'])) {
            echo '<div class="alert alert-success">' . $_SESSION['mensagem_sucesso'] . '</div>';
            unset($_SESSION['mensagem_sucesso']);
        }
        if (isset($_SESSION['mensagem_erro'])) {
            echo '<div class="alert alert-error">' . $_SESSION['mensagem_erro'] . '</div>';
            unset($_SESSION['mensagem_erro']);
        }
    ?>

    <form action="salvar_produto.php" method="POST" enctype="multipart/form-data">
        <?php if ($produto): ?>
            <input type="hidden" name="id" value="<?php echo $id; ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="nome">Nome do Produto:</label>
            <input
                type="text" id="nome" name="nome" required
                value="<?php echo $produto ? htmlspecialchars($produto['nome'], ENT_QUOTES) : ''; ?>"
            >
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="preco">Preço (Ex: 9,50):</label>
                <input
                    type="text" id="preco" name="preco" required
                    value="<?php echo $produto ? number_format($produto['preco'], 2, ',', '.') : ''; ?>"
                >
            </div>
            <div class="form-group">
                <label for="categoria">Categoria:</label>
                <select id="categoria" name="id_categoria" required>
                    <option value="">-- Escolha uma Categoria --</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option
                            value="<?php echo $cat['id']; ?>"
                            <?php echo ($produto && $produto['id_categoria'] == $cat['id']) ? 'selected' : ''; ?>
                        >
                            <?php echo htmlspecialchars($cat['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <a href="gerenciar_categorias.php" class="btn-new-category">🏷️ Nova Categoria</a>
        </div>

        <div class="form-group">
            <label for="descricao-produto">Descrição:</label>
            <textarea id="descricao-produto" name="descricao" rows="10"><?php
                echo $produto ? htmlspecialchars($produto['descricao']) : '';
            ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="imagem_capa">Imagem de Capa (Principal):</label>
                <input type="file" id="imagem_capa" name="imagem_capa" accept="image/*">
                <div id="preview-capa" class="image-preview-grid">
                    <?php if ($produto && !empty($produto['capa_imagem'])): ?>
                        <div class="preview-item-container">
                            <img
                                src="../<?php echo htmlspecialchars($produto['capa_imagem']); ?>"
                                class="preview-image"
                            >
                            <button type="button" class="remove-preview-btn">&times;</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="form-group">
                <label for="outras_imagens">Outras Imagens (Galeria):</label>
                <input type="file" id="outras_imagens" name="outras_imagens[]" multiple accept="image/*">
                <div id="preview-galeria" class="image-preview-grid">
                    <?php foreach ($imagens as $img): ?>
                        <div class="preview-item-container">
                            <img
                                src="../<?php echo htmlspecialchars($img['caminho_imagem']); ?>"
                                class="preview-image"
                            >
                            <button type="button" class="remove-preview-btn">&times;</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-submit">Salvar Produto</button>
    </form>
</div>

<script>
// --- LÓGICA DE PREVIEW COM REMOÇÃO
document.addEventListener('DOMContentLoaded', function() {
    function removeFileFromInput(inputElement, indexToRemove) {
        const dt = new DataTransfer();
        Array.from(inputElement.files)
            .filter((_, i) => i !== indexToRemove)
            .forEach(f => dt.items.add(f));
        inputElement.files = dt.files;
        inputElement.dispatchEvent(new Event('change'));
    }

    ['imagem_capa', 'outras_imagens'].forEach(function(id) {
        const input = document.getElementById(id);
        const previewArea = document.getElementById(
            id === 'imagem_capa' ? 'preview-capa' : 'preview-galeria'
        );
        input.addEventListener('change', function() {
            previewArea.innerHTML = '';
            Array.from(this.files).forEach(function(file, index) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'preview-item-container';

                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'preview-image';

                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'remove-preview-btn';
                    btn.innerHTML = '&times;';
                    btn.addEventListener('click', function() {
                        removeFileFromInput(input, index);
                    });

                    div.appendChild(img);
                    div.appendChild(btn);
                    previewArea.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        });
    });
});
</script>
</body>
</html>

