<?php
require_once 'auth.php';
require_once 'banco.php';

header('Content-Type: application/json; charset=utf-8');

$cat = intval($_GET['categoria'] ?? 0);
if ($cat < 1) {
    echo json_encode(['erro'=>'Categoria inválida']);
    exit;
}

$stmt = $conn->prepare("
  SELECT conteudo
    FROM descricao_modelos
   WHERE categoria_id = ?
");
$stmt->bind_param("i",$cat);
$stmt->execute();
$res = $stmt->get_result();
$modelo = $res->fetch_assoc()['conteudo'] ?? '';

echo json_encode(['conteudo'=>$modelo]);

