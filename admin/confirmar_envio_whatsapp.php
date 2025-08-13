<?php
session_start();
require_once 'auth.php'; //🔒 Garante que só admin logado acesse

// Verifica se há informações de redirecionamento para o WhatsApp na sessão
if (!isset($_SESSION['whatsapp_redirect_info'])) {
    // Se não houver informações na sessão, redireciona de volta para gerenciar_pedidos.php
    $_SESSION['mensagem_erro'] = "Nenhuma informação de WhatsApp para confirmar envio. Por favor, gerencie o pedido novamente.";
    header('Location: gerenciar_pedidos.php');
    exit;
}

$whatsapp_info = $_SESSION['whatsapp_redirect_info'];
// Não removemos mais a informação da sessão aqui. Ela será removida após a confirmação do admin.

$phone_number = $whatsapp_info['phone'];
$encoded_message = $whatsapp_info['message'];
$pedido_id = $whatsapp_info['pedido_id'];

$whatsapp_url = "https://wa.me/{$phone_number}?text={$encoded_message}";
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Envio WhatsApp</title>
    <link rel="stylesheet" href="../css/confirmar_envio_whatsapp.css">
    <link href="../img/icones/whatsapp.png" rel="icon" type="image/png">
</head>
<body>
    <div class="admin-panel">
        <h1>💬 Notificação por WhatsApp</h1>
        <p>Você está prestes a enviar uma notificação por WhatsApp para o cliente do Pedido #<?php echo htmlspecialchars($pedido_id); ?>.</p>
        <p>Atenção: A mensagem do WhatsApp é apenas uma notificação. Certifique-se de que o produto digital já foi enviado para o e-mail do cliente!</p>

        <div class="whatsapp-send-box">
            <p>Clique aqui para abrir o WhatsApp e enviar a mensagem:</p>
            <a href="<?php echo htmlspecialchars($whatsapp_url); ?>" target="_blank" rel="noopener" class="btn btn-whatsapp-send">
                🚀 Abrir WhatsApp e Enviar Notificação
            </a>
            <p class="note">Isso abrirá o WhatsApp (web ou aplicativo) para você enviar a mensagem.</p>
        </div>

        <div class="confirmation-actions">
            <h2>Confirmação</h2>
            <p>Após enviar a mensagem pelo WhatsApp, clique no botão abaixo para registrar a confirmação no sistema:</p>
            <a href="mudar_status_pedido.php?id=<?php echo $pedido_id; ?>&action=confirmar_whatsapp_notificado" class="btn btn-confirm-whatsapp" onclick="return confirm('Você já enviou a notificação via WhatsApp para o Pedido #<?php echo $pedido_id; ?>?');">
                ✔️ Confirmar Notificação WhatsApp Enviada
            </a>
            <p class="note">Isso registrará que você notificou o cliente via WhatsApp.</p>
        </div>

        <div class="secondary-actions">
            <a href="ver_pedido.php?id=<?php echo $pedido_id; ?>" class="btn btn-secondary">↩️ Voltar para Detalhes do Pedido</a>
            <a href="gerenciar_pedidos.php" class="btn btn-secondary">↩️ Voltar para Pedidos</a>
            <a href="logout.php" class="btn btn-secondary">🚪 Desconectar</a>
        </div>
    </div>
</body>
</html>

