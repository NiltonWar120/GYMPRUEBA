<?php
session_start();
require_once '../conexion.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

$usuario = $_SESSION['usuario'];

// PROCESAR ASIGNACIÓN DE HORARIO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['asignar_horario'])) {
    $dni_cliente = trim($_POST['dni_cliente']);
    $cod_horario = intval($_POST['cod_horario']);
    
    // Verificar que el cliente no tenga ya un horario asignado
    $sql_check = "SELECT * FROM DETALLE_CLIENTE_H WHERE DNI_Cliente = ? AND Estado = 1";
    $stmt_check = $conexion->prepare($sql_check);
    $stmt_check->bind_param("s", $dni_cliente);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'El cliente ya tiene un horario asignado']);
        exit;
    }
    
    // Insertar el horario
    $sql_insert = "INSERT INTO DETALLE_CLIENTE_H (Cod_Horario, DNI_Cliente, Estado) VALUES (?, ?, 1)";
    $stmt_insert = $conexion->prepare($sql_insert);
    $stmt_insert->bind_param("is", $cod_horario, $dni_cliente);
    
    if ($stmt_insert->execute()) {
        echo json_encode(['success' => true, 'message' => 'Horario asignado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al asignar horario: ' . $conexion->error]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Horarios - PRO FIT Gym</title>
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

    .horarios-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .horario-card {
        background: #2b1a0e;
        padding: 20px;
        border-radius: 10px;
        border: 1px solid #b8860b;
    }

    .cupo {
        font-size: 1.2em;
        font-weight: bold;
        margin: 10px 0;
    }

    .lleno {
        color: #ff4444;
    }

    .disponible {
        color: #44ff44;
    }

    .medio {
        color: #ffaa00;
    }

    /* Estilos del Modal */
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
        margin: 3% auto;
        padding: 30px;
        border: 2px solid #d4af37;
        border-radius: 15px;
        width: 80%;
        max-width: 800px;
        color: white;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5);
        max-height: 80vh;
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

    .modal-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .modal-table th {
        background: #d4af37;
        color: #1a1a1a;
        padding: 12px;
        text-align: left;
    }

    .modal-table td {
        padding: 12px;
        border-bottom: 1px solid #b8860b;
    }

    .horario-select-card {
        background: #2b1a0e;
        padding: 15px;
        margin: 10px 0;
        border-radius: 8px;
        border: 2px solid #b8860b;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .horario-select-card:hover {
        border-color: #d4af37;
        transform: translateY(-2px);
    }

    .alert {
        padding: 15px;
        border-radius: 8px;
        margin: 10px 0;
        font-weight: bold;
    }

    .alert-success {
        background: #4CAF50;
        color: white;
    }

    .alert-error {
        background: #f44336;
        color: white;
    }
    </style>
</head>

<body>
    <div class="header">
        <h1>🕐 Asignar Horarios - PRO FIT Gym</h1>
        <div>
            <span style="color: #1a1a1a; margin-right: 15px;">
                Recepcionista: <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong>
            </span>
            <a href="../dashboard.php" class="btn btn-back">← REGRESAR</a>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <h2>📅 Horarios Disponibles para Asignar</h2>
            <p style="color: #ccc; margin-bottom: 20px;">Selecciona un horario para asignar a un cliente</p>

            <div class="horarios-grid">
                <?php
                $sql_horarios = "SELECT Cod_Horario, Turno, Fecha FROM HORARIO WHERE Estado = 1 ORDER BY Cod_Horario ASC";
                $result_horarios = $conexion->query($sql_horarios);
                
                if ($result_horarios && $result_horarios->num_rows > 0) {
                    while ($horario_row = $result_horarios->fetch_assoc()) {
                        $cod_horario = $horario_row['Cod_Horario'];
                        $turno = $horario_row['Turno'];
                        
                        $sql_count = "SELECT COUNT(*) as total FROM DETALLE_CLIENTE_H WHERE Cod_Horario = ? AND Estado = 1";
                        $stmt_count = $conexion->prepare($sql_count);
                        $stmt_count->bind_param("i", $cod_horario);
                        $stmt_count->execute();
                        $result_count = $stmt_count->get_result();
                        $row_count = $result_count->fetch_assoc();
                        $cupo_actual = $row_count['total'];
                        $stmt_count->close();
                        
                        $disponible = 50 - $cupo_actual;
                        
                        if ($cupo_actual >= 0 && $cupo_actual <= 15) {
                            $color_clase = 'disponible';
                            $estado = '🟢 Aforo Bajo';
                        } elseif ($cupo_actual >= 16 && $cupo_actual <= 35) {
                            $color_clase = 'medio';
                            $estado = '🟡 Aforo Moderado';
                        } else {
                            $color_clase = 'lleno';
                            $estado = '🔴 Aforo Alto';
                        }
                        ?>
                <div class="horario-card">
                    <h3>⏰ <?php echo htmlspecialchars($turno); ?></h3>
                    <div class="cupo <?php echo $color_clase; ?>">
                        👥 <?php echo $cupo_actual; ?> / 50 personas
                    </div>
                    <div style="color: #ccc; margin: 10px 0;">
                        📊 <?php echo $estado; ?> - Disponible: <?php echo $disponible; ?> cupos
                    </div>
                    <div style="margin: 15px 0;">
                        <strong>Coach asignado:</strong><br>
                        <?php
    $sql_coach = "SELECT c.Nombre, c.Apellido 
                  FROM coach c 
                  INNER JOIN horario h ON c.cod_coach = h.Cod_Coach
                  WHERE h.Cod_Horario = ? AND c.estado = 1";
    $stmt_coach = $conexion->prepare($sql_coach);
    $stmt_coach->bind_param("i", $cod_horario);
    $stmt_coach->execute();
    $result_coach = $stmt_coach->get_result();

    if ($result_coach && $result_coach->num_rows > 0) {
        $row_coach = $result_coach->fetch_assoc();
        echo "💪 " . htmlspecialchars($row_coach['Nombre'] . " " . $row_coach['Apellido']);
    } else {
        echo "<span style='color:#888;'>Sin coach asignado</span>";
    }
    $stmt_coach->close();
    ?>
                    </div>

                    <div>
                        <button class="btn"
                            onclick="verListaClientes(<?php echo $cod_horario; ?>, '<?php echo htmlspecialchars($turno); ?>')">📋
                            Ver Lista</button>
                    </div>
                </div>
                <?php
                    }
                } else {
                    echo '<div style="text-align: center; color: #888; padding: 30px;">No hay horarios disponibles</div>';
                }
                ?>
            </div>
        </div>

        <div class="card">
            <h2>🔍 Buscar Cliente para Asignar Horario</h2>
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px; margin-top: 15px;">
                <input type="text" id="buscar_cliente_input" placeholder="🔍 Buscar por DNI o nombre del cliente..."
                    style="padding: 12px; background: #2b1a0e; border: 2px solid #b8860b; border-radius: 8px; color: white;">
                <button class="btn" onclick="buscarClienteSinHorario()">🔍 Buscar Cliente</button>
            </div>

            <div style="margin-top: 20px;">
                <h3>Clientes Sin Horario Asignado</h3>
                <div id="tabla_clientes_sin_horario">
                    <table style="width: 100%; border-collapse: collapse; margin-top: 15px; color: white;">
                        <thead>
                            <tr>
                                <th style="background: #d4af37; color: #1a1a1a; padding: 12px;">DNI</th>
                                <th style="background: #d4af37; color: #1a1a1a; padding: 12px;">Nombre</th>
                                <th style="background: #d4af37; color: #1a1a1a; padding: 12px;">Membresía</th>
                                <th style="background: #d4af37; color: #1a1a1a; padding: 12px;">Estado</th>
                                <th style="background: #d4af37; color: #1a1a1a; padding: 12px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql_sin_horario = "SELECT c.DNI, c.Nombre, tm.Nombre as Membresia, m.Fecha_Fin,
                                               DATEDIFF(m.Fecha_Fin, CURDATE()) as Dias_Restantes
                                               FROM CLIENTE c
                                               INNER JOIN MEMBRESIA m ON c.DNI = m.DNI_Cliente AND m.Estado = 1
                                               LEFT JOIN TIPO_MEMBRESIA tm ON m.Cod_Tipo_Membresia = tm.Cod_Tipo_Membresia
                                               LEFT JOIN DETALLE_CLIENTE_H dch ON c.DNI = dch.DNI_Cliente AND dch.Estado = 1
                                               WHERE c.Estado = 1 
                                               AND dch.DNI_Cliente IS NULL
                                               ORDER BY c.Nombre ASC";
                            
                            $result_sin_horario = $conexion->query($sql_sin_horario);
                            
                            if ($result_sin_horario && $result_sin_horario->num_rows > 0) {
                                while ($cliente = $result_sin_horario->fetch_assoc()) {
                                    $dias = $cliente['Dias_Restantes'];
                                    
                                    if ($dias < 0) {
                                        $estado_texto = '🔴 Vencido';
                                    } elseif ($dias <= 7) {
                                        $estado_texto = '🟡 ' . $dias . ' días';
                                    } else {
                                        $estado_texto = '🟢 Activo';
                                    }
                                    
                                    echo "<tr>";
                                    echo "<td style='padding: 10px; border-bottom: 1px solid #b8860b;'>" . htmlspecialchars($cliente['DNI']) . "</td>";
                                    echo "<td style='padding: 10px; border-bottom: 1px solid #b8860b;'>" . htmlspecialchars($cliente['Nombre']) . "</td>";
                                    echo "<td style='padding: 10px; border-bottom: 1px solid #b8860b;'>" . htmlspecialchars($cliente['Membresia']) . "</td>";
                                    echo "<td style='padding: 10px; border-bottom: 1px solid #b8860b;'>" . $estado_texto . "</td>";
                                    echo "<td style='padding: 10px; border-bottom: 1px solid #b8860b;'>";
                                   $nombre_seguro = addslashes($cliente['Nombre']);
echo "<button class='btn' onclick='abrirModalAsignar(\"" . $cliente['DNI'] . "\", \"" . $nombre_seguro . "\")'>🕐 Asignar Horario</button>";

                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' style='text-align: center; padding: 20px; color: #888;'>✅ Todos los clientes tienen horario asignado</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para mostrar lista de clientes -->
    <div id="modalListaClientes" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal('modalListaClientes')">&times;</span>
            <h2 id="modal-titulo">📋 Lista de Clientes</h2>
            <div id="modal-contenido">
                <p style="text-align: center; padding: 20px;">Cargando...</p>
            </div>
        </div>
    </div>

    <!-- Modal para asignar horario -->
    <div id="modalAsignarHorario" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal('modalAsignarHorario')">&times;</span>
            <h2>🕐 Asignar Horario</h2>
            <div id="mensaje-asignacion"></div>
            <p style="margin: 15px 0;">Cliente: <strong id="nombre-cliente-asignar"></strong></p>
            <input type="hidden" id="dni-cliente-asignar">

            <h3 style="margin: 20px 0;">Selecciona un horario:</h3>
            <div id="horarios-disponibles">
                <?php
                // Nueva consulta independiente para el modal
                $sql_horarios_modal = "SELECT Cod_Horario, Turno FROM HORARIO WHERE Estado = 1 ORDER BY Cod_Horario ASC";
                $result_horarios_modal = $conexion->query($sql_horarios_modal);
                
                if ($result_horarios_modal && $result_horarios_modal->num_rows > 0) {
                    while ($horario = $result_horarios_modal->fetch_assoc()) {
                        $cod = $horario['Cod_Horario'];
                        $turno = $horario['Turno'];
                        
                        // Obtener cupo actual
                        $sql_count2 = "SELECT COUNT(*) as total FROM DETALLE_CLIENTE_H WHERE Cod_Horario = ? AND Estado = 1";
                        $stmt_count2 = $conexion->prepare($sql_count2);
                        $stmt_count2->bind_param("i", $cod);
                        $stmt_count2->execute();
                        $result_count2 = $stmt_count2->get_result();
                        $cupo = $result_count2->fetch_assoc()['total'];
                        $disponible = 50 - $cupo;
                        $stmt_count2->close();
                        
                        // Obtener coach del horario
$sql_coach2 = "SELECT c.Nombre, c.Apellido 
               FROM coach c 
               INNER JOIN horario h ON c.cod_coach = h.Cod_Coach
               WHERE h.Cod_Horario = ? AND c.estado = 1";
$stmt_coach2 = $conexion->prepare($sql_coach2);
$stmt_coach2->bind_param("i", $cod);
$stmt_coach2->execute();
$result_coach2 = $stmt_coach2->get_result();
$texto_coach = "Sin coach asignado";
if ($result_coach2 && $result_coach2->num_rows > 0) {
    $row_coach2 = $result_coach2->fetch_assoc();
    $texto_coach = "Coach: " . $row_coach2['Nombre'] . " " . $row_coach2['Apellido'];
}
$stmt_coach2->close();

echo "<div class='horario-select-card' onclick='confirmarAsignacion($cod)'>";
echo "<strong>⏰ " . htmlspecialchars($turno) . "</strong><br>";
echo "<span style='color: #ccc;'>👥 $cupo / 50 personas - Disponibles: $disponible</span><br>";
echo "<span style='color: #d4af37;'>💪 " . htmlspecialchars($texto_coach) . "</span>";
echo "</div>";

                    }
                }
                ?>
            </div>
        </div>
    </div>

    <script>
    function buscarClienteSinHorario() {
        const busqueda = document.getElementById('buscar_cliente_input').value;
        const contenedor = document.getElementById('tabla_clientes_sin_horario');

        contenedor.innerHTML =
            '<div style="text-align: center; padding: 30px; color: #d4af37;"><h3>🔄 Buscando...</h3></div>';

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'buscar_clientes_ajax.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function() {
            if (xhr.status === 200) {
                contenedor.innerHTML = xhr.responseText;
            } else {
                contenedor.innerHTML =
                    '<div style="text-align: center; padding: 30px; color: red;">❌ Error al buscar</div>';
            }
        };

        xhr.send('busqueda=' + encodeURIComponent(busqueda));
    }

    document.getElementById('buscar_cliente_input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            buscarClienteSinHorario();
        }
    });

    function verListaClientes(codHorario, turno) {
        const modal = document.getElementById('modalListaClientes');
        const modalTitulo = document.getElementById('modal-titulo');
        const modalContenido = document.getElementById('modal-contenido');

        modalTitulo.innerHTML = '📋 Clientes en Horario: ' + turno;
        modalContenido.innerHTML =
            '<p style="text-align: center; padding: 20px; color: #d4af37;">🔄 Cargando lista...</p>';

        modal.style.display = 'block';

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'ver_lista_clientes.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function() {
            if (xhr.status === 200) {
                modalContenido.innerHTML = xhr.responseText;
            } else {
                modalContenido.innerHTML =
                    '<p style="text-align: center; padding: 20px; color: red;">❌ Error al cargar la lista</p>';
            }
        };

        xhr.send('cod_horario=' + codHorario);
    }

    function abrirModalAsignar(dni, nombre) {
        document.getElementById('dni-cliente-asignar').value = dni;
        document.getElementById('nombre-cliente-asignar').innerText = nombre;
        document.getElementById('mensaje-asignacion').innerHTML = '';
        document.getElementById('modalAsignarHorario').style.display = 'block';
    }

    function confirmarAsignacion(codHorario) {
        const dni = document.getElementById('dni-cliente-asignar').value;

        if (!confirm('¿Confirmar asignación de horario?')) {
            return;
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                const mensaje = document.getElementById('mensaje-asignacion');

                if (response.success) {
                    mensaje.innerHTML = '<div class="alert alert-success">✅ ' + response.message + '</div>';
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    mensaje.innerHTML = '<div class="alert alert-error">❌ ' + response.message + '</div>';
                }
            }
        };

        xhr.send('asignar_horario=1&dni_cliente=' + encodeURIComponent(dni) + '&cod_horario=' + codHorario);
    }

    function cerrarModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
    </script>
</body>

</html>