<?php
//🔍 Exibe todos os erros do PHP para facilitar a depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); //🔒 Inicia a sessão para controle de autenticação e permissões
require_once 'auth.php'; //🔒 Restringe o acesso apenas para administradores logados
include 'banco.php';     // 💾 Conexão com o banco de dados

// LÓGICA DE AÇÕES (DESPESAS E CATEGORIAS)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ação para adicionar nova despesa
    if (isset($_POST['adicionar_despesa'])) {
        $descricao = trim($_POST['descricao']);
        $valor = str_replace(',', '.', $_POST['valor']);
        $id_categoria = $_POST['id_categoria_despesa'];
        $data_despesa = $_POST['data_despesa'];

        if (!empty($descricao) && is_numeric($valor) && !empty($data_despesa)) {
            $stmt = $conn->prepare("INSERT INTO despesas (descricao, valor, id_categoria_despesa, data_despesa) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sdis", $descricao, $valor, $id_categoria, $data_despesa);
            if ($stmt->execute()) {
                $_SESSION['mensagem_sucesso'] = "Despesa registrada com sucesso!";
            } else {
                $_SESSION['mensagem_erro'] = "Erro ao registrar despesa: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['mensagem_erro'] = "Por favor, preencha todos os campos corretamente.";
        }
    }
    // Ação para adicionar nova categoria de despesa
    elseif (isset($_POST['adicionar_categoria'])) {
        $nome_categoria = trim($_POST['nome_categoria']);
        if (!empty($nome_categoria)) {
            $stmt = $conn->prepare("INSERT INTO despesas_categorias (nome) VALUES (?)");
            $stmt->bind_param("s", $nome_categoria);
            $stmt->execute();
            $stmt->close();
        }
    }
    // Ação para editar uma categoria de despesa
    elseif (isset($_POST['editar_categoria'])) {
        $id_categoria = (int)$_POST['id_categoria'];
        $nome_categoria = trim($_POST['nome_categoria']);
        if ($id_categoria > 0 && !empty($nome_categoria)) {
            $stmt = $conn->prepare("UPDATE despesas_categorias SET nome = ? WHERE id = ?");
            $stmt->bind_param("si", $nome_categoria, $id_categoria);
            $stmt->execute();
            $stmt->close();
        }
    }
    header('Location: gerenciar_despesas.php');
    exit;
}

