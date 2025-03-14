<?php
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];

    $sql = "INSERT INTO clientes (nombre, telefono, email) VALUES ('$nombre', '$telefono', '$email')";
    if ($conn->query($sql) === TRUE) {
        echo "<p>Cliente registrado con éxito</p>";
    } else {
        echo "<p>Error al registrar cliente</p>";
    }
}
?>

<h2>Registrar Cliente</h2>
<form method="post">
    Nombre: <input type="text" name="nombre" required><br>
    Teléfono: <input type="text" name="telefono"><br>
    Email: <input type="email" name="email"><br>
    <button type="submit">Registrar</button>
</form>
