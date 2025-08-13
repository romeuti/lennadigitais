<?php
//🔍 Exibe todos os erros do PHP para facilitar a depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); //🔒 Inicia a sessão para controle de autenticação e permissões
include 'admin/banco.php'; // 💾 Conexão com o banco de dados

// Garante que o carrinho exista na sessão, mesmo que vazio
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

// Conta os itens no carrinho após garantir que ele exista
$itens_no_carrinho = count($_SESSION['carrinho']);

// Verifica se o cliente já está logado. Se sim, redireciona de acordo.
if (isset($_SESSION['cliente_logado']) && $_SESSION['cliente_logado'] === true) {
    // Se o cliente já está logado, ele deveria ir para finalizar_pedido se tem algo no carrinho,
    // caso contrário, vai para o perfil.
    if (!empty($_SESSION['carrinho'])) {
        header('Location: finalizar_pedido.php');
    } else {
        header('Location: perfil_cliente.php'); // Redireciona para o perfil se não há carrinho ativo
    }
    exit;
}

// Verifica de onde o usuário veio para diferenciar o fluxo. Se veio da página do carrinho, a intenção é finalizar a compra.
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$is_from_carrinho = (strpos($referer, 'carrinho.php') !== false || strpos(mb_strtolower($referer), 'detalhe_produto.php') !== false); // Convert to lowercase for comparison

// Redireciona para o carrinho APENAS se o carrinho estiver vazio E o usuário veio de uma página onde ele tentou adicionar ao carrinho ou finalizar compra.
if (empty($_SESSION['carrinho']) && $is_from_carrinho) {
    header('Location: carrinho.php');
    exit;
}

$erro_login = '';
$sucesso_cadastro = '';

// Verifica se há alguma mensagem de erro/sucesso vinda de outra página (ex: finalizar_pedido.php, salvar_cliente.php)
if (isset($_SESSION['mensagem_erro_cliente'])) {
    $erro_login = $_SESSION['mensagem_erro_cliente'];
    unset($_SESSION['mensagem_erro_cliente']);
}
if (isset($_SESSION['mensagem_sucesso'])) { // Para mensagens de sucesso do cadastro (vindo de salvar_cliente.php)
    $sucesso_cadastro = $_SESSION['mensagem_sucesso'];
    unset($_SESSION['mensagem_sucesso']);
}

// Lógica de processamento de login
if (isset($_POST['acao']) && $_POST['acao'] === 'login') {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    // CORRIGIDO: Selecionar 'nome' e 'sobrenome' da tabela clientes
    $stmt = $conn->prepare("SELECT id, nome, sobrenome, senha FROM clientes WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $cliente = $result->fetch_assoc(); // Pega todos os resultados como array associativo
    $stmt->close();

    if ($cliente && password_verify($senha, $cliente['senha'])) {
        $_SESSION['cliente_logado'] = true;
        $_SESSION['cliente_id'] = $cliente['id'];
        // CORRIGIDO: Concatena nome e sobrenome para $_SESSION['cliente_nome']
        $_SESSION['cliente_nome'] = $cliente['nome'] . ' ' . $cliente['sobrenome'];

        if (!empty($_SESSION['carrinho'])) {
             header('Location: finalizar_pedido.php');
        } else {
             header('Location: perfil_cliente.php');
        }
        exit;
    } else {
        $erro_login = "Email ou senha inválidos.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Cliente</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/cadastro_ou_login.css">
    <link href="img/icones/login.png" rel="icon" type="image/png">
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
    </nav>
</header>

<main>
    <div class="container access-container">
        <h1>Acesso do Cliente</h1>

        <?php if ($sucesso_cadastro): ?>
            <div class="alert alert-success"><?php echo $sucesso_cadastro; ?></div>
        <?php endif; ?>

        <div class="form-wrapper">
            <div class="form-card login-card">
                <h2>Já Sou Cliente</h2>
                <?php if ($erro_login): ?>
                    <div class="alert alert-error"><?php echo $erro_login; ?></div>
                <?php endif; ?>
                <form action="cadastro_ou_login.php" method="POST">
                    <input type="hidden" name="acao" value="login">
                    <div class="form-group">
                        <label for="email_login">Email:</label>
                        <input type="email" id="email_login" name="email" required>
                    </div>
                    <div class="form-group password-field">
                        <label for="senha_login">Senha:</label>
                        <input type="password" id="senha_login" name="senha" required>
                        <span class="toggle-password" onclick="togglePasswordVisibility('senha_login')">🔒</span>
                    </div>
                    <button type="submit" class="btn-submit">Entrar</button>
                    <p class="forgot-password"><a href="recuperar_senha.php">Esqueceu a senha?</a></p>
                </form>
                <p class="mt-20 not-registered-link">Não tem cadastro? <a href="clientes.php">Crie sua conta aqui.</a></p>
            </div>
        </div>
    </div>
</main>

<footer>
    <p>Lenna Personalizados, &copy; <?php echo date('Y'); ?></p>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const celularInput = document.getElementById('celular');
        if (celularInput) {
            celularInput.addEventListener('input', function (e) {
                let value = e.target.value.replace(/\D/g, '');
                let formattedValue = '';

                if (value.length > 0) {
                    formattedValue = '(' + value.substring(0, 2);
                    if (value.length > 2) {
                        formattedValue += ') ';
                        if (value.length > 7) {
                            formattedValue += value.substring(2, 7) + '-' + value.substring(7, 11);
                        } else {
                            formattedValue += value.substring(2, 7);
                        }
                    }
                }
                e.target.value = formattedValue;
            });
        }
        const cadastroForm = document.querySelector('.register-card form');
        if (cadastroForm) { // Este bloco pode ser removido, pois .register-card foi removido do HTML
            cadastroForm.addEventListener('submit', function(event) {
                const senha = document.getElementById('senha_cadastro').value;
                const confirmaSenha = document.getElementById('confirma_senha_cadastro').value;

                if (senha.length < 6) {
                    alert('A senha deve ter no mínimo 6 caracteres.');
                    event.preventDefault();
                } else if (senha !== confirmaSenha) {
                    alert('As senhas não coincidem. Por favor, digite novamente.');
                    event.preventDefault();
                }
            });
        }
    });

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

