<?php
// Only start session if one doesn't already exist
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'barra_cliente.php';
include '../admin/mecanicos/cnx.php';

// Get diagnostics for this user
$user_id = $_SESSION['user_id'];
$sql = "SELECT d.*, v.marca, v.modelo, v.año 
        FROM diagnostico d 
        JOIN vehiculos v ON d.vehiculo_id = v.id_vehiculo 
        WHERE v.usuario_id = ?
        ORDER BY d.fecha DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Get vehicle count
$vehicles_sql = "SELECT COUNT(*) as vehicle_count FROM vehiculos WHERE usuario_id = ?";
$vehicles_stmt = $conn->prepare($vehicles_sql);
$vehicles_stmt->bind_param("i", $user_id);
$vehicles_stmt->execute();
$vehicles_result = $vehicles_stmt->get_result(); // Get the result object first
$vehicles_data = $vehicles_result->fetch_assoc();
$vehicle_count = $vehicles_data['vehicle_count'] ?? 0;

// Get repair information
$reparaciones_sql = "SELECT r.*, ot.id_orden, ot.estado as orden_estado, 
                    d.id_diagnostico, v.marca, v.modelo, v.año 
                    FROM reparaciones r
                    JOIN ordenes_trabajo ot ON r.orden_id = ot.id_orden
                    JOIN diagnostico d ON ot.diagnostico_id = d.id_diagnostico
                    JOIN vehiculos v ON d.vehiculo_id = v.id_vehiculo
                    WHERE v.usuario_id = ?
                    ORDER BY r.fecha_inicio DESC";
$reparaciones_stmt = $conn->prepare($reparaciones_sql);
$reparaciones_stmt->bind_param("i", $user_id);
$reparaciones_stmt->execute();
$reparaciones_result = $reparaciones_stmt->get_result();
$reparaciones_count = $reparaciones_result->num_rows;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Dashboard Cliente</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        .dashboard-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .summary-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            display: flex;
            align-items: center;
        }
        .summary-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e3f2fd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 24px;
            color: #1565c0;
        }
        .summary-info h3 {
            margin: 0;
            font-size: 14px;
            color: #666;
        }
        .summary-info p {
            margin: 5px 0 0;
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .tab {
            padding: 15px 20px;
            cursor: pointer;
            flex: 1;
            text-align: center;
            font-weight: bold;
            color: #666;
            border-bottom: 3px solid transparent;
        }
        .diagnostico-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #1565c0;
        }
        .diagnostico-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .diagnostico-date {
            color: #666;
            font-size: 14px;
        }
        .diagnostico-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pendiente {
            background: #fff3cd;
            color: #856404;
        }
        .status-completado {
            background: #d4edda;
            color: #155724;
        }
        h2 {
            color: #1565c0;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        h2 i {
            margin-right: 10px;
        }
        .no-diagnosticos {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            color: #666;
        }
        .vehicle-info {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="content">
        <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
        
        <div class="dashboard-summary">
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fas fa-car"></i>
                </div>
                <div class="summary-info">
                    <h3>Mis Vehículos</h3>
                    <p><?php echo $vehicle_count; ?></p>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="summary-info">
                    <h3>Diagnósticos</h3>
                    <p><?php echo $result->num_rows; ?></p>
                </div>
            </div>
        </div>
        
        <h2><i class="fas fa-clipboard-check"></i> Mis Diagnósticos Recientes</h2>
        
        <?php if ($result->num_rows > 0): ?>
            <?php while($diagnostico = $result->fetch_assoc()): ?>
                <div class="diagnostico-card">
                    <div class="diagnostico-header">
                        <div>
                            <h3>Diagnóstico #<?php echo $diagnostico['id_diagnostico']; ?></h3>
                            <p class="vehicle-info"><?php echo $diagnostico['marca'] . ' ' . $diagnostico['modelo'] . ' (' . $diagnostico['año'] . ')'; ?></p>
                        </div>
                        <div>
                            <span class="diagnostico-date"><?php echo date('d/m/Y', strtotime($diagnostico['fecha'])); ?></span>
                            <span class="diagnostico-status status-<?php echo $diagnostico['estado']; ?>">
                                <?php echo ucfirst($diagnostico['estado']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="diagnostico-content">
                        <h4>Descripción:</h4>
                        <p><?php echo nl2br(htmlspecialchars($diagnostico['descripcion'] ?? 'No hay descripción disponible')); ?></p>
                        
                        <h4>Hallazgos:</h4>
                        <p><?php echo nl2br(htmlspecialchars($diagnostico['hallazgos'] ?? 'No hay hallazgos registrados')); ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-diagnosticos">
                <i class="fas fa-info-circle"></i> No tienes diagnósticos registrados.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>