<?php
include '../barra.php';
include 'cnx.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

// Get client vehicles
$vehicles_sql = "SELECT * FROM vehiculos WHERE usuario_id = ?";
$vehicles_stmt = $conn->prepare($vehicles_sql);
$vehicles_stmt->bind_param("i", $id);
$vehicles_stmt->execute();
$vehicles_result = $vehicles_stmt->get_result();

// Get existing diagnostics for this client
$diagnosticos_sql = "SELECT d.*, v.marca, v.modelo, v.año 
                    FROM diagnostico d 
                    JOIN vehiculos v ON d.vehiculo_id = v.id_vehiculo 
                    WHERE v.usuario_id = ? 
                    ORDER BY d.fecha DESC";
$diagnosticos_stmt = $conn->prepare($diagnosticos_sql);
$diagnosticos_stmt->bind_param("i", $id);
$diagnosticos_stmt->execute();
$diagnosticos_result = $diagnosticos_stmt->get_result();

// Get existing repair orders for this client
$reparaciones_sql = "SELECT r.*, ot.id_orden, ot.estado as orden_estado, d.id_diagnostico, 
                    v.marca, v.modelo, v.año, v.id_vehiculo 
                    FROM reparaciones r
                    JOIN ordenes_trabajo ot ON r.orden_id = ot.id_orden
                    JOIN diagnostico d ON ot.diagnostico_id = d.id_diagnostico
                    JOIN vehiculos v ON d.vehiculo_id = v.id_vehiculo
                    WHERE v.usuario_id = ?
                    ORDER BY r.fecha_inicio DESC";
$reparaciones_stmt = $conn->prepare($reparaciones_sql);
$reparaciones_stmt->bind_param("i", $id);
$reparaciones_stmt->execute();
$reparaciones_result = $reparaciones_stmt->get_result();

// Get work orders for this client that don't have repairs yet
$ordenes_sql = "SELECT ot.*, d.id_diagnostico, d.descripcion as diagnostico_descripcion, 
                v.marca, v.modelo, v.año, v.id_vehiculo
                FROM ordenes_trabajo ot
                JOIN diagnostico d ON ot.diagnostico_id = d.id_diagnostico
                JOIN vehiculos v ON d.vehiculo_id = v.id_vehiculo
                WHERE v.usuario_id = ? AND ot.estado != 'cancelada'
                ORDER BY ot.fecha_creacion DESC";
$ordenes_stmt = $conn->prepare($ordenes_sql);
$ordenes_stmt->bind_param("i", $id);
$ordenes_stmt->execute();
$ordenes_result = $ordenes_stmt->get_result();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'diagnostico') {
        // Process diagnostic form
        $vehiculo_id = $_POST['vehiculo_id'];
        $reporte = $_POST['reporte'];
        $repuestos = $_POST['repuestos'];
        $mecanico_id = 1; // Default mechanic ID - you should replace this with actual mechanic ID
        $fecha = date('Y-m-d');
        
        // Insert into diagnostico table
        $sql = "INSERT INTO diagnostico (vehiculo_id, mecanico_id, fecha, descripcion, hallazgos, estado) 
                VALUES (?, ?, ?, ?, ?, 'pendiente')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisss", $vehiculo_id, $mecanico_id, $fecha, $reporte, $repuestos);
        
        if ($stmt->execute()) {
            // Create a work order automatically
            $diagnostico_id = $conn->insert_id;
            $orden_sql = "INSERT INTO ordenes_trabajo (diagnostico_id, fecha_creacion, estado) 
                         VALUES (?, ?, 'abierta')";
            $orden_stmt = $conn->prepare($orden_sql);
            $orden_stmt->bind_param("is", $diagnostico_id, $fecha);
            $orden_stmt->execute();
            
            $success = "Diagnóstico guardado correctamente y orden de trabajo creada.";
        } else {
            $error = "Error al guardar el diagnóstico: " . $conn->error;
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'reparacion') {
        // Process repair form
        $orden_id = $_POST['orden_id'];
        $descripcion = $_POST['descripcion'];
        $costo = $_POST['costo'];
        $fecha_inicio = date('Y-m-d');
        $estado = 'pendiente';
        
        // Insert into reparaciones table
        $sql = "INSERT INTO reparaciones (orden_id, descripcion, costo, fecha_inicio, estado) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isdss", $orden_id, $descripcion, $costo, $fecha_inicio, $estado);
        
        if ($stmt->execute()) {
            // Update order status to en_progreso
            $update_sql = "UPDATE ordenes_trabajo SET estado = 'en_progreso' WHERE id_orden = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $orden_id);
            $update_stmt->execute();
            
            $success = "Reparación registrada correctamente.";
        } else {
            $error = "Error al registrar la reparación: " . $conn->error;
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'completar_reparacion') {
        // Process completion of repair
        $reparacion_id = $_POST['reparacion_id'];
        $orden_id = $_POST['orden_id'];
        $fecha_fin = date('Y-m-d');
        
        // Update reparacion status to completado and set end date
        $sql = "UPDATE reparaciones SET estado = 'completado', fecha_fin = ? WHERE id_reparacion = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $fecha_fin, $reparacion_id);
        
        if ($stmt->execute()) {
            // Update order status to completada
            $update_sql = "UPDATE ordenes_trabajo SET estado = 'completada' WHERE id_orden = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $orden_id);
            $update_stmt->execute();
            
            $success = "Reparación marcada como completada.";
        } else {
            $error = "Error al actualizar el estado de la reparación: " . $conn->error;
        }
    }
}

