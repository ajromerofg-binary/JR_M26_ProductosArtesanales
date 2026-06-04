[README.md](https://github.com/user-attachments/files/28594207/README.md)
# 🕯️ Luz de Hogar — E-commerce PHP/MySQL

> Tienda online de cerámica artesanal y velas vegetales. Proyecto integrador del Grado Superior en Desarrollo de Aplicaciones Web (DAW · MEDAC 2024–26).

---

## 📋 Descripción

**Luz de Hogar** es una aplicación web e-commerce full-stack desarrollada íntegramente en PHP con acceso a base de datos mediante PDO. Incluye panel de cliente completo, sistema de pedidos con generación de facturas en PDF, módulo de eventos, reseñas con valoración por estrellas y un panel de administración con gestión total del negocio.

---

## ✨ Funcionalidades

### 🛍️ Tienda y catálogo
- Listado de productos por categoría (cerámica, velas)
- Página de detalle con galería de imágenes y valoración media
- Sistema de reseñas con puntuación por estrellas
- Carrito de compra con actualización dinámica de cantidades

### 👤 Panel de cliente (`mi_cuenta.php`)
- Registro e inicio de sesión con gestión de sesiones PHP
- Actualización de datos personales (nombre, apellido, email, teléfono)
- Cambio de contraseña con verificación de la actual
- Gestión de múltiples direcciones de envío (alta, edición, eliminación, predeterminada)
- Historial de pedidos con detalle de estado
- Lista de favoritos

### 📦 Sistema de pedidos
- Proceso de compra: carrito → dirección → resumen → confirmación
- Estados de pedido con historial de cambios (`pedido_estado_historial`)
- Generación de factura en **PDF** descargable (dompdf)
- Envío de confirmación por **email** (PHPMailer)

### 📅 Módulo de eventos
- Página pública de eventos con detalle individual
- Gestión de eventos desde el panel de administración
- Subida de imágenes para eventos

### 🔐 Seguridad y autenticación
- Contraseñas hasheadas con **bcrypt** (`password_hash` / `password_verify`)
- Compatibilidad y migración automática desde SHA-256 a bcrypt en el login
- Control de acceso por roles: `admin` / `cliente`
- Recuperación de contraseña por email con token seguro (`reset.php`)
- Sesiones con `session_unset()` + `session_destroy()` en logout

### 🛠️ Panel de administración (`admin.php`)
- Gestión de productos: alta, edición, baja, subida de imágenes múltiples
- Gestión de pedidos y cambio de estado
- Gestión de clientes
- Gestión de eventos
- Vista de reseñas

---

## 🗄️ Base de datos

Esquema relacional con **13 tablas** y claves foráneas con integridad referencial:

| Tabla | Descripción |
|---|---|
| `cliente` | Usuarios registrados |
| `administrador` | Cuenta de administración |
| `producto` | Catálogo de productos |
| `producto_imagenes` | Imágenes por producto (orden) |
| `categoria` | Categorías de producto |
| `carrito` | Carrito activo por cliente |
| `carrito_item` | Líneas del carrito |
| `pedido` | Cabecera de pedido |
| `pedido_item` | Líneas de pedido |
| `pedido_estado_historial` | Historial de cambios de estado |
| `direccion` | Direcciones de envío del cliente |
| `evento` | Eventos y talleres |
| `resena` | Reseñas y valoraciones de productos |

---

## 🧰 Stack técnico

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.2 |
| Base de datos | MariaDB 10.4 / MySQL · PDO |
| Frontend | Bootstrap 5.3 · Bootstrap Icons · CSS3 |
| Tipografía | Playfair Display · Lato (Google Fonts) |
| PDF | dompdf ^3.1 |
| Email | PHPMailer ^7.0 |
| Servidor local | XAMPP |
| Gestor de dependencias | Composer |

---

## 🚀 Instalación local

### Requisitos previos
- XAMPP (Apache + MySQL/MariaDB + PHP 8.x)
- Composer

### Pasos

**1. Clonar el repositorio**
```bash
git clone https://github.com/ajromerofg-binary/luz-de-hogar.git
```
Mover la carpeta a `C:/xampp/htdocs/` (Windows) o `/opt/lampp/htdocs/` (Linux).

**2. Instalar dependencias PHP**
```bash
cd luz-de-hogar
composer install
```

**3. Importar la base de datos**

Abrir phpMyAdmin → crear base de datos `luz_de_hogar` → importar `BD/luz_de_hogar.sql`.

**4. Configurar la conexión**

Editar `BD/conexion.php` con tus credenciales:
```php
$host = 'localhost';
$db   = 'luz_de_hogar';
$user = 'root';       // tu usuario MySQL
$pass = '';           // tu contraseña MySQL
```

**5. Acceder a la aplicación**
```
http://localhost/luz-de-hogar/
```

### Credenciales de prueba

| Rol | Email | Contraseña |
|---|---|---|
| Administrador | `admin@luzdehogar.es` | `admin` |
| Cliente | Registrarse desde el formulario | — |

---

## 📁 Estructura del proyecto

```
luz-de-hogar/
├── BD/
│   ├── conexion.php          # Conexión PDO
│   ├── RegistrarBD.php       # Registro de nuevos clientes
│   └── luz_de_hogar.sql      # Esquema y datos iniciales
├── img/                      # Imágenes estáticas y subidas
├── vendor/                   # Dependencias Composer (no incluido en repo)
├── index.php                 # Login
├── IndexRegistro.php         # Registro de usuario
├── home.php                  # Página principal
├── tienda.php                # Catálogo de productos
├── detalle.php               # Detalle de producto + reseñas
├── carrito.php               # Carrito de compra
├── add_carrito.php           # Añadir al carrito (AJAX)
├── update_carrito.php        # Actualizar cantidades
├── pedido_resumen.php        # Resumen antes de confirmar
├── procesar_pago.php         # Procesado del pedido
├── pedido_confirmado.php     # Confirmación post-compra
├── pedido_pdf.php            # Generación de factura PDF
├── envio_pedido.php          # Email de confirmación
├── mi_cuenta.php             # Panel de cliente
├── get_historial.php         # Historial de pedidos (AJAX)
├── eventos.php               # Listado de eventos
├── detalle_evento.php        # Detalle de evento
├── admin.php                 # Panel de administración
├── reset.php                 # Recuperación de contraseña
├── navbar.php                # Barra de navegación
├── footer.php                # Pie de página
├── funcionAuth.php           # Funciones de sesión y roles
├── funciones_pedido.php      # Lógica de pedidos
├── funcionEstrellas.php      # Renderizado de valoraciones
├── styles.css                # Estilos globales
├── admin.css                 # Estilos panel admin
└── composer.json             # Dependencias del proyecto
```

---

## 🔒 Consideraciones de seguridad

- Las contraseñas **nunca se almacenan en texto plano** — bcrypt con `PASSWORD_DEFAULT`
- Migración automática SHA-256 → bcrypt en el primer login post-migración
- Acceso al panel de administración protegido por rol de sesión
- Consultas parametrizadas con PDO (protección contra SQLi)
- Recuperación de contraseña mediante token de un solo uso

---

## 📄 Licencia

Proyecto académico — Grado Superior DAW · MEDAC Zaragoza · 2024–26  
Desarrollado por **Antonio José Romero Fdez-Giro**
