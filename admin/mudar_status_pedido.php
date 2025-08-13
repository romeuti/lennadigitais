<?php
//🔍 Exibe todos os erros do PHP para facilitar a depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'auth.php'; //🔒 Garante que só admin logado acesse
require_once 'banco.php'; //💾 Conexão com o banco de dados

// Importa as classes do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Pega o ID do pedido e o tipo de ação/status
$id_pedido   = intval($_GET['id'] ?? 0);
$novo_status = $_GET['status'] ?? null;
$action_type = $_GET['action'] ?? '';

// 1. Valida ID do pedido
if ($id_pedido <= 0) {
    $_SESSION['mensagem_erro'] = "ID do pedido inválido.";
    header('Location: gerenciar_pedidos.php');
    exit;
}

// --- CAMINHO 1: Ação de confirmar notificação por WhatsApp ---
if ($action_type === 'confirmar_whatsapp_notificado') {
    $stmt = $conn->prepare("UPDATE pedidos SET whatsapp_notificado = TRUE, data_whatsapp_notificado = NOW() WHERE id = ?");
    $stmt->bind_param("i", $id_pedido);
    if ($stmt->execute()) {
        $_SESSION['mensagem_sucesso'] = "Notificação por WhatsApp para o Pedido #{$id_pedido} registrada com sucesso!";
    } else {
        $_SESSION['mensagem_erro'] = "Erro ao registrar a notificação de WhatsApp: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
    // Redireciona de volta para a página de detalhes do pedido e PARA a execução
    header('Location: ver_pedido.php?id=' . $id_pedido);
    exit;
}

// --- CAMINHO 2: Ação de mudar o status do pedido ---
$status_permitidos = ['Pago', 'Concluído', 'Cancelado'];
if ($novo_status && in_array($novo_status, $status_permitidos)) {
    $stmt = $conn->prepare("UPDATE pedidos SET status_pedido = ? WHERE id = ?");
    $stmt->bind_param("si", $novo_status, $id_pedido);

    if ($stmt->execute()) {
        $_SESSION['mensagem_sucesso'] = "Status do pedido #{$id_pedido} atualizado para '{$novo_status}' com sucesso!";

        // Envia e-mail de "Pagamento Aprovado"
        if ($novo_status === 'Pago') {
            $info = $conn->prepare("SELECT nome_cliente, email_cliente FROM pedidos WHERE id = ?");
            $info->bind_param("i", $id_pedido);
            $info->execute();
            $cliente = $info->get_result()->fetch_assoc();
            $info->close();

            require __DIR__ . '/../vendor/autoload.php';
            $mailPago = new PHPMailer(true);
            try {
                // Configurações SMTP
                $mailPago->isSMTP();
                $mailPago->Host       = 'smtp.gmail.com';
                $mailPago->SMTPAuth   = true;
                $mailPago->Username   = 'lennadigitais@gmail.com';
                $mailPago->Password   = 'hwho ekhw dabv yofo';
                $mailPago->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mailPago->Port       = 587;
                $mailPago->CharSet    = 'UTF-8';
                // E-mail
                $mailPago->setFrom('lennadigitais@gmail.com', 'Lenna Digitais');
                $mailPago->addAddress($cliente['email_cliente'], $cliente['nome_cliente']);
                $mailPago->isHTML(true);
                $mailPago->Subject = "✅ Pagamento Aprovado - Pedido #{$id_pedido}";
                $mailPago->Body    = "<p>Olá, <strong>{$cliente['nome_cliente']}</strong>!</p><p>Ótima notícia! Confirmamos o pagamento do seu pedido <strong style='color: #28a745;'>#{$id_pedido}</strong>.</p><p>Já estamos preparando seu produto digital para o envio.</p>";
                $mailPago->send();
            } catch (Exception $e) {
                error_log('Mail error [Pagamento Confirmado]: ' . $e->getMessage());
            }
        }
        
        $conn->close();
        // Se o status for 'Concluído', redireciona para ver_pedido
        if ($novo_status === 'Concluído') {
            header('Location: ver_pedido.php?id=' . $id_pedido);
        } else {
            header('Location: gerenciar_pedidos.php');
        }
        exit; // PARA a execução após a mudança de status

    } else {
        $_SESSION['mensagem_erro'] = "Erro ao atualizar o status do pedido: " . $stmt->error;
    }
    $stmt->close();
}

// --- CAMINHO 3: Se nenhuma ação válida foi encontrada ---
$_SESSION['mensagem_erro'] = "Parâmetros inválidos para alterar o status do pedido.";
$conn->close();
header('Location: gerenciar_pedidos.php');
exit;
?>

