<?php
//🔍 Exibe todos os erros do PHP para facilitar a depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'auth.php'; //🔒 Garante que só admin logado acesse
require_once 'banco.php'; //💾 Conexão com o banco de dados

// Verifica se o ID do cliente foi passado via GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensagem_erro'] = "ID do cliente inválido para remoção.";
    header('Location: usuario.php');
    exit;
}

$id_cliente = (int)$_GET['id'];

// Antes de remover o cliente, é uma boa prática verificar
// se há pedidos associados a ele. Se houver, você pode:
// 1. Impedir a exclusão (e avisar o admin).
// 2. Definir o id_cliente desses pedidos como NULL (usando ON DELETE SET NULL na FK).
//    Pelo seu lenna.sql, a FK `fk_pedidos_clientes` em `pedidos` já tem `ON DELETE SET NULL`,
//    o que significa que ao remover um cliente, os pedidos dele não serão removidos,
//    mas o `id_cliente` no pedido ficará NULL. Isso é o ideal para manter o histórico.

try {
    $stmt = $conn->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->bind_param("i", $id_cliente);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $_SESSION['mensagem_sucesso'] = "Cliente removido com sucesso!";
        } else {
            $_SESSION['mensagem_erro'] = "Nenhum cliente encontrado com o ID fornecido.";
        }
    } else {
        throw new Exception("Erro ao remover cliente: " . $stmt->error);
    }
} catch (Exception $e) {
    $_SESSION['mensagem_erro'] = "Erro: " . $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
    header('Location: usuario.php');
    exit;
}
?>

