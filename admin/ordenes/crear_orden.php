<?php
include '../barra.php';
include '../mecanicos/cnx.php';

// Get all completed diagnostics that don't have a work order yet
$sql = "SELECT d.*, v.marca, v.modelo, v.año, u.nombre as cliente_nombre, m.nombre as mecanico_nombre
        FROM diagnostico d
        JOIN vehiculos v ON d.vehiculo_id = v.id_vehiculo
        JOIN usuario u ON v.usuario_id = u.id_usuario
        JOIN mecanicos m ON d.mecanico_id = m.id_mecanico
        WHERE d.estado = 'completado'
        AND NOT EXISTS (
            SELECT 1 FROM ordenes_trabajo ot WHERE ot.diagnostico_id = d.id_diagnostico
        )
        ORDER BY d.fecha DESC";
$result = $conn->query($sql);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $diagnostico_id = $_POST['diagnostico_id'];
    $fecha_creacion = date('Y-m-d');
    $estado = 'abierta';
    
    // Insert new work order
    $sql = "INSERT INTO ordenes_trabajo (diagnostico_id, fecha_creacion, estado) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $diagnostico_id, $fecha_creacion, $estado);
    
    if ($stmt->execute()) {
        $orden_id = $conn->insert_id;
        
        // Redirect to the edit page for the new order
        header("Location: ver_orden.php?id=$orden_id&created=1");
        exit;
    } else {
        $error = "Error al crear la orden de trabajo: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Orden de Trabajo</title>
    <style>
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        .form-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
            font-size: 16px;
        }
        .diagnostic-card {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-top: 10px;
            background-color: #f9f9f9;
        }
        .diagnostic-card p {
            margin: 5px 0;
        }
        .diagnostic-card strong {
            color: #555;
        }
        .btn-container {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
            border: none;
            font-size: 16px;
        }
        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }
        .btn-secondary {
            background-color: #f1f1f1;
            color: #333;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .no-diagnostics {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="content">
        <h2><i class="fas fa-plus-circle"></i> Crear Nueva Orden de Trabajo</h2>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <?php if ($result && $result->num_rows > 0): ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="diagnostico_id">Seleccione un diagnóstico completado:</label>
                        <select id="diagnostico_id" name="diagnostico_id" required onchange="showDiagnosticDetails(this.value)">
                            <option value="">-- Seleccionar diagnóstico --</option>
                            <?php while($diagnostico = $result->fetch_assoc()): ?>
                                <option value="<?php echo $diagnostico['id_diagnostico']; ?>" 
                                        data-cliente="<?php echo htmlspecialchars($diagnostico['cliente_nombre']); ?>"
                                        data-vehiculo="<?php echo htmlspecialchars($diagnostico['marca'] . ' ' . $diagnostico['modelo'] . ' (' . $diagnostico['año'] . ')'); ?>"
                                        data-mecanico="<?php echo htmlspecialchars($diagnostico['mecanico_nombre']); ?>"
                                        data-fecha="<?php echo date('d/m/Y', strtotime($diagnostico['fecha'])); ?>"
                                        data-descripcion="<?php echo htmlspecialchars($diagnostico['descripcion']); ?>"
                                        data-hallazgos="<?php echo htmlspecialchars($diagnostico['hallazgos']); ?>">
                                    Diagnóstico #<?php echo $diagnostico['id_diagnostico']; ?> - 
                                    <?php echo htmlspecialchars($diagnostico['cliente_nombre']); ?> - 
                                    <?php echo htmlspecialchars($diagnostico['marca'] . ' ' . $diagnostico['modelo']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div id="diagnostic-details" class="diagnostic-card" style="display: none;">
                        <h3>Detalles del diagnóstico</h3>
                        <p><strong>Cliente:</strong> <span id="detail-cliente"></span></p>
                        <p><strong>Vehículo:</strong> <span id="detail-vehiculo"></span></p>
                        <p><strong>Mecánico:</strong> <span id="detail-mecanico"></span></p>
                        <p><strong>Fecha:</strong> <span id="detail-fecha"></span></p>
                        <p><strong>Descripción:</strong> <span id="detail-descripcion"></span></p>
                        <p><strong>Hallazgos:</strong> <span id="detail-hallazgos"></span></p>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Al crear una orden de trabajo, se generará con estado "Abierta". Podrá asignar mecánicos y repuestos después de crearla.
                    </div>
                    
                    <div class="btn-container">
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Crear Orden de Trabajo</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="no-diagnostics">
                    <i class="fas fa-clipboard-check" style="font-size: 48px; color: #ddd; margin-bottom: 20px; display: block;"></i>
                    <p>No hay diagnósticos completados disponibles para crear órdenes de trabajo.</p>
                    <p>Primero debe completar un diagnóstico antes de poder crear una orden de trabajo.</p>
                    <a href="../mecanicos/index.php" class="btn btn-primary">Ir a Gestión de Mecánicos</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function showDiagnosticDetails(diagnosticoId) {
            const detailsDiv = document.getElementById('diagnostic-details');
            
            if (!diagnosticoId) {
                detailsDiv.style.display = 'none';
                return;
            }
            
            const option = document.querySelector(`option[value="${diagnosticoId}"]`);
            
            document.getElementById('detail-cliente').textContent = option.dataset.cliente;
            document.getElementById('detail-vehiculo').textContent = option.dataset.vehiculo;
            document.getElementById('detail-mecanico').textContent = option.dataset.mecanico;
            document.getElementById('detail-fecha').textContent = option.dataset.fecha;
            document.getElementById('detail-descripcion').textContent = option.dataset.descripcion;
            document.getElementById('detail-hallazgos').textContent = option.dataset.hallazgos;
            
            detailsDiv.style.display = 'block';
        }
    </script>
</body>
</html>