<?php
session_start();
require_once '../conexion.php';


if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['cod_cargo'] != 1) {
    header('Location: ../login.php');
    exit;
}


$usuario = $_SESSION['usuario'];


// Procesar formulario de agregar personal
if ($_POST && isset($_POST['agregar_personal'])) {
    $dni = trim($_POST['dni']);
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $cargo = intval($_POST['cargo']);
    $password = trim($_POST['password']);
    $direccion = trim($_POST['direccion']);
    $celular = trim($_POST['celular']);
    
    $sql_check = "SELECT DNI FROM gimnasio_colaboradores WHERE DNI = ?";
    $stmt_check = $conexion->prepare($sql_check);
    if ($stmt_check) {
        $stmt_check->bind_param("s", $dni);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $error = "El DNI ya está registrado en el sistema";
        } else {
            $sql_insert = "INSERT INTO gimnasio_colaboradores (DNI, Nombre, Direccion, Celular, Email, Password, Cod_Cargo, Estado) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
            $stmt_insert = $conexion->prepare($sql_insert);
            if ($stmt_insert) {
                $stmt_insert->bind_param("ssssssi", $dni, $nombre, $direccion, $celular, $email, $password, $cargo);
                
                if ($stmt_insert->execute()) {
                    $success = "Personal agregado correctamente";
                } else {
                    $error = "Error al agregar: " . $conexion->error;
                }
            }
        }
    }
}


// Procesar edición de personal
if ($_POST && isset($_POST['editar_personal'])) {
    $dni = trim($_POST['dni']);
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $cargo = intval($_POST['cargo']);
    $direccion = trim($_POST['direccion']);
    $celular = trim($_POST['celular']);
    
    $sql_update = "UPDATE gimnasio_colaboradores 
                  SET Nombre = ?, Direccion = ?, Celular = ?, Email = ?, Cod_Cargo = ? 
                  WHERE DNI = ?";
    $stmt_update = $conexion->prepare($sql_update);
    
    // 6 parámetros: Nombre, Direccion, Celular, Email, Cod_Cargo, DNI
    $stmt_update->bind_param("ssssis", $nombre, $direccion, $celular, $email, $cargo, $dni);
    
    if ($stmt_update->execute()) {
        $success = "Personal actualizado correctamente";
    } else {
        $error = "Error al actualizar: " . $conexion->error;
    }
}





// Procesar cambio de estado
if ($_POST && isset($_POST['cambiar_estado'])) {
    $dni = $_POST['dni'];
    $estado = intval($_POST['estado']);
    
    $sql_update = "UPDATE gimnasio_colaboradores SET Estado = ? WHERE DNI = ?";
    $stmt_update = $conexion->prepare($sql_update);
    if ($stmt_update) {
        $stmt_update->bind_param("is", $estado, $dni);
        
        if ($stmt_update->execute()) {
            $success = "Estado actualizado correctamente";
        } else {
            $error = "Error al actualizar estado: " . $conexion->error;
        }
    }
}


// Procesar eliminación
if ($_POST && isset($_POST['eliminar_personal'])) {
    $dni = $_POST['dni'];
    
    if ($dni == $usuario['dni']) {
        $error = "No puedes eliminar tu propio usuario";
    } else {
        $sql_delete = "DELETE FROM gimnasio_colaboradores WHERE DNI = ?";
        $stmt_delete = $conexion->prepare($sql_delete);
        if ($stmt_delete) {
            $stmt_delete->bind_param("s", $dni);
            
            if ($stmt_delete->execute()) {
                $success = "Personal eliminado correctamente";
            } else {
                $error = "Error al eliminar personal: " . $conexion->error;
            }
        }
    }
}


// Obtener lista de personal con información de cargos
$sql_personal = "SELECT 
                    c.DNI, 
                    c.Nombre, 
                    c.Direccion, 
                    c.Celular, 
                    c.Email, 
                    c.Password, 
                    c.Estado, 
                    c.Cod_Cargo,
                    ca.Nombre as nombre_cargo 
                 FROM gimnasio_colaboradores c 
                 LEFT JOIN gimnasio_cargo ca ON c.Cod_Cargo = ca.Cod_Cargo 
                 ORDER BY c.Estado DESC, ca.Cod_Cargo, c.Nombre";


