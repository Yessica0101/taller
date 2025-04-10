<?php
include 'barra_cliente.php';
include '../admin/mecanicos/cnx.php';

// Check if session is started and usuario_id is set
if (!isset($_SESSION['usuario_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../index.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Get user vehicles
$vehicles_sql = "SELECT * FROM vehiculos WHERE usuario_id = ?";
$vehicles_stmt = $conn->prepare($vehicles_sql);
$vehicles_stmt->bind_param("i", $usuario_id);
$vehicles_stmt->execute();
$vehicles_result = $vehicles_stmt->get_result();

// Get repair information for user vehicles - fixed query to handle missing columns
$reparaciones_sql = "SELECT r.*, ot.id_orden, ot.estado as orden_estado, d.id_diagnostico, 
                    v.marca, v.modelo, v.año, v.id_vehiculo, 
                    IFNULL(v.placa, 'No disponible') as placa
                    FROM vehiculos v
                    LEFT JOIN diagnostico d ON v.id_vehiculo = d.vehiculo_id
                    LEFT JOIN ordenes_trabajo ot ON d.id_diagnostico = ot.diagnostico_id
                    LEFT JOIN reparaciones r ON ot.id_orden = r.orden_id
                    WHERE v.usuario_id = ?
                    ORDER BY v.id_vehiculo, r.fecha_inicio DESC";
$reparaciones_stmt = $conn->prepare($reparaciones_sql);
$reparaciones_stmt->bind_param("i", $usuario_id);
$reparaciones_stmt->execute();
$reparaciones_result = $reparaciones_stmt->get_result();

// Process form submission for adding a new vehicle
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_vehicle') {
    $marca = $_POST['marca'];
    $modelo = $_POST['modelo'];
    $año = $_POST['año'];
    $placa = $_POST['placa'];
    $color = $_POST['color'];
    
    $sql = "INSERT INTO vehiculos (usuario_id, marca, modelo, año, placa, color) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ississ", $usuario_id, $marca, $modelo, $año, $placa, $color);
    
    if ($stmt->execute()) {
        $success = "Vehículo agregado correctamente.";
        // Refresh the page to show the new vehicle
        header("Location: mis_vehiculos.php");
        exit();
    } else {
        $error = "Error al agregar el vehículo: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Mis Vehículos</title>
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
        .page-header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .page-header h2 {
            margin: 0;
            color: #333;
        }
        .btn-add {
            background: #1565c0;
            color: white;
            padding: 10px 15px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            font-weight: bold;
        }
        .btn-add i {
            margin-right: 8px;
        }
        .btn-add:hover {
            background: #0d47a1;
        }
        .vehicle-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .vehicle-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            position: relative;
        }
        .vehicle-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .vehicle-title {
            font-weight: bold;
            color: #333;
            margin: 0;
            font-size: 18px;
        }
        .vehicle-content p {
            margin: 5px 0;
        }
        .vehicle-content strong {
            color: #555;
        }
        .vehicle-actions {
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
        }
        .vehicle-actions a {
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
        }
        .vehicle-actions a i {
            margin-right: 5px;
        }
        .btn-view {
            background: #1565c0;
        }
        .btn-view:hover {
            background: #0d47a1;
        }
        .btn-edit {
            background: #ffa000;
        }
        .btn-edit:hover {
            background: #ff8f00;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 10px;
            width: 50%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
        }
        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }
        .close:hover {
            color: #333;
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
        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 14px;
        }
        button[type="submit"] {
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
        button[type="submit"]:hover {
            background: #0d47a1;
        }
        button[type="submit"] i {
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
        .repair-list {
            margin-top: 20px;
        }
        .repair-item {
            background: #f9f9f9;
            border-left: 4px solid #1565c0;
            padding: 10px 15px;
            margin-bottom: 10px;
            border-radius: 0 4px 4px 0;
        }
        .repair-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .repair-title {
            font-weight: bold;
            color: #333;
        }
        .repair-date {
            color: #666;
            font-size: 14px;
        }
        .repair-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
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
        .no-repairs {
            color: #666;
            font-style: italic;
            margin-top: 10px;
        }
        .vehicle-details {
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .vehicle-details h3 {
            color: #333;
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .tab-container {
            margin-top: 20px;
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
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="page-header">
            <h2><i class="fas fa-car"></i> Mis Vehículos</h2>
            <a href="#" class="btn-add" onclick="openModal()">
                <i class="fas fa-plus"></i> Agregar Vehículo
            </a>
        </div>
        
        <?php if(isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if(isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($vehicles_result->num_rows > 0): ?>
            <div class="vehicle-list">
                <?php while($vehicle = $vehicles_result->fetch_assoc()): ?>
                    <div class="vehicle-card">
                        <div class="vehicle-header">
                            <h3 class="vehicle-title"><?php echo $vehicle['marca'] . ' ' . $vehicle['modelo']; ?></h3>
                        </div>
                        <div class="vehicle-content">
                            <p><strong>Año:</strong> <?php echo $vehicle['año']; ?></p>
                            <p><strong>Placa:</strong> <?php echo $vehicle['placa']; ?></p>
                            <p><strong>Color:</strong> <?php echo $vehicle['color']; ?></p>
                            
                            <?php
                            // Reset pointer to get repairs for this vehicle
                            $reparaciones_stmt->execute();
                            $reparaciones_result = $reparaciones_stmt->get_result();
                            $has_repairs = false;
                            $active_repairs = false;
                            
                            while($reparacion = $reparaciones_result->fetch_assoc()) {
                                if ($reparacion['id_vehiculo'] == $vehicle['id_vehiculo'] && $reparacion['id_reparacion']) {
                                    $has_repairs = true;
                                    if ($reparacion['estado'] == 'pendiente' || $reparacion['estado'] == 'en_progreso') {
                                        $active_repairs = true;
                                        break;
                                    }
                                }
                            }
                            ?>
                            
                            <?php if($active_repairs): ?>
                                <p><strong>Estado:</strong> <span class="repair-status status-en_progreso">En reparación</span></p>
                            <?php else: ?>
                                <p><strong>Estado:</strong> <span class="repair-status status-completada">Disponible</span></p>
                            <?php endif; ?>
                        </div>
                        <div class="vehicle-actions">
                            <a href="detalle_vehiculo.php?id=<?php echo $vehicle['id_vehiculo']; ?>" class="btn-view">
                                <i class="fas fa-eye"></i> Ver Detalles
                            </a>
                            <a href="editar_vehiculo.php?id=<?php echo $vehicle['id_vehiculo']; ?>" class="btn-edit">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <div class="tab-container">
                <div class="tabs">
                    <div class="tab active" onclick="openTab('reparaciones')"><i class="fas fa-tools"></i> Reparaciones Activas</div>
                    <div class="tab" onclick="openTab('historial')"><i class="fas fa-history"></i> Historial de Reparaciones</div>
                </div>
                
                <div id="reparaciones" class="tab-content active">
                    <h3><i class="fas fa-tools"></i> Reparaciones en Progreso</h3>
                    
                    <?php
                    // Reset pointer
                    $reparaciones_stmt->execute();
                    $reparaciones_result = $reparaciones_stmt->get_result();
                    $has_active_repairs = false;
                    
                    while($reparacion = $reparaciones_result->fetch_assoc()) {
                        if ($reparacion['id_reparacion'] && ($reparacion['estado'] == 'pendiente' || $reparacion['estado'] == 'en_progreso')) {
                            $has_active_repairs = true;
                            ?>
                            <div class="repair-item">
                                <div class="repair-header">
                                    <span class="repair-title">
                                        <?php echo $reparacion['marca'] . ' ' . $reparacion['modelo'] . ' (' . $reparacion['año'] . ')'; ?>
                                        - Placa: <?php echo $reparacion['placa']; ?>
                                    </span>
                                    <span class="repair-date">
                                        Inicio: <?php echo date('d/m/Y', strtotime($reparacion['fecha_inicio'])); ?>
                                        <span class="repair-status status-<?php echo $reparacion['estado']; ?>">
                                            <?php echo ucfirst($reparacion['estado']); ?>
                                        </span>
                                    </span>
                                </div>
                                <p><strong>Descripción:</strong> <?php echo nl2br(htmlspecialchars($reparacion['descripcion'])); ?></p>
                                <p><strong>Costo Estimado:</strong> $<?php echo number_format($reparacion['costo'], 2); ?></p>
                            </div>
                            <?php
                        }
                    }
                    
                    if (!$has_active_repairs) {
                        echo '<p class="no-repairs">No hay reparaciones activas en este momento.</p>';
                    }
                    ?>
                </div>
                
                <div id="historial" class="tab-content">
                    <h3><i class="fas fa-history"></i> Historial de Reparaciones</h3>
                    
                    <?php
                    // Reset pointer
                    $reparaciones_stmt->execute();
                    $reparaciones_result = $reparaciones_stmt->get_result();
                    $has_completed_repairs = false;
                    
                    while($reparacion = $reparaciones_result->fetch_assoc()) {
                        if ($reparacion['id_reparacion'] && $reparacion['estado'] == 'completado') {
                            $has_completed_repairs = true;
                            ?>
                            <div class="repair-item">
                                <div class="repair-header">
                                    <span class="repair-title">
                                        <?php echo $reparacion['marca'] . ' ' . $reparacion['modelo'] . ' (' . $reparacion['año'] . ')'; ?>
                                        - Placa: <?php echo $reparacion['placa']; ?>
                                    </span>
                                    <span class="repair-date">
                                        <?php echo date('d/m/Y', strtotime($reparacion['fecha_inicio'])); ?> - 
                                        <?php echo date('d/m/Y', strtotime($reparacion['fecha_fin'])); ?>
                                        <span class="repair-status status-completado">
                                            Completado
                                        </span>
                                    </span>
                                </div>
                                <p><strong>Descripción:</strong> <?php echo nl2br(htmlspecialchars($reparacion['descripcion'])); ?></p>
                                <p><strong>Costo Final:</strong> $<?php echo number_format($reparacion['costo'], 2); ?></p>
                            </div>
                            <?php
                        }
                    }
                    
                    if (!$has_completed_repairs) {
                        echo '<p class="no-repairs">No hay reparaciones completadas en el historial.</p>';
                    }
                    ?>
                </div>
            </div>
        <?php else: ?>
            <div class="no-items" style="background: white; padding: 20px; border-radius: 10px; text-align: center; color: #666; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <i class="fas fa-info-circle"></i> No tienes vehículos registrados. Agrega tu primer vehículo haciendo clic en el botón "Agregar Vehículo".
            </div>
        <?php endif; ?>
        
        <!-- Modal for adding a new vehicle -->
        <div id="addVehicleModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h3><i class="fas fa-car"></i> Agregar Nuevo Vehículo</h3>
                
                <form method="POST">
                    <input type="hidden" name="action" value="add_vehicle">
                    
                    <div class="form-group">
                        <label for="marca"><i class="fas fa-tag"></i> Marca</label>
                        <input type="text" id="marca" name="marca" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="modelo"><i class="fas fa-car-side"></i> Modelo</label>
                        <input type="text" id="modelo" name="modelo" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="año"><i class="fas fa-calendar-alt"></i> Año</label>
                        <input type="number" id="año" name="año" min="1900" max="<?php echo date('Y') + 1; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="placa"><i class="fas fa-id-card"></i> Placa</label>
                        <input type="text" id="placa" name="placa" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="color"><i class="fas fa-palette"></i> Color</label>
                        <input type="text" id="color" name="color" required>
                    </div>
                    
                    <button type="submit"><i class="fas fa-save"></i> Guardar Vehículo</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function openModal() {
            document.getElementById('addVehicleModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('addVehicleModal').style.display = 'none';
        }
        
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
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            var modal = document.getElementById('addVehicleModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>