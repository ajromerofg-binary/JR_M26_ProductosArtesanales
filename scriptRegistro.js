//Funcionamiento del ojo para mostrar contraseña
    
        document.querySelectorAll('.btn-toggle-pass').forEach(button => {
            button.addEventListener('click', () => {
                const input = button.parentElement.querySelector('input');
                const icon = button.querySelector('i');

                const isVisible = input.type === 'text';
                input.type = isVisible ? 'password' : 'text';

                icon.className = isVisible ? 'bi bi-eye' : 'bi bi-eye-slash';
            });
        });
    

    //Duracion del mensaje mostrado en pantalla
    
    setTimeout(() => {
        let alert = document.querySelector('.alert');
        if (alert) {
            let bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 3000); // 3 segundos
  

    //Script de barra de contraseña debil/fuerte
     
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');

        passwordInput.addEventListener('input', () => {
            const value = passwordInput.value;
            let strength = 0;

            if (value.length >= 6) strength++;
            if (/[A-Z]/.test(value)) strength++;
            if (/[0-9]/.test(value)) strength++;
            if (/[^A-Za-z0-9]/.test(value)) strength++;

            //Reset
            strengthBar.style.width = "0%";
            strengthBar.className = "progress-bar";

            if (value.length === 0) {
                strengthText.textContent = "";
                return;
            }

            if (strength <= 1) {
                strengthBar.style.width = "33%";
                strengthBar.classList.add("bg-danger");
                strengthText.textContent = "Debil";
            }else if (strength <= 3) {
                strengthBar.style.width = "66%";
                strengthBar.classList.add("bg-warning");
                strengthText.textContent = "Media";
            }else {
                strengthBar.style.width = "100%";
                strengthBar.classList.add("bg-success");
                strengthText.textContent = "Fuerte";
            }
        });
    

    //Validar pass en tiempo real
     
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirmPassword');
        const matchText = document.getElementById('matchText');

        function checkPassword(){
            if (confirmPassword.value === ""){
                matchText.textContent = "";
                return;
            }

            if (password.value === confirmPassword.value){
                matchText.textContent = "✔ Las contraseñas coinciden";
                matchText.className = "text-success";
            } else{
                matchText.textContent = "❌ Las contraseñas no coinciden";
                matchText.className = "text-danger";
            }
        }
        password.addEventListener('input', checkPassword);
        confirmPassword.addEventListener('input', checkPassword);

     