<?php
// ------------------- BLOQUE DE SEGURIDAD -------------------
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Para páginas que generan contenido como PDFs, redirigimos al login.
    header("location: login.php");
    exit;
}
// ----------------- FIN BLOQUE DE SEGURIDAD -----------------

ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'conexion.php';
require 'fpdf/fpdf.php';

// --- FUNCIÓN PARA MOSTRAR PÁGINA DE ERROR MODERNA ---
function mostrarError($titulo, $mensaje) {
    $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error en Cotización</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: #333;
        }
        .error-container {
            text-align: center;
            background-color: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 90%;
        }
        .error-icon {
            font-size: 50px;
            color: #dc3545; /* Rojo de error */
            margin-bottom: 20px;
        }
        h1 {
            font-size: 24px;
            margin-bottom: 15px;
            color: #333;
        }
        p {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 25px;
            color: #555;
        }
        strong {
            color: #000;
            font-weight: 500;
        }
        .back-button {
            display: inline-block;
            text-decoration: none;
            padding: 12px 25px;
            background-color: #007bff;
            color: white;
            border-radius: 8px;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }
        .back-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">&#9888;</div> <!-- Símbolo de advertencia -->
        <h1>{$titulo}</h1>
        <p>{$mensaje}</p>
        <a href="panel.php" class="back-button">Volver al Panel</a>
    </div>
