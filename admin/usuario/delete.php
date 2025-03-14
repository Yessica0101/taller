<?php
include '../barra.php';
include 'cnx.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}


$id = $_GET['id'];

$sql = "DELETE FROM vehiculos WHERE id_vehiculo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: index.php");
    exit();
} else {
    $error = "Error al eliminar el vehículo: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Eliminar Vehículo</title>
    <style>
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="content">
        <h2>Eliminar Vehículo</h2>
        <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
    </div>
</body>
</html>