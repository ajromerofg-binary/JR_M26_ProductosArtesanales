<?php
// Define una función que muestra un modal de confirmación. Recibe como parámetro el email del cliente.
function mostrarModalConfirmacion($emailCliente) {
?>

<!-- Modal de Bootstrap -->
<div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-labelledby="modalConfirmacionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-luz">

            <div class="modal-header">
                <h4 class="modal-title" id="modalConfirmacionLabel">Correo enviado</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body text-center">
                <p class="mb-0">
                    Se ha enviado un correo con los datos de su compra a
                    <!-- Muestra el email del cliente protegido con htmlspecialchars para evitar código malicioso. -->
                    <strong><?= htmlspecialchars($emailCliente) ?></strong>
                </p>
            </div>

            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-tierra" data-bs-dismiss="modal"> <!-- Botón que cierra el modal usando Bootstrap. -->
                    Cerrar
                </button>
            </div>

        </div>
    </div>
</div>

<script>
// Función autoejecutable: se ejecuta automáticamente al cargarse este bloque.
(function () {
    // Busca el modal en el HTML por su ID.
    const modalElement = document.getElementById('modalConfirmacion');
    if (modalElement && typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    } else {
        console.error('No se pudo abrir el modal de confirmación');
    }
})();
</script>

<?php
}
?>