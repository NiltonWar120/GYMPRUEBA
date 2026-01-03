<?php
session_start();
require_once '../conexion.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['cod_cargo'] != 1) {
    header('Location: ../login.php');
    exit;
}

$usuario = $_SESSION['usuario'];

// Consultar membresías con información de clientes
$sql_membresias = "SELECT 
                    c.DNI,
                    c.Nombre,
                    tm.Nombre as Tipo_Membresia,
                    m.Precio,
                    m.Fecha_Inicio,
                    m.Fecha_Fin,
                    DATEDIFF(m.Fecha_Fin, CURDATE()) as Dias_Restantes,
                    m.Estado
                FROM MEMBRESIA m
                INNER JOIN CLIENTE c ON m.DNI_Cliente = c.DNI
                INNER JOIN TIPO_MEMBRESIA tm ON m.Cod_Tipo_Membresia = tm.Cod_Tipo_Membresia
                WHERE m.Estado = 1
                ORDER BY m.Fecha_Fin ASC";

$result_membresias = $conexion->query($sql_membresias);
$membresias_data = [];
if ($result_membresias && $result_membresias->num_rows > 0) {
    $membresias_data = $result_membresias->fetch_all(MYSQLI_ASSOC);
}

// Calcular estadísticas
$total_activas = 0;
$total_por_vencer = 0;
$total_vencidas = 0;
$ingreso_mensual = 0;

foreach ($membresias_data as $membresia) {
    $dias = $membresia['Dias_Restantes'];
    
    if ($dias < 0) {
        $total_vencidas++;
    } elseif ($dias <= 10) {
        $total_por_vencer++;
        $ingreso_mensual += $membresia['Precio'];
    } else {
        $total_activas++;
        $ingreso_mensual += $membresia['Precio'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento de Membresías - PRO FIT Gym</title>
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
        padding: 10px 20px;
        background: linear-gradient(135deg, #d4af37, #b8860b);
        color: #1a1a1a;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        margin: 5px;
        text-decoration: none;
        display: inline-block;
    }

    .btn-back {
        background: linear-gradient(135deg, #666, #888);
        color: white;
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
    }

    .vencido {
        color: #ff4444;
        font-weight: bold;
    }

    .proximo {
        color: #ffaa00;
        font-weight: bold;
    }

    .activo {
        color: #44ff44;
        font-weight: bold;
    }
    </style>
</head>

<body>
    <div class="header">
        <h1>📊 Seguimiento de Membresías - PRO FIT Gym</h1>
        <div>
            <a href="../dashboard.php" class="btn btn-back">← REGRESAR</a>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <h2>📋 Listado de Membresías Activas</h2>

            <table>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>DNI</th>
                        <th>Tipo Membresía</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Días Restantes</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($membresias_data)): ?>
                    <?php foreach ($membresias_data as $membresia): ?>
                    <?php
                            $dias = $membresia['Dias_Restantes'];
                            
                            // Determinar clase y estado
                            if ($dias < 0) {
                                $clase = 'vencido';
                                $estado_texto = '🔴 Vencida';
                                $dias_texto = abs($dias) . ' días vencido';
                            } elseif ($dias <= 10) {
                                $clase = 'proximo';  // Color ámbar
                                $estado_texto = '🟢 Activa';  // Pero estado es Activa
                                $dias_texto = $dias . ' días';
                            } else {
                                $clase = 'activo';
                                $estado_texto = '🟢 Activa';
                                $dias_texto = $dias . ' días';
                            }
                            ?>
                    <tr>
                        <td><?php echo htmlspecialchars($membresia['Nombre']); ?></td>
                        <td><?php echo htmlspecialchars($membresia['DNI']); ?></td>
                        <td><?php echo htmlspecialchars($membresia['Tipo_Membresia']); ?> - S/
                            <?php echo number_format($membresia['Precio'], 2); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($membresia['Fecha_Inicio'])); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($membresia['Fecha_Fin'])); ?></td>
                        <td class="<?php echo $clase; ?>"><?php echo $dias_texto; ?></td>
                        <td class="<?php echo $clase; ?>"><?php echo $estado_texto; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #888; padding: 20px;">
                            No hay membresías registradas
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>📈 Resumen de Membresías</h2>
            <div
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; text-align: center;">
                <div style="background: #2b1a0e; padding: 20px; border-radius: 10px; border: 2px solid #44ff44;">
                    <h3>🟢 Activas</h3>
                    <div style="font-size: 2em; color: #44ff44;"><?php echo $total_activas; ?></div>
                </div>
                <div style="background: #2b1a0e; padding: 20px; border-radius: 10px; border: 2px solid #ffaa00;">
                    <h3>🟡 Por Vencer </h3>
                    <div style="font-size: 2em; color: #ffaa00;"><?php echo $total_por_vencer; ?></div>
                </div>
                <div style="background: #2b1a0e; padding: 20px; border-radius: 10px; border: 2px solid #ff4444;">
                    <h3>🔴 Vencidas</h3>
                    <div style="font-size: 2em; color: #ff4444;"><?php echo $total_vencidas; ?></div>
                </div>
                <div style="background: #2b1a0e; padding: 20px; border-radius: 10px; border: 2px solid #d4af37;">
                    <h3>💰 Ingreso Mensual</h3>
                    <div style="font-size: 1.5em; color: #d4af37;">S/ <?php echo number_format($ingreso_mensual, 2); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>