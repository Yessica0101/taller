<?php
include '../barra.php';
include 'cnx.php';


// Fetch clients from the database
$sql = "SELECT id_usuario, nombre, email FROM usuario WHERE roles = 'cliente'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Gestión de Mecánicos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .content {
            margin-left: 250px;
            padding: 20px;
            background: #f0f2f5;
        }
        h2 {
            text-align: center;
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        h2 i {
            margin-right: 10px;
            color: #1877f2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 15px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #1877f2;
            color: white;
            font-weight: bold;
        }
        .action-buttons a {
            margin-right: 10px;
            text-decoration: none;
            color: #1877f2;
            display: inline-flex;
            align-items: center;
        }
        .action-buttons a:hover {
            text-decoration: underline;
        }
        .action-buttons i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="content">
        <h2><i class="fas fa-users"></i> Gestión de Clientes</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id_usuario']; ?></td>
                            <td><?php echo $row['nombre']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td class="action-buttons">
                                <a href="view_client.php?id=<?php echo $row['id_usuario']; ?>"><i class="fas fa-eye"></i> Ver Cliente</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No se encontraron clientes.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>