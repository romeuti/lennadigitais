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
include 'vendor/autoload.php'; // Inclua o autoloader do Composer. Se não usar Composer, ajuste para 'PHPMailer/src/PHPMailer.php', etc.

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$mensagem_sucesso = '';
$mensagem_erro = '';

if (isset($_SESSION['mensagem_sucesso'])) {
    $mensagem_sucesso = $_SESSION['mensagem_sucesso'];
    unset($_SESSION['mensagem_sucesso']);
}
if (isset($_SESSION['mensagem_erro'])) {
    $mensagem_erro = $_SESSION['mensagem_erro'];
    unset($_SESSION['mensagem_erro']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $mensagem_erro = "Por favor, digite seu e-mail.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM clientes WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $cliente = $result->fetch_assoc();
        $stmt->close();

        if ($cliente) {
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Log da criação do token
            error_log("RECUPERAR_SENHA: Novo token gerado: " . $token . " expira em: " . $expires_at . " (PHP Time: " . time() . ")");

            $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $token, $expires_at);
            if ($stmt->execute()) {
                error_log("RECUPERAR_SENHA: Token inserido no DB para " . $email . ". ID do token: " . $conn->insert_id);

                $reset_link = "https://www.lennadigitais.shop/resetar_senha.php?token=" . $token;

                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'lennadigitais@gmail.com';
                    $mail->Password   = 'hwho ekhw dabv yofo';

                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    // NOVO: Definir a codificação para o assunto e corpo do e-mail
                    $mail->CharSet = 'UTF-8';

                    $mail->setFrom('lennadigitais@gmail.com', 'Lenna Digitais');
                    $mail->addAddress($email);

                    $mail->isHTML(true);
                    $mail->Subject = '🔑 Redefinição de Senha - Lenna Digitais'; // Assunto com ícone
                    $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.6;'>
                        <p>Olá! 👋</p>
                        <p>Recebemos uma solicitação para redefinir a senha da sua conta na <strong style='color: #e83e8c;'>Lenna Digitais</strong>. </p>
                        <p>Para criar uma nova senha, por favor, clique no link seguro abaixo:</p>
                        <p style='text-align: center; margin: 25px 0;'>
                            <a href='{$reset_link}' style='background-color: #63b3ed; color: #ffffff; padding: 12px 25px; border-radius: 5px; text-decoration: none; font-weight: bold; display: inline-block;'>
                                Redefinir Minha Senha Agora! 🚀
                            </a>
                        </p>
                        <p style='font-size: 12px; color: #777;'>
                            ⚠️ Este link de redefinição de senha expirará em <strong style='color: #e83e8c;'>1 hora</strong> por motivos de segurança.
                            Se você não solicitou esta redefinição, por favor, ignore este e-mail. Sua senha atual permanecerá inalterada.
                        </p>
                        <p>Qualquer d&uacute;vida, estamos &agrave; disposi&ccedil;&atilde;o!</p>
                        <p style='margin-top: 30px;'>Atenciosamente,<br>Equipe Lenna Digitais ✨</p>
                    </div>
                    ";
                    $mail->AltBody = "Olá,\n\nVocê solicitou a redefinição de senha para sua conta na Lenna Digitais. \nCopie e cole o link abaixo em seu navegador para criar uma nova senha:\n\n{$reset_link}\n\nEste link expirará em 1 hora. Se você não solicitou esta redefinição, por favor, ignore este e-mail.\n\nAtenciosamente,\nEquipe Lenna Digitais";

                    $mail->send();
                    error_log("RECUPERAR_SENHA: Email de redefinição enviado com sucesso para " . $email);
                    $mensagem_sucesso = "Um link para redefinição de senha foi enviado para o seu e-mail. Verifique sua caixa de entrada e spam.";
                } catch (Exception $e) {
                    $mensagem_erro = "Não foi possível enviar o e-mail de redefinição de senha. Mailer Error: {$mail->ErrorInfo}";
                    error_log("PHPMailer Error: " . $e->getMessage()); // Loga o erro completo para depuração
                }
            } else {
                $mensagem_erro = "Erro ao gerar token de redefinição. Por favor, tente novamente.";
                error_log("RECUPERAR_SENHA: Erro ao inserir token no DB: " . $stmt->error);
            }
        } else {
            $mensagem_sucesso = "Se o e-mail estiver cadastrado, um link para redefinição de senha foi enviado para ele.";
            error_log("RECUPERAR_SENHA: Email " . $email . " não encontrado no DB.");
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/redefinir_senha.css">
    <link href="img/icones/recuperar-senha.png" rel="icon" type="image/png">
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
            <h1>Recuperar Senha</h1>
            <div class="form-wrapper">
                <div class="form-card login-card">
                    <h2>Informe seu e-mail</h2>
                    <?php if ($mensagem_sucesso): ?>
                        <div class="alert alert-success"><?php echo $mensagem_sucesso; ?></div>
                    <?php endif; ?>
                    <?php if ($mensagem_erro): ?>
                        <div class="alert alert-error"><?php echo $mensagem_erro; ?></div>
                    <?php endif; ?>
                    <form action="recuperar_senha.php" method="POST">
                        <div class="form-group">
                            <label for="email_recuperacao">Email:</label>
                            <input type="email" id="email_recuperacao" name="email" required>
                        </div>
                        <button type="submit" class="btn-submit">Enviar Link de Redefinição</button>
                    </form>
                    <p class="mt-15"><a href="cadastro_ou_login.php">Voltar para Login</a></p>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>Lenna Digitais, &copy; <?php echo date('Y'); ?></p>
    </footer>
</body>
</html>

