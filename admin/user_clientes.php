<?php
//🔍 Exibe todos os erros do PHP para facilitar a depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); //🔒 Inicia a sessão para controle de autenticação e permissões
require_once 'auth.php'; //🔒 Restringe o acesso apenas para administradores logados
include 'banco.php'; // 💾 Conexão com o banco de dados

// --- Variáveis para controle ---
$acao_get = $_GET['action'] ?? ''; // 'editar' ou 'remover'
$id_alvo_get = intval($_GET['id'] ?? 0); // ID do cliente a ser editado/removido

$dados_para_edicao = null; // Armazena dados do cliente para o formulário de edição

// --- LÓGICA DE PROCESSAMENTO DE FORMULÁRIO (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = intval($_POST['id'] ?? 0);

    // Processar Salvar Cliente Existente
    if (isset($_POST['form_action_type']) && $_POST['form_action_type'] === 'salvar_cliente' && $post_id > 0) {
        $nome = trim($_POST['nome'] ?? '');
        $sobrenome = trim($_POST['sobrenome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $celular = trim($_POST['celular'] ?? '');

        if (empty($nome) || empty($sobrenome) || empty($email)) {
            $_SESSION['mensagem_erro'] = "Nome, sobrenome e e-mail são obrigatórios para atualizar o cliente.";
        } else {
            $stmt = $conn->prepare("UPDATE clientes SET nome = ?, sobrenome = ?, email = ?, celular = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $nome, $sobrenome, $email, $celular, $post_id);

            if ($stmt->execute()) {
                $_SESSION['mensagem_sucesso'] = "Cliente '{$nome} {$sobrenome}' atualizado com sucesso!";
            } else {
                if ($conn->errno == 1062) {
                    $_SESSION['mensagem_erro'] = "Erro: Já existe um cliente com este e-mail.";
                } else {
                    $_SESSION['mensagem_erro'] = "Erro ao atualizar cliente: " . $stmt->error;
                }
            }
            $stmt->close();
        }
        header('Location: user_clientes.php');
        exit;
    }
}

// --- LÓGICA DE REMOÇÃO (GET) ---
if ($acao_get === 'remover' && $id_alvo_get > 0) {
    // A FK em 'pedidos' para 'id_cliente' já tem 'ON DELETE SET NULL'
    $stmt = $conn->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->bind_param("i", $id_alvo_get);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $_SESSION['mensagem_sucesso'] = "Cliente removido com sucesso!";
        } else {
            $_SESSION['mensagem_erro'] = "Nenhum cliente encontrado com o ID fornecido para remoção.";
        }
    } else {
        $_SESSION['mensagem_erro'] = "Erro ao remover cliente: " . $stmt->error;
    }
    $stmt->close();
    header('Location: user_clientes.php');
    exit;
}

// --- LÓGICA DE CARREGAMENTO PARA FORMULÁRIO DE EDIÇÃO (GET) ---
if ($acao_get === 'editar' && $id_alvo_get > 0) {
    $stmt = $conn->prepare("SELECT id, nome, sobrenome, email, celular FROM clientes WHERE id = ?");
    $stmt->bind_param("i", $id_alvo_get);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $dados_para_edicao = $result->fetch_assoc();
    } else {
        $_SESSION['mensagem_erro'] = "Cliente não encontrado para edição.";
        header('Location: user_clientes.php'); exit;
    }
    $stmt->close();
}

// --- LÓGICA DE PAGINAÇÃO PARA CLIENTES (READ) ---
$clientes_por_pagina = 15; // Ajustado para 15 conforme solicitado
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;

$offset = ($pagina_atual - 1) * $clientes_por_pagina;

$total_clientes_sql = "SELECT COUNT(*) FROM clientes";
$total_result = $conn->query($total_clientes_sql);
$total_clientes = $total_result->fetch_row()[0];
$total_paginas = ceil($total_clientes / $clientes_por_pagina);

$stmt = $conn->prepare("SELECT id, nome, sobrenome, email, celular FROM clientes ORDER BY nome LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $clientes_por_pagina, $offset);
$stmt->execute();
$clientes_result = $stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Clientes</title>
    <link rel="stylesheet" href="../css/usuario.css">
    <link href="../img/icones/usuario.png" rel="icon" type="image/png">
</head>
<body>
    <div class="management-container">
        <div class="top-actions">
            <a href="../index.php">🏠 Início</a>
            <a href="gerenciar.php">↩️ Gerenciar ⚙️</a>
            <a href="logout.php">🚪 Desconectar</a>
        </div>

        <h1>Gerenciamento de Clientes</h1>

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

        <?php if ($dados_para_edicao): ?>
            <div class="card">
                <h2>Editar Cliente</h2>
                <form action="user_clientes.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($dados_para_edicao['id']); ?>">
                    <input type="hidden" name="form_action_type" value="salvar_cliente">
                    <div class="input-group">
                        <label for="nome">Nome</label>
                        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($dados_para_edicao['nome']); ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="sobrenome">Sobrenome</label>
                        <input type="text" id="sobrenome" name="sobrenome" value="<?php echo htmlspecialchars($dados_para_edicao['sobrenome']); ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($dados_para_edicao['email']); ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="celular">Celular</label>
                        <input type="text" id="celular" name="celular" value="<?php echo htmlspecialchars($dados_para_edicao['celular']); ?>">
                    </div>
                    <p class="note">A senha do cliente não pode ser alterada por aqui. O cliente deve usar a função "Esqueci a Senha" no portal.</p>
                    <button type="submit">Salvar Alterações</button>
                    <a href="user_clientes.php" class="btn btn-secondary mt-10">Cancelar Edição</a>
                </form>
            </div>
            <hr class="divider">
        <?php endif; ?>

        <div class="card">
            <h2>Lista de Clientes</h2>
            <div class="table-wrapper">
                <table class="client-list">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome Completo</th>
                            <th>Email</th>
                            <th>Celular</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($clientes_result->num_rows > 0): ?>
                            <?php while($cliente = $clientes_result->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="ID"><?php echo htmlspecialchars($cliente['id']); ?></td>
                                    <td data-label="Nome Completo"><?php echo htmlspecialchars($cliente['nome'] . ' ' . $cliente['sobrenome']); ?></td>
                                    <td data-label="Email"><?php echo htmlspecialchars($cliente['email']); ?></td>
                                    <td data-label="Celular"><?php echo htmlspecialchars($cliente['celular']); ?></td>
                                    <td data-label="Ações" class="action-links">
                                        <a href="?action=editar&id=<?php echo htmlspecialchars($cliente['id']); ?>" class="btn-edit">Alterar</a>
                                        <a href="?action=remover&id=<?php echo htmlspecialchars($cliente['id']); ?>"
                                           class="btn-remove"
                                           onclick="return confirm('Tem certeza que deseja remover o cliente <?php echo htmlspecialchars($cliente['nome'] . ' ' . $cliente['sobrenome']); ?>? Os pedidos associados não serão removidos.');">Remover</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">Nenhum cliente encontrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="?pagina=<?php echo $i; ?>" class="<?php if($pagina_atual == $i) echo 'active'; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</body>
</html>

