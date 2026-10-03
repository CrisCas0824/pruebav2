function togglePass(id, btn) {
            var input = document.getElementById(id);
            var eyeClosed = btn.querySelector('.eye-closed');
            var eyeOpen = btn.querySelector('.eye-open');

            if (input.type === 'password') {
                input.type = 'text';
                if(eyeClosed) eyeClosed.style.display = 'none';
                if(eyeOpen) eyeOpen.style.display = 'block';
            } else {
                input.type = 'password';
                if(eyeClosed) eyeClosed.style.display = 'block';
                if(eyeOpen) eyeOpen.style.display = 'none';
            }
        }


        
        document.getElementById('regConfirmPassword').addEventListener('input', function () {
            var password = document.getElementById('regPassword').value;
            var confirm = this.value;
            var errorSpan = document.getElementById('match-error');

            if (confirm.length > 0 && password !== confirm) {
                this.classList.add('error');
                errorSpan.textContent = 'Las contraseñas no coinciden';
                errorSpan.classList.add('show');
            } else {
                this.classList.remove('error');
                errorSpan.textContent = '';
                errorSpan.classList.remove('show');
            }
        });

        
        document.getElementById('regNombre').addEventListener('input', function () {
            this.classList.remove('error');
        });

        document.getElementById('regEmail').addEventListener('input', function () {
            this.classList.remove('error');
        });

        document.getElementById('regPassword').addEventListener('input', function () {
            this.classList.remove('error');
        });

        
        document.getElementById('registerForm').addEventListener('submit', function (e) {
            var nombre = document.getElementById('regNombre');
            var email = document.getElementById('regEmail');
            var password = document.getElementById('regPassword');
            var confirmPassword = document.getElementById('regConfirmPassword');
            var valid = true;

            
            if (!nombre.value.trim()) {
                nombre.classList.add('error');
                valid = false;
            }

            
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email.value || !emailRegex.test(email.value)) {
                email.classList.add('error');
                valid = false;
            }

            
            if (!password.value || password.value.length < 6) {
                password.classList.add('error');
                valid = false;
            }

            
            if (password.value !== confirmPassword.value) {
                confirmPassword.classList.add('error');
                document.getElementById('match-error').textContent = 'Las contraseñas no coinciden';
                document.getElementById('match-error').classList.add('show');
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
            }
        });
