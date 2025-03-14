
<?php
include '../barra.php';
include 'cnx.php';

// Fetch vehicles from the database
$sql = "SELECT v.id_vehiculo, v.marca, v.modelo, v.año, v.vin, u.nombre AS usuario 
        FROM vehiculos v 
        JOIN usuario u ON v.usuario_id = u.id_usuario";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Gestión de Vehículos</title>
    <style>
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        .action-buttons a {
            margin-right: 10px;
            text-decoration: none;
            color: #1877f2;
        }
        .action-buttons a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="content">
        <h2>Gestión de Vehículos</h2>
        <a href="add.php" style="color: #1877f2;">Agregar Vehículo</a>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Año</th>
                    <th>VIN</th>
                    <th>Usuario</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id_vehiculo']; ?></td>
                            <td><?php echo $row['marca']; ?></td>
                            <td><?php echo $row['modelo']; ?></td>
                            <td><?php echo $row['año']; ?></td>
                            <td><?php echo $row['vin']; ?></td>
                            <td><?php echo $row['usuario']; ?></td>
                            <td class="action-buttons">
                                <a href="edit.php?id=<?php echo $row['id_vehiculo']; ?>">Editar</a>
                                <a href="delete.php?id=<?php echo $row['id_vehiculo']; ?>" onclick="return confirm('¿Estás seguro de que deseas eliminar este vehículo?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">No se encontraron vehículos.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>