<?php
//🔍 Exibe todos os erros do PHP para facilitar a depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'auth.php'; // Garante que só admin logado acesse
include 'banco.php'; // Conexão com o banco de dados

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensagem_erro'] = "ID do pedido inválido.";
    header('Location: gerenciar_pedidos.php');
    exit();
}

$id_pedido = (int)$_GET['id'];

// 1. Buscar detalhes do pedido
$sql_pedido = "
    SELECT
        p.id, p.nome_cliente, p.email_cliente, p.whatsapp_cliente, p.id_cliente, p.data_pedido, p.status_pedido,
        p.email_produto_enviado, p.data_envio_email, p.whatsapp_notificado,
        SUM(ip.quantidade * ip.preco_unitario) AS valor_total
    FROM pedidos p
    JOIN itens_pedido ip ON p.id = ip.id_pedido
    WHERE p.id = ?
    GROUP BY p.id
";
$stmt_pedido = $conn->prepare($sql_pedido);
$stmt_pedido->bind_param("i", $id_pedido);
$stmt_pedido->execute();
$result_pedido = $stmt_pedido->get_result();

if ($result_pedido->num_rows === 0) {
    $_SESSION['mensagem_erro'] = "Pedido não encontrado.";
    header('Location: gerenciar_pedidos.php');
    exit();
}
$pedido = $result_pedido->fetch_assoc();
$stmt_pedido->close();
$conn->close();

// --- LÓGICA PARA GERAR LINKS DO WHATSAPP ---
$whatsapp_disponivel = !empty($pedido['whatsapp_cliente']);
if ($whatsapp_disponivel) {
    $telefone_limpo = preg_replace('/\D+/', '', "55" . $pedido['whatsapp_cliente']);
    $mensagem_padrao_whats = "Olá " . htmlspecialchars($pedido['nome_cliente']) . ", seu pedido (#" . $pedido['id'] . ") na Lenna Digitais foi concluído e o produto enviado para o seu e-mail. Obrigado pela preferência!";
    $mensagem_encoded = urlencode($mensagem_padrao_whats);
    $app_url = "https://wa.me/{$telefone_limpo}?text={$mensagem_encoded}";
    $web_url = "https://web.whatsapp.com/send?phone={$telefone_limpo}&text={$mensagem_encoded}";
}

// Mensagem padrão para o corpo do e-mail
$mensagem_padrao_email = "Olá " . htmlspecialchars($pedido['nome_cliente']) . ",\n\nSegue em anexo o seu produto digital referente ao pedido #" . $pedido['id'] . ".\n\nO arquivo está no formato .RAR ou .ZIP. Para extrair, você precisará de um programa descompactador.\n\nCaso não tenha um, recomendamos o 7-Zip, que é gratuito e seguro. Você pode baixá-lo em: https://www.7-zip.org/download.html\n\nQualquer dúvida, estamos à disposição!\n\nAtenciosamente,\nEquipe Lenna Digitais";
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Pedido #<?php echo htmlspecialchars($pedido['id']); ?> (Admin)</title>
    <link rel="stylesheet" href="../css/ver_pedido.css">
    <link href="../img/icones/compras.png" rel="icon" type="image/png">
</head>
<body>
    <div class="admin-panel">
        <div class="top-actions">
            <a href="gerenciar_pedidos.php" class="btn btn-secondary">↩️ Voltar para Pedidos</a>
            <a href="logout.php" class="btn btn-secondary">🚪 Desconectar</a>
        </div>

        <h1>Detalhes do Pedido #<?php echo htmlspecialchars($pedido['id']); ?></h1>

        <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
            <div class="alert alert-success" style="background-color: #28a745; padding: 15px; border-radius: 5px; margin-bottom: 20px; color: white;"><?php echo $_SESSION['mensagem_sucesso']; unset($_SESSION['mensagem_sucesso']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['mensagem_erro'])): ?>
            <div class="alert alert-error" style="background-color: #dc3545; padding: 15px; border-radius: 5px; margin-bottom: 20px; color: white;"><?php echo $_SESSION['mensagem_erro']; unset($_SESSION['mensagem_erro']); ?></div>
        <?php endif; ?>

        <div class="order-details-wrapper">
            <div class="order-summary-card card">
                <h3>Resumo do Pedido</h3>
                <p><strong>Status:</strong> <span class="status-concluido"><?php echo htmlspecialchars($pedido['status_pedido']); ?></span></p>
                <p><strong>Valor Total:</strong> R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></p>
            </div>
            <div class="customer-info-card card">
                <h3>Dados do Cliente</h3>
                <p><strong>Nome:</strong> <?php echo htmlspecialchars($pedido['nome_cliente']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($pedido['email_cliente']); ?></p>
            </div>
        </div>

        <?php if ($pedido['status_pedido'] === 'Concluído'): ?>
            <div class="email-delivery-card card">
                <h3>📧 Envio do Produto Digital</h3>
                <?php if ($pedido['email_produto_enviado']): ?>
                    <p class="status-success">✅ Produto já enviado por e-mail em: <?php echo date('d/m/Y H:i', strtotime($pedido['data_envio_email'])); ?></p>
                <?php else: ?>
                    <form action="enviar_produto_email.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id_pedido" value="<?php echo $pedido['id']; ?>">
                        <div class="form-group">
                            <label for="assunto">Assunto do E-mail</label>
                            <input type="text" id="assunto" name="assunto" value="📥 Entrega do seu Pedido #<?php echo $pedido['id']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="mensagem">Mensagem</label>
                            <textarea id="mensagem" name="mensagem" required><?php echo $mensagem_padrao_email; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Anexar Arquivo (.rar, .zip)</label>
                            <div class="file-input-wrapper">
                                <label for="anexo_produto" class="file-input-button">Escolher Arquivo</label>
                                <input type="file" id="anexo_produto" name="anexo_produto" accept=".rar,.zip" required>
                                <span id="file-chosen">Nenhum arquivo escolhido</span>
                            </div>
                        </div>
                        <button type="submit" class="btn-send-email">🚀 Enviar E-mail com Anexo</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($pedido['status_pedido'] === 'Concluído' && $whatsapp_disponivel): ?>
            <div class="whatsapp-notification-card card">
                <h3>💬 Notificação Opcional via WhatsApp</h3>
                <?php if ($pedido['whatsapp_notificado']): ?>
                    <p class="status-success">✅ Cliente já notificado por WhatsApp.</p>
                <?php else: ?>
                    <p>Clique para notificar o cliente diretamente (abre em nova aba):</p>
                    <div class="whatsapp-direct-actions">
                        <a href="<?php echo htmlspecialchars($app_url); ?>" target="_blank" class="btn-whatsapp-direct app">WhatsApp (App)</a>
                        <a href="<?php echo htmlspecialchars($web_url); ?>" target="_blank" class="btn-whatsapp-direct web">WhatsApp (Web)</a>
                    </div>
                    <a href="mudar_status_pedido.php?id=<?php echo $pedido['id']; ?>&action=confirmar_whatsapp_notificado" class="btn-confirm-notification" onclick="return confirm('Confirmar que a notificação por WhatsApp foi enviada?');">
                        ✔️ Marcar como Notificado
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
    <script>
        const anexoInput = document.getElementById('anexo_produto');
        const fileChosen = document.getElementById('file-chosen');
        anexoInput.addEventListener('change', function(){
            fileChosen.textContent = this.files[0] ? this.files[0].name : 'Nenhum arquivo escolhido';
        });
    </script>
</body>
</html>

