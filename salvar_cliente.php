<?php
session_start();
include 'admin/banco.php'; // Conexão com o banco de dados

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'cadastro') { // Ação de cadastro de cliente
        $nome = trim($_POST['nome'] ?? ''); // CORRIGIDO: Coleta o campo 'name="nome"' do HTML
        $sobrenome = trim($_POST['sobrenome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $celular = trim($_POST['celular'] ?? '');
        $senha_plana = $_POST['senha'] ?? '';
        $confirma_senha = $_POST['confirma_senha'] ?? '';

        // Validação dos dados
        if (empty($nome) || empty($sobrenome) || empty($email) || empty($celular) || empty($senha_plana) || empty($confirma_senha)) {
            $_SESSION['mensagem_erro'] = "Erro: Todos os campos são obrigatórios para o cadastro.";
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'clientes.php'));
            exit();
        } elseif (strlen($senha_plana) < 6) {
            $_SESSION['mensagem_erro'] = "Erro: A senha deve ter no mínimo 6 caracteres.";
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'clientes.php'));
            exit();
        } elseif ($senha_plana !== $confirma_senha) {
            $_SESSION['mensagem_erro'] = "Erro: As senhas não coincidem.";
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'clientes.php'));
            exit();
        }

        // Hash da senha
        $senha_hash = password_hash($senha_plana, PASSWORD_BCRYPT, ['cost' => 12]);

        // Verifica se o email já existe
        $stmt_check = $conn->prepare("SELECT id FROM clientes WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) {
            $_SESSION['mensagem_erro'] = "Erro: Este e-mail já está cadastrado. Por favor, faça login ou use outro e-mail.";
            $stmt_check->close();
            $conn->close();
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'clientes.php'));
            exit();
        }
        $stmt_check->close();

        // Insere o novo cliente no banco de dados
        // CORRIGIDO: nome_completo -> nome
        $stmt = $conn->prepare("INSERT INTO clientes (nome, sobrenome, email, celular, senha) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nome, $sobrenome, $email, $celular, $senha_hash);

        if ($stmt->execute()) {
            $_SESSION['mensagem_sucesso'] = "Cadastro realizado com sucesso!";
            
            // Faz o login automático do cliente
            $_SESSION['cliente_logado'] = true;
            $_SESSION['cliente_id'] = $conn->insert_id;
            $_SESSION['cliente_nome'] = $nome . ' ' . $sobrenome; // Nome completo na sessão para exibição

            if (isset($_SESSION['carrinho']) && !empty($_SESSION['carrinho'])) {
                header("Location: finalizar_pedido.php");
            } else {
                header("Location: perfil_cliente.php");
            }
            exit();

        } else {
            $_SESSION['mensagem_erro'] = "Erro ao cadastrar o cliente: " . $stmt->error;
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'clientes.php'));
            exit();
        }

        $stmt->close();
        $conn->close();

    } else { // Requisição POST não é de cadastro de cliente
        $_SESSION['mensagem_erro'] = "Ação inválida.";
        $conn->close();
        header("Location: clientes.php");
        exit();
    }

} else { // Não é uma requisição POST
    header("Location: clientes.php");
    exit();
}
?>

