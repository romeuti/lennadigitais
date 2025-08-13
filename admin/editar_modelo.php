<?php
//🔍 Exibe todos os erros do PHP para facilitar a depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'auth.php'; //🔒 Restringe o acesso apenas para administradores logados
include 'banco.php'; // 💾 Conexão com o banco de dados

$cat = intval($_GET['categoria'] ?? 0);
if ($cat < 1) header('Location: gerenciar_modelos.php');


// carrega nome e conteúdo existente
$stmt = $conn->prepare("
  SELECT nome FROM categorias WHERE id = ?
");
$stmt->bind_param("i",$cat);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$titulo = $row['nome'] ?? '---';

$stmt->close();

$conteudo = '';
$stmt = $conn->prepare("
  SELECT conteudo FROM descricao_modelos WHERE categoria_id = ?
");
$stmt->bind_param("i",$cat);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows) {
    $conteudo = $res->fetch_assoc()['conteudo'];
}
$stmt->close();

// processa POST
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $html = $_POST['conteudo'] ?? '';
    if ($conteudo) {
        $stmt = $conn->prepare("
          UPDATE descricao_modelos
             SET conteudo = ?
           WHERE categoria_id = ?
        ");
        $stmt->bind_param("si",$html,$cat);
    } else {
        $stmt = $conn->prepare("
          INSERT INTO descricao_modelos(categoria_id,conteudo)
          VALUES(?,?)
        ");
        $stmt->bind_param("is",$cat,$html);
    }
    $stmt->execute();
    header('Location: gerenciar_modelos.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Editar Modelo: <?=$titulo?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/editar_modelo.css">
  <script src="https://cdn.tiny.cloud/1/xlck5vxrnz1ac67yhebv1k...ndmn/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
  
  <script>
    tinymce.init({
      selector: 'textarea#conteudo',
      skin: 'oxide-dark',
      content_css: 'dark',
      
      // --- LINHAS ADICIONADAS PARA RESTAURAR OS BOTÕES ---
      plugins: 'lists link image table code help wordcount',
      toolbar: 'undo redo | blocks | bold italic | bullist numlist | alignleft aligncenter alignright | link image table | code'
      // --- FIM DAS LINHAS ADICIONADAS ---
    });
  </script>
  
</head>
<body>
  <div class="admin-panel">
    <h1>Modelo para “<?=$titulo?>”</h1>
    <form method="POST">
      <textarea id="conteudo" name="conteudo" rows="15">
        <?=htmlspecialchars($conteudo)?>
      </textarea>
      <div class="form-actions">
        <button type="submit" class="btn-save">💾 Salvar</button>
        <a href="gerenciar_modelos.php" class="btn-cancel">❌ Cancelar</a>
        <a href="logout.php" class="btn-logout">🚪 Desconectar</a>
      </div>
    </form>
  </div>
</body>
</html>