$result_personal = $conexion->query($sql_personal);
$personal_data = [];
if ($result_personal && $result_personal->num_rows > 0) {
    $personal_data = $result_personal->fetch_all(MYSQLI_ASSOC);
}


// Obtener lista de cargos para el formulario
$sql_cargos = "SELECT Cod_Cargo, Nombre FROM gimnasio_cargo WHERE Estado = 1";
$result_cargos = $conexion->query($sql_cargos);
$cargos_data = [];
if ($result_cargos && $result_cargos->num_rows > 0) {
    $cargos_data = $result_cargos->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Personal - PRO FIT Gym</title>
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


    .btn-danger {
        background: linear-gradient(135deg, #8b0000, #ff0000);
        color: white;
    }


    .btn-edit {
        background: linear-gradient(135deg, #0066cc, #0099ff);
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
        font-size: 14px;
    }


    input:focus,
    select:focus {
        outline: none;
        border-color: #d4af37;
        box-shadow: 0 0 10px rgba(212, 175, 55, 0.5);
    }


    .alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: bold;
    }


    .alert-success {
        background: #24d24dff;
        color: #e0fbe6ff;
        border: none;
    }


    .alert-error {
        background: #da1a2aff;
        color: #edd6d8ff;
        border: none;
    }


    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }


    .full-width {
        grid-column: span 2;
    }


    .actions-form {
        display: inline;
    }


    .cargo-badge {
        background: #d4af37;
        color: #1a1a1a;
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: bold;
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
        margin: 3% auto;
        padding: 30px;
        border: 2px solid #d4af37;
        border-radius: 15px;
        width: 80%;
        max-width: 700px;
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
    .alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: bold;
    transition: opacity 0.5s ease; /* Agregar esta línea */
}

    </style>
</head>


<body>
    <div class="header">
        <h1>👥 Gestión de Personal - PRO FIT Gym</h1>
        <div>
            <a href="../dashboard.php" class="btn btn-back">← REGRESAR</a>
        </div>
    </div>


    <div class="container">
        <?php if (isset($success)): ?>
<div class="alert alert-success" id="alertSuccess"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="alert alert-error" id="alertError"><?php echo $error; ?></div>
<?php endif; ?>



        <div class="card">
            <div class="card-header">
                <h2>📋 Listado de Personal</h2>
                <button class="btn" onclick="abrirModalAgregar()">➕ Registrar Personal</button>
            </div>


            <table>
                <thead>
                    <tr>
                        <th>DNI</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Cargo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($personal_data)): ?>
                    <?php foreach ($personal_data as $personal): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($personal['DNI']); ?></td>
                        <td><?php echo htmlspecialchars($personal['Nombre']); ?></td>
                        <td><?php echo htmlspecialchars($personal['Email']); ?></td>
                        <td><?php echo htmlspecialchars($personal['Celular']); ?></td>
                        <td>
                            <span class="cargo-badge">
                                <?php echo !empty($personal['nombre_cargo']) ? htmlspecialchars($personal['nombre_cargo']) : 'Sin cargo'; ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" class="actions-form">
                                <input type="hidden" name="dni" value="<?php echo $personal['DNI']; ?>">
                                <select name="estado" onchange="this.form.submit()"
                                    style="background: <?php echo $personal['Estado'] == 1 ? '#1a3c1a' : '#3c1a1a'; ?>;">
                                    <option value="1" <?php echo $personal['Estado'] == 1 ? 'selected' : ''; ?>>🟢
                                        Activo</option>
                                    <option value="0" <?php echo $personal['Estado'] == 0 ? 'selected' : ''; ?>>🔴
                                        Inactivo</option>
                                </select>
                                <input type="hidden" name="cambiar_estado" value="1">
                            </form>
                        </td>
                        <td>
                            <button
                                onclick="abrirModalEditar('<?php echo htmlspecialchars(json_encode($personal)); ?>')"
                                class="btn btn-edit">✏️ Editar</button>
                            <?php if ($personal['DNI'] != $usuario['dni']): ?>
                            <form method="POST" class="actions-form"
                                onsubmit="return confirm('¿Estás seguro de eliminar a <?php echo htmlspecialchars($personal['Nombre']); ?>?')">
                                <input type="hidden" name="dni" value="<?php echo $personal['DNI']; ?>">
                                <button type="submit" name="eliminar_personal" value="1" class="btn btn-danger">🗑️
                                    Eliminar</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #888; padding: 20px;">
                            No hay personal registrado en el sistema.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>


    <!-- Modal Agregar Personal -->
    <div id="modalAgregar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal('modalAgregar')">&times;</span>
            <h2>➕ Registrar Nuevo Personal</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>DNI *</label>
                        <input type="text" name="dni" maxlength="8" pattern="[0-9]{8}" required>
                    </div>
                    <div class="form-group">
                        <label>Nombre Completo *</label>
                        <input type="text" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Teléfono/Celular</label>
                        <input type="text" name="celular" maxlength="9" pattern="[0-9]{9}">
                    </div>
                    <div class="form-group">
                        <label>Dirección</label>
                        <input type="text" name="direccion">
                    </div>
                    <div class="form-group">
                        <label>Cargo *</label>
                        <select name="cargo" required>
                            <option value="">Seleccionar cargo</option>
                            <?php foreach ($cargos_data as $cargo): ?>
                            <option value="<?php echo $cargo['Cod_Cargo']; ?>">
                                <?php echo htmlspecialchars($cargo['Nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label>Contraseña *</label>
                        <input type="password" name="password" required minlength="6" placeholder="Mínimo 6 caracteres">
                    </div>
                    <div class="form-group full-width">
                        <button type="submit" name="agregar_personal" value="1" class="btn"
                            style="width: 100%; padding: 15px;">
                            ✅ Registrar Personal
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>


    <!-- Modal Editar -->
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal('modalEditar')">&times;</span>
            <h2>✏️ Editar Personal</h2>
            <form method="POST">
                <input type="hidden" name="dni" id="edit_dni">
                <div class="form-grid">
                    <div class="form-group">
                        <label>DNI:</label>
                        <input type="text" id="edit_dni_display" disabled style="background: #444;">
                    </div>
                    <div class="form-group">
                        <label>Nombre Completo:</label>
                        <input type="text" name="nombre" id="edit_nombre" required>
                    </div>
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" id="edit_email" required>
                    </div>
                    <div class="form-group">
                        <label>Teléfono/Celular:</label>
                        <input type="text" name="celular" id="edit_celular" maxlength="9">
                    </div>
                    <div class="form-group">
                        <label>Dirección:</label>
                        <input type="text" name="direccion" id="edit_direccion">
                    </div>
                    <div class="form-group">
                        <label>Cargo:</label>
                        <select name="cargo" id="edit_cargo" required>
                            <?php foreach ($cargos_data as $cargo): ?>
                            <option value="<?php echo $cargo['Cod_Cargo']; ?>">
                                <?php echo htmlspecialchars($cargo['Nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <button type="submit" name="editar_personal" value="1" class="btn"
                            style="width: 100%; padding: 15px;">
                            💾 Guardar Cambios
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

<script>
function abrirModalAgregar() {
    document.getElementById('modalAgregar').style.display = 'block';
}

// Auto-ocultar mensaje de éxito
setTimeout(function() {
    var msgSuccess = document.getElementById('alertSuccess');
    if (msgSuccess) {
        msgSuccess.style.opacity = '0';
        setTimeout(function() { msgSuccess.remove(); }, 500);
    }
}, 3000);

// Auto-ocultar mensaje de error
setTimeout(function() {
    var msgError = document.getElementById('alertError');
    if (msgError) {
        msgError.style.opacity = '0';
        setTimeout(function() { msgError.remove(); }, 500);
    }
}, 5000); // 5 segundos para errores

function abrirModalEditar(datosJson) {
    const datos = JSON.parse(datosJson);
    document.getElementById('edit_dni').value = datos.DNI;
    document.getElementById('edit_dni_display').value = datos.DNI;
    document.getElementById('edit_nombre').value = datos.Nombre;
    document.getElementById('edit_email').value = datos.Email;
    document.getElementById('edit_celular').value = datos.Celular;
    document.getElementById('edit_direccion').value = datos.Direccion;
    document.getElementById('edit_cargo').value = datos.Cod_Cargo;
    document.getElementById('modalEditar').style.display = 'block';
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