// Ação para remover categoria (via GET)
if (isset($_GET['remover_cat'])) {
    $id_categoria = (int)$_GET['remover_cat'];
    if ($id_categoria > 0) {
        $stmt = $conn->prepare("DELETE FROM despesas_categorias WHERE id = ?");
        $stmt->bind_param("i", $id_categoria);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: gerenciar_despesas.php');
    exit;
}

// LÓGICA PARA LISTAR DADOS
$sql_despesas = "SELECT d.*, dc.nome AS categoria_nome FROM despesas d LEFT JOIN despesas_categorias dc ON d.id_categoria_despesa = dc.id ORDER BY d.data_despesa DESC";
$despesas_result = $conn->query($sql_despesas);
$categorias_result = $conn->query("SELECT * FROM despesas_categorias ORDER BY nome ASC");
$categorias_para_modal_result = $conn->query("SELECT * FROM despesas_categorias ORDER BY nome ASC");

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Despesas</title>
    <link rel="stylesheet" href="../css/gerenciar_despesas.css">
    <link href="../img/icones/despesas.png" rel="icon" type="image/png">
</head>
<body>

    <div id="modal-categorias" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Gerenciar Categorias de Despesa</h2>
            
            <div class="modal-section">
                <h3>Adicionar Nova Categoria</h3>
                <form action="gerenciar_despesas.php" method="POST" class="form-inline">
                    <input type="text" name="nome_categoria" placeholder="Nome da nova categoria" required>
                    <button type="submit" name="adicionar_categoria">Adicionar</button>
                </form>
            </div>

            <div class="modal-section">
                <h3>Categorias Existentes</h3>
                <ul class="category-list">
                    <?php while($cat = $categorias_para_modal_result->fetch_assoc()): ?>
                        <li>
                            <form action="gerenciar_despesas.php" method="POST" class="form-inline">
                                <input type="hidden" name="id_categoria" value="<?php echo $cat['id']; ?>">
                                <input type="text" name="nome_categoria" value="<?php echo htmlspecialchars($cat['nome']); ?>" required>
                                <button type="submit" name="editar_categoria">Salvar</button>
                                <a href="?remover_cat=<?php echo $cat['id']; ?>" class="btn-remove" onclick="return confirm('Tem certeza? Despesas nesta categoria ficarão como Sem Categoria.');">Remover</a>
                            </form>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="top-actions">
            <a href="financeiro.php">↩️ Voltar ao Financeiro</a>
            <a href="gerenciar.php">⚙️ Gerenciar</a>
            <a href="logout.php">🚪 Desconectar</a>
        </div>
        <h1>💸 Gerenciar Despesas</h1>

        <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['mensagem_sucesso']; unset($_SESSION['mensagem_sucesso']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['mensagem_erro'])): ?>
            <div class="alert alert-error"><?php echo $_SESSION['mensagem_erro']; unset($_SESSION['mensagem_erro']); ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Adicionar Nova Despesa</h2>
            <form action="gerenciar_despesas.php" method="POST">
                <div class="form-row">
                    <div class="form-group"><label for="descricao">Descrição</label><input type="text" id="descricao" name="descricao" required></div>
                    <div class="form-group"><label for="valor">Valor (R$)</label><input type="text" id="valor" name="valor" placeholder="Ex: 59,90" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="categoria">Categoria</label>
                        <div class="category-select-wrapper">
                            <select id="categoria" name="id_categoria_despesa">
                                <?php mysqli_data_seek($categorias_result, 0); // Reinicia o ponteiro do resultado ?>
                                <?php while($cat = $categorias_result->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nome']); ?></option>
                                <?php endwhile; ?>
                            </select>
                            <button type="button" id="btn-gerenciar-categorias" class="btn-manage-cat">Gerenciar Categorias</button>
                        </div>
                    </div>
                    <div class="form-group"><label for="data_despesa">Data da Despesa</label><input type="date" id="data_despesa" name="data_despesa" value="<?php echo date('Y-m-d'); ?>" required></div>
                </div>
                <button type="submit" name="adicionar_despesa" class="btn-submit">Adicionar Despesa</button>
            </form>
        </div>

        <div class="card">
            <h2>Histórico de Despesas</h2>
            <div class="table-wrapper">
                <table class="responsive-table">
                    <thead><tr><th>Data</th><th>Descrição</th><th>Categoria</th><th>Valor</th></tr></thead>
                    <tbody>
                        <?php while($despesa = $despesas_result->fetch_assoc()): ?>
                        <tr>
                            <td data-label="Data"><?php echo (new DateTime($despesa['data_despesa']))->format('d/m/Y'); ?></td>
                            <td data-label="Descrição"><?php echo htmlspecialchars($despesa['descricao']); ?></td>
                            <td data-label="Categoria"><?php echo htmlspecialchars($despesa['categoria_nome'] ?? 'Sem Categoria'); ?></td>
                            <td data-label="Valor">R$ <?php echo number_format($despesa['valor'], 2, ',', '.'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<script>
    const modal = document.getElementById('modal-categorias');
    const btnOpenModal = document.getElementById('btn-gerenciar-categorias');
    const btnCloseModal = document.querySelector('.close-button');

    btnOpenModal.onclick = () => { modal.style.display = 'block'; }
    btnCloseModal.onclick = () => { modal.style.display = 'none'; }
    window.onclick = (event) => {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
</script>

</body>
</html>

