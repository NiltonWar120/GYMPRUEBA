<?php
session_start();
require_once '../conexion.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['cod_cargo'] != 1) {
    header('Location: ../login.php');
    exit;
}

$usuario = $_SESSION['usuario'];

// PROCESAR REGISTRO DE NUEVO COACH
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar_coach'])) {
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $dni = trim($_POST['dni']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $especialidad = trim($_POST['especialidad']);
    $sueldo = floatval($_POST['sueldo']);
    $fecha_contrato = $_POST['fecha_contrato'];
    $horarios_seleccionados = isset($_POST['horarios']) ? $_POST['horarios'] : [];
    
    $sql_insert = "INSERT INTO coach (Nombre, Apellido, dni, telefono, email, especialidad, sueldo, fecha_contrato, estado) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
    $stmt_insert = $conexion->prepare($sql_insert);
    $stmt_insert->bind_param("ssssssds", $nombre, $apellido, $dni, $telefono, $email, $especialidad, $sueldo, $fecha_contrato);
    
    if ($stmt_insert->execute()) {
        $nuevo_cod_coach = $conexion->insert_id;
        
        if (!empty($horarios_seleccionados)) {
            $sql_update_horario = "UPDATE horario SET Cod_Coach = ? WHERE Cod_Horario = ?";
            $stmt_horario = $conexion->prepare($sql_update_horario);
            
            foreach ($horarios_seleccionados as $cod_horario) {
                $stmt_horario->bind_param("ii", $nuevo_cod_coach, $cod_horario);
                $stmt_horario->execute();
            }
            $stmt_horario->close();
        }
        
        header("Location: coaches.php?success=1");
        exit;
    }
}

// PROCESAR EDICIÓN DE COACH
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar_coach'])) {
    $cod_coach = intval($_POST['cod_coach']);
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $dni = trim($_POST['dni']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $especialidad = trim($_POST['especialidad']);
    $sueldo = floatval($_POST['sueldo']);
    $fecha_contrato = $_POST['fecha_contrato'];
    
    $sql_update = "UPDATE coach SET Nombre=?, Apellido=?, dni=?, telefono=?, email=?, especialidad=?, sueldo=?, fecha_contrato=? WHERE cod_coach=?";
    $stmt_update = $conexion->prepare($sql_update);
    $stmt_update->bind_param("ssssssdsi", $nombre, $apellido, $dni, $telefono, $email, $especialidad, $sueldo, $fecha_contrato, $cod_coach);
    
    if ($stmt_update->execute()) {
        header("Location: coaches.php?success=2");
        exit;
    }
}

// PROCESAR ASIGNACIÓN DE HORARIOS ADICIONALES
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['asignar_horarios'])) {
    $cod_coach = intval($_POST['cod_coach']);
    $horarios_seleccionados = isset($_POST['horarios']) ? $_POST['horarios'] : [];
    
    if (!empty($horarios_seleccionados)) {
        $sql_update_horario = "UPDATE horario SET Cod_Coach = ? WHERE Cod_Horario = ?";
        $stmt_horario = $conexion->prepare($sql_update_horario);
        
        foreach ($horarios_seleccionados as $cod_horario) {
            $stmt_horario->bind_param("ii", $cod_coach, $cod_horario);
            $stmt_horario->execute();
        }
        $stmt_horario->close();
        
        header("Location: coaches.php?success=3");
        exit;
    }
}

// Consultar coaches
$sql_coaches = "SELECT cod_coach, Nombre, Apellido, dni, telefono, email, 
                especialidad, sueldo, fecha_contrato, estado 
                FROM coach 
                WHERE estado = 1 
                ORDER BY Nombre ASC";
$result_coaches = $conexion->query($sql_coaches);

// Obtener horarios disponibles
$sql_horarios_disponibles = "SELECT Cod_Horario, Turno 
                             FROM horario 
                             WHERE (Cod_Coach IS NULL OR Cod_Coach = 0) AND Estado = 1 
                             ORDER BY Cod_Horario ASC";
