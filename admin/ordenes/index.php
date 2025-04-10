<?php
include '../barra.php';
include '../mecanicos/cnx.php';

// Get all work orders with related information
$sql = "SELECT ot.*, d.vehiculo_id, d.mecanico_id, d.descripcion, 
        v.marca, v.modelo, v.año, u.nombre as cliente_nombre, 
        m.nombre as mecanico_nombre
        FROM ordenes_trabajo ot
        JOIN diagnostico d ON ot.diagnostico_id = d.id_diagnostico
        JOIN vehiculos v ON d.vehiculo_id = v.id_vehiculo
        JOIN usuario u ON v.usuario_id = u.id_usuario
        JOIN mecanicos m ON d.mecanico_id = m.id_mecanico
        ORDER BY ot.fecha_creacion DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Órdenes de Trabajo</title>
    <style>
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .orders-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .order-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            position: relative;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .order-id {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .order-date {
            color: #666;
            font-size: 14px;
        }
        .order-status {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
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
        .order-details p {
            margin: 5px 0;
            display: flex;
            align-items: center;
        }
        .order-details i {
            width: 20px;
            margin-right: 10px;
            color: #666;
        }
        .order-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .action-btn {
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 13px;
            text-decoration: none;
            color: white;
        }
        .btn-view {
            background-color: #17a2b8;
        }
        .btn-edit {
            background-color: #ffc107;
            color: #212529;
        }
        .btn-delete {
            background-color: #dc3545;
        }
        .empty-message {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="header-actions">
            <h2><i class="fas fa-clipboard-list"></i> Gestión de Órdenes de Trabajo</h2>
            <a href="crear_orden.php" class="btn"><i class="fas fa-plus"></i> Nueva Orden</a>
        </div>
        
        <div class="orders-container">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($orden = $result->fetch_assoc()): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <span class="order-id">Orden #<?php echo $orden['id_orden']; ?></span>
                            <span class="order-date"><?php echo date('d/m/Y', strtotime($orden['fecha_creacion'])); ?></span>
                        </div>
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
                        <div class="order-details">
                            <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($orden['cliente_nombre']); ?></p>
                            <p><i class="fas fa-car"></i> <?php echo htmlspecialchars($orden['marca'] . ' ' . $orden['modelo'] . ' (' . $orden['año'] . ')'); ?></p>
                            <p><i class="fas fa-wrench"></i> <?php echo htmlspecialchars($orden['mecanico_nombre']); ?></p>
                            <p><i class="fas fa-clipboard"></i> <?php echo htmlspecialchars(substr($orden['descripcion'], 0, 100) . (strlen($orden['descripcion']) > 100 ? '...' : '')); ?></p>
                        </div>
                        <div class="order-actions">
                            <a href="ver_orden.php?id=<?php echo $orden['id_orden']; ?>" class="action-btn btn-view"><i class="fas fa-eye"></i> Ver</a>
                            <a href="editar_orden.php?id=<?php echo $orden['id_orden']; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i> Editar</a>
                            <a href="eliminar_orden.php?id=<?php echo $orden['id_orden']; ?>" class="action-btn btn-delete" onclick="return confirm('¿Está seguro de eliminar esta orden?')"><i class="fas fa-trash"></i> Eliminar</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-message">
                    <i class="fas fa-clipboard-list" style="font-size: 48px; color: #ddd; margin-bottom: 20px; display: block;"></i>
                    <p>No hay órdenes de trabajo registradas.</p>
                    <a href="crear_orden.php" class="btn"><i class="fas fa-plus"></i> Crear Primera Orden</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>