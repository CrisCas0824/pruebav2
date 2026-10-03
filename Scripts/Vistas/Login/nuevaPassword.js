function togglePass(id, btn) {
            var input = document.getElementById(id);
            var eyeClosed = btn.querySelector('.eye-closed');
            var eyeOpen = btn.querySelector('.eye-open');

            if (input.type === 'password') {
                input.type = 'text';
                btn.style.color = '#15803D';
                if(eyeClosed) eyeClosed.style.display = 'none';
                if(eyeOpen) eyeOpen.style.display = 'block';
            } else {
                input.type = 'password';
                btn.style.color = '#94a3b8';
                if(eyeClosed) eyeClosed.style.display = 'block';
                if(eyeOpen) eyeOpen.style.display = 'none';
            }
        }


        
        document.getElementById('confirmNewPassword').addEventListener('input', function () {
            var password = document.getElementById('newPassword').value;
            var confirm = this.value;
            var errorSpan = document.getElementById('reset-match-error');

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

        
        document.getElementById('newPassword').addEventListener('input', function () {
            this.classList.remove('error');
        });

        
        document.getElementById('finalResetForm').addEventListener('submit', function (e) {
            var password = document.getElementById('newPassword');
            var confirmPassword = document.getElementById('confirmNewPassword');
            var valid = true;

            
            if (!password.value || password.value.length < 6) {
                password.classList.add('error');
                valid = false;
            }

            
            if (password.value !== confirmPassword.value) {
                confirmPassword.classList.add('error');
                document.getElementById('reset-match-error').textContent = 'Las contraseñas no coinciden';
                document.getElementById('reset-match-error').classList.add('show');
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
            }
        });
