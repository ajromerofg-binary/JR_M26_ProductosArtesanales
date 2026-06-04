<?php
require_once("BD/conexion.php");

$pedidoId = $_GET['id'] ?? 1;

// Consulta SQL se obtienen los datos del pedido y del datos del cliente.
$sqlPedido = "
    SELECT 
        p.ID,
        p.fecha,
        p.Hora,
        p.estado,
        p.total,
        p.direccion_envio_snapshot,
        c.Nombre,
        c.Apellido,
        c.email,
        c.telefono,
        c.direccion
    FROM PEDIDO p
    INNER JOIN CLIENTE c ON p.cliente_id = c.ID
    WHERE p.ID = :pedido_id
";

$stmtPedido = $pdo->prepare($sqlPedido);

// sustituye pedido_id por el ID real del pedido.
$stmtPedido->execute(['pedido_id' => $pedidoId]);
$pedidoCompleto = $stmtPedido->fetch();

// si no se encuentra el pedido se muestra un mensaje.
if (!$pedidoCompleto) {
    die("No se encontró el pedido.");
}

// Consulta para obtener los productos incluidos en el pedido.
$sqlProductos = "
    SELECT 
        pr.nombre,
        pi.cantidad,
        pi.precio_unitario
    FROM PEDIDO_ITEM pi
    INNER JOIN PRODUCTO pr ON pi.producto_id = pr.ID
    WHERE pi.pedido_id = :pedido_id
";


$stmtProductos = $pdo->prepare($sqlProductos);
$stmtProductos->execute(['pedido_id' => $pedidoId]);

// guarda todos los productos del pedido en un array.
$productos = $stmtProductos->fetchAll();

