<?php
//🔍 Exibe todos os erros do PHP para facilitar a depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'auth.php'; //🔒 Restringe o acesso apenas para administradores logados
include 'banco.php'; // 💾 Conexão com o banco de dados

// busca todos os templates
$sql = "SELECT c.id, c.nome, IFNULL(m.conteudo,'') AS conteudo
          FROM categorias c
     LEFT JOIN descricao_modelos m
            ON m.categoria_id = c.id
      ORDER BY c.nome ASC";
$res = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
	<meta charset="UTF-8">
	<title>Modelos de Descrição</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="../css/gerenciar_modelos.css">
</head>
<body>
  <div class="admin-panel">
    <h1>📝 Modelos de Descrição por Categoria</h1>
    <p class="subtitle">Edite o texto formatado que será puxado no cadastro de produtos</p>
    <table>
      <thead>
        <tr>
          <th>Categoria</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php while($row = $res->fetch_assoc()): ?>
        <tr>
          <td data-label="Categoria"><?= htmlspecialchars($row['nome']) ?></td>
          <td data-label="Ações">
            <?php if ($row['conteudo']): ?>
              <a href="editar_modelo.php?categoria=<?= $row['id'] ?>"
                 class="btn btn-edit">✏️ Editar</a>
            <?php else: ?>
              <a href="editar_modelo.php?categoria=<?= $row['id'] ?>"
                 class="btn btn-create">➕ Criar</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>

    <div class="secondary-actions">
      <a href="../index.php" class="btn btn-secondary">🏠 Início</a>
      <a href="gerenciar.php" class="btn btn-secondary">↩️ Gerenciar ⚙️</a>
      <a href="logout.php" class="btn btn-secondary">🚪 Desconectar</a>
    </div>
  </div>
</body>
</html>

