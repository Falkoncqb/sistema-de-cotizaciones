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

// Determinar la acción a realizar
$accion = $_GET['accion'] ?? (json_decode(file_get_contents('php://input'), true)['accion'] ?? null);

$response = [];

try {
    switch ($accion) {
        case 'get_all':
            $resultado = $conn->query("SELECT * FROM clientes ORDER BY nombre ASC");
            $response = $resultado->fetch_all(MYSQLI_ASSOC);
            break;

        case 'get_by_id':
            $id = intval($_GET['id'] ?? 0);
            $stmt = $conn->prepare("SELECT * FROM clientes WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $response = $stmt->get_result()->fetch_assoc();
            break;

        case 'buscar':
            $term = '%' . ($_GET['term'] ?? '') . '%';
            $stmt = $conn->prepare("SELECT * FROM clientes WHERE nombre LIKE ? OR rut LIKE ?");
            $stmt->bind_param("ss", $term, $term);
            $stmt->execute();
            $response = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            break;

        case 'crear':
        case 'actualizar':
            $data = json_decode(file_get_contents('php://input'), true);
            $rut = $data['rut'] ?? '';
            $id = intval($data['id'] ?? 0);

            $stmt_check = $conn->prepare("SELECT id FROM clientes WHERE rut = ? AND id != ?");
            $stmt_check->bind_param("si", $rut, $id);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                throw new Exception("Ya existe un cliente con el mismo RUT.");
            }

            if ($accion === 'crear') {
                $stmt = $conn->prepare("INSERT INTO clientes (nombre, empresa, rut, direccion, telefono, email) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $data['nombre'], $data['empresa'], $data['rut'], $data['direccion'], $data['telefono'], $data['email']);
                $stmt->execute();
                $response['success'] = 'Cliente creado exitosamente.';
            } else {
                $stmt = $conn->prepare("UPDATE clientes SET nombre=?, empresa=?, rut=?, direccion=?, telefono=?, email=? WHERE id=?");
                $stmt->bind_param("ssssssi", $data['nombre'], $data['empresa'], $data['rut'], $data['direccion'], $data['telefono'], $data['email'], $id);
                $stmt->execute();
                $response['success'] = 'Cliente actualizado exitosamente.';
            }
            break;

        case 'borrar':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = intval($data['id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM clientes WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $response['success'] = 'Cliente eliminado exitosamente.';
            break;

        default:
            throw new Exception("Acción no válida.");
    }
} catch (Exception $e) {
    $response = ['error' => $e->getMessage()];
}

$conn->close();

// Enviar la respuesta final
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>

