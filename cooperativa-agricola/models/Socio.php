<?php
require_once __DIR__ . '/../php/config.php';

class Socio {
    private $db;
    
    public function __construct() {
        $this->db = conectarDB();
    }
    
    public function getAll($page = 1, $limit = 10, $search = '') {
        try {
            $offset = ($page - 1) * $limit;
            
            // Construir consulta con búsqueda
            $whereClause = '';
            $params = [];
            
            if (!empty($search)) {
                $whereClause = "WHERE nombre LIKE ? OR cedula LIKE ? OR email LIKE ?";
                $searchTerm = "%{$search}%";
                $params = [$searchTerm, $searchTerm, $searchTerm];
            }
            
            // Contar total de registros
            $countQuery = "SELECT COUNT(*) as total FROM socios {$whereClause}";
            $countStmt = $this->db->prepare($countQuery);
            $countStmt->execute($params);
            $totalRecords = $countStmt->fetch()['total'];
            $totalPages = ceil($totalRecords / $limit);
            
            // Obtener registros paginados
            $query = "SELECT * FROM socios {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $socios = $stmt->fetchAll();
            
            return [
                'success' => true,
                'socios' => $socios,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_records' => $totalRecords,
                    'limit' => $limit
                ]
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error al obtener socios: ' . $e->getMessage()];
        }
    }
    
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM socios WHERE id_socio = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
    
    public function create($data) {
        try {
            // Validar datos requeridos
            if (empty($data['nombre']) || empty($data['cedula'])) {
                return ['success' => false, 'message' => 'Nombre y cédula son obligatorios'];
            }
            
            // Validar formato de email
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Formato de email inválido'];
            }
            
            // Verificar si la cédula ya existe
            $stmt = $this->db->prepare("SELECT id_socio FROM socios WHERE cedula = ?");
            $stmt->execute([$data['cedula']]);
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Ya existe un socio con esta cédula'];
            }
            
            // Insertar nuevo socio
            $stmt = $this->db->prepare("
                INSERT INTO socios (nombre, cedula, telefono, direccion, email, fecha_ingreso, estado, aportes_totales, deudas_pendientes, observaciones)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $data['nombre'],
                $data['cedula'],
                $data['telefono'],
                $data['direccion'],
                $data['email'],
                $data['fecha_ingreso'],
                $data['estado'],
                $data['aportes_totales'],
                $data['deudas_pendientes'],
                $data['observaciones']
            ]);
            
            if ($result) {
                $socioId = $this->db->lastInsertId();
                $socio = $this->getById($socioId);
                return [
                    'success' => true,
                    'message' => 'Socio agregado exitosamente',
                    'socio' => $socio
                ];
            } else {
                return ['success' => false, 'message' => 'Error al agregar socio'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()];
        }
    }
    
    public function update($id, $data) {
        try {
            // Validar datos requeridos
            if (empty($data['nombre']) || empty($data['cedula'])) {
                return ['success' => false, 'message' => 'Nombre y cédula son obligatorios'];
            }
            
            // Validar formato de email
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Formato de email inválido'];
            }
            
            // Verificar si la cédula ya existe (excluyendo el socio actual)
            $stmt = $this->db->prepare("SELECT id_socio FROM socios WHERE cedula = ? AND id_socio != ?");
            $stmt->execute([$data['cedula'], $id]);
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Ya existe un socio con esta cédula'];
            }
            
            // Actualizar socio
            $stmt = $this->db->prepare("
                UPDATE socios SET 
                nombre = ?, cedula = ?, telefono = ?, direccion = ?, email = ?, 
                fecha_ingreso = ?, estado = ?, aportes_totales = ?, deudas_pendientes = ?, 
                observaciones = ?, updated_at = NOW()
                WHERE id_socio = ?
            ");
            
            $result = $stmt->execute([
                $data['nombre'],
                $data['cedula'],
                $data['telefono'],
                $data['direccion'],
                $data['email'],
                $data['fecha_ingreso'],
                $data['estado'],
                $data['aportes_totales'],
                $data['deudas_pendientes'],
                $data['observaciones'],
                $id
            ]);
            
            if ($result) {
                $socio = $this->getById($id);
                return [
                    'success' => true,
                    'message' => 'Socio actualizado exitosamente',
                    'socio' => $socio
                ];
            } else {
                return ['success' => false, 'message' => 'Error al actualizar socio'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()];
        }
    }
    
    public function delete($id) {
        try {
            // Verificar si el socio existe
            $socio = $this->getById($id);
            if (!$socio) {
                return ['success' => false, 'message' => 'Socio no encontrado'];
            }
            
            // Eliminar socio
            $stmt = $this->db->prepare("DELETE FROM socios WHERE id_socio = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Socio eliminado exitosamente',
                    'socio' => $socio
                ];
            } else {
                return ['success' => false, 'message' => 'Error al eliminar socio'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()];
        }
    }
}
?>
