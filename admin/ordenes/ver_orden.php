<?php
include '../barra.php';
include '../mecanicos/cnx.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$orden_id = $_GET['id'];

// Get work order details with related information
$sql = "SELECT ot.*, d.vehiculo_id, d.mecanico_id, d.descripcion, d.hallazgos,
        v.marca, v.modelo, v.año, v.vin, u.nombre as cliente_nombre, u.telefono, u.email,
        m.nombre as mecanico_nombre, m.especialidad
        FROM ordenes_trabajo ot
        JOIN diagnostico d ON ot.diagnostico_id = d.id_diagnostico
        JOIN vehiculos v ON d.vehiculo_id = v.id_vehiculo
        JOIN usuario u ON v.usuario_id = u.id_usuario
        JOIN mecanicos m ON d.mecanico_id = m.id_mecanico
        WHERE ot.id_orden = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $orden_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit;
}

$orden = $result->fetch_assoc();

// Get all mechanics for assignment
$mecanicos_sql = "SELECT * FROM mecanicos WHERE estado = 'disponible' ORDER BY nombre";
$mecanicos_result = $conn->query($mecanicos_sql);

// Get all repairs for this order
$reparaciones_sql = "SELECT * FROM reparaciones WHERE orden_id = ? ORDER BY fecha_inicio";
$reparaciones_stmt = $conn->prepare($reparaciones_sql);
$reparaciones_stmt->bind_param("i", $orden_id);
$reparaciones_stmt->execute();
$reparaciones_result = $reparaciones_stmt->get_result();

// Process status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $nuevo_estado = $_POST['estado'];
    
    $update_sql = "UPDATE ordenes_trabajo SET estado = ? WHERE id_orden = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $nuevo_estado, $orden_id);
    
    if ($update_stmt->execute()) {
        // Refresh the page to show updated status
        header("Location: ver_orden.php?id=$orden_id&updated=1");
        exit;
    } else {
        $error = "Error al actualizar el estado: " . $conn->error;
    }
}

// Process new repair
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_repair'])) {
    $descripcion = $_POST['descripcion'];
    $costo = $_POST['costo'];
    $fecha_inicio = date('Y-m-d');
    $estado = 'pendiente';
    
    $repair_sql = "INSERT INTO reparaciones (orden_id, descripcion, costo, fecha_inicio, estado) 
                  VALUES (?, ?, ?, ?, ?)";
    $repair_stmt = $conn->prepare($repair_sql);
    $repair_stmt->bind_param("isdss", $orden_id, $descripcion, $costo, $fecha_inicio, $estado);
    
    if ($repair_stmt->execute()) {
        // Refresh the page
        header("Location: ver_orden.php?id=$orden_id&repair_added=1");
        exit;
    } else {
        $error = "Error al agregar la reparación: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Trabajo #<?php echo $orden_id; ?></title>
    <style>
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        .order-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        .order-details, .order-sidebar, .order-repairs, .add-repair-form {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .order-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .order-status {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
        }
        .status-abierta {
            background-color: #cce5ff;
            color: #004085;
        }
        .status-en_progreso {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-completada {
            background-color: #d4edda;
            color: #155724;
        }
        .status-cancelada {
            background-color: #f8d7da;
            color: #721c24;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-section h3 {
            color: #555;
            font-size: 18px;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .info-item {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            color: #666;
            display: block;
            margin-bottom: 5px;
        }
        .info-value {
            color: #333;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            border: none;
        }
        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }
        .btn-secondary {
            background-color: #f1f1f1;
            color: #333;
        }
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        select, input, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        textarea {
            min-height: 100px;
        }
        .repair-item {
            border: 1px solid #eee;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .repair-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        .repair-title {
            font-weight: bold;
            color: #333;
        }
        .repair-status {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pendiente {
            background-color: #cce5ff;
            color: #004085;
        }
        .status-en_progreso {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-completado {
            background-color: #d4edda;
            color: #155724;
        }
        .status-cancelado {
            background-color: #f8d7da;
            color: #721c24;
        }
        .repair-cost {
            font-weight: bold;
            color: #28a745;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .no-repairs {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="order-header">
            <h2 class="order-title">Orden de Trabajo #<?php echo $orden_id; ?></h2>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a la lista</a>
        </div>
        
        <?php if(isset($_GET['created'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> La orden de trabajo se ha creado correctamente.
            </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['updated'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> La orden de trabajo se ha actualizado correctamente.
            </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['repair_added'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> La reparación se ha agregado correctamente.
            </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="order-container">
            <div class="main-content">
                <div class="order-details">
                    <div class="order-header">
                        <h3>Detalles de la Orden</h3>
                        <span class="order-status status-<?php echo $orden['estado']; ?>">
                            <?php 
                                $estados = [
                                    'abierta' => 'Abierta',
                                    'en_progreso' => 'En Progreso',
                                    'completada' => 'Completada',
                                    'cancelada' => 'Cancelada'
                                ];
                                echo $estados[$orden['estado']] ?? ucfirst($orden['estado']);
                            ?>
                        </span>
                    </div>
                    
                    <div class="info-section">
                        <h3>Información del Cliente</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Nombre:</span>
                                <span class="info-value"><?php echo htmlspecialchars($orden['cliente_nombre']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Teléfono:</span>
                                <span class="info-value"><?php echo htmlspecialchars($orden['telefono'] ?? 'No disponible'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?php echo htmlspecial