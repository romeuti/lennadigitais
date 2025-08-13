<?php
//🔍 Exibe todos os erros do PHP para facilitar a depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // Inicia a sessão para controle de mensagens

// Garante que o carrinho exista na sessão, mesmo que vazio
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}
$itens_no_carrinho = count($_SESSION['carrinho']);

// Se o cliente já estiver logado, redireciona para o perfil
if (isset($_SESSION['cliente_logado']) && $_SESSION['cliente_logado'] === true) {
    header('Location: perfil_cliente.php'); // Redireciona para a página de perfil
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Cliente</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/clientes.css">
    <link href="img/icones/cadastro.png" rel="icon" type="image/png">
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
                        <a href="perfil_cliente.php">
                            <span>👤</span>
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
    <div class="container form-page-container">
        <div class="form-container">
            <h2>Crie sua Conta</h2>
            <p>Preencha os dados abaixo para se cadastrar.</p>

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

            <form action="salvar_cliente.php" method="POST">
                <input type="hidden" name="acao" value="cadastro">
                <div class="form-row"> <div class="input-group">
                        <label for="nome_completo">Nome</label>
                        <input type="text" id="nome_completo" name="nome" required>
                    </div>
                    <div class="input-group">
                        <label for="sobrenome">Sobrenome</label>
                        <input type="text" id="sobrenome" name="sobrenome" required>
                    </div>
                </div>
                <div class="form-row"> <div class="input-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="input-group">
                        <label for="celular">Celular</label>
                        <input
                            type="tel" id="celular" name="celular" required
                            pattern="^\([0-9]{2}\) [0-9]{4,5}-[0-9]{4}$"
                            title="Formato: (DD) XXXXX-XXXX ou (DD) XXXX-XXXX"
                        >
                    </div>
                </div>
                <div class="form-row"> <div class="input-group password-field"> <label for="senha">Senha</label>
                        <input type="password" id="senha" name="senha" required>
                        <span class="toggle-password" onclick="togglePasswordVisibility('senha')">🔒</span> </div>
                    <div class="input-group password-field"> <label for="confirma_senha">Confirmar Senha</label>
                        <input type="password" id="confirma_senha" name="confirma_senha" required>
                        <span class="toggle-password" onclick="togglePasswordVisibility('confirma_senha')">🔒</span> </div>
                </div>
                <button type="submit" class="btn-submit">Cadastrar</button>
            </form>
            <div class="login-link">
                Já tem conta? <a href="cadastro_ou_login.php">Faça login aqui</a>. </div>
        </div>
    </div>
</main>

<footer>
    <p>Lenna Digitais, &copy; <?php echo date('Y'); ?></p>
</footer>

    <script>
        // Função para formatar o campo de celular
        document.addEventListener('DOMContentLoaded', function() {
            const celularInput = document.getElementById('celular');
            if (celularInput) {
                celularInput.addEventListener('input', function (e) {
                    let value = e.target.value.replace(/\D/g, ''); // Remove tudo que não é dígito
                    let formattedValue = '';

                    if (value.length > 0) {
                        formattedValue = '(' + value.substring(0, 2);
                        if (value.length > 2) {
                            formattedValue += ') ';
                            if (value.length > 7) { // 9xxxx-xxxx ou 8xxxx-xxxx
                                formattedValue += value.substring(2, 7) + '-' + value.substring(7, 11);
                            } else {
                                formattedValue += value.substring(2, 7);
                            }
                        }
                    }
                    e.target.value = formattedValue;
                });
            }

            // Validação de senhas no front-end para o formulário de cadastro
            const cadastroForm = document.querySelector('.form-container form');
            if (cadastroForm) {
                cadastroForm.addEventListener('submit', function(event) {
                    const senha = document.getElementById('senha').value;
                    const confirmaSenha = document.getElementById('confirma_senha').value;

                    // Adicionado validação de tamanho mínimo da senha no JS
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

        // Função para alternar visibilidade da senha (reutilizando de cadastro_ou_login.php)
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

