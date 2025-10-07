# sistema-de-cotizaciones
sistema de cotizaciones que permite ingresar servicios o productos y genera un archivo pdf descargable con historial de clientes y numero de cotizaciones  
Este es un sistema web simple pero robusto desarrollado en PHP y MySQL para la creación, gestión y generación de cotizaciones en formato PDF. La aplicación permite administrar clientes y llevar un historial de todas las cotizaciones emitidas.

## ✨ Características Principales

* **Autenticación de Usuarios**: Sistema de inicio de sesión para proteger el acceso al panel.
* **Gestión de Clientes (CRUD)**:
    * Crear, leer, actualizar y eliminar clientes.
    * Formulario dedicado para la edición y creación de clientes en una ventana separada.
* **Gestión de Cotizaciones (CRUD)**:
    * Crear cotizaciones dinámicas con múltiples ítems y sub-ítems.
    * Modificar cotizaciones existentes.
    * Eliminar cotizaciones del historial.
* **Cálculos Automáticos**: El sistema calcula automáticamente los montos netos, el IVA (19%) y el total final en tiempo real.
* **Generación de PDF**: Crea un documento PDF profesional de la cotización con un solo clic, listo para ser enviado al cliente.
* **Búsqueda Integrada**: Permite buscar rápidamente clientes (por nombre o RUT) y cotizaciones (por número o cliente) en el historial.
* **Interfaz Intuitiva**: Un panel de control de una sola página que facilita la creación de cotizaciones y la gestión de datos.

## 💻 Pila Tecnológica

* **Backend**: PHP
* **Frontend**: HTML, CSS, JavaScript (Vanilla)
* **Base de Datos**: MySQL / MariaDB
* **Generación de PDF**: Biblioteca [FPDF](http://www.fpdf.org/)

## 🚀 Instalación y Puesta en Marcha

Sigue estos pasos para configurar el proyecto en un entorno de desarrollo local (usando XAMPP, WAMP, MAMP, etc.).

### 1. Prerrequisitos

* Un servidor web local con soporte para PHP (se recomienda PHP 7.4 o superior).
* Un servidor de base de datos MySQL o MariaDB.
* La biblioteca **FPDF**.

### 2. Configuración de la Base de Datos

1.  Abre tu gestor de base de datos (como phpMyAdmin).
2.  Crea una nueva base de datos llamada `sistema_cotizaciones`.
3.  Selecciona la base de datos recién creada y ejecuta el siguiente script SQL para crear las tablas necesarias:

    ```sql
    CREATE TABLE `clientes` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `nombre` varchar(255) NOT NULL,
      `empresa` varchar(255) DEFAULT NULL,
      `rut` varchar(50) NOT NULL,
      `direccion` varchar(255) DEFAULT NULL,
      `telefono` varchar(50) DEFAULT NULL,
      `email` varchar(100) DEFAULT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `rut` (`rut`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    CREATE TABLE `cotizaciones` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `cliente_id` int(11) NOT NULL,
      `cotizacion_no` varchar(100) DEFAULT NULL,
      `fecha_cotizacion` date NOT NULL,
      `referencia` varchar(255) DEFAULT NULL,
      `descripcion_servicio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
      `monto_total` decimal(15,2) DEFAULT NULL,
      `aceptacion` text DEFAULT NULL,
      `garantia` text DEFAULT NULL,
      `forma_pago` text DEFAULT NULL,
      `lugar_entrega` text DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `cliente_id` (`cliente_id`),
      CONSTRAINT `cotizaciones_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ```

### 3. Instalación de Archivos y Dependencias

1.  Clona este repositorio o copia todos los archivos del proyecto en el directorio raíz de tu servidor web (ej. `C:/xampp/htdocs/cotizador`).
2.  Descarga la biblioteca **FPDF** desde su [sitio web oficial](http://www.fpdf.org/en/download.php).
3.  Descomprime el archivo y copia el contenido en una carpeta llamada `fpdf` dentro del directorio principal de tu proyecto. La estructura de archivos debería verse así:
    ```
    /cotizador
    |-- /fpdf
    |   |-- fpdf.php
    |   |-- ... (otros archivos de la biblioteca)
    |-- api_clientes.php
    |-- api_cotizaciones.php
    |-- panel.php
    |-- ... (resto de los archivos)
    ```

### 4. Configuración de la Conexión

1.  Abre el archivo `conexion.php`.
2.  Verifica que los datos de acceso (`$servername`, `$username`, `$password`, `$dbname`) coincidan con la configuración de tu base de datos local. Por defecto, está configurado para un entorno XAMPP estándar.

    ```php
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "sistema_cotizaciones";
    ```

### 5. Ejecutar la Aplicación

1.  Inicia tu servidor web Apache y MySQL.
2.  Abre tu navegador y ve a la URL donde alojaste el proyecto. Si no creaste una carpeta específica, será `http://localhost/login.php` o `http://localhost/cotizador/login.php`.
3.  Utiliza las siguientes credenciales para iniciar sesión:
    * **Usuario**: `xxxxxxxxxxx`
    * **Contraseña**: `xxxxxxxxxxx`

## ⚠️ Consideraciones de Seguridad

Este proyecto fue diseñado con un propósito funcional y de aprendizaje. **No se recomienda para un entorno de producción sin realizar mejoras de seguridad**, especialmente en los siguientes puntos:

* **Credenciales Hardcodeadas**: Las credenciales de inicio de sesión se encuentran directamente en el archivo `verificar_login.php`. En un entorno real, estas deberían ser almacenadas en la base de datos de forma segura (usando hashes).
* **Inyección SQL**: Aunque se utilizan sentencias preparadas en la mayoría de las consultas, es crucial revisar todo el código para prevenir vulnerabilidades de inyección SQL.

## 📁 Estructura del Proyecto

* `login.php`: Página de inicio de sesión (no proporcionada, pero necesaria).
* `verificar_login.php`: Procesa los datos del formulario de login y gestiona la sesión.
* `panel.php`: Panel principal para crear y visualizar cotizaciones y clientes.
* `cliente_form.php`: Formulario para crear y editar clientes.
* `api_clientes.php`: API para todas las operaciones relacionadas con los clientes.
* `api_cotizaciones.php`: API para las operaciones de las cotizaciones.
* `ver_pdf.php`: Script que genera el documento PDF de una cotización.
* `generar_cotizacion.php`: Script que recibe los datos del formulario para crear la cotización y el cliente si es nuevo.
* `conexion.php`: Maneja la conexión con la base de datos.
* `logout.php`: Cierra la sesión del usuario.