$sql = "SELECT * FROM usuario WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Ver Cliente</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
            color: #333;
        }
        .client-header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .client-info h2 {
            margin: 0;
            color: #333;
        }
        .client-info p {
            margin: 5px 0 0;
            color: #666;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
            background: white;
            border-radius: 10px;
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
            transition: all 0.3s;
        }
        .tab.active {
            color: #1565c0;
            border-bottom: 3px solid #1565c0;
            background: #e3f2fd;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h3 {
            color: #333;
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
            font-size: 16px;
        }
        textarea, select, input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 14px;
        }
        textarea {
            height: 120px;
            resize: none;
        }
        button {
            background: #1565c0;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        button:hover {
            background: #0d47a1;
        }
        button i {
            margin-right: 8px;
        }
        .error {
            color: #d32f2f;
            margin-bottom: 20px;
            text-align: center;
            background: #ffebee;
            padding: 10px;
            border-radius: 4px;
        }
        .success {
            color: #388e3c;
            margin-bottom: 20px;
            text-align: center;
            background: #e8f5e9;
            padding: 10px;
            border-radius: 4px;
        }
        .card-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            position: relative;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .card-title {
            font-weight: bold;
            color: #333;
            margin: 0;
        }
        .card-date {
            color: #666;
            font-size: 14px;
        }
        .card-status {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pendiente, .status-abierta {
            background: #fff3cd;
            color: #856404;
        }
        .status-completado, .status-completada {
            background: #d4edda;
            color: #155724;
        }
        .status-en_progreso {
            background: #cce5ff;
            color: #004085;
        }
        .status-cancelado, .status-cancelada {
            background: #f8d7da;
            color: #721c24;
        }
        .card-content p {
            margin: 5px 0;
        }
        .card-content strong {
            color: #555;
        }
        .no-items {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            color: #666;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .card-actions {
            margin-top: 15px;
            display: flex;
            justify-content: flex-end;
        }
        .card-actions form {
            padding: 0;
            margin: 0;
            box-shadow: none;
            background: transparent;
        }
        .card-actions button {
            padding: 8px 15px;
            width: auto;
            font-size: 14px;
            margin-left: 10px;
        }
        .btn-success {
            background: #4caf50;
        }
        .btn-success:hover {
            background: #388e3c;
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="client-header">
            <div class="client-info">
                <h2><i class="fas fa-user"></i> <?php echo htmlspecialchars($client['nombre']); ?></h2>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($client['email']); ?></p>
                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($client['telefono'] ?? 'No disponible'); ?></p>
                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($client['direccion'] ?? 'No disponible'); ?></p>
            </div>
            <a href="index.php" class="btn" style="background: #f1f1f1; color: #333; padding: 10px 15px; border-radius: 4px; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
        
        <?php if(isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if(isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab active" onclick="openTab('diagnostico')"><i class="fas fa-clipboard-check"></i> Diagnóstico</div>
            <div class="tab" onclick="openTab('reparacion')"><i class="fas fa-tools"></i> Reparación</div>
            <div class="tab" onclick="openTab('historial')"><i class="fas fa-history"></i> Historial</div>
        </div>
        
        <div id="diagnostico" class="tab-content active">
            <form method="POST">
                <input type="hidden" name="action" value="diagnostico">
                <h3><i class="fas fa-clipboard-check"></i> Nuevo Diagnóstico</h3>
                
                <div class="form-group">
                    <label for="vehiculo_id"><i class="fas fa-car"></i> Seleccionar Vehículo</label>
                    <select id="vehiculo_id" name="vehiculo_id" required>
                        <?php if ($vehicles_result->num_rows > 0): ?>
                            <?php while($vehicle = $vehicles_result->fetch_assoc()): ?>
                                <option value="<?php echo $vehicle['id_vehiculo']; ?>">
                                    <?php echo $vehicle['marca'] . ' ' . $vehicle['modelo'] . ' (' . $vehicle['año'] . ')'; ?>
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="" disabled>No hay vehículos registrados</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="reporte"><i class="fas fa-file-alt"></i> Reporte del Vehículo</label>
                    <textarea id="reporte" name="reporte" required placeholder="Describa el estado actual del vehículo y los problemas reportados por el cliente..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="repuestos"><i class="fas fa-tools"></i> Lista de Repuestos Necesarios</label>
                    <textarea id="repuestos" name="repuestos" required placeholder="Detalle los repuestos que podrían ser necesarios para la reparación..."></textarea>
                </div>
                
                <button type="submit"><i class="fas fa-save"></i> Guardar Diagnóstico</button>
            </form>
            
            <h3><i class="fas fa-clipboard-list"></i> Diagnósticos Anteriores</h3>
            
            <?php if ($diagnosticos_result->num_rows > 0): ?>
                <div class="card-list">
                    <?php while($diagnostico = $diagnosticos_result->fetch_assoc()): ?>
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Diagnóstico #<?php echo $diagnostico['id_diagnostico']; ?></h4>
                                <span class="card-date"><?php echo date('d/m/Y', strtotime($diagnostico['fecha'])); ?></span>
                            </div>
                            <span class="card-status status-<?php echo $diagnostico['estado']; ?>">
                                <?php echo ucfirst($diagnostico['estado']); ?>
                            </span>
                            <div class="card-content">
                                <p><strong>Vehículo:</strong> <?php echo $diagnostico['marca'] . ' ' . $diagnostico['modelo'] . ' (' . $diagnostico['año'] . ')'; ?></p>
                                <p><strong>Descripción:</strong> <?php echo nl2br(htmlspecialchars($diagnostico['descripcion'])); ?></p>
                                <p><strong>Repuestos:</strong> <?php echo nl2br(htmlspecialchars($diagnostico['hallazgos'])); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-items">
                    <i class="fas fa-info-circle"></i> No hay diagnósticos previos para este cliente.
                </div>
            <?php endif; ?>
        </div>
        
        <div id="reparacion" class="tab-content">
            <form method="POST">
                <input type="hidden" name="action" value="reparacion">
                <h3><i class="fas fa-tools"></i> Nueva Reparación</h3>
                
                <div class="form-group">
                    <label for="orden_id"><i class="fas fa-clipboard-list"></i> Seleccionar Orden de Trabajo</label>
                    <select id="orden_id" name="orden_id" required>
                        <?php if ($ordenes_result->num_rows > 0): ?>
                            <?php while($orden = $ordenes_result->fetch_assoc()): ?>
                                <option value="<?php echo $orden['id_orden']; ?>">
                                    Orden #<?php echo $orden['id_orden']; ?> - 
                                    <?php echo $orden['marca'] . ' ' . $orden['modelo'] . ' (' . $orden['año'] . ')'; ?> - 
                                    Estado: <?php echo ucfirst($orden['estado']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="" disabled>No hay órdenes de trabajo disponibles</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="descripcion"><i class="fas fa-file-alt"></i> Descripción de la Reparación</label>
                    <textarea id="descripcion" name="descripcion" required placeholder="Detalle las reparaciones que se realizarán..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="costo"><i class="fas fa-dollar-sign"></i> Costo Estimado</label>
                    <input type="number" id="costo" name="costo" step="0.01" min="0" required placeholder="0.00">
                </div>
                
                <button type="submit"><i class="fas fa-save"></i> Registrar Reparación</button>
            </form>
            
            <h3><i class="fas fa-history"></i> Reparaciones Registradas</h3>
            
            <?php if ($reparaciones_result->num_rows > 0): ?>
                <div class="card-list">
                    <?php while($reparacion = $reparaciones_result->fetch_assoc()): ?>
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Reparación #<?php echo $reparacion['id_reparacion']; ?></h4>
                                <span class="card-date">
                                    <?php echo date('d/m/Y', strtotime($reparacion['fecha_inicio'])); ?>
                                    <?php if($reparacion['fecha_fin']): ?>
                                        - <?php echo date('d/m/Y', strtotime($reparacion['fecha_fin'])); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <span class="card-status status-<?php echo $reparacion['estado']; ?>">
                                <?php echo ucfirst($reparacion['estado']); ?>
                            </span>
                            <div class="card-content">
                                <p><strong>Orden:</strong> #<?php echo $reparacion['id_orden']; ?></p>
                                <p><strong>Vehículo:</strong> <?php echo $reparacion['marca'] . ' ' . $reparacion['modelo'] . ' (' . $reparacion['año'] . ')'; ?></p>
                                <p><strong>Descripción:</strong> <?php echo nl2br(htmlspecialchars($reparacion['descripcion'])); ?></p>
                                <p><strong>Costo:</strong> $<?php echo number_format($reparacion['costo'], 2); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-items">
                    <i class="fas fa-info-circle"></i> No hay reparaciones registradas para este cliente.
                </div>
            <?php endif; ?>
        </div>
        
        <div id="historial" class="tab-content">
            <h3><i class="fas fa-history"></i> Historial Completo</h3>
            
            <?php 
            // Reset result pointers
            $diagnosticos_stmt->execute();
            $diagnosticos_result = $diagnosticos_stmt->get_result();
            
            $reparaciones_stmt->execute();
            $reparaciones_result = $reparaciones_stmt->get_result();
            
            if ($diagnosticos_result->num_rows > 0 || $reparaciones_result->num_rows > 0): 
            ?>
                <div class="card-list">
                    <?php while($diagnostico = $diagnosticos_result->fetch_assoc()): ?>
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Diagnóstico #<?php echo $diagnostico['id_diagnostico']; ?></h4>
                                <span class="card-date"><?php echo date('d/m/Y', strtotime($diagnostico['fecha'])); ?></span>
                            </div>
                            <span class="card-status status-<?php echo $diagnostico['estado']; ?>">
                                <?php echo ucfirst($diagnostico['estado']); ?>
                            </span>
                            <div class="card-content">
                                <p><strong>Vehículo:</strong> <?php echo $diagnostico['marca'] . ' ' . $diagnostico['modelo'] . ' (' . $diagnostico['año'] . ')'; ?></p>
                                <p><strong>Descripción:</strong> <?php echo nl2br(htmlspecialchars($diagnostico['descripcion'])); ?></p>
                                <p><strong>Repuestos:</strong> <?php echo nl2br(htmlspecialchars($diagnostico['hallazgos'])); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                    <?php while($reparacion = $reparaciones_result->fetch_assoc()): ?>
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Reparación #<?php echo $reparacion['id_reparacion']; ?></h4>
                                <span class="card-date">
                                    <?php echo date('d/m/Y', strtotime($reparacion['fecha_inicio'])); ?>
                                    <?php if($reparacion['fecha_fin']): ?>
                                        - <?php echo date('d/m/Y', strtotime($reparacion['fecha_fin'])); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <span class="card-status status-<?php echo $reparacion['estado']; ?>">
                                <?php echo ucfirst($reparacion['estado']); ?>
                            </span>
                            <div class="card-content">
                                <p><strong>Orden:</strong> #<?php echo $reparacion['id_orden']; ?></p>
                                <p><strong>Vehículo:</strong> <?php echo $reparacion['marca'] . ' ' . $reparacion['modelo'] . ' (' . $reparacion['año'] . ')'; ?></p>
                                <p><strong>Descripción:</strong> <?php echo nl2br(htmlspecialchars($reparacion['descripcion'])); ?></p>
                                <p><strong>Costo:</strong> $<?php echo number_format($reparacion['costo'], 2); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-items">
                    <i class="fas fa-info-circle"></i> No hay registros para este cliente.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function openTab(tabName) {
            // Hide all tab contents
            var tabContents = document.getElementsByClassName('tab-content');
            for (var i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove('active');
            }
            
            // Remove active class from all tabs
            var tabs = document.getElementsByClassName('tab');
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }
            
            // Show the selected tab content and mark the tab as active
            document.getElementById(tabName).classList.add('active');
            
            // Find the clicked tab and add active class
            var tabs = document.getElementsByClassName('tab');
            for (var i = 0; i < tabs.length; i++) {
                if (tabs[i].textContent.toLowerCase().includes(tabName)) {
                    tabs[i].classList.add('active');
                }
            }
        }
    </script>
</body>
</html>
