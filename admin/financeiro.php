<?php
//🔍 Exibe todos os erros do PHP para facilitar a depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); //🔒 Inicia a sessão para controle de autenticação e permissões
require_once 'auth.php'; //🔒 Restringe o acesso apenas para administradores logados
include 'banco.php';     // 💾 Conexão com o banco de dados

// --- LÓGICA DE FILTRO DE DATA ---
$data_inicio_dt = new DateTime('first day of this month');
$data_fim_dt = new DateTime('last day of this month');
if (isset($_GET['inicio']) && !empty($_GET['inicio'])) { $data_inicio_dt = new DateTime($_GET['inicio']); }
if (isset($_GET['fim']) && !empty($_GET['fim'])) { $data_fim_dt = new DateTime($_GET['fim']); }
$data_inicio_sql_dt = $data_inicio_dt->format('Y-m-d 00:00:00');
$data_fim_sql_dt = $data_fim_dt->format('Y-m-d 23:59:59');
$data_inicio_sql = $data_inicio_dt->format('Y-m-d');
$data_fim_sql = $data_fim_dt->format('Y-m-d');

// --- QUERIES PARA OS INDICADORES (KPIs) ---
// 1. Receita Bruta Total
$stmt_receita = $conn->prepare("SELECT SUM(ip.preco_unitario * ip.quantidade) as total FROM pedidos p JOIN itens_pedido ip ON p.id = ip.id_pedido WHERE p.data_pedido BETWEEN ? AND ? AND (p.status_pedido = 'Pago' OR p.status_pedido = 'Concluído')");
$stmt_receita->bind_param("ss", $data_inicio_sql_dt, $data_fim_sql_dt);
$stmt_receita->execute();
$receita_bruta = $stmt_receita->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_receita->close();

// 2. Total de Pedidos Realizados
$stmt_pedidos = $conn->prepare("SELECT COUNT(id) as total FROM pedidos WHERE data_pedido BETWEEN ? AND ?");
$stmt_pedidos->bind_param("ss", $data_inicio_sql_dt, $data_fim_sql_dt);
$stmt_pedidos->execute();
$total_pedidos = $stmt_pedidos->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_pedidos->close();

// 3. Total de Produtos Vendidos
$stmt_produtos = $conn->prepare("SELECT SUM(ip.quantidade) as total FROM pedidos p JOIN itens_pedido ip ON p.id = ip.id_pedido WHERE p.data_pedido BETWEEN ? AND ?");
$stmt_produtos->bind_param("ss", $data_inicio_sql_dt, $data_fim_sql_dt);
$stmt_produtos->execute();
$total_produtos_vendidos = $stmt_produtos->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_produtos->close();

// 4. Ticket Médio
$ticket_medio = ($total_pedidos > 0) ? ($receita_bruta / $total_pedidos) : 0;

// 5. Total de Despesas
$stmt_despesas = $conn->prepare("SELECT SUM(valor) as total FROM despesas WHERE data_despesa BETWEEN ? AND ?");
$stmt_despesas->bind_param("ss", $data_inicio_sql, $data_fim_sql);
$stmt_despesas->execute();
$total_despesas = $stmt_despesas->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_despesas->close();

// 6. Lucro Líquido
$lucro_liquido = $receita_bruta - $total_despesas;

// --- QUERIES PARA AS TABELAS DETALHADAS ---
// Produtos mais vendidos
$stmt_top_produtos = $conn->prepare("SELECT pr.nome, SUM(ip.quantidade) as qtd_vendida, SUM(ip.preco_unitario * ip.quantidade) as valor_total FROM itens_pedido ip JOIN produtos pr ON ip.id_produto = pr.id JOIN pedidos p ON ip.id_pedido = p.id WHERE p.data_pedido BETWEEN ? AND ? GROUP BY ip.id_produto, pr.nome ORDER BY valor_total DESC LIMIT 10");
$stmt_top_produtos->bind_param("ss", $data_inicio_sql_dt, $data_fim_sql_dt);
$stmt_top_produtos->execute();
$top_produtos_result = $stmt_top_produtos->get_result();
$stmt_top_produtos->close();

