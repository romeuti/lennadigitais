<?php
//🔍 Exibe todos os erros do PHP para facilitar a depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'auth.php'; //🔒 Garante que só admin logado acesse
require_once 'banco.php'; //💾 Conexão com o banco de dados

// Lógica de Paginação
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Garante que a página seja no mínimo 1
$limit = 10; // 10 pedidos por página
$offset = ($page - 1) * $limit;

// Obter o total de pedidos para a paginação
$total_rows_query = $conn->query("SELECT COUNT(*) AS total FROM pedidos");
$total_rows = $total_rows_query->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Buscar pedidos com informações do cliente e ordenação
$sql_pedidos = "
    SELECT
        p.id,
        p.nome_cliente,
        p.email_cliente,
        p.whatsapp_cliente,
        p.data_pedido,
        p.status_pedido,
        SUM(ip.quantidade * ip.preco_unitario) AS valor_total
    FROM
        pedidos p
    JOIN
        itens_pedido ip ON p.id = ip.id_pedido
    GROUP BY
        p.id, p.nome_cliente, p.email_cliente, p.whatsapp_cliente, p.data_pedido, p.status_pedido
    ORDER BY
        p.data_pedido DESC
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sql_pedidos);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result_pedidos = $stmt->get_result();
$pedidos = $result_pedidos->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Pedidos</title>
    <link rel="stylesheet" href="../css/gerenciar_pedidos.css">
    <link href="../img/icones/pedido.png" rel="icon" type="image/png">
</head>
<body>
    <div class="admin-panel">
        <h1>📦 Gerenciar Pedidos de Clientes</h1>
        <p>Visualize e gerencie os pedidos recebidos na loja.</p>

        <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['mensagem_sucesso']; unset($_SESSION['mensagem_sucesso']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['mensagem_erro'])): ?>
            <div class="alert alert-error"><?php echo $_SESSION['mensagem_erro']; unset($_SESSION['mensagem_erro']); ?></div>
        <?php endif; ?>

        <div class="table-wrapper">
            <table class="table-list">
                <thead>
                    <tr>
                        <th>ID Pedido</th>
                        <th>Cliente</th>
                        <th>Email</th>
                        <th>WhatsApp</th>
                        <th>Data Pedido</th>
                        <th>Valor Total</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pedidos)): ?>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td data-label="ID Pedido"><?php echo htmlspecialchars($pedido['id']); ?></td>
                                <td data-label="Cliente"><?php echo htmlspecialchars($pedido['nome_cliente']); ?></td>
                                <td data-label="Email"><?php echo htmlspecialchars($pedido['email_cliente']); ?></td>
                                <td data-label="WhatsApp"><?php echo htmlspecialchars($pedido['whatsapp_cliente']); ?></td>
                                <td data-label="Data Pedido"><?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></td>
                                <td data-label="Valor Total">R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></td>
                                <td data-label="Status" class="status-<?php echo strtolower(str_replace(' ', '-', $pedido['status_pedido'])); ?>">
                                    <?php echo htmlspecialchars($pedido['status_pedido']); ?>
                                </td>
                                <td data-label="Ações">
                                    <a href="ver_pedido.php?id=<?php echo $pedido['id']; ?>" class="btn btn-view">🔎 Ver Detalhes</a>
                                    
                                    <?php if ($pedido['status_pedido'] === 'Aguardando Pagamento'): ?>
                                        <a href="mudar_status_pedido.php?id=<?= $pedido['id'] ?>&status=Pago"
                                           class="btn btn-approve"
                                           onclick="return confirm('Confirma que o pagamento do Pedido #<?= $pedido['id'] ?> foi recebido?');">
                                           ✔️ Marcar como Pago
                                        </a>
                                        <a href="mudar_status_pedido.php?id=<?= $pedido['id'] ?>&status=Cancelado"
                                           class="btn btn-reject"
                                           onclick="return confirm('Tem certeza que deseja cancelar o Pedido #<?= $pedido['id'] ?>?');">
                                           ✖️ Cancelar
                                        </a>
                                    <?php elseif ($pedido['status_pedido'] === 'Pago'): ?>
                                        <a href="mudar_status_pedido.php?id=<?= $pedido['id'] ?>&status=Concluído"
                                           class="btn btn-approve"
                                           onclick="return confirm('Marcar Pedido #<?= $pedido['id'] ?> como Concluído? Esta ação levará à tela de envio do produto.');">
                                           ✅ Concluir Pedido
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8">Nenhum pedido encontrado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>

        <div class="secondary-actions">
            <a href="gerenciar.php" class="btn btn-secondary">↩️ Gerenciar ⚙️</a>
            <a href="logout.php" class="btn btn-secondary">🚪 Desconectar</a>
        </div>
    </div>
</body>
</html>

