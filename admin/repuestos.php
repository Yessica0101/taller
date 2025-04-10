<?php
include 'barra.php';
include 'mecanicos/cnx.php';

// Handle form submission for adding new parts
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $precio = $_POST['precio'];
        $stock = $_POST['stock'];
        
        $sql = "INSERT INTO repuestos (nombre, descripcion, precio, stock) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdi", $nombre, $descripcion, $precio, $stock);
        
        if ($stmt->execute()) {
            $success = "Repuesto agregado correctamente.";
        } else {
            $error = "Error al agregar el repuesto: " . $conn->error;
        }
    } elseif ($_POST['action'] == 'update' && isset($_POST['id_repuesto'])) {
        $id = $_POST['id_repuesto'];
        $stock = $_POST['stock'];
        
        $sql = "UPDATE repuestos SET stock = ? WHERE id_repuesto = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $stock, $id);
        
        if ($stmt->execute()) {
            $success = "Stock actualizado correctamente.";
        } else {
            $error = "Error al actualizar el stock: " . $conn->error;
        }
    }
}

// Get all parts from the database
$sql = "SELECT * FROM repuestos ORDER BY nombre";
$result = $conn->query($sql);

// Get usage statistics
$usage_sql = "SELECT r.id_repuesto, r.nombre, SUM(ru.cantidad) as total_usado 
              FROM repuestos r 
              LEFT JOIN repuestos_utilizados ru ON r.id_repuesto = ru.repuesto_id 
              GROUP BY r.id_repuesto, r.nombre
              ORDER BY total_usado DESC";
$usage_result = $conn->query($usage_sql);
$usage_data = [];
if ($usage_result) {
    while ($row = $usage_result->fetch_assoc()) {
        $usage_data[$row['id_repuesto']] = $row['total_usado'] ?? 0;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Inventario de Repuestos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        .dashboard {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            display: flex;
            align-items: center;
        }
        .card-icon {
            font-size: 2.5rem;
            margin-right: 20px;
            color: #1565c0;
        }
        .card-content h3 {
            margin: 0;
            font-size: 1.2rem;
            color: #555;
        }
        .card-content p {
            margin: 5px 0 0;
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
        }
        .inventory-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        .inventory-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .add-part-form {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            color: #333;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .stock-low {
            color: #dc3545;
            font-weight: bold;
        }
        .stock-medium {
            color: #fd7e14;
        }
        .stock-good {
            color: #28a745;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        button {
            background: #1565c0;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background: #0d47a1;
        }
        .update-stock-form {
            display: flex;
            align-items: center;
        }
        .update-stock-form input {
            width: 80px;
            margin-right: 10px;
        }
        .update-stock-form button {
            padding: 8px 12px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        h2 i {
            margin-right: 10px;
            color: #1565c0;
        }
    </style>
</head>
<body>
    <div class="content">
        <h2><i class="fas fa-cogs"></i> Inventario de Repuestos</h2>
        
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="dashboard">
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="card-content">
                    <h3>Total de Repuestos</h3>
                    <p><?php echo $result->num_rows; ?></p>
                </div>
            </div>
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="card-content">
                    <h3>Repuestos con Stock Bajo</h3>
                    <?php
                    $low_stock_sql = "SELECT COUNT(*) as count FROM repuestos WHERE stock < 5";
                    $low_stock_result = $conn->query($low_stock_sql);
                    $low_stock_count = $low_stock_result->fetch_assoc()['count'];
                    ?>
                    <p><?php echo $low_stock_count; ?></p>
                </div>
            </div>
        </div>
        
        <div class="inventory-section">
            <div class="inventory-table">
                <h3>Lista de Repuestos</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Uso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id_repuesto']; ?></td>
                                    <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                                    <td>$<?php echo number_format($row['precio'], 2); ?></td>
                                    <td class="<?php 
                                        if ($row['stock'] < 5) echo 'stock-low';
                                        elseif ($row['stock'] < 10) echo 'stock-medium';
                                        else echo 'stock-good';
                                    ?>">
                                        <?php echo $row['stock']; ?>
                                    </td>
                                    <td><?php echo $usage_data[$row['id_repuesto']] ?? 0; ?> unidades</td>
                                    <td>
                                        <form class="update-stock-form" method="POST">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="id_repuesto" value="<?php echo $row['id_repuesto']; ?>">
                                            <input type="number" name="stock" value="<?php echo $row['stock']; ?>" min="0">
                                            <button type="submit"><i class="fas fa-sync-alt"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">No hay repuestos registrados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="add-part-form">
                <h3>Agregar Nuevo Repuesto</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="nombre"><i class="fas fa-tag"></i> Nombre</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion"><i class="fas fa-align-left"></i> Descripción</label>
                        <textarea id="descripcion" name="descripcion"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="precio"><i class="fas fa-dollar-sign"></i> Precio</label>
                        <input type="number" id="precio" name="precio" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock"><i class="fas fa-cubes"></i> Stock Inicial</label>
                        <input type="number" id="stock" name="stock" min="0" required>
                    </div>
                    
                    <button type="submit"><i class="fas fa-plus"></i> Agregar Repuesto</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>