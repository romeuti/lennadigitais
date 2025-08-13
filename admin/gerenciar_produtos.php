<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'auth.php';
require_once 'banco.php';

// Paginação
$page   = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$page   = max(1, $page);
$limit  = 15;
$offset = ($page - 1) * $limit;

// total e total_pages
$total_row   = $conn->query("SELECT COUNT(*) AS total FROM produtos")->fetch_assoc();
$total       = $total_row['total'];
$total_pages = (int) ceil($total / $limit);

// Busca ASC com paginação
$sql    = "
  SELECT p.id, p.nome, p.preco, c.nome AS categoria
    FROM produtos p
    JOIN categorias c ON p.id_categoria = c.id
   ORDER BY p.id ASC
   LIMIT $limit OFFSET $offset
";
$result = $conn->query($sql);
$produtos = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Gerenciar Produtos</title>
  <link rel="stylesheet" href="../css/gerenciar_produtos.css">
</head>
<body>
  <div class="admin-panel">
    <h1>📋 Gerenciar Produtos</h1>

    <a href="cadastrar_produto.php" class="btn btn-primary">📦 Novo Produto</a>

    <table class="table-list">
      <thead>
        <tr>
          <th>ID</th>
          <th>Produto</th>
          <th>Preço</th>
          <th>Categoria</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($produtos): ?>
          <?php foreach ($produtos as $p): ?>
            <tr>
              <td><?= $p['id'] ?></td>
              <td><?= htmlspecialchars($p['nome']) ?></td>
              <td>R$ <?= number_format($p['preco'], 2, ',', '.') ?></td>
              <td><?= htmlspecialchars($p['categoria']) ?></td>
              <td>
                <a href="cadastrar_produto.php?id=<?= $p['id'] ?>" class="btn btn-edit">✏️ Editar</a>
                <a href="excluir_produto.php?id=<?= $p['id'] ?>"
                   class="btn btn-delete"
                   onclick="return confirm('Confirma exclusão deste produto?');">
                   ❌ Excluir
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="5">Nenhum produto cadastrado.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

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
      <a href="gerenciar.php"     class="btn btn-back">↩️ Voltar</a>
      <a href="logout.php"        class="btn btn-logout">🚪 Desconectar</a>
    </div>
  </div>
</body>
</html>

