<?php
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_vehiculo = $_POST['id_vehiculo'];
    $informe = $_POST['informe'];

    $sql = "UPDATE vehiculos SET informe='$informe' WHERE id_vehiculo='$id_vehiculo'";
    if ($conn->query($sql) === TRUE) {
        echo "<p>Informe registrado con éxito</p>";
    } else {
        echo "<p>Error al guardar informe</p>";
    }
}
?>

<h2>Generar Informe de Vehículo</h2>
<form method="post">
    ID Vehículo: <input type="text" name="id_vehiculo" required><br>
    Informe: <textarea name="informe" required></textarea><br>
    <button type="submit">Guardar Informe</button>
</form>
