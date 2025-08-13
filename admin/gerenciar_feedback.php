<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'auth.php';
include 'banco.php';

// --- LÓGICA PARA APROVAR/REPROVAR FEEDBACK ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_feedback'])) {
    $id_feedback = (int)$_POST['id_feedback'];
    $pagina_atual = intval($_POST['pagina_atual'] ?? 1); // Mantém a página atual após a ação
    $filtro_status = $_POST['filtro_status'] ?? 'pendente'; // Mantém o filtro atual
    
    if (isset($_POST['aprovar'])) {
        $status = 'aprovado';
        $mensagem = 'Feedback aprovado com sucesso!';
    } elseif (isset($_POST['reprovar'])) {
        $status = 'reprovado';
        $mensagem = 'Feedback reprovado com sucesso!';
    }

    if (isset($status)) {
        $stmt = $conn->prepare("UPDATE feedback SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id_feedback);
        if ($stmt->execute()) {
            $_SESSION['mensagem_sucesso'] = $mensagem;
        } else {
            $_SESSION['mensagem_erro'] = "Erro ao atualizar o status do feedback.";
        }
        $stmt->close();
    }
    header("Location: gerenciar_feedback.php?filtro={$filtro_status}&pagina={$pagina_atual}");
    exit;
}

// --- LÓGICA DE PAGINAÇÃO E FILTRO ---
$filtro_status = $_GET['filtro'] ?? 'pendente';
$feedbacks_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;

// Contar total de feedbacks para o filtro atual
$stmt_total = $conn->prepare("SELECT COUNT(*) FROM feedback WHERE status = ?");
$stmt_total->bind_param("s", $filtro_status);
$stmt_total->execute();
$total_feedbacks = $stmt_total->get_result()->fetch_row()[0];
$total_paginas = ceil($total_feedbacks / $feedbacks_por_pagina);
$stmt_total->close();

$offset = ($pagina_atual - 1) * $feedbacks_por_pagina;

// --- LÓGICA PARA LISTAR FEEDBACKS COM PAGINAÇÃO ---
$sql_feedbacks = "
    SELECT f.id, f.id_produto, f.nome_cliente, f.avaliacao, f.nota, f.data_envio, f.status, p.nome as nome_produto
    FROM feedback f
    JOIN produtos p ON f.id_produto = p.id
    WHERE f.status = ?
    ORDER BY f.data_envio DESC
    LIMIT ? OFFSET ?
";
$stmt_feedbacks = $conn->prepare($sql_feedbacks);
$stmt_feedbacks->bind_param("sii", $filtro_status, $feedbacks_por_pagina, $offset);
$stmt_feedbacks->execute();
$result_feedbacks = $stmt_feedbacks->get_result();

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Feedbacks</title>
    <link rel="stylesheet" href="../css/gerenciar_feedback.css">
    <link href="../img/icones/feedback.png" rel="icon" type="image/png">
</head>
<body>
    <div class="container">
        <div class="top-actions">
            <a href="../index.php">🏠 Início</a>
            <a href="gerenciar.php">↩️ Gerenciar ⚙️</a>
            <a href="logout.php">🚪 Desconectar</a>
        </div>

        <h1>Gerenciamento de Feedbacks</h1>

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

        <div class="filter-tabs">
            <a href="?filtro=pendente" class="<?php echo ($filtro_status == 'pendente') ? 'active' : ''; ?>">Pendentes</a>
            <a href="?filtro=aprovado" class="<?php echo ($filtro_status == 'aprovado') ? 'active' : ''; ?>">Aprovados</a>
            <a href="?filtro=reprovado" class="<?php echo ($filtro_status == 'reprovado') ? 'active' : ''; ?>">Reprovados</a>
        </div>

        <div class="feedback-grid">
            <?php if ($result_feedbacks->num_rows > 0): ?>
                <?php while ($feedback = $result_feedbacks->fetch_assoc()): ?>
                    <div class="feedback-card">
                        <div class="card-header">
                            <div class="customer-info">
                                <strong><?php echo htmlspecialchars($feedback['nome_cliente']); ?></strong>
                                <span>em <?php echo (new DateTime($feedback['data_envio']))->format('d/m/Y'); ?></span>
                            </div>
                            <div class="star-rating">
                                <?php for($i=0; $i<5; $i++): ?>
                                    <span class="star <?php echo ($i < $feedback['nota']) ? 'filled' : ''; ?>">&#9733;</span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="product-info">
                            Produto: <a href="../detalhe_produto.php?id=<?php echo $feedback['id_produto']; ?>" target="_blank"><?php echo htmlspecialchars($feedback['nome_produto']); ?></a>
                        </div>
                        <p class="feedback-content">
                            <?php echo nl2br(htmlspecialchars($feedback['avaliacao'])); ?>
                        </p>
                        <?php if ($filtro_status == 'pendente'): ?>
                            <div class="card-actions">
                                <form action="gerenciar_feedback.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="id_feedback" value="<?php echo $feedback['id']; ?>">
                                    <input type="hidden" name="pagina_atual" value="<?php echo $pagina_atual; ?>">
                                    <input type="hidden" name="filtro_status" value="<?php echo $filtro_status; ?>">
                                    <button type="submit" name="aprovar" class="btn btn-approve">Aprovar</button>
                                </form>
                                <form action="gerenciar_feedback.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="id_feedback" value="<?php echo $feedback['id']; ?>">
                                    <input type="hidden" name="pagina_atual" value="<?php echo $pagina_atual; ?>">
                                    <input type="hidden" name="filtro_status" value="<?php echo $filtro_status; ?>">
                                    <button type="submit" name="reprovar" class="btn btn-reject">Reprovar</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-feedback-message">Nenhum feedback encontrado para o status "<?php echo htmlspecialchars($filtro_status); ?>".</p>
            <?php endif; ?>
        </div>
        
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="?filtro=<?php echo $filtro_status; ?>&pagina=<?php echo $i; ?>" class="<?php if($pagina_atual == $i) echo 'active'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
    </div>
</body>
</html>

