<?php
session_start();
require_once '../conexion.php';


if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}


$usuario = $_SESSION['usuario'];


// PROCESAR BÚSQUEDA Y FILTROS
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';


// Construir consulta SQL simplificada
$sql = "SELECT c.DNI, c.Nombre, c.Telefono, c.Sexo, c.Direccion,
        m.Cod_Tipo_Membresia,
        m.Fecha_Fin, m.Fecha_Inicio,
        h.Turno,
        DATEDIFF(m.Fecha_Fin, CURDATE()) as Dias_Restantes
        FROM CLIENTE c
        LEFT JOIN MEMBRESIA m ON c.DNI = m.DNI_Cliente AND m.Estado = 1
        LEFT JOIN DETALLE_CLIENTE_H dch ON c.DNI = dch.DNI_Cliente AND dch.Estado = 1
        LEFT JOIN HORARIO h ON dch.Cod_Horario = h.Cod_Horario
        WHERE c.Estado = 1";


// Aplicar filtro de búsqueda por DNI o nombre
if (!empty($busqueda)) {
    $sql .= " AND (c.DNI LIKE ? OR c.Nombre LIKE ?)";
}


// Aplicar filtro de estado de membresía (CAMBIO: 10 días para por vencer)
if (!empty($filtro_estado)) {
    if ($filtro_estado == 'activo') {
        $sql .= " AND DATEDIFF(m.Fecha_Fin, CURDATE()) > 10";
    } elseif ($filtro_estado == 'por-vencer') {
        $sql .= " AND DATEDIFF(m.Fecha_Fin, CURDATE()) BETWEEN 1 AND 10";
    } elseif ($filtro_estado == 'vencido') {
        $sql .= " AND (DATEDIFF(m.Fecha_Fin, CURDATE()) < 0 OR m.Fecha_Fin IS NULL)";
    }
}


$sql .= " ORDER BY c.DNI DESC";


// Ejecutar consulta
if (!empty($busqueda)) {
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $busqueda_like = "%" . $busqueda . "%";
        $stmt->bind_param("ss", $busqueda_like, $busqueda_like);
        $stmt->execute();
        $resultado = $stmt->get_result();
    } else {
        die("Error en la consulta SQL: " . $conexion->error);
    }
} else {
    $resultado = $conexion->query($sql);
    if (!$resultado) {
        die("Error en la consulta SQL: " . $conexion->error);
    }
}


// Array con nombres de membresías
$tipos_membresia = array(
    1 => 'BLACK',
    2 => 'ESPECIAL',
    3 => 'MODOFIT',
    4 => 'PLATINIUM',
    5 => 'PREMIUM'
);

