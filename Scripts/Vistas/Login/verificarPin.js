document.addEventListener('DOMContentLoaded', function () {
            const inputs = Array.from(document.querySelectorAll('.pin-digit-input'));
            const fullPinInput = document.getElementById('fullPin');
            const pinForm = document.getElementById('pinForm');
            const btnVerificar = document.getElementById('btnVerificar');
            const resendLink = document.getElementById('resendLink');
            const countdownSpan = document.getElementById('countdown');

            // Countdown timer para reenviar código
            let timeLeft = 60;
            const timer = setInterval(() => {
                timeLeft--;
                if (countdownSpan) countdownSpan.textContent = timeLeft;
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    if (resendLink) {
                        resendLink.classList.remove('disabled');
                        resendLink.style.color = '#15803D';
                        resendLink.style.pointerEvents = 'auto';
                        resendLink.style.textDecoration = 'none';
                        resendLink.style.fontWeight = '700';
                        resendLink.textContent = 'Reenviar código ahora';
                    }
                }
            }, 1000);

            function updateFullPin() {
                const pin = inputs.map(input => input.value).join('');
                fullPinInput.value = pin;
                inputs.forEach(input => {
                    if (input.value) {
                        input.classList.add('filled');
                    } else {
                        input.classList.remove('filled');
                    }
                });
                return pin;
            }

            inputs.forEach((input, index) => {
                // Focus en el primer input al cargar
                if (index === 0) input.focus();

                input.addEventListener('input', function (e) {
                    // Permitir solo números
                    this.value = this.value.replace(/[^0-9]/g, '');

                    if (this.value.length === 1) {
                        if (index < inputs.length - 1) {
                            inputs[index + 1].focus();
                        }
                    }

                    const pin = updateFullPin();
                    if (pin.length === inputs.length) {
                        // Todos los 6 dígitos han sido ingresados
                        pinForm.submit();
                    }
                });

                input.addEventListener('keydown', function (e) {
                    if (e.key === 'Backspace') {
                        if (!this.value && index > 0) {
                            inputs[index - 1].focus();
                            inputs[index - 1].value = '';
                            updateFullPin();
                        }
                    } else if (e.key === 'ArrowLeft' && index > 0) {
                        inputs[index - 1].focus();
                    } else if (e.key === 'ArrowRight' && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                });

                // Soporte para pegar el PIN completo
                input.addEventListener('paste', function (e) {
                    e.preventDefault();
                    const pastedData = (e.clipboardData || window.clipboardData).getData('text').trim();
                    const cleanData = pastedData.replace(/[^0-9]/g, '').slice(0, inputs.length);

                    if (cleanData.length > 0) {
                        cleanData.split('').forEach((char, i) => {
                            if (inputs[i]) {
                                inputs[i].value = char;
                            }
                        });
                        const focusIndex = Math.min(cleanData.length, inputs.length - 1);
                        inputs[focusIndex].focus();
                        const pin = updateFullPin();
                        if (pin.length === inputs.length) {
                            pinForm.submit();
                        }
                    }
                });
            });

            pinForm.addEventListener('submit', function (e) {
                const pin = updateFullPin();
                if (pin.length < inputs.length) {
                    e.preventDefault();
                    alert('Por favor, ingresa los 6 dígitos del código.');
                    const emptyIndex = inputs.findIndex(i => !i.value);
                    if (emptyIndex !== -1) inputs[emptyIndex].focus();
                }
            });
        });
