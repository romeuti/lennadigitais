<?php
session_start(); //🔒 Inicia a sessão para controle de autenticação e permissões
include 'banco.php'; //💾 Conexão com o banco de dados

// LÓGICA PARA PROCESSAR AS AÇÕES - Verifica se é uma requisição POST (Cadastrar ou Editar)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $acao = $_POST['acao'] ?? '';
    
    // Ação de CADASTRAR uma nova categoria
    if ($acao == 'cadastrar') {
        $nome_categoria = trim($_POST['nome_categoria']);
        if (!empty($nome_categoria)) {
            $stmt = $conn->prepare("INSERT INTO categorias (nome) VALUES (?)");
            $stmt->bind_param("s", $nome_categoria);
            if ($stmt->execute()) {
                $_SESSION['mensagem_sucesso'] = "Categoria cadastrada com sucesso!";
            } else {
                $_SESSION['mensagem_erro'] = "Erro ao cadastrar categoria: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['mensagem_erro'] = "O nome da categoria não pode ser vazio.";
        }
    }

    // Ação de EDITAR uma categoria existente
    elseif ($acao == 'editar') {
        $id_categoria = (int)($_POST['id_categoria'] ?? 0);
        $nome_categoria = trim($_POST['nome_categoria']);

        if ($id_categoria > 0 && !empty($nome_categoria)) {
            $stmt = $conn->prepare("UPDATE categorias SET nome = ? WHERE id = ?");
            $stmt->bind_param("si", $nome_categoria, $id_categoria);
            if ($stmt->execute()) {
                $_SESSION['mensagem_sucesso'] = "Categoria atualizada com sucesso!";
            } else {
                $_SESSION['mensagem_erro'] = "Erro ao atualizar categoria: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['mensagem_erro'] = "Dados inválidos para atualização.";
        }
    }
}

// Verifica se é uma requisição GET (Excluir)
elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
    $acao = $_GET['acao'] ?? '';

    // Ação de EXCLUIR uma categoria
    if ($acao == 'excluir') {
        $id_categoria = (int)($_GET['id'] ?? 0);

        if ($id_categoria > 0) {
            // Adicional: Verificar se a categoria não está sendo usada por produtos antes de excluir
            // (Esta verificação não foi implementada para simplificar, mas é uma boa prática)
            $stmt = $conn->prepare("DELETE FROM categorias WHERE id = ?");
            $stmt->bind_param("i", $id_categoria);
            if ($stmt->execute()) {
                $_SESSION['mensagem_sucesso'] = "Categoria excluída com sucesso!";
            } else {
                $_SESSION['mensagem_erro'] = "Erro ao excluir categoria: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['mensagem_erro'] = "ID de categoria inválido para exclusão.";
        }
    }
}

$conn->close();
// Redireciona de volta para a página de categorias em todos os casos
header("Location: gerenciar_categorias.php");
exit();
?>

