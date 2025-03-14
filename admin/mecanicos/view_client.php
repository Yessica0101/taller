<?php
include '../barra.php';
include 'cnx.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reporte = $_POST['reporte'];
    $repuestos = $_POST['repuestos'];

    // Save report and parts list to the database or process as needed
    // Example: $sql = "INSERT INTO reports (usuario_id, reporte, repuestos) VALUES (?, ?, ?)";
    // $stmt = $conn->prepare($sql);
    // $stmt->bind_param("iss", $id, $reporte, $repuestos);
    // $stmt->execute();
}

$sql = "SELECT * FROM usuario WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Ver Cliente</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(to right, #6a11cb, #2575fc);
            margin: 0;
            padding: 0;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
            color: #333;
        }
        form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            max-width: 600px;
            margin: auto;
        }
        h2 {
            text-align: center;
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
            font-size: 16px;
        }
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
            height: 120px;
            resize: none;
            font-size: 14px;
        }
        button {
            background: #6a11cb;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        button:hover {
            background: #2575fc;
        }
        button i {
            margin-right: 8px;
        }
        .error {
            color: #d32f2f;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="content">
        <h2><i class="fas fa-user"></i> Ver Cliente: <?php echo htmlspecialchars($client['nombre']); ?></h2>
        <form method="POST">
            <div class="form-group">
                <label for="reporte"><i class="fas fa-file-alt"></i> Reporte del Vehículo</label>
                <textarea id="reporte" name="reporte" required></textarea>
            </div>
            <div class="form-group">
                <label for="repuestos"><i class="fas fa-tools"></i> Lista de Repuestos Necesarios</label>
                <textarea id="repuestos" name="repuestos" required></textarea>
            </div>
            <button type="submit"><i class="fas fa-save"></i> Guardar Reporte</button>
        </form>
    </div>
</body>
</html>