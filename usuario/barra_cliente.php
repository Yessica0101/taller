<?php
session_start();

// For testing purposes, if usuario_id is not set, set it to 1 (or another valid ID)
// Remove this in production and implement proper login
if (!isset($_SESSION['usuario_id'])) {
    // This is just for testing - in production, redirect to login
    $_SESSION['usuario_id'] = 1; // Use an ID that exists in your database
}

$usuario_id = $_SESSION['usuario_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Cliente</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background: #f4f4f4;
        }
        .sidebar {
            width: 250px;
            background: linear-gradient(to bottom, #1e88e5, #1565c0);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            color: white;
            padding-top: 20px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        .client-header {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid #4b9ffc;
            background: #0d47a1;
        }
        .client-header h2 {
            margin: 0;
            font-size: 24px;
            color: #fff;
        }
        .client-header p {
            margin: 5px 0 0;
            font-size: 14px;
            color: #e3f2fd;
        }
        .menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .menu li {
            padding: 15px 25px;
            border-bottom: 1px solid #4b9ffc;
            transition: background 0.3s;
        }
        .menu li:hover {
            background: #1976d2;
        }
        .menu a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        .menu i {
            margin-right: 15px;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="client-header">
            <h2>Panel Cliente</h2>
            <p>Bienvenido, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Cliente'); ?></p>
        </div>
        <ul class="menu">
            <li><a href="cliente.php"><i class="fas fa-home"></i> Inicio</a></li>
            <li><a href="mis_vehiculos.php"><i class="fas fa-car"></i> Mis Vehículos</a></li>
            <li><a href="mis_diagnosticos.php"><i class="fas fa-clipboard-check"></i> Mis Diagnósticos</a></li>
            <li><a href="historial.php"><i class="fas fa-history"></i> Historial de Servicios</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
        </ul>
    </div>
</body>
</html>