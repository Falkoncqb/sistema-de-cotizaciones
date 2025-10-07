<?php
session_start();
// Si el usuario no ha iniciado sesión, redirigirlo a la página de acceso
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Cliente</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            box-sizing: border-box;
        }
        .form-container {
            background-color: #ffffff;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            font-weight: 500;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 16px;
        }
        .button-container {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        .button-container button {
            flex: 1;
            padding: 15px;
            border-radius: 8px;
            border: none;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
        }
        .btn-save {
            background-color: #28a745;
            color: white;
        }
        .btn-save:hover {
            background-color: #218838;
        }
        .btn-close {
            background-color: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
        }
        .btn-close:hover {
            background-color: #e2e6ea;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1 id="form-title">Crear Nuevo Cliente</h1>
        <form id="clienteForm">
            <input type="hidden" id="form_cliente_id">
            <div class="form-group">
                <label for="form_cliente_nombre">Nombre:</label>
                <input type="text" id="form_cliente_nombre" required>
            </div>
            <div class="form-group">
                <label for="form_cliente_empresa">Empresa:</label>
                <input type="text" id="form_cliente_empresa">
            </div>
            <div class="form-group">
                <label for="form_cliente_rut">RUT:</label>
                <input type="text" id="form_cliente_rut" required>
            </div>
            <div class="form-group">
                <label for="form_cliente_direccion">Dirección:</label>
                <input type="text" id="form_cliente_direccion">
            </div>
            <div class="form-group">
                <label for="form_cliente_telefono">Teléfono:</label>
                <input type="text" id="form_cliente_telefono">
            </div>
            <div class="form-group">
                <label for="form_cliente_email">Email:</label>
                <input type="email" id="form_cliente_email">
            </div>
            <div class="button-container">
                <button type="button" class="btn-save" onclick="guardarCliente()">Guardar Cambios</button>
                <button type="button" class="btn-close" onclick="window.close()">Cerrar</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const clienteId = urlParams.get('id');
            if (clienteId) {
                document.getElementById('form-title').textContent = 'Editar Cliente';
                document.getElementById('form_cliente_id').value = clienteId;
                fetch(`api_clientes.php?accion=get_by_id&id=${clienteId}`)
                    .then(res => res.json())
                    .then(cliente => {
                        if (cliente) {
                            document.getElementById('form_cliente_nombre').value = cliente.nombre || '';
                            document.getElementById('form_cliente_empresa').value = cliente.empresa || '';
                            document.getElementById('form_cliente_rut').value = cliente.rut || '';
                            document.getElementById('form_cliente_direccion').value = cliente.direccion || '';
                            document.getElementById('form_cliente_telefono').value = cliente.telefono || '';
                            document.getElementById('form_cliente_email').value = cliente.email || '';
                        }
                    });
            }
        });

        function guardarCliente() {
            const id = document.getElementById('form_cliente_id').value;
            const esNuevo = id === '';
            const data = {
                accion: esNuevo ? 'crear' : 'actualizar',
                id: id,
                nombre: document.getElementById('form_cliente_nombre').value,
                empresa: document.getElementById('form_cliente_empresa').value,
                rut: document.getElementById('form_cliente_rut').value,
                direccion: document.getElementById('form_cliente_direccion').value,
                telefono: document.getElementById('form_cliente_telefono').value,
                email: document.getElementById('form_cliente_email').value
            };

            if (!data.nombre || !data.rut) {
                alert('Por favor, complete al menos los campos Nombre y RUT.');
                return;
            }

            fetch('api_clientes.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    alert(response.success);
                    if (window.opener && !window.opener.closed) {
                        window.opener.cargarClientes();
                    }
                    window.close();
                } else {
                    alert('Error: ' + (response.error || 'Ocurrió un problema.'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ocurrió un error de conexión.');
            });
        }
    </script>
</body>
</html>

