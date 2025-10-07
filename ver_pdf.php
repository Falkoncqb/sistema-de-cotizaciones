<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Para las APIs, detenemos la ejecución y enviamos un error.
    if (strpos($_SERVER['REQUEST_URI'], 'api_') !== false) {
         header('Content-Type: application/json; charset=utf-8');
         http_response_code(403); // Forbidden
         echo json_encode(['error' => 'Acceso no autorizado.']);
    } else {
        // Para otras páginas, redirigimos al login.
        header("location: login.php");
    }
    exit;
}
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'conexion.php';
require 'fpdf/fpdf.php';

// Obtener el ID de la cotización desde la URL
$id_cotizacion = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_cotizacion <= 0) {
    die('Error: ID de cotización no válido.');
}

// Consulta para obtener los datos de la cotización y del cliente asociado
$sql = "SELECT cot.*, 
               cli.nombre AS cliente_nombre, 
               cli.empresa AS cliente_empresa, 
               cli.rut AS cliente_rut, 
               cli.direccion AS cliente_direccion, 
               cli.telefono AS cliente_telefono, 
               cli.email AS cliente_email
        FROM cotizaciones cot
        LEFT JOIN clientes cli ON cot.cliente_id = cli.id
        WHERE cot.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_cotizacion);
$stmt->execute();
$resultado = $stmt->get_result();
if ($resultado->num_rows > 0) {
    $datos = $resultado->fetch_assoc();
} else {
    die('Error: No se encontró la cotización.');
}
$stmt->close();

// --- INICIO DE LA GENERACIÓN DEL PDF ---

class PDF extends FPDF {
    // Pie de página
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10, mb_convert_encoding('Página ', 'ISO-8859-1', 'UTF-8').$this->PageNo().'/{nb}',0,0,'C');
    }
}

$pdf = new PDF('P','mm','Letter');
$pdf->SetMargins(10, 10, 10);
$pdf->AliasNbPages();
$pdf->AddPage();

// Cabecera, Emisor y Datos de Cotización
$pdf->Image('logomb.jpg', 10, 10, 60);
$pdf->Ln(20);
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, mb_convert_encoding('OFERTA ECONÓMICA', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(98, 5, mb_convert_encoding('DATOS DEL EMISOR', 'ISO-8859-1', 'UTF-8'), 'B', 0, 'L');
$pdf->Cell(98, 5, mb_convert_encoding('DATOS DE LA COTIZACIÓN', 'ISO-8859-1', 'UTF-8'), 'B', 1, 'L');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(98, 5, mb_convert_encoding('Empresa: M&B Soluciones SpA', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
$pdf->Cell(98, 5, mb_convert_encoding('Nº Cotización: ' . $datos['cotizacion_no'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->Cell(98, 5, mb_convert_encoding('RUT: 77.858.422-0', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
$fecha_formateada = date("d/m/Y", strtotime($datos['fecha_cotizacion']));
$pdf->Cell(98, 5, mb_convert_encoding('Fecha: ' . $fecha_formateada, 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->Cell(98, 5, '', 0, 0, 'L'); 
$pdf->Cell(98, 5, mb_convert_encoding('Referencia: ' . $datos['referencia'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->Ln(10);

// Datos del Cliente
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 5, mb_convert_encoding('DATOS DEL CLIENTE', 'ISO-8859-1', 'UTF-8'), 'B', 1, 'L');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(98, 5, mb_convert_encoding('Nombre: ' . $datos['cliente_nombre'], 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
$pdf->Cell(98, 5, mb_convert_encoding('Dirección: ' . $datos['cliente_direccion'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->Cell(98, 5, mb_convert_encoding('Empresa: ' . $datos['cliente_empresa'], 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
$pdf->Cell(98, 5, mb_convert_encoding('Teléfono: ' . $datos['cliente_telefono'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->Cell(98, 5, mb_convert_encoding('RUT: ' . $datos['cliente_rut'], 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
$pdf->Cell(98, 5, mb_convert_encoding('Email: ' . $datos['cliente_email'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->Ln(10);

// Tabla de Ítems
$items_para_pdf = json_decode($datos['descripcion_servicio'], true);
if (json_last_error() === JSON_ERROR_NONE && !empty($items_para_pdf)) {
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
                $cant = floatval($subitem['cantidad'] ?? '0');
                $precio = floatval($subitem['precio_unitario'] ?? '0');
                $total_item = $cant * $precio;
                $pdf->Cell(15, 7, ($group_index + 1) . '.' . ($subitem_index + 1), 1, 0, 'C');
                $pdf->Cell(80, 7, mb_convert_encoding($subitem['descripcion'], 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
                $pdf->Cell(25, 7, $cant, 1, 0, 'R');
                $pdf->Cell(38, 7, '$' . number_format($precio, 0, ',', '.'), 1, 0, 'R');
                $pdf->Cell(38, 7, '$' . number_format($total_item, 0, ',', '.'), 1, 1, 'R');
            }
        }
        $pdf->Ln(5);
    }
}

// Totales
$monto_total = floatval($datos['monto_total']);
$iva = $monto_total * 0.19;
$total_final = $monto_total + $iva;
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(158, 7, 'Neto', 1, 0, 'R');
$pdf->Cell(38, 7, '$' . number_format($monto_total, 0, ',', '.'), 1, 1, 'R');
$pdf->Cell(158, 7, 'IVA (19%)', 1, 0, 'R');
$pdf->Cell(38, 7, '$' . number_format($iva, 0, ',', '.'), 1, 1, 'R');
$pdf->Cell(158, 7, 'Total', 1, 0, 'R');
$pdf->Cell(38, 7, '$' . number_format($total_final, 0, ',', '.'), 1, 1, 'R');
$pdf->Ln(5);

// Condiciones Comerciales
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(0, 5, 'CONDICIONES COMERCIALES', 'T', 1, 'L');
$pdf->Ln(2);

$fields = [
    'Aceptación de Cotización' => $datos['aceptacion'],
    'Garantía' => $datos['garantia'],
    'Forma de Pago' => $datos['forma_pago'],
    'Lugar de Entrega' => $datos['lugar_entrega']
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

// Antecedentes de Transferencia
$pdf->SetFont('Arial', 'B', 9);
$pdf->MultiCell(0, 5, mb_convert_encoding("Antecedentes para transferencia bancaria:\n1. Empresa: M&B Soluciones SpA\n2. RUT: 77.858.422-0\n3. Dirección: Roberto Lorca Olguín 180, El Bosque, Santiago de Chile\n4. Contacto: Ramón Méndez Román, C.I. 9.023466-8\n5. Nro. Celular: Móvil (5699 3241 4560 ó (569) 6595 787\n6. Giro: Rep. de otro tipo de Maq. y Eq. Ind. N.C.P.\n7. Correo: rmendez@solucionesmb.cl o ventas@solucionesmb.cl", 'ISO-8859-1', 'UTF-8'), 'T');

// Salida del PDF
$pdf->Output('I', 'Cotizacion-'.$datos['cotizacion_no'].'.pdf');
$conn->close();
?>

