<?php
session_start();
include 'admin/banco.php';

// 1. Redireciona se o carrinho estiver vazio
if (empty($_SESSION['carrinho'])) {
    header('Location: carrinho.php');
    exit();
}

// 2. Verifica se o cliente está logado
if (!isset($_SESSION['cliente_logado'], $_SESSION['cliente_id']) || $_SESSION['cliente_logado'] !== true) {
    $_SESSION['mensagem_erro_cliente'] = "Para finalizar a compra, faça login ou cadastre-se.";
    header('Location: cadastro_ou_login.php');
    exit();
}

// 3. Busca dados do cliente
$id_cliente = $_SESSION['cliente_id'];
$stmt_cliente = $conn->prepare("
    SELECT nome, sobrenome, email, celular 
      FROM clientes 
     WHERE id = ?
");
$stmt_cliente->bind_param("i", $id_cliente);
$stmt_cliente->execute();
$result_cliente = $stmt_cliente->get_result();
if ($result_cliente->num_rows !== 1) {
    session_unset();
    session_destroy();
    $_SESSION['mensagem_erro_cliente'] = "Usuário não encontrado. Faça login novamente.";
    header('Location: cadastro_ou_login.php');
    exit();
}
$u = $result_cliente->fetch_assoc();
$nome_cliente     = trim($u['nome'] . ' ' . $u['sobrenome']);
$email_cliente    = $u['email'];
$whatsapp_cliente = $u['celular'];
$stmt_cliente->close();

// 4. Insere o pedido
$stmt_pedido = $conn->prepare("
    INSERT INTO pedidos 
      (nome_cliente, email_cliente, whatsapp_cliente, id_cliente, status_pedido) 
    VALUES (?, ?, ?, ?, 'Aguardando Pagamento')
");
$stmt_pedido->bind_param("sssi", $nome_cliente, $email_cliente, $whatsapp_cliente, $id_cliente);
$stmt_pedido->execute();
$id_pedido = $conn->insert_id;
$stmt_pedido->close();

// 5. Configura PHPMailer + SMTP e envia confirmação
require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
try {
    // --- INÍCIO DO BLOCO ATUALIZADO ---
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'lennadigitais@gmail.com';
    $mail->Password   = 'hwho ekhw dabv yofo';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom('lennadigitais@gmail.com', 'Lenna Digitais');
    // --- FIM DO BLOCO ATUALIZADO ---

    $mail->addAddress($email_cliente, $nome_cliente);

    $mail->isHTML(true);
    $mail->Subject = "📦 Pedido #{$id_pedido} Recebido - Lenna Digitais";
    $mail->Body    = "
    <div style='font-family: Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.6;'>
        <p>Olá, <strong>{$nome_cliente}</strong>! 👋</p>
        <p>Recebemos o seu pedido de número <strong style='color: #e83e8c;'>#{$id_pedido}</strong>.</p>
        <p>Ele foi registrado com o status 'Aguardando Pagamento'. Após a confirmação do pagamento, iniciaremos a preparação do seu produto digital.</p>
        <p>Você pode acompanhar o status do seu pedido a qualquer momento na sua área de cliente em nosso site.</p>
        <p style='margin-top: 30px;'>Obrigado pela sua preferência!<br>Equipe Lenna Digitais ✨</p>
    </div>
    ";
    $mail->AltBody = "Olá {$nome_cliente},\n\nRecebemos seu pedido (#{$id_pedido}). Ele está aguardando a confirmação de pagamento.\n\nObrigado pela preferência!\nEquipe Lenna Digitais";

    $mail->send();
} catch (Exception $e) {
    error_log('Mail error [finalizar_pedido]: ' . $e->getMessage());
}

// 6. Insere itens do carrinho
$total_carrinho = 0;
if (!empty($_SESSION['carrinho'])) {
    $ids = implode(',', array_keys($_SESSION['carrinho']));
    $res = $conn->query("SELECT id, preco FROM produtos WHERE id IN ($ids)");
    $produtos = $res->fetch_all(MYSQLI_ASSOC);

    $stmt_item = $conn->prepare("
        INSERT INTO itens_pedido (id_pedido, id_produto, quantidade, preco_unitario) 
        VALUES (?, ?, ?, ?)
    ");
    foreach ($produtos as $p) {
        $q = $_SESSION['carrinho'][$p['id']];
        $stmt_item->bind_param("iiid", $id_pedido, $p['id'], $q, $p['preco']);
        $stmt_item->execute();
        $total_carrinho += $q * $p['preco'];
    }
    $stmt_item->close();
}

// 7. Limpa carrinho e redireciona
unset($_SESSION['carrinho']);
$_SESSION['id_pedido_para_confirmacao'] = $id_pedido;
$_SESSION['valor_total_pedido']         = $total_carrinho;
header('Location: confirmar_pagamento.php');
exit();
?>

