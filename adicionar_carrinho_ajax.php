<?php
session_start();

// Garante que o carrinho exista na sessão
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

// Verifica se o ID do produto foi enviado via POST
if (isset($_POST['id_produto'])) {
    $id_produto = (int)$_POST['id_produto'];
    $quantidade = 1; // Para arquivos digitais, a quantidade é sempre 1

    // Adiciona o produto ao carrinho (ou apenas confirma que ele está lá)
    $_SESSION['carrinho'][$id_produto] = $quantidade;
    
    // Calcula a nova contagem de itens
    $novo_total_itens = count($_SESSION['carrinho']);

    // Retorna uma resposta JSON com o sucesso e a nova contagem
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'cartCount' => $novo_total_itens]);
    exit;
}

// Se o acesso não for correto, retorna um erro
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
exit;

