<?php
//🔍 Exibe todos os erros do PHP para facilitar a depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'auth.php'; //🔒 Restringe o acesso apenas para administradores logados
include 'banco.php'; // 💾 Conexão com o banco de dados

// Busca todas as categorias para listar na tabela, ORDENADO POR ID
$lista_categorias = [];
$sql = "SELECT id, nome FROM categorias ORDER BY id ASC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $lista_categorias[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Categorias</title>
    <link rel="stylesheet" href="../css/categoria.css">
</head>
<body>

    <div id="modal-editar" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="fecharModal()">&times;</span>
            <h2>Editar Categoria</h2>
            <form action="processa_categoria.php" method="POST">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" id="edit-id-categoria" name="id_categoria">
                <div class="form-group">
                    <label for="edit-nome-categoria">Nome da Categoria:</label>
                    <input type="text" id="edit-nome-categoria" name="nome_categoria" required>
                </div>
                <button type="submit" class="btn-submit">Salvar Alterações</button>
            </form>
        </div>
    </div>


    <div class="container">
        <div class="top-actions">
            <a href="cadastrar_produto.php">↩️ Cadastrar Produto 📦</a>
            <a href="gerenciar.php">↩️ Gerenciar ⚙️</a>
            <a href="logout.php">🚪 Desconectar</a>
        </div>

        <h1>🏷️ Gerenciamento de Categorias 🖋️</h1>

        <?php
            if (isset($_SESSION['mensagem_sucesso'])) {
                echo '<div class="alert alert-success">' . $_SESSION['mensagem_sucesso'] . '</div>';
                unset($_SESSION['mensagem_sucesso']);
            }
            if (isset($_SESSION['mensagem_erro'])) {
                echo '<div class="alert alert-error">' . $_SESSION['mensagem_erro'] . '</div>';
                unset($_SESSION['mensagem_erro']);
            }
        ?>

        <div class="card">
            <h2>🛍️ Cadastrar Nova Categoria</h2>
            <form action="processa_categoria.php" method="POST">
                <input type="hidden" name="acao" value="cadastrar">
                <div class="form-group">
                    <label for="nome_categoria">Nome da Categoria:</label>
                    <input type="text" id="nome_categoria" name="nome_categoria" required>
                </div>
                <button type="submit" class="btn-submit">Salvar Categoria</button>
            </form>
        </div>

        <div class="card">
            <h2>Categorias Existentes</h2>
            <div class="table-wrapper">
                <table class="category-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($lista_categorias)): ?>
                            <?php foreach ($lista_categorias as $cat): ?>
                                <tr>
                                    <td data-label="ID"><?php echo $cat['id']; ?></td>
                                    <td data-label="Nome"><?php echo htmlspecialchars($cat['nome']); ?></td>
                                    <td data-label="Ações" class="action-links">
                                        <button class="btn-edit" onclick="abrirModal(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars(addslashes($cat['nome']), ENT_QUOTES); ?>')">Editar</button>
                                        <a href="processa_categoria.php?acao=excluir&id=<?php echo $cat['id']; ?>" class="btn-remove" onclick="return confirm('Tem certeza que deseja excluir esta categoria? Produtos associados podem ficar sem categoria.');">Excluir</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">Nenhuma categoria cadastrada.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<script>
    // Funções para controlar o modal de edição
    const modal = document.getElementById('modal-editar');

    function abrirModal(id, nome) {
        // Preenche o formulário do modal com os dados da categoria
        document.getElementById('edit-id-categoria').value = id;
        document.getElementById('edit-nome-categoria').value = nome;
        modal.style.display = 'block'; // Exibe o modal
    }

    function fecharModal() {
        modal.style.display = 'none'; // Oculta o modal
    }

    // Fecha o modal se o usuário clicar fora da área de conteúdo
    window.onclick = function(event) {
        if (event.target == modal) {
            fecharModal();
        }
    }
</script>

</body>
</html>

