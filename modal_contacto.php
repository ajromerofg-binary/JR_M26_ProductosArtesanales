
<div class="modal fade" id="modalContacto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-custom-content">
            
            <div class="modal-header modal-header-tierra">
                <h5 class="modal-title fw-bold">Formulario de Contacto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <p class="text-muted">¿Te gusta nuestro trabajo? Déjanos un mensaje y te responderemos en menos de 24 horas.</p>
                
                <form action="mensaje_contacto.php" method="POST">
                    <div class="mb-3 text-start">
                        <label class="form-label-style">Tu email</label>
                        <input type="email" name="email" class="form-control" placeholder="nombre@ejemplo.com" required>
                    </div>
                    <div class="mb-3 text-start">
                        <label class="form-label-style">Mensaje</label>
                        <textarea name="mensaje" class="form-control" rows="3" placeholder="Escribe aquí tu duda..." required></textarea>
                    </div>
                    
                    <div class="modal-footer-custom">
                        <button type="button" class="btn btn-cerrar-modal" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-tierra">Enviar mensaje</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