// Contar resultados antes de mostrar
$total_clientes = $resultado ? $resultado->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="es">


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Clientes - PRO FIT Gym</title>
    <link rel="icon" type="image/png" href="../fit.png">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }


    body {
        font-family: Arial, sans-serif;
        background: linear-gradient(135deg, #1a1a1a 0%, #2b1a0e 100%);
        min-height: 100vh;
        color: #d4af37;
    }


    .header {
        background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%);
        color: #1a1a1a;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }


    .container {
        padding: 30px;
        background: rgba(43, 26, 14, 0.8);
        min-height: calc(100vh - 80px);
    }


    .card {
        background: linear-gradient(135deg, #1a1a1a, #2b1a0e);
        padding: 25px;
        border-radius: 15px;
        margin-bottom: 25px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5);
        border: 2px solid #d4af37;
    }


    .btn {
        padding: 12px 20px;
        background: linear-gradient(135deg, #d4af37, #b8860b);
        color: #1a1a1a;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        margin: 5px;
        text-decoration: none;
        display: inline-block;
        font-size: 16px;
    }


    .btn:hover {
        background: linear-gradient(135deg, #b8860b, #d4af37);
        transform: scale(1.02);
    }


    .btn-back {
        background: linear-gradient(135deg, #666, #888);
        color: white;
    }


    .filtros {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 15px;
        margin-bottom: 20px;
        align-items: center;
    }


    input,
    select {
        padding: 12px;
        background: #2b1a0e;
        border: 2px solid #b8860b;
        border-radius: 8px;
        color: white;
        font-size: 16px;
    }


    input:focus,
    select:focus {
        border-color: #d4af37;
        outline: none;
    }


    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        color: white;
    }


    th,
    td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #b8860b;
    }


    th {
        background: #d4af37;
        color: #1a1a1a;
        font-weight: bold;
    }


    tr:hover {
        background: rgba(212, 175, 55, 0.1);
    }


    .activo {
        color: #44ff44;
        font-weight: bold;
    }


    .por-vencer {
        color: #ffaa00;
        font-weight: bold;
    }


    .vencido {
        color: #ff4444;
        font-weight: bold;
    }


    .total-clientes {
        background: #d4af37;
        color: #1a1a1a;
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 15px;
    }
    </style>
</head>


<body>
    <div class="header">
        <h1>🔍 Consultar Clientes - PRO FIT</h1>
        <div>
            <span style="color: #1a1a1a; margin-right: 15px;">
                Recepcionista: <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong>
            </span>
            <a href="../dashboard.php" class="btn btn-back">← REGRESAR</a>
        </div>
    </div>


    <div class="container">
        <div class="card">
            <h2>🔎 Búsqueda y Filtros de Clientes</h2>


            <form method="GET" action="">
                <div class="filtros">
                    <input type="text" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>"
                        placeholder="🔍 Buscar por DNI o nombre..." autocomplete="off">


                    <select name="estado">
                        <option value="">📋 Todos los estados</option>
                        <option value="activo" <?php echo $filtro_estado == 'activo' ? 'selected' : ''; ?>>
                            🟢 Activo (Más de 10 días)
                        </option>
                        <option value="por-vencer" <?php echo $filtro_estado == 'por-vencer' ? 'selected' : ''; ?>>
                            🟡 Por Vencer (1-10 días)
                        </option>
                        <option value="vencido" <?php echo $filtro_estado == 'vencido' ? 'selected' : ''; ?>>
                            🔴 Vencido
                        </option>
                    </select>


                    <button type="submit" class="btn">🔍 Buscar</button>
                </div>
            </form>

            <!-- Contador arriba de la tabla -->
            <div class="total-clientes">
                📊 Total de clientes: <strong><?php echo $total_clientes; ?></strong>
            </div>


            <table>
                <thead>
                    <tr>
                        <th>DNI</th>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Membresía</th>
                        <th>Horario</th>
                        <th>Vence</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($resultado && $resultado->num_rows > 0) {
                        // Resetear el puntero para recorrer los resultados
                        $resultado->data_seek(0);
                        
                        while ($fila = $resultado->fetch_assoc()) {
                            $dias = $fila['Dias_Restantes'];
                            
                            // Obtener nombre de membresía del array
                            $cod_tipo = $fila['Cod_Tipo_Membresia'];
                            $nombre_membresia = isset($tipos_membresia[$cod_tipo]) ? $tipos_membresia[$cod_tipo] : 'Sin membresía';
                            
                            // Determinar clase y texto de estado (CAMBIO: 10 días)
                            if ($dias === null || $dias < 0) {
                                $clase_estado = 'vencido';
                                $texto_estado = '🔴 Vencido';
                            } elseif ($dias <= 10) {
                                $clase_estado = 'por-vencer';
                                $texto_estado = '🟡 ' . $dias . ' días';
                            } else {
                                $clase_estado = 'activo';
                                $texto_estado = '🟢 ' . $dias . ' días';
                            }
                            
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($fila['DNI']) . "</td>";
                            echo "<td>" . htmlspecialchars($fila['Nombre']) . "</td>";
                            echo "<td>" . htmlspecialchars($fila['Telefono']) . "</td>";
                            echo "<td>" . htmlspecialchars($nombre_membresia) . "</td>";
                            echo "<td>" . htmlspecialchars($fila['Turno'] ?? 'Sin horario') . "</td>";
                            echo "<td>" . ($fila['Fecha_Fin'] ? date('d/m/Y', strtotime($fila['Fecha_Fin'])) : 'N/A') . "</td>";
                            echo "<td class='$clase_estado'>$texto_estado</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo '<tr><td colspan="7" style="text-align: center; color: #888; padding: 30px;">';
                        echo '❌ No se encontraron clientes';
                        echo '</td></tr>';
                    }
                    
                    if (isset($stmt)) {
                        $stmt->close();
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>


</html>
