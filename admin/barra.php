<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <title>Panel de Administración</title>
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
            background: linear-gradient(to bottom, #333, #444);
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
        .admin-header {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid #555;
            background: #222;
        }
        .admin-header h2 {
            margin: 0;
            font-size: 24px;
            color: #fff;
        }
        .admin-header p {
            margin: 5px 0 0;
            font-size: 14px;
            color: #bbb;
        }
        .menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .menu li {
            padding: 15px 25px;
            border-bottom: 1px solid #555;
            transition: background 0.3s;
        }
        .menu li:hover {
            background: #555;
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
        .welcome-section {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="admin-header">
            <h2>Panel Admin</h2>
            <p>Bienvenido, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Administrador'); ?></p>
        </div>
        <ul class="menu">
            <li><a href="/taller/admin/admin.php"><i class="fas fa-tachometer-alt"></i> Inicio</a></li>
            <li><a href="/taller\admin/usuarios/index.php"><i class="fas fa-users"></i> Gestión de Usuarios</a></li>
            <li><a href="\taller\admin\mecanicos\index.php"><i class="fas fa-wrench"></i> Gestión de Mecánicos</a></li>
            <li><a href="/taller\admin/gesVehiculo/index.php"><i class="fas fa-car"></i> Gestión de Vehículos</a></li>
            <li><a href="\taller\admin\ordenes\index.php"><i class="fas fa-clipboard-list"></i> Órdenes de Trabajo</a></li>
            <li><a href="\taller\admin\repuestos\index.php"><i class="fas fa-cogs"></i> Inventario de Repuestos</a></li>
            <li><a href="\taller\admin\informes\index.php"><i class="fas fa-chart-bar"></i> Informes</a></li>
            <li><a href="\taller\admin\configuracion.php"><i class="fas fa-cog"></i> Configuración</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
        </ul>
    </div>
</body>
</html>
