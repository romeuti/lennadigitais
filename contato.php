<?php
// INFORMAÇÕES ESPECÍFICAS DESTA PÁGINA
// Estas variáveis serão usadas pelo header.php
$page_title = 'Contato - Lenna Digitais';
$page_icon = 'img/icones/contato.png';
$page_description = 'Entre em contato com a Lenna Digitais. WhatsApp, email e endereço para atendimento personalizado.';

// Inclui o cabeçalho padrão, que agora usará as variáveis acima
include 'includes/header.php';

// --- O RESTANTE DO SEU CÓDIGO PHP CONTINUA NORMAL ---
$whatsapp_message = "Olá! Tudo bem? 😊 Vi algo interessante no site e gostaria de tirar algumas dúvidas.";
$encoded_message = urlencode($whatsapp_message);
?>

<link rel="stylesheet" href="css/contato.css">
<link rel="preload" href="img/lenna.png" as="image">

<main id="main-content">
    <div class="container contact-page">
        <h1 class="page-title">Entre em Contato</h1>

        <section class="contact-options" aria-labelledby="atendimento-rapido">
            <h2 id="atendimento-rapido">Atendimento Rápido</h2>
            <p>Prefere um contato mais direto? Fale comigo através do WhatsApp ou E-mail.</p>
            <div class="buttons-container">
                <a href="mailto:a.s.games2017@gmail.com" class="contact-button email" aria-label="Enviar email para contato">
                    <img src="img/icones/e-mail.png" alt="" role="presentation"> 
                    E-mail
                </a>
                <a href="https://wa.me/5573991824641?text=<?php echo $encoded_message; ?>" target="_blank" rel="noopener" class="contact-button whatsapp-app" aria-label="Abrir WhatsApp no aplicativo com mensagem pronta">
                    <img src="img/icones/whatsapp1.png" alt="" role="presentation"> 
                    WhatsApp (App)
                </a>
                <a href="https://web.whatsapp.com/send?phone=5573991824641&text=<?php echo $encoded_message; ?>" target="_blank" rel="noopener" class="contact-button whatsapp-web" aria-label="Abrir WhatsApp na web com mensagem pronta">
                    <img src="img/icones/whatsapp2.png" alt="" role="presentation"> 
                    WhatsApp (Web)
                </a>
            </div>
        </section>

        <section class="store-address" aria-labelledby="endereco-loja">
            <h2 id="endereco-loja">Visite Nossa Loja (Agendamento Prévio)</h2>
            <p>Caso deseje fazer uma visita, por favor, entre em contato para agendarmos um horário.</p>
            <div class="address-info">
                <img src="img/icones/endereco.png" alt="" class="address-icon" role="presentation">
                <address>
                    Endereço: Rua Edite Marquês, 28<br>
                    Bairro: Baixa da Colina<br>
                    Cidade: Itagi-BA<br>
                    CEP: 45230-000
                </address>
            </div>
        </section>
    </div>
</main>

<?php
// Inclui o rodapé padrão
include 'includes/footer.php';
?>

<script>
document.querySelectorAll('.contact-button').forEach(button => {
    button.addEventListener('click', function() {
        if (this.href.includes('mailto:')) {
            this.style.opacity = '0.7';
            setTimeout(() => {
                this.style.opacity = '1';
            }, 1000);
        }
    });
});
</script>

