<?php
//🔍 Exibe todos os erros do PHP para facilitar a depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); //🔒 Inicia a sessão para controle de autenticação e permissões
require_once 'auth.php'; //🔒 Restringe o acesso apenas para administradores logados
include 'banco.php'; // 💾 Conexão com o banco de dados

// --- Variáveis para controle de formulário e dados a serem editados ---
$acao_get = $_GET['action'] ?? ''; // 'editar' ou 'remover'
$id_alvo_get = intval($_GET['id'] ?? 0); // ID do usuário a ser editado/removido

$dados_para_edicao = null; // Irá armazenar os dados do usuário se estiver em modo de edição

// --- LÓGICA DE PROCESSAMENTO DE FORMULÁRIO (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_action_type = $_POST['form_action_type'] ?? ''; // 'adicionar_usuario_admin' ou 'salvar_usuario_admin'
    $post_id = intval($_POST['id'] ?? 0);

    // Processar Adicionar Novo Usuário Administrativo
    if ($form_action_type === 'adicionar_usuario_admin') {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha_plana = $_POST['senha'] ?? '';

        if (empty($nome) || empty($email) || empty($senha_plana)) {
            $_SESSION['mensagem_erro'] = "Preencha todos os campos para cadastrar um novo administrador.";
        } else {
            $senha_hash = password_hash($senha_plana, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $nome, $email, $senha_hash);
            if ($stmt->execute()) {
                $_SESSION['mensagem_sucesso'] = "Novo usuário administrativo '{$nome}' cadastrado com sucesso!";
            } else {
                if ($conn->errno == 1062) {
                    $_SESSION['mensagem_erro'] = "Erro: Já existe um usuário com este e-mail.";
                } else {
                    $_SESSION['mensagem_erro'] = "Erro ao cadastrar administrador: " . $stmt->error;
                }
            }
            $stmt->close();
        }
        header('Location: usuario.php');
        exit;
    }

    // Processar Salvar Usuário Administrativo Existente
    elseif ($form_action_type === 'salvar_usuario_admin' && $post_id > 0) {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha_nova = $_POST['senha'] ?? '';

        if (empty($nome) || empty($email)) {
            $_SESSION['mensagem_erro'] = "Nome e e-mail são obrigatórios para atualizar o usuário administrativo.";
        } else {
            if ($post_id == 1) {
                if ($_SESSION['usuario_id'] != 1) {
                    $_SESSION['mensagem_erro'] = "Você não tem permissão para alterar os dados do superusuário.";
                } else {
                    $_SESSION['mensagem_erro'] = "Os dados do superusuário (nome, email e senha) não podem ser alterados por esta interface.";
                }
            } else {
                $sql_update = "UPDATE usuarios SET nome = ?, email = ?";
                $params = "ss";
                $valores = [$nome, $email];

                if (!empty($senha_nova)) {
                    $senha_hash = password_hash($senha_nova, PASSWORD_BCRYPT, ['cost' => 12]);
                    $sql_update .= ", senha = ?";
                    $params .= "s";
                    $valores[] = $senha_hash;
                }
                $sql_update .= " WHERE id = ?";
                $params .= "i";
                $valores[] = $post_id;

                $stmt = $conn->prepare($sql_update);
                $stmt->bind_param($params, ...$valores);

                if ($stmt->execute()) {
                    $_SESSION['mensagem_sucesso'] = "Usuário administrativo '{$nome}' atualizado com sucesso!";
                } else {
                    if ($conn->errno == 1062) {
                        $_SESSION['mensagem_erro'] = "Erro: Já existe um usuário com este e-mail.";
                    } else {
                        $_SESSION['mensagem_erro'] = "Erro ao atualizar usuário administrativo: " . $stmt->error;
                    }
                }
                $stmt->close();
            }
        }
        header('Location: usuario.php');
        exit;
    }
}

// --- LÓGICA DE REMOÇÃO (GET) ---
if ($acao_get === 'remover' && $id_alvo_get > 0) {
    if ($id_alvo_get == 1) {
        $_SESSION['mensagem_erro'] = "Não é possível remover o superusuário.";
    } elseif ($id_alvo_get == $_SESSION['usuario_id']) {
        $_SESSION['mensagem_erro'] = "Você não pode remover seu próprio usuário.";
    } else {
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id_alvo_get);
        if ($stmt->execute()) {
            $_SESSION['mensagem_sucesso'] = "Usuário administrativo removido com sucesso!";
        } else {
            $_SESSION['mensagem_erro'] = "Erro ao remover usuário administrativo: " . $stmt->error;
        }
        $stmt->close();
    }
    header('Location: usuario.php');
    exit;
}

