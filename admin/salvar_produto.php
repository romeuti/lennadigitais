<?php
session_start();
include 'banco.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Inicia a transação para garantir a integridade dos dados
    $conn->begin_transaction();

    try {
        // --- Processamento dos Dados do Formulário ---
        $is_edit_mode = isset($_POST['id']) && !empty($_POST['id']);
        $id_produto = $is_edit_mode ? (int)$_POST['id'] : null;
        
        $nome = trim($_POST['nome']);
        $descricao = $_POST['descricao'];
        $preco_formulario = trim($_POST['preco']);
        $id_categoria = (int)$_POST['id_categoria'];
        $preco_para_banco = str_replace(',', '.', $preco_formulario);

        // Validação básica
        if (empty($nome) || empty($preco_para_banco) || empty($id_categoria)) {
            throw new Exception("Nome, Preço e Categoria são obrigatórios.");
        }

        // --- LÓGICA DE ATUALIZAÇÃO OU INSERÇÃO ---
        if ($is_edit_mode) {
            // MODO EDIÇÃO
            $stmt = $conn->prepare("UPDATE produtos SET nome = ?, descricao = ?, preco = ?, id_categoria = ? WHERE id = ?");
            if (!$stmt) throw new Exception("Erro ao preparar a atualização: " . $conn->error);
            $stmt->bind_param("ssdii", $nome, $descricao, $preco_para_banco, $id_categoria, $id_produto);
            if (!$stmt->execute()) throw new Exception("Erro ao executar a atualização: " . $stmt->error);
            $stmt->close();
            $_SESSION['mensagem_sucesso'] = "Produto atualizado com sucesso!";
        } else {
            // MODO CADASTRO
            $stmt = $conn->prepare("INSERT INTO produtos (nome, descricao, preco, id_categoria) VALUES (?, ?, ?, ?)");
            if (!$stmt) throw new Exception("Erro ao preparar a inserção: " . $conn->error);
            $stmt->bind_param("ssdi", $nome, $descricao, $preco_para_banco, $id_categoria);
            if (!$stmt->execute()) throw new Exception("Erro ao executar a inserção: " . $stmt->error);
            $id_produto = $conn->insert_id; // Pega o ID do produto recém-criado
            $stmt->close();
            $_SESSION['mensagem_sucesso'] = "Produto cadastrado com sucesso!";
        }
        
        // --- LÓGICA DE UPLOAD DE IMAGENS ---
        $diretorio_produto = ROOT_PATH . '/upload/produtos/' . $id_produto . '/';
        if (!is_dir($diretorio_produto)) {
            if (!mkdir($diretorio_produto, 0755, true)) {
                throw new Exception("Falha ao criar o diretório do produto.");
            }
        }

        // UPLOAD DA IMAGEM DE CAPA
        if (isset($_FILES['imagem_capa']) && $_FILES['imagem_capa']['error'] == UPLOAD_ERR_OK) {
            $extensao = pathinfo($_FILES['imagem_capa']['name'], PATHINFO_EXTENSION);
            $nome_arquivo = 'capa_' . uniqid() . '.' . $extensao;
            $caminho_completo = $diretorio_produto . $nome_arquivo;

            if (move_uploaded_file($_FILES['imagem_capa']['tmp_name'], $caminho_completo)) {
                $caminho_banco = 'upload/produtos/' . $id_produto . '/' . $nome_arquivo;
                $update_stmt = $conn->prepare("UPDATE produtos SET capa_imagem = ? WHERE id = ?");
                if (!$update_stmt) throw new Exception("Erro ao preparar a atualização da imagem: " . $conn->error);
                $update_stmt->bind_param("si", $caminho_banco, $id_produto);
                if (!$update_stmt->execute()) throw new Exception("Erro ao salvar o caminho da imagem: " . $update_stmt->error);
                $update_stmt->close();
            } else {
                throw new Exception("Falha ao mover o arquivo de imagem para o destino.");
            }
        }

        // --- INÍCIO: LÓGICA DE UPLOAD DAS IMAGENS DA GALERIA ---
        if (isset($_FILES['outras_imagens']) && !empty($_FILES['outras_imagens']['name'][0])) {
            $imagens_galeria = $_FILES['outras_imagens'];
            
            // Prepara o statement de inserção uma única vez
            $stmt_galeria = $conn->prepare("INSERT INTO produto_imagens (id_produto, caminho_imagem) VALUES (?, ?)");
            if (!$stmt_galeria) throw new Exception("Erro ao preparar a inserção da galeria: " . $conn->error);

            foreach ($imagens_galeria['name'] as $i => $nome) {
                if ($imagens_galeria['error'][$i] === UPLOAD_ERR_OK) {
                    $extensao = pathinfo($nome, PATHINFO_EXTENSION);
                    $nome_arquivo_galeria = 'galeria_' . uniqid() . '.' . $extensao;
                    $caminho_completo_galeria = $diretorio_produto . $nome_arquivo_galeria;

                    if (move_uploaded_file($imagens_galeria['tmp_name'][$i], $caminho_completo_galeria)) {
                        $caminho_banco_galeria = 'upload/produtos/' . $id_produto . '/' . $nome_arquivo_galeria;
                        $stmt_galeria->bind_param("is", $id_produto, $caminho_banco_galeria);
                        if (!$stmt_galeria->execute()) throw new Exception("Erro ao salvar imagem da galeria no banco: " . $stmt_galeria->error);
                    } else {
                        throw new Exception("Falha ao mover um dos arquivos da galeria.");
                    }
                }
            }
            $stmt_galeria->close();
        }
        // --- FIM: LÓGICA DE UPLOAD DAS IMAGENS DA GALERIA ---

        // Se tudo deu certo, confirma a transação
        $conn->commit();

    } catch (Exception $e) {
        // Se algo deu errado, desfaz tudo
        $conn->rollback();
        $_SESSION['mensagem_erro'] = $e->getMessage();
    } finally {
        $conn->close();
    }
    
    // Redireciona de volta para a página de edição para ver as alterações ou para a página de cadastro
    if ($is_edit_mode) {
        header('Location: cadastrar_produto.php?id=' . $id_produto);
    } else {
        header('Location: cadastrar_produto.php');
    }
    exit();
}
?>

