<?php
include '../barra.php';
include 'cnx.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}


$id = $_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario_id = $_POST['usuario_id'];
    $marca = $_POST['marca'];
    $modelo = $_POST['modelo'];
    $año = $_POST['año'];
    $vin = $_POST['vin'];

    $sql = "UPDATE vehiculos SET usuario_id = ?, marca = ?, modelo = ?, año = ?, vin = ? WHERE id_vehiculo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssi", $usuario_id, $marca, $modelo, $año, $vin, $id);

    if ($stmt->execute()) {
        header("Location: index.php");
        exit();
    } else {
        $error = "Error al actualizar el vehículo: " . $conn->error;
    }
} else {
    $sql = "SELECT * FROM vehiculos WHERE id_vehiculo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $vehicle = $result->fetch_assoc();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Editar Vehículo</title>
    <style>
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        form {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: auto;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background: #1877f2;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background: #166fe5;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="content">
        <h2>Editar Vehículo</h2>
        <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <div class="form-group">
                <label for="usuario_id">Usuario</label>
                <select id="usuario_id" name="usuario_id" required>
                    <?php
                    $user_sql = "SELECT id_usuario, nombre FROM usuario";
                    $user_result = $conn->query($user_sql);
                    while ($user = $user_result->fetch_assoc()) {
                        $selected = $user['id_usuario'] == $vehicle['usuario_id'] ? 'selected' : '';
                        echo "<option value='{$user['id_usuario']}' $selected>{$user['nombre']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="marca">Marca</label>
                <input type="text" id="marca" name="marca" value="<?php echo htmlspecialchars($vehicle['marca']); ?>" required>
            </div>
            <div class="form-group">
                <label for="modelo">Modelo</label>
                <input type="text" id="modelo" name="modelo" value="<?php echo htmlspecialchars($vehicle['modelo']); ?>" required>
            </div>
            <div class="form-group">
                <label for="año">Año</label>
                <input type="text" id="año" name="año" value="<?php echo htmlspecialchars($vehicle['año']); ?>" required>
            </div>
            <div class="form-group">
                <label for="vin">VIN</label>
                <input type="text" id="vin" name="vin" value="<?php echo htmlspecialchars($vehicle['vin']); ?>" required>
            </div>
            <button type="submit">Actualizar Vehículo</button>
        </form>
    </div>
</body>
</html>