$result_horarios_disponibles = $conexion->query($sql_horarios_disponibles);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Coaches - PRO FIT Gym</title>
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
        font-size: 14px;
    }

    .btn-back {
        background: linear-gradient(135deg, #666, #888);
        color: white;
    }

    .coaches-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .coach-card {
        background: #2b1a0e;
        padding: 20px;
        border-radius: 10px;
        border: 2px solid #b8860b;
        transition: all 0.3s ease;
    }

    .coach-card:hover {
        border-color: #d4af37;
        transform: translateY(-5px);
    }

    .coach-header {
        border-bottom: 2px solid #b8860b;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }

    .coach-info {
        margin: 8px 0;
        color: #ccc;
    }

    .coach-info strong {
        color: #d4af37;
    }

    .especialidad {
        color: #d4af37;
        font-weight: bold;
        margin: 10px 0;
        font-size: 16px;
    }

    .horarios {
        background: #1a1a1a;
        padding: 10px;
        border-radius: 5px;
        margin: 10px 0;
        color: #ccc;
    }

    .sueldo {
        background: rgba(212, 175, 55, 0.2);
        padding: 8px;
        border-radius: 5px;
        margin: 10px 0;
        text-align: center;
        font-weight: bold;
        color: #d4af37;
    }

    .no-coaches {
        text-align: center;
        padding: 40px;
        color: #888;
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
        overflow-y: auto;
    }

    .modal-content {
        background: linear-gradient(135deg, #1a1a1a, #2b1a0e);
        margin: 2% auto;
        padding: 30px;
        border: 2px solid #d4af37;
        border-radius: 15px;
        width: 90%;
        max-width: 600px;
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

    .form-group {
        margin: 15px 0;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        color: #d4af37;
        font-weight: bold;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 10px;
        background: #2b1a0e;
        border: 2px solid #b8860b;
        border-radius: 5px;
        color: white;
        font-size: 14px;
    }

    .form-group input:focus,
    .form-group select:focus {
        border-color: #d4af37;
        outline: none;
    }

    .horarios-checkbox {
        background: #2b1a0e;
        padding: 15px;
        border-radius: 5px;
        border: 2px solid #b8860b;
        max-height: 200px;
        overflow-y: auto;
    }

    .checkbox-item {
        padding: 8px;
        margin: 5px 0;
        background: #1a1a1a;
        border-radius: 5px;
    }

    .checkbox-item input[type="checkbox"] {
        width: auto;
        margin-right: 10px;
    }

    .alert-success {
        background: #4CAF50;
        color: white;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        transition: opacity 0.5s ease;
    }
    </style>
</head>

<body>
    <div class="header">
        <h1>💪 Gestión de Coaches - PRO FIT Gym</h1>
        <div>
            <a href="../dashboard.php" class="btn btn-back">← REGRESAR</a>
        </div>
    </div>

    <div class="container">
        <?php 
        if (isset($_GET['success'])) {
            $mensajes = [
                1 => '✅ Coach registrado exitosamente',
                2 => '✅ Coach actualizado exitosamente',
                3 => '✅ Horarios asignados exitosamente'
            ];
            echo '<div class="alert-success" id="alertSuccess">' . $mensajes[$_GET['success']] . '</div>';
        }
        ?>

        <div class="card">
            <h2>Listado de Coaches</h2>
            <button class="btn" onclick="abrirModalAgregar()">➕ Agregar Nuevo Coach</button>

            <div class="coaches-grid">
                <?php
                if ($result_coaches && $result_coaches->num_rows > 0) {
                    while ($coach = $result_coaches->fetch_assoc()) {
                        $cod_coach = $coach['cod_coach'];
                        $nombre_completo = $coach['Nombre'] . ' ' . $coach['Apellido'];
                        
                        $sql_horarios = "SELECT Turno FROM horario 
                                        WHERE Cod_Coach = ? AND Estado = 1 
                                        ORDER BY Cod_Horario ASC";
                        $stmt_horarios = $conexion->prepare($sql_horarios);
                        $stmt_horarios->bind_param("i", $cod_coach);
                        $stmt_horarios->execute();
                        $result_horarios = $stmt_horarios->get_result();
                        
                        echo '<div class="coach-card">';
                        echo '<div class="coach-header">';
                        echo '<h3>' . htmlspecialchars($nombre_completo) . '</h3>';
                        echo '<div class="especialidad">🏋️ Especialidad: ' . htmlspecialchars($coach['especialidad'] ?? 'No especificada') . '</div>';
                        echo '</div>';
                        
                        echo '<div class="coach-info"><strong>📋 DNI:</strong> ' . htmlspecialchars($coach['dni'] ?? 'No registrado') . '</div>';
                        echo '<div class="coach-info"><strong>📞 Teléfono:</strong> ' . htmlspecialchars($coach['telefono'] ?? 'No registrado') . '</div>';
                        echo '<div class="coach-info"><strong>📧 Email:</strong> ' . htmlspecialchars($coach['email'] ?? 'No registrado') . '</div>';
                        echo '<div class="coach-info"><strong>📅 Fecha Contrato:</strong> ' . ($coach['fecha_contrato'] ? date('d/m/Y', strtotime($coach['fecha_contrato'])) : 'No registrada') . '</div>';
                        
                        echo '<div class="sueldo">💰 Sueldo: S/ ' . number_format($coach['sueldo'] ?? 0, 2) . '</div>';
                        
                        echo '<div class="horarios"><strong>Horarios Asignados:</strong><br>';
                        if ($result_horarios && $result_horarios->num_rows > 0) {
                            while ($horario = $result_horarios->fetch_assoc()) {
                                echo '• ' . htmlspecialchars($horario['Turno']) . '<br>';
                            }
                        } else {
                            echo '<span style="color: #888;">• Sin horarios asignados</span>';
                        }
                        echo '</div>';
                        
                        $coach_json = htmlspecialchars(json_encode($coach), ENT_QUOTES, 'UTF-8');
                        
                        echo '<div style="margin-top: 15px;">';
                        echo '<button class="btn" onclick=\'abrirModalEditar(' . $coach_json . ')\'>✏️ Editar</button>';
                        echo '<button class="btn" onclick="abrirModalAsignarHorarios(' . $cod_coach . ', \'' . htmlspecialchars($nombre_completo) . '\')">🕐 Asignar Horario</button>';
                        echo '</div>';
                        echo '</div>';
                        
                        $stmt_horarios->close();
                    }
                } else {
                    echo '<div class="no-coaches"><h3>📭 No hay coaches registrados</h3><p>Agrega el primer coach para comenzar</p></div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Coach -->
    <div id="modalAgregarCoach" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal('modalAgregarCoach')">&times;</span>
            <h2>➕ Agregar Nuevo Coach</h2>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" required>
                </div>

                <div class="form-group">
                    <label>Apellido *</label>
                    <input type="text" name="apellido" required>
                </div>

                <div class="form-group">
                    <label>DNI *</label>
                    <input type="text" name="dni" maxlength="8" pattern="[0-9]{8}" required>
                </div>

                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" maxlength="9">
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email">
                </div>

                <div class="form-group">
                    <label>Especialidad *</label>
                    <input type="text" name="especialidad" required placeholder="Ej: Musculación, Cardio, CrossFit">
                </div>

                <div class="form-group">
                    <label>Sueldo Mensual (S/) *</label>
                    <input type="number" name="sueldo" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label>Fecha de Contrato *</label>
                    <input type="date" name="fecha_contrato" required value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label>Horarios a Asignar</label>
                    <div class="horarios-checkbox">
                        <?php
                        if ($result_horarios_disponibles && $result_horarios_disponibles->num_rows > 0) {
                            while ($horario = $result_horarios_disponibles->fetch_assoc()) {
                                echo '<div class="checkbox-item">';
                                echo '<input type="checkbox" name="horarios[]" value="' . $horario['Cod_Horario'] . '" id="horario_' . $horario['Cod_Horario'] . '">';
                                echo '<label for="horario_' . $horario['Cod_Horario'] . '" style="display: inline; color: white;">⏰ ' . htmlspecialchars($horario['Turno']) . '</label>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p style="color: #888;">No hay horarios disponibles</p>';
                        }
                        ?>
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" name="agregar_coach" class="btn">💾 Guardar Coach</button>
                    <button type="button" class="btn btn-back"
                        onclick="cerrarModal('modalAgregarCoach')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Coach -->
    <div id="modalEditarCoach" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal('modalEditarCoach')">&times;</span>
            <h2>✏️ Editar Coach</h2>

            <form method="POST" action="">
                <input type="hidden" name="cod_coach" id="edit_cod_coach">

                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" id="edit_nombre" required>
                </div>

                <div class="form-group">
                    <label>Apellido *</label>
                    <input type="text" name="apellido" id="edit_apellido" required>
                </div>

                <div class="form-group">
                    <label>DNI *</label>
                    <input type="text" name="dni" id="edit_dni" maxlength="8" pattern="[0-9]{8}" required>
                </div>

                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" id="edit_telefono" maxlength="9">
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_email">
                </div>

                <div class="form-group">
                    <label>Especialidad *</label>
                    <input type="text" name="especialidad" id="edit_especialidad" required>
                </div>

                <div class="form-group">
                    <label>Sueldo Mensual (S/) *</label>
                    <input type="number" name="sueldo" id="edit_sueldo" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label>Fecha de Contrato *</label>
                    <input type="date" name="fecha_contrato" id="edit_fecha_contrato" required>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" name="editar_coach" class="btn">💾 Actualizar Coach</button>
                    <button type="button" class="btn btn-back"
                        onclick="cerrarModal('modalEditarCoach')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Asignar Horarios -->
    <div id="modalAsignarHorarios" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal('modalAsignarHorarios')">&times;</span>
            <h2>🕐 Asignar Horarios</h2>
            <p style="margin: 10px 0;">Coach: <strong id="nombre_coach_horarios"></strong></p>

            <form method="POST" action="">
                <input type="hidden" name="cod_coach" id="horarios_cod_coach">

                <div class="form-group">
                    <label>Selecciona horarios disponibles</label>
                    <div class="horarios-checkbox" id="horarios_disponibles_lista">
                        <p style="color: #888;">Cargando...</p>
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" name="asignar_horarios" class="btn">💾 Asignar Horarios</button>
                    <button type="button" class="btn btn-back"
                        onclick="cerrarModal('modalAsignarHorarios')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    setTimeout(function() {
        var alert = document.getElementById('alertSuccess');
        if (alert) alert.remove();
    }, 1000);

    function abrirModalAgregar() {
        document.getElementById('modalAgregarCoach').style.display = 'block';
    }

    function abrirModalEditar(coach) {
        document.getElementById('edit_cod_coach').value = coach.cod_coach;
        document.getElementById('edit_nombre').value = coach.Nombre;
        document.getElementById('edit_apellido').value = coach.Apellido;
        document.getElementById('edit_dni').value = coach.dni || '';
        document.getElementById('edit_telefono').value = coach.telefono || '';
        document.getElementById('edit_email').value = coach.email || '';
        document.getElementById('edit_especialidad').value = coach.especialidad || '';
        document.getElementById('edit_sueldo').value = coach.sueldo || 0;
        document.getElementById('edit_fecha_contrato').value = coach.fecha_contrato || '';
        document.getElementById('modalEditarCoach').style.display = 'block';
    }

    function abrirModalAsignarHorarios(codCoach, nombreCoach) {
        document.getElementById('horarios_cod_coach').value = codCoach;
        document.getElementById('nombre_coach_horarios').innerText = nombreCoach;
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'get_horarios_disponibles.php', true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                document.getElementById('horarios_disponibles_lista').innerHTML = xhr.responseText;
            }
        };
        xhr.send();
        document.getElementById('modalAsignarHorarios').style.display = 'block';
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
<?php
if (isset($result_coaches)) {
    $result_coaches->free();
}
?>