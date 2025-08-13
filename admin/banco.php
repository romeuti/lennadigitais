<?php
//🔍 Exibe todos os erros do PHP para facilitar a depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 🔧 Caminho absoluto
if (!defined('ROOT_PATH')) {
  define('ROOT_PATH', dirname(__DIR__)); // Aponta para a pasta 'romeutech'
}

// 💾 Conexão com o banco de dados
$servidor = "localhost";
$usuario = "romeuti";
$senha = "Ph@ndora#2025";
$banco = "lenna";
$conn = new mysqli($servidor, $usuario, $senha, $banco);

// Define o charset para UTF-8 para evitar problemas com acentuação
$conn->set_charset("utf8mb4");

// NOVO: Define o fuso horário da conexão MySQL para o fuso horário do Brasil (GMT-03:00)
// Isso é crucial para consistência de datas como expires_at.
$conn->query("SET time_zone = '-03:00'");

if ($conn->connect_error) {
  // Em produção, é melhor logar o erro do que exibi-lo na tela
  error_log("Erro na conexão com o banco de dados: " . $conn->connect_error);
  die("Ocorreu um erro inesperado ao conectar com o banco de dados. Por favor, tente novamente mais tarde.");
}
?>