</body>
</html>
HTML;
    die($html);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // --- NUEVA VERIFICACIÓN DE NÚMERO DE COTIZACIÓN DUPLICADO ---
    $cotizacion_no_a_verificar = $_POST['cotizacion_no'] ?? '';
    if (empty($cotizacion_no_a_verificar)) {
        mostrarError(
            "Error: Falta el Número de Cotización",
            "El campo 'Nº Cotización' no puede estar vacío."
        );
    }

    $stmt_check = $conn->prepare("SELECT id FROM cotizaciones WHERE cotizacion_no = ?");
    $stmt_check->bind_param("s", $cotizacion_no_a_verificar);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        // Si el número ya existe, detenemos la ejecución y mostramos un error amigable.
        $stmt_check->close();
        $conn->close();
        $mensaje_error = "El número de cotización '<strong>" . htmlspecialchars($cotizacion_no_a_verificar) . "</strong>' ya existe en la base de datos.<br>Por favor, vuelva atrás e ingrese un número único.";
        mostrarError("Error: Número de Cotización Duplicado", $mensaje_error);
    }
    $stmt_check->close();
    // --- FIN DE LA VERIFICACIÓN ---


    // --- LÓGICA DE CLIENTE ---
    $cliente_id = !empty($_POST['cliente_id']) ? intval($_POST['cliente_id']) : 0;
    $cliente_nombre = $_POST['cliente_nombre'] ?? '';
    $cliente_rut = $_POST['cliente_rut'] ?? '';
    if (empty($cliente_id) && !empty($cliente_rut)) {
        $stmt_find = $conn->prepare("SELECT id FROM clientes WHERE rut = ?");
        $stmt_find->bind_param("s", $cliente_rut);
        $stmt_find->execute();
        $result_find = $stmt_find->get_result();
        if ($result_find->num_rows > 0) {
            $cliente_existente = $result_find->fetch_assoc();
            $cliente_id = $cliente_existente['id'];
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO clientes (nombre, empresa, rut, direccion, telefono, email) VALUES (?, ?, ?, ?, ?, ?)");
            
            $cliente_empresa = $_POST['cliente_empresa'] ?? '';
            $cliente_direccion = $_POST['cliente_direccion'] ?? '';
            $cliente_telefono = $_POST['cliente_telefono'] ?? '';
            $cliente_email = $_POST['cliente_email'] ?? '';
            
            $stmt_insert->bind_param("ssssss", $cliente_nombre, $cliente_empresa, $cliente_rut, $cliente_direccion, $cliente_telefono, $cliente_email);
            $stmt_insert->execute();
            $cliente_id = $conn->insert_id;
            $stmt_insert->close();
        }
        $stmt_find->close();
    }

    // --- LÓGICA DE ITEMS ---
    $items_data = $_POST['items'] ?? [];
    $descripcion_servicio_json = json_encode($items_data);
    $monto_total = 0;
    if (!empty($items_data)) {
        foreach ($items_data as $group) {
            if (isset($group['subitems']) && is_array($group['subitems'])) {
                foreach ($group['subitems'] as $subitem) {
                    $cant = floatval(str_replace('.', '', $subitem['cantidad'] ?? '0'));
                    $precio_str = str_replace('.', '', $subitem['precio_unitario'] ?? '0');
                    $precio = floatval(str_replace(',', '.', $precio_str));
                    $monto_total += $cant * $precio;
                }
            }
        }
    }

    // --- CAMPOS DE CONDICIONES ---
    $aceptacion = $_POST['aceptacion'] ?? '';
    $garantia = $_POST['garantia'] ?? '';
    $forma_pago = $_POST['forma_pago'] ?? '';
    $lugar_entrega = $_POST['lugar_entrega'] ?? '';

    // --- GUARDAR COTIZACIÓN ---
    $stmt_cot = $conn->prepare("INSERT INTO cotizaciones (cliente_id, cotizacion_no, fecha_cotizacion, referencia, descripcion_servicio, monto_total, aceptacion, garantia, forma_pago, lugar_entrega) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_cot->bind_param("issssdssss", 
        $cliente_id, $_POST['cotizacion_no'], $_POST['fecha'], $_POST['referencia'], 
        $descripcion_servicio_json, $monto_total,
        $aceptacion, $garantia, $forma_pago, $lugar_entrega
    );
    if (!$stmt_cot->execute()) {
        die("Error al guardar la cotización: " . $stmt_cot->error);
    }
    $id_nueva_cotizacion = $conn->insert_id;
    $stmt_cot->close();

    // --- GENERACIÓN DEL PDF ---
    // (El resto del código para generar el PDF permanece sin cambios)
    class PDF extends FPDF {
        function Footer() {
            $this->SetY(-15); $this->SetFont('Arial','I',8);
            $this->Cell(0,10, mb_convert_encoding('Página ', 'ISO-8859-1', 'UTF-8').$this->PageNo().'/{nb}',0,0,'C');
        }
    }
    
    $pdf = new PDF('P','mm','Letter');
    $pdf->SetMargins(10, 10, 10);
    $pdf->AliasNbPages();
    $pdf->AddPage();

    // Cabecera, Emisor, Cliente, Ítems
    $pdf->Image('logomb.jpg', 10, 10, 60); $pdf->Ln(20);
    $pdf->SetFont('Arial', 'B', 16); $pdf->Cell(0, 10, mb_convert_encoding('OFERTA ECONÓMICA', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C'); $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 10); $pdf->Cell(98, 5, mb_convert_encoding('DATOS DEL EMISOR', 'ISO-8859-1', 'UTF-8'), 'B', 0, 'L'); $pdf->Cell(98, 5, mb_convert_encoding('DATOS DE LA COTIZACIÓN', 'ISO-8859-1', 'UTF-8'), 'B', 1, 'L');
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(98, 5, mb_convert_encoding('Empresa: M&B Soluciones SpA', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
    $pdf->Cell(98, 5, mb_convert_encoding('Nº Cotización: ' . $_POST['cotizacion_no'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
    $pdf->Cell(98, 5, mb_convert_encoding('RUT: 77.858.422-0', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
    $fecha_formateada = date("d/m/Y", strtotime($_POST['fecha']));
    $pdf->Cell(98, 5, mb_convert_encoding('Fecha: ' . $fecha_formateada, 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
    $pdf->Cell(98, 5, mb_convert_encoding('Cotizado por: ' . ($_POST['emisor_cotizado_por'] ?? ''), 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
    $pdf->Cell(98, 5, mb_convert_encoding('Referencia: ' . $_POST['referencia'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L'); $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 10); $pdf->Cell(0, 5, mb_convert_encoding('DATOS DEL CLIENTE', 'ISO-8859-1', 'UTF-8'), 'B', 1, 'L');
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(98, 5, mb_convert_encoding('Nombre: ' . $_POST['cliente_nombre'], 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
    $pdf->Cell(98, 5, mb_convert_encoding('Dirección: ' . $_POST['cliente_direccion'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
    $pdf->Cell(98, 5, mb_convert_encoding('Empresa: ' . $_POST['cliente_empresa'], 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
    $pdf->Cell(98, 5, mb_convert_encoding('Teléfono: ' . $_POST['cliente_telefono'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
    $pdf->Cell(98, 5, mb_convert_encoding('RUT: ' . $_POST['cliente_rut'], 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
    $pdf->Cell(98, 5, mb_convert_encoding('Email: ' . $_POST['cliente_email'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L'); $pdf->Ln(10);
    $items_para_pdf = json_decode($descripcion_servicio_json, true);
    if (!empty($items_para_pdf)) {
        foreach ($items_para_pdf as $group_index => $group) {
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetFillColor(210, 225, 255);
            $pdf->Cell(196, 7, mb_convert_encoding(($group_index + 1) . '. ' . ($group['title'] ?: 'Ítem sin título'), 'ISO-8859-1', 'UTF-8'), 1, 1, 'L', true);
            if (isset($group['subitems']) && is_array($group['subitems'])) {
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->SetFillColor(230, 230, 230);
                $pdf->Cell(15, 7, '#', 1, 0, 'C', true);
                $pdf->Cell(80, 7, mb_convert_encoding('Descripción', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                $pdf->Cell(25, 7, 'Cantidad', 1, 0, 'C', true);
                $pdf->Cell(38, 7, 'P. Unitario', 1, 0, 'C', true);
                $pdf->Cell(38, 7, 'P. Total', 1, 1, 'C', true);
                $pdf->SetFont('Arial', '', 9);
                foreach ($group['subitems'] as $subitem_index => $subitem) {
                    $cant_pdf = floatval(str_replace('.', '', $subitem['cantidad'] ?? '0'));
                    $precio_str_pdf = str_replace('.', '', $subitem['precio_unitario'] ?? '0');
                    $precio_pdf = floatval(str_replace(',', '.', $precio_str_pdf));
                    $total_item_pdf = $cant_pdf * $precio_pdf;
                    $pdf->Cell(15, 7, ($group_index + 1) . '.' . ($subitem_index + 1), 1, 0, 'C');
                    $pdf->Cell(80, 7, mb_convert_encoding($subitem['descripcion'], 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
                    $pdf->Cell(25, 7, $cant_pdf, 1, 0, 'R');
                    $pdf->Cell(38, 7, '$' . number_format($precio_pdf, 0, ',', '.'), 1, 0, 'R');
                    $pdf->Cell(38, 7, '$' . number_format($total_item_pdf, 0, ',', '.'), 1, 1, 'R');
                }
            }
            $pdf->Ln(5);
        }
    }
    
    // Totales
    $iva = $monto_total * 0.19; $total_final = $monto_total + $iva;
    $pdf->SetFont('Arial', 'B', 10); $pdf->Cell(158, 7, 'Neto', 1, 0, 'R'); $pdf->Cell(38, 7, '$' . number_format($monto_total, 0, ',', '.'), 1, 1, 'R');
    $pdf->Cell(158, 7, 'IVA (19%)', 1, 0, 'R'); $pdf->Cell(38, 7, '$' . number_format($iva, 0, ',', '.'), 1, 1, 'R');
    $pdf->Cell(158, 7, 'Total', 1, 0, 'R'); $pdf->Cell(38, 7, '$' . number_format($total_final, 0, ',', '.'), 1, 1, 'R');
    $pdf->Ln(5);
    
    // --- SECCIÓN DE CONDICIONES EN PDF ---
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(0, 5, 'CONDICIONES COMERCIALES', 'T', 1, 'L');
    $pdf->Ln(2);

    $fields = [
        'Aceptación de Cotización' => $aceptacion,
        'Garantía' => $garantia,
        'Forma de Pago' => $forma_pago,
        'Lugar de Entrega' => $lugar_entrega
    ];

    foreach ($fields as $label => $value) {
        if (!empty($value)) {
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(45, 5, mb_convert_encoding($label . ':', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
            $pdf->SetFont('Arial', '', 9);
            $pdf->MultiCell(0, 5, mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8'), 0, 'L');
            $pdf->Ln(1);
        }
    }
    $pdf->Ln(5);

    // Antecedentes de Transferencia (se mantiene por defecto)
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->MultiCell(0, 5, mb_convert_encoding("Antecedentes para transferencia bancaria:\n1. Empresa: M&B Soluciones SpA\n2. RUT: 77.858.422-0\n3. Dirección: Roberto Lorca Olguín 180, El Bosque, Santiago de Chile\n4. Contacto: Ramón Méndez Román, C.I. 9.023466-8\n5. Nro. Celular: Móvil (5699 3241 4560 ó (569) 6595 787\n6. Giro: Rep. de otro tipo de Maq. y Eq. Ind. N.C.P.\n7. Correo: rmendez@solucionesmb.cl o ventas@solucionesmb.cl", 'ISO-8859-1', 'UTF-8'), 'T');

    $pdf->Output('I', 'Cotizacion-'.$_POST['cotizacion_no'].'.pdf');
    $conn->close();
} else {
    // Si alguien intenta acceder a este archivo sin enviar el formulario, lo redirigimos al panel.
    header('Location: panel.php');
    exit();
}
?>

