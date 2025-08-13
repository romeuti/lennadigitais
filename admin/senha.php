<?php
// 1) Defina a senha originalmente (ou receba via parâmetro, conforme sua necessidade)
$senha = 'rsntecti';

// 2) Gere o hash com bcrypt (cost 12, por exemplo)
$hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

// 3) Verifique se estamos no terminal (CLI) ou no webserver
if (php_sapi_name() === 'cli') {
    // Saída para terminal: use quebras de linha nativas
    echo "Senha original: {$senha}" . PHP_EOL . PHP_EOL;
    echo "Copie o HASH abaixo e cole no campo 'senha' do seu usuário no phpMyAdmin:" . PHP_EOL . PHP_EOL;
    echo "{$hash}" . PHP_EOL;
} else {
    // Saída para navegador: HTML estruturado e seguro
    header('Content-Type: text/html; charset=UTF-8');
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
      <meta charset="UTF-8">
      <title>Hash de Senha</title>
      <style>
        body { font-family: sans-serif; line-height: 1.5; margin: 2rem; }
        pre { background: #f5f5f5; padding: 1rem; border-radius: 4px; }
      </style>
    </head>
    <body>
      <h1>Hash de Senha</h1>
      <p><strong>Senha original:</strong> <?php echo htmlspecialchars($senha, ENT_QUOTES, 'UTF-8'); ?></p>
      <p>Copie o <strong>HASH</strong> abaixo e cole no campo <code>senha</code> do seu usuário no <em>phpMyAdmin</em>:</p>
      <pre><?php echo htmlspecialchars($hash, ENT_QUOTES, 'UTF-8'); ?></pre>
    </body>
    </html>
    <?php
}

