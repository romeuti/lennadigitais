<?php
//🔍 Exibe todos os erros do PHP para facilitar a depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'auth.php';    //🔒 Garante que só admin logado acesse
require_once 'banco.php';   //💾 Conexão e definição de ROOT_PATH

// Obtém e valida o ID
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: gerenciar_produtos.php?erro=ID inválido');
    exit;
}

// 1) Remove o arquivo de capa, se existir
$stmt = $conn->prepare("
    SELECT capa_imagem 
      FROM produtos 
     WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($capa);
$stmt->fetch();
$stmt->close();

if ($capa) {
    $fullCapa = ROOT_PATH . '/' . $capa;
    if (file_exists($fullCapa)) {
        unlink($fullCapa);
    }
}

// 2) Coleta todos os caminhos da galeria
$stmt2 = $conn->prepare("
    SELECT caminho_imagem
      FROM produto_imagens
     WHERE id_produto = ?
");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$stmt2->bind_result($imgPath);

$galerias = [];
while ($stmt2->fetch()) {
    $galerias[] = $imgPath;
}
$stmt2->close();

// Remove arquivos físicos da galeria
foreach ($galerias as $g) {
    $fullImg = ROOT_PATH . '/' . $g;
    if (file_exists($fullImg)) {
        unlink($fullImg);
    }
}

// 3) Remove registros de galeria no banco
$stmt3 = $conn->prepare("
    DELETE FROM produto_imagens 
     WHERE id_produto = ?
");
$stmt3->bind_param("i", $id);
$stmt3->execute();
$stmt3->close();

// 4) Remove o registro do produto
$stmt4 = $conn->prepare("
    DELETE FROM produtos 
     WHERE id = ?
");
$stmt4->bind_param("i", $id);
$stmt4->execute();
$stmt4->close();

// 5) Redireciona de volta à listagem com mensagem
header('Location: gerenciar_produtos.php?msg=Produto excluído com sucesso');
exit;
?>

