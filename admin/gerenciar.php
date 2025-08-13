<?php
//🔍 Exibe todos os erros do PHP para facilitar a depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'auth.php'; //🔒 Garante que só admin logado acesse
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Painel de Gerenciamento</title>
  <link rel="stylesheet" href="../css/gerenciar.css">
  <link href="../img/icones/configuracao.png" rel="icon" type="image/png">
</head>
<body>
  <div class="admin-panel">
    <h1>⚙️ Painel de Gerenciamento 🛠️</h1>
    <p>Selecione uma opção abaixo para continuar.</p>

    <div class="panel-buttons">
      <a href="cadastrar_produto.php" class="btn">
        <span class="icon">📦</span> Cadastrar Produtos
      </a>

      <a href="gerenciar_produtos.php" class="btn">
        <span class="icon">🛍️</span> Listar Produtos
      </a>

      <a href="gerenciar_modelos.php" class="btn">
        <span class="icon">📝</span> Modelos de Descrição
      </a>

      <a href="gerenciar_pedidos.php" class="btn">
        <span class="icon">🛒</span> Gerenciar Pedidos
      </a>

      <a href="financeiro.php" class="btn">
        <span class="icon">💰</span> Financeiro
      </a>

      <a href="gerenciar_categorias.php" class="btn">
        <span class="icon">🏷️</span> Nova Categoria
      </a>

      <a href="gerenciar_feedback.php" class="btn">
        <span class="icon">🌟</span> Aprovar Feedbacks
      </a>

      <a href="usuario.php" class="btn">
        <span class="icon">🧑‍💻</span> Gerenciar ADM
      </a>

      <a href="user_clientes.php" class="btn">
        <span class="icon">👤</span> Gerenciar Clientes
      </a>

      <a href="https://www.romeuti.shop/phpmyadmin/index.php"
         class="btn" target="_blank" rel="noopener">
        <span class="icon">🛢️</span> Banco de Dados
      </a>
    </div>

    <div class="secondary-actions">
      <a href="../index.php" class="btn-secondary">🏠 Início</a>
      <a href="logout.php"   class="btn-secondary">🚪 Desconectar</a>
    </div>
  </div>
</body>
</html>

