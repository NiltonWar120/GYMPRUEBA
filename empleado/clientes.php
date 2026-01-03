<?php
session_start();
require_once '../conexion.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$success = "";
$error = "";

// PROCESAR REGISTRO DE CLIENTE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dni'])) {
    $dni = trim($_POST['dni']);
    $nombre = trim($_POST['nombre']);
    $sexo = trim($_POST['sexo']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $tipo_membresia = intval($_POST['tipo_membresia']);
    $metodo_pago = trim($_POST['metodo_pago']);
    $horario = !empty($_POST['horario']) ? intval($_POST['horario']) : 0;

    if (empty($dni) || empty($nombre)) {
        $error = "DNI y Nombre son obligatorios";
    } elseif (!is_numeric($dni) || strlen($dni) != 8) {
        $error = "El DNI debe tener 8 dígitos numéricos";
    } else {
        $sql_check = "SELECT DNI FROM CLIENTE WHERE DNI = ?";
        $stmt_check = $conexion->prepare($sql_check);
        if ($stmt_check) {
            $stmt_check->bind_param("s", $dni);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            
            if ($result_check->num_rows > 0) {
                $error = "El DNI $dni ya está registrado";
            } else {
                $conexion->autocommit(FALSE);
                $todo_ok = true;
                
                // 1. Insertar en CLIENTE
                $sql_cliente = "INSERT INTO CLIENTE (DNI, Nombre, Sexo, Telefono, Direccion, Estado) VALUES (?, ?, ?, ?, ?, 1)";
                $stmt_cliente = $conexion->prepare($sql_cliente);
                if ($stmt_cliente) {
                    $stmt_cliente->bind_param("sssss", $dni, $nombre, $sexo, $telefono, $direccion);
                    if (!$stmt_cliente->execute()) {
                        $error = "Error al insertar cliente: " . $conexion->error;
                        $todo_ok = false;
                    }
                }

                if ($todo_ok) {
                    // 2. Configurar datos 
                    $fecha_inicio = date('Y-m-d'); 
                    $fecha_fin = date('Y-m-d', strtotime($fecha_inicio . ' +29 days'));
                    
                    // Obtener precio real desde la base de datos (respetando ofertas)
                    $sql_precio = "SELECT 
                        CASE 
                            WHEN tiene_oferta = 1 THEN precio_oferta 
                            ELSE precio_base 
                        END as precio_final 
                    FROM tipo_membresia 
                    WHERE Cod_Tipo_Membresia = ? AND Estado = 1";

                    $stmt_precio = $conexion->prepare($sql_precio);
                    $precio = 50.00; // Precio por defecto

                    if ($stmt_precio) {
                        $stmt_precio->bind_param("i", $tipo_membresia);
                        $stmt_precio->execute();
                        $result_precio = $stmt_precio->get_result();

                        if ($result_precio->num_rows > 0) {
                            $row_precio = $result_precio->fetch_assoc();
                            $precio = $row_precio['precio_final'];
                        }
                    }

                    $cod_pago_map = array('Efectivo' => 1, 'Yape' => 2, 'Transferencia' => 3, 'Tarjeta' => 4);
                    $cod_pago = isset($cod_pago_map[$metodo_pago]) ? $cod_pago_map[$metodo_pago] : 1;

                    // 3. SOLUCIÓN PARA MEMBRESIA - Obtener próximo ID
                    $sql_max_id = "SELECT COALESCE(MAX(Cod_Membresia), 0) + 1 as nuevo_id FROM MEMBRESIA";
                    $result_max = $conexion->query($sql_max_id);
                    $row = $result_max->fetch_assoc();
                    $nuevo_id = $row['nuevo_id'];

                    $sql_membresia = "INSERT INTO MEMBRESIA (Cod_Membresia, Fecha_Inicio, Fecha_Fin, Precio, DNI_Cliente, Cod_Pago, Cod_Tipo_Membresia, Estado) 
                        VALUES (?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), ?, ?, ?, ?, 1)";
                    $stmt_membresia = $conexion->prepare($sql_membresia);
                    if ($stmt_membresia) {
                        $stmt_membresia->bind_param("idsii", $nuevo_id, $precio, $dni, $cod_pago, $tipo_membresia);
                        if (!$stmt_membresia->execute()) {
                            $error = "Error al insertar membresía: " . $conexion->error;
                            $todo_ok = false;
                        }
                    }
                }

                if ($todo_ok && !empty($horario) && $horario > 0) {
                    // 4. Verificar/crear horario (SOLO SI SE SELECCIONÓ)
                    $sql_check_horario = "SELECT Cod_Horario FROM HORARIO WHERE Cod_Horario = ?";
                    $stmt_check_horario = $conexion->prepare($sql_check_horario);
                    $horario_existe = false;
                    
                    if ($stmt_check_horario) {
                        $stmt_check_horario->bind_param("i", $horario);
                        $stmt_check_horario->execute();
                        $result_horario = $stmt_check_horario->get_result();
                        $horario_existe = ($result_horario->num_rows > 0);
                    }

                    if (!$horario_existe) {
                        $sql_insert_horario = "INSERT INTO HORARIO (Cod_Horario, Turno, Fecha, Estado) VALUES (?, ?, CURDATE(), 1)";
                        $stmt_insert_horario = $conexion->prepare($sql_insert_horario);
                        $turno = "Horario " . $horario;
                        if ($stmt_insert_horario) {
                            $stmt_insert_horario->bind_param("is", $horario, $turno);
                            $stmt_insert_horario->execute();
                        }
                    }

                    // 5. Insertar en DETALLE_CLIENTE_H
                    $sql_detalle = "INSERT INTO DETALLE_CLIENTE_H (Cod_Horario, DNI_Cliente, Estado) VALUES (?, ?, 1)";
                    $stmt_detalle = $conexion->prepare($sql_detalle);
                    if ($stmt_detalle) {
                        $stmt_detalle->bind_param("is", $horario, $dni);
                        if (!$stmt_detalle->execute()) {
                            $error = "Error al insertar detalle horario: " . $conexion->error;
                            $todo_ok = false;
                        }
                    }
                }

                if ($todo_ok) {
                    $conexion->commit();
                    $success = "Cliente registrado exitosamente (DNI: $dni)";
                } else {
                    $conexion->rollback();
                }
                
                $conexion->autocommit(TRUE);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REGISTRO CLIENTE - PRO FIT Gym</title>
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

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
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
        transition: all 0.3s ease;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(212, 175, 55, 0.4);
    }

    .btn-back {
        background: linear-gradient(135deg, #666, #888);
        color: white;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    label {
        display: block;
        margin-bottom: 5px;
        color: #d4af37;
        font-weight: bold;
    }

    input,
    select {
        width: 100%;
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
        box-shadow: 0 0 10px rgba(212, 175, 55, 0.5);
    }

    .alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: bold;
        transition: opacity 0.5s ease;
    }

    .alert-success {
        background: #24d24dff;
        color: #e5f5e8ff;
        border: none;
    }

    .alert-error {
        background: #da1a2aff;
        color: #e5f5e8ff;
        border: none;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        color: white;
    }

    th,
    td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #b8860b;
    }

    th {
        background: #d4af37;
        color: #1a1a1a;
    }

    /* Modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.8);
        overflow: auto;
    }

    .modal-content {
        background: linear-gradient(135deg, #1a1a1a, #2b1a0e);
        margin: 2% auto;
        padding: 30px;
        border: 2px solid #d4af37;
        border-radius: 15px;
        width: 90%;
        max-width: 800px;
        color: white;
        max-height: 90vh;
        overflow-y: auto;
    }

    .close {
        color: #d4af37;
        float: right;
        font-size: 35px;
        font-weight: bold;
        cursor: pointer;
    }

    .close:hover {
        color: #fff;
    }

    .full-width {
        grid-column: span 2;
    }
    </style>
</head>

<body>
    <div class="header">
        <h1>🏋️ Registrar Cliente - PRO FIT Gym</h1>
        <div>
            <span style="color: #1a1a1a; margin-right: 15px;">
                Recepcionista: <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong>
            </span>
            <a href="../dashboard.php" class="btn btn-back">← REGRESAR</a>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($success)): ?>
        <div class="alert alert-success" id="alertSuccess"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
        <div class="alert alert-error" id="alertError"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>📋 Todos los Clientes Registrados</h2>
                <button class="btn" onclick="abrirModalRegistrar()">➕ Registrar Cliente</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>DNI</th>
                        <th>Nombre</th>
                        <th>Sexo</th>
                        <th>Teléfono</th>
                        <th>Membresía</th>
                        <th>Horario</th>
                        <th>Vence</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Consulta con JOIN para traer membresía y horarios
                    $sql_clientes = "SELECT 
                        c.DNI,
                        c.Nombre, 
                        c.Sexo, 
                        c.Telefono, 
                        c.Direccion,
                        c.Estado,
                        tm.Nombre as Tipo_Membresia,
                        m.Fecha_Inicio,
                        m.Fecha_Fin,
                        m.Precio,
                        h.Turno,
                        DATEDIFF(m.Fecha_Fin, CURDATE()) as Dias_Restantes
                    FROM CLIENTE c
                    LEFT JOIN MEMBRESIA m ON c.DNI = m.DNI_Cliente AND m.Estado = 1
                    LEFT JOIN TIPO_MEMBRESIA tm ON m.Cod_Tipo_Membresia = tm.Cod_Tipo_Membresia
                    LEFT JOIN DETALLE_CLIENTE_H dch ON c.DNI = dch.DNI_Cliente AND dch.Estado = 1
                    LEFT JOIN HORARIO h ON dch.Cod_Horario = h.Cod_Horario
                    WHERE c.Estado = 1
                    ORDER BY c.DNI DESC";
                    
                    $resultado = $conexion->query($sql_clientes);
                    
                    if (!$resultado) {
                        echo '<tr><td colspan="8" style="text-align: center; color: red;">ERROR EN CONSULTA: ' . htmlspecialchars($conexion->error) . '</td></tr>';
                    } elseif ($resultado->num_rows > 0) {
                        while ($fila = $resultado->fetch_assoc()) {
                            $dias = $fila['Dias_Restantes'];
                            
                            if ($dias === null) {
                                $estado_texto = '⚪ Sin membresía';
                                $estado_color = '#888';
                            } elseif ($dias < 0) {
                                $estado_texto = '🔴 Vencido';
                                $estado_color = '#ff4444';
                            } elseif ($dias <= 7) {
                                $estado_texto = '🟡 ' . $dias . ' días';
                                $estado_color = '#ffaa00';
                            } else {
                                $estado_texto = '🟢 ' . $dias . ' días';
                                $estado_color = '#44ff44';
                            }
                            
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($fila['DNI']) . "</td>";
                            echo "<td>" . htmlspecialchars($fila['Nombre']) . "</td>";
                            echo "<td>" . htmlspecialchars($fila['Sexo']) . "</td>";
                            echo "<td>" . htmlspecialchars($fila['Telefono']) . "</td>";
                            echo "<td>" . htmlspecialchars($fila['Tipo_Membresia'] ?? 'Sin membresía') . "</td>";
                            echo "<td>" . htmlspecialchars($fila['Turno'] ?? 'Sin horario') . "</td>";
                            echo "<td>" . ($fila['Fecha_Fin'] ? date('d/m/Y', strtotime($fila['Fecha_Fin'])) : 'N/A') . "</td>";
                            echo "<td style='color: $estado_color; font-weight: bold;'>$estado_texto</td>";
                            echo "</tr>";
                        }
                        $resultado->free();
                    } else {
                        echo '<tr><td colspan="8" style="text-align: center; color: #888;">No hay clientes registrados</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Registrar Cliente -->
    <div id="modalRegistrar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal('modalRegistrar')">&times;</span>
            <h2>📝 Registrar Nuevo Cliente</h2>

            <form method="POST" style="margin-top: 20px;">
                <div class="form-grid">
                    <div class="form-group">
                        <label>👤 DNI del Cliente *</label>
                        <input type="text" name="dni" autocomplete="off" maxlength="8" placeholder="Ej: 71234567"
                            required pattern="[0-9]{8}" title="8 dígitos numéricos">
                    </div>

                    <div class="form-group">
                        <label>👤 Nombre Completo *</label>
                        <input type="text" name="nombre" autocomplete="off" placeholder="Ej: Juan Pérez García"
                            required>
                    </div>

                    <div class="form-group">
                        <label>📞 Celular *</label>
                        <input type="tel" name="telefono" maxlength="9" placeholder="Ej: 987654321" required
                            pattern="[0-9]{9}" title="9 dígitos numéricos">
                    </div>

                    <div class="form-group">
                        <label>🏠 Dirección</label>
                        <input type="text" name="direccion" autocomplete="off" placeholder="Ej: Av. Principal 123">
                    </div>

                    <div class="form-group">
                        <label>⚤ Sexo *</label>
                        <select name="sexo" required>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>🎫 Tipo de Membresía *</label>
                        <select name="tipo_membresia" required style="color: white;">
                            <?php
                            $sql_membresias = "SELECT 
                                Cod_Tipo_Membresia, 
                                Nombre, 
                                precio_base, 
                                precio_oferta, 
                                tiene_oferta, 
                                porcentaje_descuento 
                            FROM tipo_membresia 
                            WHERE Estado = 1 
                            ORDER BY Cod_Tipo_Membresia ASC";
                            
                            $result_membresias = $conexion->query($sql_membresias);
                            
                            if ($result_membresias && $result_membresias->num_rows > 0) {
                                while ($mem = $result_membresias->fetch_assoc()) {
                                    $cod = $mem['Cod_Tipo_Membresia'];
                                    $nombre = $mem['Nombre'];
                                    $precio_base = $mem['precio_base'];
                                    $precio_oferta = $mem['precio_oferta'];
                                    $tiene_oferta = $mem['tiene_oferta'];
                                    $porcentaje = $mem['porcentaje_descuento'];
                                    
                                    $precio_final = $tiene_oferta ? $precio_oferta : $precio_base;
                                    
                                    if ($tiene_oferta && $porcentaje > 0) {
                                        $texto = $nombre . ' - 🔥 ¡OFERTA! S/ ' . number_format($precio_final, 2) . 
                                                 ' -' . number_format($porcentaje, 0) . '%';
                                        echo '<option value="' . $cod . '" style="background: #ff4444; font-weight: bold;">';
                                    } else {
                                        $texto = $nombre . ' - S/ ' . number_format($precio_final, 2);
                                        echo '<option value="' . $cod . '">';
                                    }
                                    
                                    echo htmlspecialchars($texto);
                                    echo '</option>';
                                }
                                $result_membresias->free();
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>💳 Método de Pago *</label>
                        <select name="metodo_pago" required>
                            <option value="Efectivo">Efectivo</option>
                            <option value="Yape">Yape</option>
                            <option value="Transferencia">Transferencia</option>
                            <option value="Tarjeta">Tarjeta</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>🕐 Horario Preferido (Opcional)</label>
                        <select name="horario">
                            <option value="">Sin horario asignado</option>
                            <?php
                            $sql_horarios = "SELECT Cod_Horario, Turno FROM HORARIO WHERE Estado = 1 ORDER BY Cod_Horario ASC";
                            $result_horarios = $conexion->query($sql_horarios);
                            
                            if ($result_horarios && $result_horarios->num_rows > 0) {
                                while ($horario_row = $result_horarios->fetch_assoc()) {
                                    echo '<option value="' . $horario_row['Cod_Horario'] . '">';
                                    echo htmlspecialchars($horario_row['Turno']);
                                    echo '</option>';
                                }
                                $result_horarios->free();
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="full-width" style="margin-top: 20px;">
                    <button type="submit" class="btn" style="width: 100%; padding: 15px; font-size: 18px;">
                        ✅ REGISTRAR CLIENTE
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function abrirModalRegistrar() {
        document.getElementById('modalRegistrar').style.display = 'block';
    }

    function cerrarModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }

    // Auto-ocultar mensajes de éxito
    setTimeout(function() {
        var msgSuccess = document.getElementById('alertSuccess');
        if (msgSuccess) {
            msgSuccess.style.opacity = '0';
            setTimeout(function() { msgSuccess.remove(); }, 500);
        }
    }, 3000);

    // Auto-ocultar mensajes de error
    setTimeout(function() {
        var msgError = document.getElementById('alertError');
        if (msgError) {
            msgError.style.opacity = '0';
            setTimeout(function() { msgError.remove(); }, 500);
        }
    }, 5000);
    </script>
</body>

</html>