// --- LÓGICA DE CARREGAMENTO PARA FORMULÁRIO DE EDIÇÃO (GET) ---
if ($acao_get === 'editar' && $id_alvo_get > 0) {
    $stmt = $conn->prepare("SELECT id, nome, email FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id_alvo_get);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $dados_para_edicao = $result->fetch_assoc();
    } else {
        $_SESSION['mensagem_erro'] = "Usuário administrativo não encontrado para edição.";
        header('Location: usuario.php'); exit;
    }
    $stmt->close();
}

// --- LÓGICA PARA LISTAR USUÁRIOS ADMIN (READ) ---
$usuarios_sql = "SELECT id, nome, email FROM usuarios ORDER BY nome ASC";
$usuarios_result = $conn->query($usuarios_sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Usuários Administrativos</title>
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

        <h1>Gerenciamento de Usuários Administrativos</h1>

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
                <h2>Editar Usuário Administrativo</h2>
                <form action="usuario.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($dados_para_edicao['id']); ?>">
                    <input type="hidden" name="form_action_type" value="salvar_usuario_admin">

                    <div class="input-group">
                        <label for="nome">Nome do Administrador</label>
                        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($dados_para_edicao['nome']); ?>" required
                            <?php echo ($dados_para_edicao['id'] == 1) ? 'readonly' : ''; ?>>
                        <?php if ($dados_para_edicao['id'] == 1): ?>
                            <p class="note">Os dados do superusuário (nome, email e senha) não podem ser alterados por esta interface.</p>
                        <?php endif; ?>
                    </div>
                    <div class="input-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($dados_para_edicao['email']); ?>" required
                            <?php echo ($dados_para_edicao['id'] == 1) ? 'readonly' : ''; ?>>
                    </div>
                    <div class="input-group">
                        <label for="senha">Nova Senha (deixe em branco para manter a atual)</label>
                        <input type="password" id="senha" name="senha"
                            <?php echo ($dados_para_edicao['id'] == 1) ? 'readonly' : ''; ?>>
                        <?php if ($dados_para_edicao['id'] != 1): ?>
                            <p class="note">A senha será criptografada com hash seguro.</p>
                        <?php endif; ?>
                    </div>
                    <button type="submit" <?php echo ($dados_para_edicao['id'] == 1) ? 'disabled' : ''; ?>>
                        Salvar Alterações
                    </button>
                    <a href="usuario.php" class="btn btn-secondary mt-10">Cancelar Edição</a>
                </form>
            </div>
            <hr class="divider">
        <?php endif; ?>

        <div class="card">
            <h2>Usuários Administrativos</h2>
            <div class="table-wrapper">
                <table class="client-list">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($usuario = $usuarios_result->fetch_assoc()): ?>
                            <tr>
                                <td data-label="ID"><?php echo htmlspecialchars($usuario['id']); ?></td>
                                <td data-label="Nome"><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                <td data-label="Email"><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td data-label="Ações" class="action-links">
                                    <?php
                                    if ($usuario['id'] == 1) {
                                        if ($_SESSION['usuario_id'] == 1) {
                                            echo '<a href="?action=editar&id=' . htmlspecialchars($usuario['id']) . '" class="btn-edit">Visualizar/Editar</a>';
                                        } else {
                                            echo '<span class="disabled-action">Visualizar/Editar</span>';
                                        }
                                        echo '<span class="disabled-action">Remover (Superusuário)</span>';
                                    } else {
                                        echo '<a href="?action=editar&id=' . htmlspecialchars($usuario['id']) . '" class="btn-edit">Alterar</a>';
                                        if ($usuario['id'] == $_SESSION['usuario_id']) {
                                            echo '<span class="disabled-action">Remover (Seu Usuário)</span>';
                                        } else {
                                            echo '<a href="?action=remover&id=' . htmlspecialchars($usuario['id']) . '" ' .
                                                 'class="btn-remove" ' .
                                                 'onclick="return confirm(\'Tem certeza que deseja remover o usuário administrativo ' . htmlspecialchars($usuario['nome']) . '?\');">Remover</a>';
                                        }
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h2>Cadastrar Novo Usuário Administrativo</h2>
            <form action="usuario.php" method="POST">
                <input type="hidden" name="form_action_type" value="adicionar_usuario_admin">
                <div class="input-group">
                    <label for="admin-nome">Nome do Administrador</label>
                    <input type="text" id="admin-nome" name="nome" required>
                </div>
                <div class="input-group">
                    <label for="admin-email">Email</label>
                    <input type="email" id="admin-email" name="email" required>
                </div>
                <div class="input-group">
                    <label for="admin-senha">Senha</label>
                    <input type="password" id="admin-senha" name="senha" required>
                </div>
                <p class="note"><strong>Atenção:</strong> A senha será criptografada com hash seguro.</p>
                <button type="submit">Cadastrar Administrador</button>
            </form>
        </div>
    </div>
</body>
</html>

