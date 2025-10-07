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
// Prevenir que errores de PHP corrompan el JSON
ini_set('display_errors', 0);
error_reporting(0);

// Establecer el tipo de contenido a JSON
header('Content-Type: application/json; charset=utf-8');

require 'conexion.php';

$accion = $_GET['accion'] ?? (json_decode(file_get_contents('php://input'), true)['accion'] ?? null);

$response = [];

try {
    switch ($accion) {
        case 'get_all':
            $sql = "SELECT cot.id, cot.cotizacion_no, cot.fecha_cotizacion, cot.monto_total, cli.nombre as nombre_cliente 
                    FROM cotizaciones cot 
                    LEFT JOIN clientes cli ON cot.cliente_id = cli.id 
                    ORDER BY cot.fecha_cotizacion DESC, cot.id DESC";
            $resultado = $conn->query($sql);
            if (!$resultado) {
                throw new Exception("Error en la consulta de cotizaciones: " . $conn->error);
            }
            $response = $resultado->fetch_all(MYSQLI_ASSOC);
            break;

        case 'get_by_id':
            $id = intval($_GET['id'] ?? 0);
            $stmt = $conn->prepare("SELECT * FROM cotizaciones WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $cotizacion = $result->fetch_assoc();
            // Decodificar el JSON de los ítems antes de enviarlo
            if ($cotizacion && isset($cotizacion['descripcion_servicio'])) {
                $cotizacion['items'] = json_decode($cotizacion['descripcion_servicio'], true);
            }
            $response = $cotizacion;
            break;

        case 'check_nro':
            $nro = $_GET['nro'] ?? '';
            $id_actual = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $stmt = $conn->prepare("SELECT id FROM cotizaciones WHERE cotizacion_no = ? AND id != ?");
            $stmt->bind_param("si", $nro, $id_actual);
            $stmt->execute();
            $response['exists'] = $stmt->get_result()->num_rows > 0;
            break;
        
        case 'buscar':
            $term = '%' . ($_GET['term'] ?? '') . '%';
            $sql = "SELECT cot.id, cot.cotizacion_no, cli.nombre as nombre_cliente 
                    FROM cotizaciones cot 
                    LEFT JOIN clientes cli ON cot.cliente_id = cli.id 
                    WHERE cot.cotizacion_no LIKE ? OR cli.nombre LIKE ?
                    ORDER BY cot.id DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $term, $term);
            $stmt->execute();
            $response = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            break;

        case 'borrar':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = intval($data['id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM cotizaciones WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $response['success'] = 'Cotización eliminada exitosamente.';
            break;
        
        case 'update':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = intval($data['id'] ?? 0);
            $monto_total = 0;
            if (isset($data['items']) && is_array($data['items'])) {
                 foreach ($data['items'] as $group) {
                    if (isset($group['subitems']) && is_array($group['subitems'])) {
                        foreach ($group['subitems'] as $subitem) {
                            $cant = floatval(str_replace(',', '.', $subitem['cantidad'] ?? '0'));
                            $precio = floatval(str_replace(',', '.', $subitem['precio_unitario'] ?? '0'));
                            $monto_total += $cant * $precio;
                        }
                    }
                }
            }

            $stmt = $conn->prepare("UPDATE cotizaciones SET 
                cliente_id = ?, cotizacion_no = ?, fecha_cotizacion = ?, referencia = ?, 
                descripcion_servicio = ?, monto_total = ?, aceptacion = ?, garantia = ?, 
                forma_pago = ?, lugar_entrega = ? 
                WHERE id = ?");
            $descripcion_json = json_encode($data['items']);
            $stmt->bind_param("issssdssssi",
                $data['cliente_id'], $data['cotizacion_no'], $data['fecha'], $data['referencia'],
                $descripcion_json, $monto_total, $data['aceptacion'], $data['garantia'],
                $data['forma_pago'], $data['lugar_entrega'], $id
            );
            $stmt->execute();
            $response['success'] = 'Cotización actualizada exitosamente.';
            break;

        default:
            throw new Exception("Acción no válida.");
    }
} catch (Exception $e) {
    // Capturar cualquier excepción y devolver un error JSON
    http_response_code(400); // Bad Request
    $response = ['error' => $e->getMessage()];
}

$conn->close();

// Enviar la respuesta final
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>

