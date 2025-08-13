<?php
session_start(); //🔒 Inicia a sessão para controle de autenticação e permissões
$erro = '';  //🔒 Verifica se o usuário está logado
require_once 'banco.php'; //💾 Conexão com o banco de dados

// Processa o login quando o formulário for enviado (método POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- INÍCIO DO ÚNICO BLOCO AJUSTADO ---
    $user = trim($_POST['usuario'] ?? '');
    $pass = $_POST['senha'] ?? '';
    
    // Consulta segura para buscar ID, NOME e HASH da senha, permitindo login com usuário ou email
    $stmt = $conn->prepare("SELECT id, nome, senha FROM usuarios WHERE nome = ? OR email = ?");
    $stmt->bind_param("ss", $user, $user);
    $stmt->execute();
    $stmt->bind_result($usuario_id, $usuario_nome, $senhaHash);
    $loginOk = $stmt->fetch();
    $stmt->close();
    
    // Redireciona para gerenciar.php após login bem-sucedido
    if ($loginOk && password_verify($pass, $senhaHash)) {
        // Guarda os dados essenciais na sessão
        $_SESSION['logado'] = true;
        $_SESSION['usuario_id'] = $usuario_id;
        $_SESSION['usuario_nome'] = $usuario_nome;
        $_SESSION['ultimo_acesso'] = time();
        
        header('Location: gerenciar.php');
        exit;
    } else {
        $erro = 'Usuário ou senha inválidos.';
    }
    // --- FIM DO ÚNICO BLOCO AJUSTADO ---
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Login Lenna</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/login.css">
  <link href="../img/icones/login.png" rel="icon" type="image/png">
</head>
<body>
<div class="painel-sci">
  <img src="../img/romeutech.png" alt="Slogan" class="slogan">
  <h2>⚙️ LOGIN-ADM 🛠️</h2>

  <?php if ($erro): ?>
    <div class="error"><?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>

  <form method="post" action="login.php">
    <div class="input-group">
      <i>🧑‍💻</i>
      <input type="text" name="usuario" placeholder="Usuário" required>
    </div>

    <div class="input-group">
      <i>🔒</i>
      <div class="input-eye-container">
        <input type="password" name="senha" placeholder="Senha" required>
        <span onclick="mostrarSenhaAdm()" class="eye-icon" id="toggleSenhaAdm">🛡️</span>
      </div>
    </div>

    <script>
    function mostrarSenhaAdm() {
      const campo = document.querySelector('input[name="senha"]');
      const icone = document.getElementById("toggleSenhaAdm");
      if (campo.type === "password") {
        campo.type = "text";
        icone.textContent = "🕵️";
      } else {
        campo.type = "password";
        icone.textContent = "🛡️";
      }
    }
    </script>

    <button type="submit" class="btn login">🔓 Entrar</button>
    <a href="../index.php" class="btn home">🏠 Voltar para Início</a>
  </form>
</div>

</body>
</html>

