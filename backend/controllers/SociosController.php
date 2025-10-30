<?php
require_once __DIR__ . '/../models/Socio.php';
require_once __DIR__ . '/../php/config.php';

class SociosController {
    private $socioModel;
    
    public function __construct() {
        $this->socioModel = new Socio();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        
        switch ($method) {
            case 'GET':
                $this->getSocios();
                break;
            case 'POST':
                $this->createSocio();
                break;
            case 'PUT':
                $this->updateSocio();
                break;
            case 'DELETE':
                $this->deleteSocio();
                break;
            default:
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        }
    }
    
    private function getSocios() {
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $id = isset($_GET['id_socio']) ? (int)$_GET['id_socio'] : null;
            
            if ($id) {
                // Obtener un socio específico
                $socio = $this->socioModel->getById($id);
                if ($socio) {
                    echo json_encode(['success' => true, 'socio' => $socio]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Socio no encontrado']);
                }
            } else {
                // Obtener lista de socios con paginación
                $result = $this->socioModel->getAll($page, $limit, $search);
                echo json_encode($result);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
        }
    }
    
    private function createSocio() {
        try {
            $data = [
                'nombre' => $_POST['nombre'] ?? '',
                'cedula' => $_POST['cedula'] ?? '',
                'telefono' => $_POST['telefono'] ?? '',
                'direccion' => $_POST['direccion'] ?? '',
                'email' => $_POST['email'] ?? '',
                'fecha_ingreso' => $_POST['fecha_ingreso'] ?? date('Y-m-d'),
                'estado' => $_POST['estado'] ?? 'activo',
                'aportes_totales' => !empty($_POST['aportes_totales']) ? (float)$_POST['aportes_totales'] : 0,
                'deudas_pendientes' => !empty($_POST['deudas_pendientes']) ? (float)$_POST['deudas_pendientes'] : 0,
                'observaciones' => $_POST['observaciones'] ?? ''
            ];
            
            $result = $this->socioModel->create($data);
            
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
        }
    }
    
    private function updateSocio() {
        try {
            parse_str(file_get_contents("php://input"), $data);
            
            $id = $data['id_socio'] ?? null;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID del socio requerido']);
                return;
            }
            
            $updateData = [
                'nombre' => $data['nombre'] ?? '',
                'cedula' => $data['cedula'] ?? '',
                'telefono' => $data['telefono'] ?? '',
                'direccion' => $data['direccion'] ?? '',
                'email' => $data['email'] ?? '',
                'fecha_ingreso' => $data['fecha_ingreso'] ?? '',
                'estado' => $data['estado'] ?? 'activo',
                'aportes_totales' => !empty($data['aportes_totales']) ? (float)$data['aportes_totales'] : 0,
                'deudas_pendientes' => !empty($data['deudas_pendientes']) ? (float)$data['deudas_pendientes'] : 0,
                'observaciones' => $data['observaciones'] ?? ''
            ];
            
            $result = $this->socioModel->update($id, $updateData);
            
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
        }
    }
    
    private function deleteSocio() {
        try {
            parse_str(file_get_contents("php://input"), $data);
            
            $id = $data['id_socio'] ?? null;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID del socio requerido']);
                return;
            }
            
            $result = $this->socioModel->delete($id);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
        }
    }
}
?>
