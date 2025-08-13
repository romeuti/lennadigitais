<?php
//🔍 Exibe todos os erros do PHP para facilitar a depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuração de log temporária para depuração (REMOVER EM PRODUÇÃO)
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/log/php_debug.log');

session_start();
// Definir o fuso horário padrão para o PHP
date_default_timezone_set('America/Sao_Paulo');

include 'admin/banco.php'; // Conexão com o banco de dados

$mensagem_sucesso = '';
$mensagem_erro = '';

// Limpa mensagens de sessão de sucesso/erro
if (isset($_SESSION['mensagem_sucesso'])) {
    $mensagem_sucesso = $_SESSION['mensagem_sucesso'];
    unset($_SESSION['mensagem_sucesso']);
}
if (isset($_SESSION['mensagem_erro'])) {
    $mensagem_erro = $_SESSION['mensagem_erro'];
    unset($_SESSION['mensagem_erro']);
}

$token = $_GET['token'] ?? '';
$cliente_email = ''; // Para exibir no formulário

// Adicionar logs para depuração no início (GET request)
error_log("--- RESETAR_SENHA (GET) ---");
error_log("Token recebido na URL: " . ($token ? $token : 'Nenhum token na URL'));


// 1. Validar o token e buscar o e-mail associado para exibir o formulário
if (empty($token)) {
    $mensagem_erro = "Token de redefinição de senha inválido ou ausente.";
} else {
    $stmt = $conn->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $reset_info = $result->fetch_assoc();
    $stmt->close();

    // Logs detalhados após a busca no DB
    if ($reset_info) {
        error_log("GET: Info do DB para token: " . print_r($reset_info, true));
        error_log("GET: expires_at (DB): " . $reset_info['expires_at']);
        error_log("GET: strtotime(expires_at): " . strtotime($reset_info['expires_at']));
        error_log("GET: time() atual: " . time());
        error_log("GET: Comparação: " . (strtotime($reset_info['expires_at']) < time() ? 'EXPIRADO' : 'VÁLIDO'));
    } else {
        error_log("GET: Token NÃO encontrado no DB.");
    }


    if (!$reset_info) {
        $mensagem_erro = "Token de redefinição de senha inválido ou não encontrado.";
    } elseif (strtotime($reset_info['expires_at']) < time()) {
        $mensagem_erro = "Token de redefinição de senha expirado. Por favor, solicite um novo.";
    } else {
        $cliente_email = $reset_info['email'];
        // Se o token é válido no GET, garante que nenhuma mensagem de erro inicial impeça o formulário.
        $mensagem_erro = ''; // Importante: limpa a mensagem de erro se o token for válido no GET.
    }
}