// Últimos pedidos
$stmt_ultimos_pedidos = $conn->prepare("SELECT p.id, p.data_pedido, p.nome_cliente, p.status_pedido, (SELECT SUM(ip.preco_unitario * ip.quantidade) FROM itens_pedido ip WHERE ip.id_pedido = p.id) as valor_total FROM pedidos p WHERE p.data_pedido BETWEEN ? AND ? ORDER BY p.data_pedido DESC LIMIT 10");
$stmt_ultimos_pedidos->bind_param("ss", $data_inicio_sql_dt, $data_fim_sql_dt);
$stmt_ultimos_pedidos->execute();
$ultimos_pedidos_result = $stmt_ultimos_pedidos->get_result();
$stmt_ultimos_pedidos->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Financeiro</title>
    <link rel="stylesheet" href="../css/financeiro.css">
    <link href="../img/icones/financeiro.png" rel="icon" type="image/png">
</head>
<body>
    <div class="finance-container">
        <div class="top-actions">
            <a href="../index.php">🏠 Início</a>
            <a href="gerenciar.php">↩️ Gerenciar ⚙️</a>
            <a href="logout.php">🚪 Desconectar</a>
        </div>

        <h1>Painel Financeiro</h1>

        <div class="finance-actions">
            <a href="gerenciar_despesas.php" class="btn-manage-expenses">💸 Lançar/Gerenciar Despesas</a>
        </div>

        <div class="card filter-bar">
            <form action="financeiro.php" method="GET">
                <div class="date-range">
                    <label for="inicio">De:</label>
                    <input type="date" id="inicio" name="inicio" value="<?php echo $data_inicio_dt->format('Y-m-d'); ?>">
                    <label for="fim">Até:</label>
                    <input type="date" id="fim" name="fim" value="<?php echo $data_fim_dt->format('Y-m-d'); ?>">
                </div>
                <button type="submit" class="btn-filter">Filtrar</button>
            </form>
            <div class="quick-filters">
                <a href="?inicio=<?php echo date('Y-m-d'); ?>&fim=<?php echo date('Y-m-d'); ?>">Hoje</a>
                <a href="?inicio=<?php echo date('Y-m-01'); ?>&fim=<?php echo date('Y-m-t'); ?>">Este Mês</a>
                <a href="?inicio=<?php echo date('Y-01-01'); ?>&fim=<?php echo date('Y-12-31'); ?>">Este Ano</a>
            </div>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card receita">
                <h2>Receita Bruta (Paga)</h2>
                <p>R$ <?php echo number_format($receita_bruta, 2, ',', '.'); ?></p>
            </div>
            <div class="kpi-card despesa">
                <h2>Despesas Totais</h2>
                <p>R$ <?php echo number_format($total_despesas, 2, ',', '.'); ?></p>
            </div>
            <div class="kpi-card lucro">
                <h2>Lucro Líquido</h2>
                <p>R$ <?php echo number_format($lucro_liquido, 2, ',', '.'); ?></p>
            </div>
            <div class="kpi-card">
                <h2>Pedidos Realizados</h2>
                <p><?php echo $total_pedidos; ?></p>
            </div>
            <div class="kpi-card">
                <h2>Produtos Vendidos</h2>
                <p><?php echo $total_produtos_vendidos; ?></p>
            </div>
            <div class="kpi-card">
                <h2>Ticket Médio</h2>
                <p>R$ <?php echo number_format($ticket_medio, 2, ',', '.'); ?></p>
            </div>
        </div>

        <div class="content-grid">
            <div class="card">
                <h2>Produtos Mais Vendidos</h2>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Qtd. Vendida</th>
                                <th>Valor Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($top_produtos_result->num_rows > 0): ?>
                                <?php while($produto = $top_produtos_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                                    <td><?php echo $produto['qtd_vendida']; ?></td>
                                    <td>R$ <?php echo number_format($produto['valor_total'], 2, ',', '.'); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3">Nenhum produto vendido no período.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card">
                <h2>Últimos Pedidos</h2>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Data</th>
                                <th>Cliente</th>
                                <th>Valor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($ultimos_pedidos_result->num_rows > 0): ?>
                                <?php while($pedido = $ultimos_pedidos_result->fetch_assoc()): ?>
                                <tr>
                                    <td><a href="ver_pedido.php?id=<?php echo $pedido['id']; ?>">#<?php echo $pedido['id']; ?></a></td>
                                    <td><?php echo (new DateTime($pedido['data_pedido']))->format('d/m/Y'); ?></td>
                                    <td><?php echo htmlspecialchars($pedido['nome_cliente']); ?></td>
                                    <td>R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></td>
                                    <td><span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $pedido['status_pedido'])); ?>"><?php echo htmlspecialchars($pedido['status_pedido']); ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5">Nenhum pedido encontrado no período.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

