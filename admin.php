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
</head>
<body>
    <h2>Bienvenido, Administrador</h2>
    <ul>
        <li><a href="registrar_cliente.php">Registrar Cliente</a></li>
        <li><a href="informe.php">Generar Informe de Vehículo</a></li>
        <li><a href="repuestos.php">Ver Repuestos Disponibles</a></li>
        <li><a href="logout.php">Cerrar Sesión</a></li>
    </ul>
</body>
</html>
