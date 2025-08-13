<footer>
    <div class="container footer-container">
        <div class="copyright-text">
            <p>Lenna Digitais, &copy; <?php echo date('Y'); ?></p>
        </div>
        
        <div class="developer-credit">
            <a href="https://www.romeutech.shop" target="_blank" rel="noopener noreferrer">
                <img src="img/romeutech.png" alt="Desenvolvido por RomeuTech">
            </a>
            <p class="developer-text">Desenvolvido por RomeuTech</p>
        </div>
    </div>
</footer>

<script>
// SEUS SCRIPTS ORIGINAIS E COMPLETOS ESTÃO AQUI...
document.addEventListener('DOMContentLoaded', function() {
    
    function initializeSlider(sliderContainer) {
        if (sliderContainer.initialized) {
            return;
        }

        const wrapper = sliderContainer.querySelector('.slider-wrapper');
        const items = sliderContainer.querySelectorAll('.product-card');
        const totalItems = items.length;

        if (totalItems === 0) {
            return;
        }

        const getItemsPerPage = () => {
            const width = window.innerWidth;
            if (width <= 480) return 1;
            if (width <= 768) return 2;
            if (width <= 992) return 3;
            if (width <= 1200) return 4;
            return 5;
        };

        let itemsPerPage = getItemsPerPage();
        let currentIndex = 0;

        if (totalItems > itemsPerPage) {
            if (!sliderContainer.querySelector('.slider-button')) {
                const prevBtn = document.createElement('button');
                prevBtn.className = 'slider-button prev';
                prevBtn.innerHTML = '‹';
                const nextBtn = document.createElement('button');
                nextBtn.className = 'slider-button next';
                nextBtn.innerHTML = '›';
                sliderContainer.appendChild(prevBtn);
                sliderContainer.appendChild(nextBtn);

                prevBtn.addEventListener('click', showPrev);
                nextBtn.addEventListener('click', showNext);
            }
        }

        function updateSliderPosition() {
            const itemWidth = items[0].offsetWidth + 25;
            const offset = -currentIndex * itemWidth;
            wrapper.style.transform = `translateX(${offset}px)`;
        }

        function showNext() {
            if (currentIndex < (totalItems - itemsPerPage)) {
                currentIndex++;
            } else {
                currentIndex = 0;
            }
            updateSliderPosition();
        }

        function showPrev() {
            if (currentIndex > 0) {
                currentIndex--;
            } else {
                currentIndex = totalItems - itemsPerPage;
            }
            updateSliderPosition();
        }

        function startAutoLoop() {
            stopAutoLoop();
            sliderContainer.intervalId = setInterval(showNext, 5000);
        }

        function stopAutoLoop() {
            clearInterval(sliderContainer.intervalId);
        }
        
        sliderContainer.updateForResize = () => {
            const newItemsPerPage = getItemsPerPage();
            if (newItemsPerPage !== itemsPerPage) {
                itemsPerPage = newItemsPerPage;
                if (currentIndex > totalItems - itemsPerPage) {
                    currentIndex = 0;
                }
                const buttons = sliderContainer.querySelectorAll('.slider-button');
                if (totalItems > itemsPerPage) {
                    buttons.forEach(btn => btn.style.display = 'block');
                } else {
                    buttons.forEach(btn => btn.style.display = 'none');
                }
            }
            updateSliderPosition();
        };

        sliderContainer.addEventListener('mouseenter', stopAutoLoop);
        sliderContainer.addEventListener('mouseleave', startAutoLoop);
        
        sliderContainer.initialized = true;
        sliderContainer.updateForResize();
        startAutoLoop();
    }

    document.querySelectorAll('.slider-container').forEach(initializeSlider);

    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            document.querySelectorAll('.slider-container').forEach(slider => {
                if (slider.updateForResize) {
                    slider.updateForResize();
                }
            });
        }, 250);
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.ajax-add-cart-form');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(this);
            const button = this.querySelector('button[type="submit"]');
            const originalButtonText = button.innerHTML;

            button.innerHTML = 'Adicionando...';
            button.disabled = true;

            fetch('adicionar_carrinho_ajax.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const cartCountElement = document.querySelector('.cart-count');
                        if (cartCountElement) {
                            cartCountElement.innerText = data.cartCount;
                            cartCountElement.style.display = 'flex'; // Garante que seja visível
                        }
                    } else {
                        alert('Ocorreu um erro ao adicionar o produto.');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Ocorreu um erro de conexão.');
                })
                .finally(() => {
                    setTimeout(() => {
                        button.innerHTML = originalButtonText;
                        button.disabled = false;
                    }, 1500);
                });
        });
    });
});
</script>

<script>
const hamburgerButton = document.getElementById('hamburger-menu');
const mainNavList = document.getElementById('main-nav-list');

hamburgerButton.addEventListener('click', function() {
    mainNavList.classList.toggle('mobile-open');
    const isExpanded = this.getAttribute('aria-expanded') === 'true';
    this.setAttribute('aria-expanded', !isExpanded);
});
</script>

<script>
// SCRIPT PARA AJUSTE DO RODAPÉ (AGORA GLOBAL)
function adjustFooter() {
    const main = document.querySelector('main');
    const header = document.querySelector('.site-header');
    const footer = document.querySelector('footer');
    if (!main || !header || !footer) return;
    const viewportHeight = window.innerHeight;
    const headerHeight = header.offsetHeight;
    const footerHeight = footer.offsetHeight;
    const mainMinHeight = viewportHeight - headerHeight - footerHeight;
    main.style.minHeight = mainMinHeight + 'px';
}
window.addEventListener('load', adjustFooter);
window.addEventListener('resize', adjustFooter);
</script>

</body>
</html>