// Función auxiliar para mostrar cantidades en euros.
function euros($cantidad) {
    return number_format((float)$cantidad, 2, ',', '.') . ' €';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resumen de pedido - Luz De Hogar</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">

  <style>

    .pedido-wrapper {
      max-width: 1000px;
      margin: 40px auto;
      background-color: var(--color-blanco-roto);
      border: 1px solid var(--color-borde);
      border-radius: 14px;
      overflow: hidden;
      box-shadow: 0 8px 20px rgba(0,0,0,0.06);
    }


    .pedido-header {
      position: relative;
      background-color: var(--color-tierra-claro);
      padding: 2rem;
      text-align: center;
    }


    .logo-pedido {
      position: absolute;
      left: 30px;
      top: 50%;
      transform: translateY(-50%);
      width: 150px;
      height: auto;
    }


    .pedido-body {
      padding: 2rem;
    }


    .seccion-titulo {
      color: var(--color-tierra-oscuro);
      border-bottom: 2px solid var(--color-tierra-claro);
      padding-bottom: 0.4rem;
      margin-bottom: 1rem;
      font-size: 1.5rem;
    }


    .info-card {
      background-color: #fffdfb;
      border: 1px solid var(--color-borde);
      border-radius: 10px;
      padding: 1rem 1.2rem;
    }


    .info-card p {
      margin-bottom: 0.5rem;
    }


    .table-pedido thead th {
      background-color: var(--color-franja-hero);
      color: var(--color-texto);
      border-color: var(--color-borde);
    }


    .table-pedido td,
    .table-pedido th {
      vertical-align: middle;
      border-color: var(--color-borde);
    }


    .resumen-total {
      background-color: var(--color-crema);
      border: 1px solid var(--color-borde);
      border-radius: 10px;
      padding: 1rem 1.25rem;
    }


    .resumen-total .fila {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.5rem;
    }


    .resumen-total .fila.total {
      border-top: 2px solid var(--color-tierra-medio);
      padding-top: 0.8rem;
      margin-top: 0.8rem;
      margin-bottom: 0;
      font-weight: 700;
      font-size: 1.1rem;
      color: var(--color-tierra-oscuro);
    }


    .mensaje-final {
      background-color: var(--color-crema);
      border-left: 5px solid var(--color-tierra-medio);
      border-radius: 8px;
      padding: 1rem;
      text-align: center;
    }


    .pedido-footer {
      background-color: var(--color-tierra-claro);
      text-align: center;
      padding: 1.2rem;
      font-size: 0.95rem;
    }


    @media (max-width: 768px) {
      .pedido-header {
        padding-top: 5.5rem;
      }

      .logo-pedido {
        left: 50%;
        top: 20px;
        transform: translateX(-50%);
        width: 60px;
      }

      .pedido-body {
        padding: 1.25rem;
      }
    }
  </style>
</head>
<body>


<div class="container">  
  <div class="pedido-wrapper">
    <header class="pedido-header">
      <img src="img/logo.png" alt="Logo Luz De Hogar" class="logo-pedido">
      <div class="text-center">
        <h1 class="mb-1">Luz De Hogar</h1>
        <p class="mb-0">Resumen de compra</p>
      </div>
    </header>

    <main class="pedido-body">
      <section class="mb-5">
        <div class="row g-4">
          <div class="col-12 col-md-6">
            <h2 class="seccion-titulo">Datos del pedido</h2>
            <div class="info-card">
              <p><strong>Número de pedido:</strong> #<?= htmlspecialchars($pedidoCompleto['ID']) ?></p>
              <p><strong>Fecha:</strong> <?= htmlspecialchars($pedidoCompleto['fecha']) ?></p>
              <p><strong>Hora:</strong> <?= htmlspecialchars($pedidoCompleto['Hora']) ?></p>
              <p><strong>Estado:</strong> <?= htmlspecialchars($pedidoCompleto['estado']) ?></p>
            </div>
          </div>


          <div class="col-12 col-md-6">
            <h2 class="seccion-titulo">Datos del cliente</h2>
            <div class="info-card">
              <p><strong>Nombre:</strong> <?= htmlspecialchars($pedidoCompleto['Nombre'] . ' ' . $pedidoCompleto['Apellido']) ?></p>
              <p><strong>Email:</strong> <?= htmlspecialchars($pedidoCompleto['email']) ?></p>
              <p><strong>Teléfono:</strong> <?= htmlspecialchars($pedidoCompleto['telefono'] ?? '') ?></p>
              <p><strong>Dirección:</strong> <?= htmlspecialchars($pedidoCompleto['direccion_envio_snapshot'] ?? $pedidoCompleto['direccion'] ?? '') ?></p>
            </div>
          </div>
        </div>
      </section>


      <section class="mb-4">
        <h2 class="seccion-titulo">Productos</h2>
        <div class="table-responsive">
          <table class="table table-pedido align-middle">
            <thead>
              <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th class="text-end">Precio unitario</th>
                <th class="text-end">Subtotal</th>
              </tr>
            </thead>
            <tbody>

              <?php foreach ($productos as $producto): ?>
                <tr>
                  <td><?= htmlspecialchars($producto['nombre']) ?></td>
                  <td><?= (int)$producto['cantidad'] ?></td>
                  <td class="text-end"><?= euros($producto['precio_unitario']) ?></td>
                  <td class="text-end"><?= euros($producto['cantidad'] * $producto['precio_unitario']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>


      <section class="mb-4">
        <div class="row justify-content-end">
          <div class="col-12 col-md-6 col-lg-4">
            <div class="resumen-total">
              <div class="fila total">
                <span>Total</span> 
                <span><?= euros($pedidoCompleto['total']) ?></span>
              </div>
            </div>
          </div>
        </div>
      </section>


      <section>
        <div class="mensaje-final">
          Gracias por tu compra. Hemos recibido tu pedido correctamente y comenzaremos a prepararlo lo antes posible.
        </div>
      </section>

    </main>

    <footer class="pedido-footer">
      <strong>Luz De Hogar</strong><br>
      © 2026 Luz de Hogar - Hecho a mano en España - infoluzdehogar@gmail.com
    </footer>

  </div>
</div>

</body>
</html>