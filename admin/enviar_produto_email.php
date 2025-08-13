<?php
//🔍 Exibe todos os erros do PHP para facilitar a depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'auth.php';
require_once 'banco.php';

// Importa as classes do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: gerenciar_pedidos.php');
    exit;
}

// Validação dos dados do formulário
$id_pedido = filter_input(INPUT_POST, 'id_pedido', FILTER_VALIDATE_INT);
$assunto = trim($_POST['assunto'] ?? '');
$mensagem = trim($_POST['mensagem'] ?? '');

if (!$id_pedido || empty($assunto) || empty($mensagem) || !isset($_FILES['anexo_produto']) || $_FILES['anexo_produto']['error'] != UPLOAD_ERR_OK) {
    $_SESSION['mensagem_erro'] = "Erro: Todos os campos e o anexo são obrigatórios.";
    header('Location: ver_pedido.php?id=' . $id_pedido);
    exit;
}

// Busca dados do cliente no banco
$stmt_cliente = $conn->prepare("SELECT nome_cliente, email_cliente FROM pedidos WHERE id = ?");
$stmt_cliente->bind_param("i", $id_pedido);
$stmt_cliente->execute();
$result_cliente = $stmt_cliente->get_result();

if ($result_cliente->num_rows === 0) {
    $_SESSION['mensagem_erro'] = "Pedido não encontrado para envio de e-mail.";
    header('Location: gerenciar_pedidos.php');
    exit;
}
$cliente = $result_cliente->fetch_assoc();
$stmt_cliente->close();

// Carrega o autoloader do Composer
require __DIR__ . '/../vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // Configurações do servidor SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'lennadigitais@gmail.com';
    $mail->Password   = 'hwho ekhw dabv yofo';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    // Remetente e Destinatário
    $mail->setFrom('lennadigitais@gmail.com', 'Lenna Digitais');
    $mail->addAddress($cliente['email_cliente'], $cliente['nome_cliente']);

    // Anexo
    $mail->addAttachment($_FILES['anexo_produto']['tmp_name'], $_FILES['anexo_produto']['name']);

    // Conteúdo do E-mail
    $mail->isHTML(true);
    $mail->Subject = $assunto;
    $mail->Body    = nl2br(htmlspecialchars($mensagem)); 
    $mail->AltBody = htmlspecialchars($mensagem);

    $mail->send();

    // Se o e-mail foi enviado, atualiza o status no banco
    $stmt_update = $conn->prepare("UPDATE pedidos SET email_produto_enviado = TRUE, data_envio_email = NOW() WHERE id = ?");
    $stmt_update->bind_param("i", $id_pedido);
    $stmt_update->execute();
    $stmt_update->close();

    $_SESSION['mensagem_sucesso'] = "Produto do Pedido #{$id_pedido} enviado com sucesso!";

} catch (Exception $e) {
    $_SESSION['mensagem_erro'] = "Não foi possível enviar o e-mail. Erro do PHPMailer: {$mail->ErrorInfo}";
}

$conn->close();
// Redireciona de volta para a página de detalhes do pedido
header('Location: ver_pedido.php?id=' . $id_pedido);
exit;
?>

