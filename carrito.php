<?php
require_once("funcionAuth.php");
require_once("BD/conexion.php");

// Calcular total
$total = 0;
if (!empty($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $total += $item['precio'] * $item['cantidad'];
    }
}

// Cargar todas las direcciones del cliente
$tokens_disponibles = 0;
$direcciones = [];
$direccion_predeterminada = null;
if (isset($_SESSION['user']['id'])) {
     // Tokens del cliente
    $stmtTok = $pdo->prepare("SELECT tokens FROM CLIENTE WHERE ID = ?");
    $stmtTok->execute([$_SESSION['user']['id']]);
    $tokens_disponibles = (int)$stmtTok->fetchColumn();
    $stmtDir = $pdo->prepare("SELECT * FROM DIRECCION WHERE cliente_id = ? ORDER BY predeterminada DESC, ID ASC");
    $stmtDir->execute([$_SESSION['user']['id']]);
    $direcciones = $stmtDir->fetchAll();
    foreach ($direcciones as $dir) {
        if ($dir['predeterminada']) {
            $direccion_predeterminada = $dir;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carrito | Luz de Hogar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="styles.css" rel="stylesheet">
</head>

<body class="d-flex flex-column min-vh-100" style="background-color: var(--color-crema);">

<?php include 'navbar.php'; ?>
<main class="container mt-4 mb-5 flex-grow-1">
<div class="container carrito-container">

    <h1 class="carrito-titulo">Carrito de la compra</h1>

    <div class="carrito-grid">

        <!-- IZQUIERDA -->
        <div>

        <?php if (!empty($_SESSION['carrito'])): ?>

            <?php foreach ($_SESSION['carrito'] as $index => $producto): ?>

                <div class="carrito-producto mb-4">

    <a href="detalle.php?id=<?php echo isset($producto['id']) ? $producto['id'] : 0; ?>">
        <img src="<?php echo $producto['imagen']; ?>" class="carrito-img">
    </a>

    <div class="carrito-info">

        <h2>
            <a href="detalle.php?id=<?php echo isset($producto['id']) ? $producto['id'] : 0; ?>" class="text-decoration-none text-dark">
                <?php echo $producto['nombre']; ?>
            </a>
        </h2>

        <div class="carrito-precio">
            <?php echo $producto['precio']; ?>€
        </div>

        <div class="carrito-cantidad mt-2">

            <a href="update_carrito.php?action=restar&id=<?php echo $index; ?>" class="btn btn-sm btn-outline-secondary">-</a>

            <span style="margin:0 10px;">
                <?php echo $producto['cantidad']; ?>
            </span>

            <a href="update_carrito.php?action=sumar&id=<?php echo $index; ?>" class="btn btn-sm btn-outline-secondary">+</a>

        </div>

        <div class="mt-2 text-muted small">
            Total: <?php echo $producto['precio'] * $producto['cantidad']; ?>€
        </div>

        <div class="mt-2">
            <a href="update_carrito.php?action=eliminar&id=<?php echo $index; ?>" class="btn btn-sm btn-danger">
                Eliminar
            </a>
        </div>

    </div>

</div>

            <?php endforeach; ?>

        <?php else: ?>

            <p>Tu carrito está vacío</p>

        <?php endif; ?>

        </div>

        <!-- DERECHA -->
        
        <?php if (!empty($_SESSION['carrito'])): ?>
        <form action="procesar_pago.php" method="POST" class="carrito-checkout">

            <?php if (count($direcciones) > 0): ?>
                <div style="background:var(--color-crema); border:1px solid var(--color-borde); border-radius:6px; padding:12px; margin-bottom:15px;">
                    <div style="font-weight:700; font-size:0.9rem; margin-bottom:4px;">
                        <i class="bi bi-geo-alt-fill"></i> Dirección de envío
                    </div>
                    <div id="txt-direccion" style="font-size:0.85rem; color:#555;">
                        <!-- Se rellena con JS o PHP -->
                    </div>
                    <div id="txt-destinatario" style="font-size:0.8rem; color:var(--color-tierra-oscuro); font-weight:700; margin-top:2px;"></div>
                    
                    <?php if (count($direcciones) > 1): ?>
                    <div style="margin-top:10px;">
                        <label style="font-size:0.78rem; font-weight:700; color:#888; text-transform:uppercase; letter-spacing:0.5px;">Enviar a otra dirección</label>
                        <select id="sel-direccion" class="form-select form-select-sm" style="margin-top:4px; font-size:0.85rem; border-color:var(--color-borde);" onchange="actualizarDireccion()">
                            <?php foreach ($direcciones as $dir): ?>
                            <option value="<?= $dir['ID'] ?>" 
                                    data-calle="<?= htmlspecialchars($dir['calle']) ?>" 
                                    data-cp="<?= htmlspecialchars($dir['codigo_postal']) ?>" 
                                    data-ciudad="<?= htmlspecialchars($dir['ciudad']) ?>" 
                                    data-pais="<?= htmlspecialchars($dir['pais']) ?>" 
                                    data-tel="<?= htmlspecialchars($dir['telefono'] ?? '') ?>"
                                    data-nombre="<?= htmlspecialchars($dir['nombre_destinatario'] ?? '') ?>" 
                                    data-apellidos="<?= htmlspecialchars($dir['apellidos_destinatario'] ?? '') ?>"  
                                    <?= $dir['predeterminada'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars(($dir['alias'] ? $dir['alias'] . ' — ' : '') . $dir['calle'] . ', ' . $dir['ciudad']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <input type="hidden" name="direccion_id" id="input-direccion-id" value="<?= $direccion_predeterminada['ID'] ?? $direcciones[0]['ID'] ?>">
                </div>
                
                <script>
                function actualizarDireccion() {
                    var sel = document.getElementById('sel-direccion');
                    var txt = document.getElementById('txt-direccion');
                    var dest = document.getElementById('txt-destinatario');
                    var hid = document.getElementById('input-direccion-id');
                    if (sel) {
                        var opt = sel.options[sel.selectedIndex];
                        var html = opt.getAttribute('data-calle') + '<br>' 
                                 + opt.getAttribute('data-cp') + ' ' 
                                 + opt.getAttribute('data-ciudad') + ', ' 
                                 + opt.getAttribute('data-pais');
                        var tel = opt.getAttribute('data-tel');
                        if (tel) html += '<br>Tel: ' + tel;
                        txt.innerHTML = html;
                        
                        var nom = opt.getAttribute('data-nombre');
                        var ape = opt.getAttribute('data-apellidos');
                        if (nom || ape) {
                            dest.innerHTML = '👤 ' + (nom ? nom + ' ' : '') + (ape ? ape : '');
                        } else {
                            dest.innerHTML = '';
                        }
                        
                        hid.value = opt.value;
                    }
                }
                
                (function(){
                    var sel = document.getElementById('sel-direccion');
                    var txt = document.getElementById('txt-direccion');
                    var dest = document.getElementById('txt-destinatario');
                    if (sel) {
                        actualizarDireccion();
                    } else if (txt) {
                        // Solo hay una dirección: mostrar directamente
                        <?php $dir = $direccion_predeterminada ?? ($direcciones[0] ?? null); if ($dir): ?>
                        txt.innerHTML = '<?= htmlspecialchars($dir['calle']) ?><br><?= htmlspecialchars($dir['codigo_postal']) ?> <?= htmlspecialchars($dir['ciudad']) ?>, <?= htmlspecialchars($dir['pais']) ?><?php if(!empty($dir['telefono'])): ?><br>Tel: <?= htmlspecialchars($dir['telefono']) ?><?php endif; ?>';
                        <?php if(!empty($dir['nombre_destinatario']) || !empty($dir['apellidos_destinatario'])): ?>
                        dest.innerHTML = '👤 <?= htmlspecialchars(trim(($dir['nombre_destinatario'] ?? '') . ' ' . ($dir['apellidos_destinatario'] ?? ''))) ?>';
                        <?php endif; ?>
                        <?php endif; ?>
                    }
                })();
                </script>
            <?php else: ?>
                <div style="background:#f8d7da; border:1px solid #f5c2c7; border-radius:6px; padding:12px; margin-bottom:15px; color:#842029; font-size:0.9rem;">
                    <i class="bi bi-exclamation-circle"></i> No tienes una dirección de envío guardada.
                    <a href="mi_cuenta.php#direcciones" style="color:#842029; font-weight:700; text-decoration:underline;">Añadir dirección</a>
                </div>
            <?php endif; ?>

          <?php $max_tokens_usables = min($tokens_disponibles, floor($total * 0.8)); ?>

            <!-- tokens -->
            <?php if ($tokens_disponibles > 0): ?>
            <div style="background:var(--color-crema); border:1px solid var(--color-borde); border-radius:6px; padding:12px; margin-bottom:15px;">
                <div style="font-weight:700; font-size:0.9rem; margin-bottom:6px; color:var(--color-tierra-oscuro);">
                    <i class="bi bi-coin"></i> Tokens de fidelidad
                </div>
                <div style="font-size:0.85rem; color:#555; margin-bottom:8px;">
                    Tienes <strong><?= $tokens_disponibles ?> tokens</strong> (1 token = 1 €)
                </div>
                <div class="fila" style="gap: 0; margin-bottom: 0;">
                    <input type="text" value="Usar" readonly style="flex: 0 0 auto; width: 50px; text-align: center; border-radius: 6px 0 0 6px; border-right: none; background: var(--color-blanco-roto); color: #555; cursor: default;">
                    <input type="number" id="tokens_input" name="tokens_usados" 
                           min="0" max="<?= $max_tokens_usables ?>" value="0" 
                           style="flex: 1; border-radius: 0; text-align: center;"
                           oninput="calcularTotalTokens()">
                    <input type="text" value="tokens" readonly style="flex: 0 0 auto; width: 60px; text-align: center; border-radius: 0 6px 6px 0; border-left: none; background: var(--color-blanco-roto); color: #555; cursor: default;">
                </div>
                <div id="tokens_descuento" style="font-size:0.85rem; color:#2a6840; margin-top:6px; font-weight:700; display:none;">
                    <i class="bi bi-check-circle"></i> Descuento aplicado: -<<span id="descuento_val">0</span> €
                </div>
                <div id="tokens_info" style="font-size:0.78rem; color:#888; margin-top:4px;">
                    Ganarás <strong id="tokens_ganar"><?= floor($total / 10) ?></strong> tokens con esta compra
                </div>
            </div>
            <?php endif; ?>

            <div class="carrito-metodo">
                <strong>Método de pago</strong>

                <input type="text" placeholder="Número de tarjeta" required>

                <div class="fila">
                    <input type="text" placeholder="MM/AA" required>
                    <input type="text" class="cvv" placeholder="CVV" required>
                </div>
            </div>
            <div class="descuento-container mb-4">
                <label for="codigo_descuento" class="form-label small fw-bold">
                    ¿Tienes un código de descuento?
                </label>

                <div class="contenedor-cupon">
                    <input
                        type="text"
                        id="codigo_descuento"
                        name="codigo_descuento"
                        class="form-control"
                        placeholder="Introduce tu código aquí"
                        value="<?=  htmlspecialchars($_POST['codigo_descuento'] ?? '') ?>"
                    >

                    <button 
                        class="btn-tierra"
                        type="button"
                        id="btn-aplicar"
                    >
                        APLICAR
                    </button>
                </div>

                <div id="mensaje-cupon" class="small mt-2" style="display: none;"></div>
            </div>



            <button type="submit" class="btn-tierra carrito-pagar" <?= count($direcciones) === 0 ? 'disabled' : '' ?>>
                Pagar <span id="total_final_btn"><?php echo number_format($total, 2, ',', '.'); ?></span> €
            </button>

            <div class="carrito-pagos">
                <div>PayPal</div>
                <div>GPay</div>
            </div>

            <div class="carrito-envio">
                Gastos de envío: 3,95 €
            </div>

        </form>
        <?php endif; ?>

    </div>

</div>
</main>

<?php include 'footer.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', function(){
        const btnAplicar = document.getElementById('btn-aplicar');
        const inputCupon = document.getElementById('codigo_descuento');
        const mensaje = document.getElementById('mensaje-cupon');
        const botonPagar = document.querySelector('.carrito-pagar');
        
        // Total original sin descuento
        const totalOriginal = <?= json_encode($total) ?>;
        let totalActual = totalOriginal;

        // Tokens
        const maxTokensPermitidos = <?= $max_tokens_usables ?>;
        const tokensInput = document.getElementById('tokens_input');
        const totalFinalBtn = document.getElementById('total_final_btn');
        const descuentoDiv = document.getElementById('tokens_descuento');
        const descuentoVal = document.getElementById('descuento_val');
        const tokensGanar = document.getElementById('tokens_ganar');

        function actualizarBoton(){
            botonPagar.innerHTML = 'Pagar <span id="total_final_btn">' + totalActual.toFixed(2).replace('.', ',') + '</span> €';
        }

        // Función tokens
        function calcularTotalTokens() {
            if (!tokensInput) return;
            let usados = parseInt(tokensInput.value) || 0;
            
            if (usados < 0) usados = 0;
            if (usados > maxTokensPermitidos) usados = maxTokensPermitidos;
            if (usados > totalOriginal) usados = Math.floor(totalOriginal);
            
            tokensInput.value = usados;
            
            let descuento = usados;
            let final = totalOriginal - descuento;
            if (final < 0) final = 0;
            
            let ganados = Math.floor(final / 10);
            if (tokensGanar) tokensGanar.textContent = ganados;
            
            if (usados > 0) {
                descuentoDiv.style.display = 'block';
                descuentoVal.textContent = descuento.toFixed(2).replace('.', ',');
            } else {
                descuentoDiv.style.display = 'none';
            }
            
            totalActual = final;
            actualizarBoton();
        }

        // Hacer global para el oninput
        window.calcularTotalTokens = calcularTotalTokens;

        // Cupón
        if (btnAplicar){
            btnAplicar.addEventListener('click', function(){
                const codigo = inputCupon.value.trim().toUpperCase();

                if (codigo === ''){
                    mensaje.style.display = 'block';
                    mensaje.style.color = '#dc3545';
                    mensaje.innerHTML = "Por favor, introduce un código";
                    return;
                }

                if (codigo === 'BIENVENIDA5'){
                    const descuento = totalOriginal * 0.05;
                    totalActual = totalOriginal - descuento;

                    mensaje.style.display = 'block';
                    mensaje.style.color = '#198754';
                    mensaje.innerHTML = 'Código <b>BIENVENIDA5</b> aplicado correctamente.<br>' +
                                        'Descuento: -' + descuento.toFixed(2) + '€';
                } else {
                    mensaje.style.display = 'block';
                    mensaje.style.color = '#dc3545';
                    mensaje.innerHTML = "El código introducido no es válido";
                }

                actualizarBoton();
            });
        }
    });

</script>
</body>
</html>
