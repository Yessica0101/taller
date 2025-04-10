<?php
include '../barra.php';
include '../mecanicos/cnx.php';

// Handle user deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // First check if user has vehicles
    $check_vehicles = "SELECT COUNT(*) as count FROM vehiculos WHERE usuario_id = ?";
    $stmt = $conn->prepare($check_vehicles);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $vehicle_count = $result->fetch_assoc()['count'];
    
    if ($vehicle_count > 0) {
        $error = "No se puede eliminar el usuario porque tiene vehículos registrados.";
    } else {
        // Delete user
        $sql = "DELETE FROM usuario WHERE id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success = "Usuario eliminado correctamente.";
        } else {
            $error = "Error al eliminar el usuario: " . $conn->error;
        }
    }
}

// Get all users with client role
$sql = "SELECT * FROM usuario WHERE roles = 'cliente' ORDER BY nombre";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .btn i {
            margin-right: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            border-radius: 4px;
            overflow: hidden;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #333;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .actions {
            display: flex;
            gap: 5px;
        }
        .btn-edit {
            background-color: #2196F3;
        }
        .btn-edit:hover {
            background-color: #0b7dda;
        }
        .btn-delete {
            background-color: #f44336;
        }
        .btn-delete:hover {
            background-color: #da190b;
        }
        .btn-view {
            background-color: #ff9800;
        }
        .btn-view:hover {
            background-color: #e68a00;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        .alert-danger {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }
        .empty-table {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="header-actions">
            <h2><i class="fas fa-users"></i> Gestión de Clientes</h2>
            <a href="add_client.php" class="btn"><i class="fas fa-plus"></i> Agregar Cliente</a>
        </div>
        
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id_usuario']; ?></td>
                            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['telefono'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['direccion'] ?? 'N/A'); ?></td>
                            <td class="actions">
                                <a href="edit_client.php?id=<?php echo $row['id_usuario']; ?>" class="btn btn-edit" title="Editar"><i class="fas fa-edit"></i></a>
                                <a href="../mecanicos/view_client.php?id=<?php echo $row['id_usuario']; ?>" class="btn btn-view" title="Ver Diagnósticos"><i class="fas fa-clipboard-list"></i></a>
                                <a href="index.php?delete=<?php echo $row['id_usuario']; ?>" class="btn btn-delete" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar este cliente? Esta acción no se puede deshacer.')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty-table">No hay clientes registrados</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>