// 2. Processar a nova senha (quando o formulário é enviado - POST request)
// O bloco POST agora será sempre executado se for um POST. A validação de erro é interna ao bloco.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("--- RESETAR_SENHA (POST) ---"); // Início do bloco POST
    // O token do POST deve vir do hidden input do formulário
    $token_from_post = trim($_POST['token'] ?? ''); // Use uma nova variável para clareza
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';

    // Logs no POST
    error_log("POST: Token recebido no POST (hidden field): " . ($token_from_post ? $token_from_post : 'Nulo no POST'));
    error_log("POST: Nova Senha preenchida? " . (!empty($nova_senha) ? 'Sim' : 'Não'));
    error_log("POST: Confirma Senha preenchida? " . (!empty($confirma_senha) ? 'Sim' : 'Não'));

    // Revalida o token para garantir que não foi adulterado ou expirou entre o GET e o POST
    $stmt = $conn->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token_from_post); // Use o token do POST aqui
    $stmt->execute();
    $result = $stmt->get_result();
    $reset_info_post = $result->fetch_assoc();
    $stmt->close();

    // Logs detalhados após a busca no DB no POST
    if ($reset_info_post) {
        error_log("POST: Info do DB para token (POST): " . print_r($reset_info_post, true));
        error_log("POST: expires_at (DB no POST): " . $reset_info_post['expires_at']);
        error_log("POST: strtotime(expires_at) (POST): " . strtotime($reset_info_post['expires_at']));
        error_log("POST: time() atual (POST): " . time());
        error_log("POST: Comparação (POST): " . (strtotime($reset_info_post['expires_at']) < time() ? 'EXPIRADO' : 'VÁLIDO'));
    } else {
        error_log("POST: Token NÃO encontrado no DB (POST).");
    }

    // A lógica de validação de erro para o POST:
    if (!$reset_info_post) {
        $mensagem_erro = "Token de redefinição de senha inválido ou não encontrado (após post).";
    } elseif (strtotime($reset_info_post['expires_at']) < time()) {
        $mensagem_erro = "Token de redefinição de senha expirado (após post). Por favor, solicite um novo.";
    } elseif (empty($nova_senha) || empty($confirma_senha)) {
        $mensagem_erro = "Por favor, preencha e confirme sua nova senha.";
    } elseif ($nova_senha !== $confirma_senha) {
        $mensagem_erro = "As senhas não coincidem.";
    } else {
        // Redefinir a senha do cliente
        $senha_hash = password_hash($nova_senha, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt_update = $conn->prepare("UPDATE clientes SET senha = ? WHERE email = ?");
        $stmt_update->bind_param("ss", $senha_hash, $reset_info_post['email']);
        
        if ($stmt_update->execute()) {
            // Invalida o token após o uso (remove do banco)
            $stmt_delete_token = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt_delete_token->bind_param("s", $token_from_post); // Use o token do POST aqui
            $stmt_delete_token->execute();
            $stmt_delete_token->close();

            $mensagem_sucesso = "Sua senha foi redefinida com sucesso! Você já pode fazer login.";
            header('Refresh: 3; URL=cadastro_ou_login.php');
        } else {
            $mensagem_erro = "Erro ao redefinir a senha: " . $stmt_update->error;
        }
        $stmt_update->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/redefinir_senha.css">
    <link href="img/icones/redefinir-senha.png" rel="icon" type="image/png">
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
                    <a href="perfil_cliente.php">Meus pedidos</a>
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
                            <div class="cart-count" <?php // if ($itens_no_carrinho == 0) echo 'style="display: none;"'; ?>>
                                <?php // echo $itens_no_carrinho; ?> </div>
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="container access-container">
            <h1>Redefinir Senha</h1>
            <div class="form-wrapper">
                <div class="form-card login-card">
                    <?php if ($mensagem_sucesso): ?>
                        <div class="alert alert-success"><?php echo $mensagem_sucesso; ?></div>
                        <p class="mt-15"><a href="cadastro_ou_login.php">Ir para Login</a></p>
                    <?php elseif ($mensagem_erro): ?>
                        <div class="alert alert-error"><?php echo $mensagem_erro; ?></div>
                        <p class="mt-15"><a href="recuperar_senha.php">Tentar novamente</a></p>
                    <?php else: ?>
                        <h2>Defina sua nova senha para <?php echo htmlspecialchars($cliente_email); ?></h2>
                        <form action="resetar_senha.php" method="POST">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                            <div class="form-group password-field">
                                <label for="nova_senha">Nova Senha:</label>
                                <input type="password" id="nova_senha" name="nova_senha" required>
                                <span class="toggle-password" onclick="togglePasswordVisibility('nova_senha')">🔒</span>
                            </div>
                            <div class="form-group password-field">
                                <label for="confirma_nova_senha">Confirmar Nova Senha:</label>
                                <input type="password" id="confirma_nova_senha" name="confirma_senha" required>
                                <span class="toggle-password" onclick="togglePasswordVisibility('confirma_nova_senha')">🔒</span>
                            </div>
                            <button type="submit" class="btn-submit">Redefinir Senha</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>Lenna Digitais, &copy; <?php echo date('Y'); ?></p>
    </footer>

    <script>
        function togglePasswordVisibility(id) {
            const input = document.getElementById(id);
            const icon = input.nextElementSibling;

            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = '🔓';
            } else {
                input.type = 'password';
                icon.textContent = '🔒';
            }
        }
    </script>
</body>
</html>

