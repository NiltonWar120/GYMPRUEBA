<?php
session_start();
require_once '../conexion.php';

// CORRECCIÓN: Verificar que la sesión existe y tiene los datos necesarios
if (!isset($_SESSION['usuario']) || !is_array($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

$usuario = $_SESSION['usuario'];

// PROCESAR EDICIÓN DE MEMBRESÍA
// PROCESAR EDICIÓN DE MEMBRESÍA
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar_membresia'])) {
    $cod_tipo = intval($_POST['cod_tipo']);
    $nombre = trim($_POST['nombre']);
    $precio_nuevo = floatval($_POST['precio_nuevo']);
    $precio_anterior = floatval($_POST['precio_anterior']);
    
    // Determinar si es descuento o aumento
    $tiene_oferta = 0;
    $porcentaje_descuento = 0;
    $precio_base_final = $precio_nuevo;
    $precio_oferta_final = $precio_nuevo;
    
    if ($precio_nuevo < $precio_anterior && $precio_anterior > 0) {
        // ES UN DESCUENTO - mantener precio base y aplicar oferta
        $tiene_oferta = 1;
        $precio_base_final = $precio_anterior;
        $precio_oferta_final = $precio_nuevo;
        $porcentaje_descuento = round((($precio_anterior - $precio_nuevo) / $precio_anterior) * 100, 2);
    } else {
        // ES UN AUMENTO O SE MANTIENE - actualizar precio base y quitar oferta
        $tiene_oferta = 0;
        $precio_base_final = $precio_nuevo;
        $precio_oferta_final = $precio_nuevo;
        $porcentaje_descuento = 0;
    }
    
    $sql_update = "UPDATE tipo_membresia SET Nombre=?, precio_base=?, precio_oferta=?, tiene_oferta=?, porcentaje_descuento=? WHERE Cod_Tipo_Membresia=?";
    $stmt = $conexion->prepare($sql_update);
    $stmt->bind_param("sddidd", $nombre, $precio_base_final, $precio_oferta_final, $tiene_oferta, $porcentaje_descuento, $cod_tipo);
    
    if ($stmt->execute()) {
        header("Location: ventas.php?success=editado");
        exit;
    }
}


// Obtener ventas del día (registros de hoy)
$sql_ventas_hoy = "SELECT COUNT(*) as total FROM MEMBRESIA WHERE DATE(Fecha_Inicio) = CURDATE()";
$result_ventas = $conexion->query($sql_ventas_hoy);
$ventas_hoy = 0;
if ($result_ventas) {
    $ventas_hoy = $result_ventas->fetch_assoc()['total'];
}

// Obtener ingreso del día
$sql_ingreso_hoy = "SELECT SUM(Precio) AS total 
                    FROM MEMBRESIA 
                    WHERE DATE(Fecha_Inicio) = CURDATE()";
$result_ingreso = $conexion->query($sql_ingreso_hoy);
$ingreso_hoy = 0;
if ($result_ingreso) {
    $row_ingreso = $result_ingreso->fetch_assoc();
    $ingreso_hoy = $row_ingreso && $row_ingreso['total'] !== null ? $row_ingreso['total'] : 0;
}

// Obtener membresía más vendida
$sql_popular = "SELECT 
                    tm.Nombre as nombre_membresia,
                    COUNT(*) as cantidad
                FROM MEMBRESIA m
                INNER JOIN TIPO_MEMBRESIA tm ON m.Cod_Tipo_Membresia = tm.Cod_Tipo_Membresia
                WHERE m.Estado = 1
                GROUP BY tm.Nombre
                ORDER BY cantidad DESC
                LIMIT 1";
                
$result_popular = $conexion->query($sql_popular);
$membresia_popular = 'N/A';
if ($result_popular && $result_popular->num_rows > 0) {
    $membresia_popular = $result_popular->fetch_assoc()['nombre_membresia'];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vender Membresía - PRO FIT Gym</title>
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

    .membresias-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .membresia-card {
        background: #2b1a0e;
        padding: 25px;
        border-radius: 10px;
        border: 2px solid #b8860b;
        text-align: center;
        transition: all 0.3s ease;
    }

    .membresia-card:hover {
        transform: translateY(-5px);
        border-color: #d4af37;
    }

    .precio {
        font-size: 2em;
        font-weight: bold;
        color: #d4af37;
        margin: 10px 0;
    }

    .duracion {
        color: #ccc;
        margin-bottom: 15px;
    }

    .beneficios {
        text-align: left;
        margin: 15px 0;
    }

    .beneficios li {
        margin: 5px 0;
        color: #ccc;
    }

    .alert-success {
        background: #4CAF50;
        color: white;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        transition: opacity 0.5s ease;
    }

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
        margin: 5% auto;
        padding: 30px;
        border: 2px solid #d4af37;
        border-radius: 15px;
        width: 90%;
        max-width: 500px;
        color: white;
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

    .form-group input {
        width: 100%;
        padding: 10px;
        background: #2b1a0e;
        border: 2px solid #b8860b;
        border-radius: 5px;
        color: white;
        font-size: 14px;
    }

    .form-group input:disabled {
        background: #333;
        border-color: #666;
    }

    .preview-descuento {
        background: #2b1a0e;
        padding: 15px;
        border-radius: 8px;
        border: 2px solid #ff4444;
        margin: 15px 0;
        text-align: center;
    }
    </style>
</head>

<body>
    <div class="header">
        <h1>🎫 Vender Membresía - PRO FIT Gym</h1>
        <div>
            <span style="color: #1a1a1a; margin-right: 15px;">
                Usuario:
                <strong><?php echo isset($usuario['nombre']) ? htmlspecialchars($usuario['nombre']) : 'Usuario'; ?></strong>
            </span>
            <a href="../dashboard.php" class="btn btn-back">← REGRESAR</a>
        </div>
    </div>

    <div class="container">
        <?php 
        if (isset($_GET['success']) && $_GET['success'] == 'editado') {
            echo '<div class="alert-success" id="alertSuccess">✅ Membresía actualizada exitosamente</div>';
        }
        ?>

        <div class="card">
            <h2>💳 Tipos de Membresías Disponibles</h2>
            <p style="color: #ccc; margin-bottom: 20px;">Selecciona una membresía para vender a un cliente</p>

            <div class="membresias-grid">
                <?php
                // Consultar membresías con precios y ofertas
                $sql_membresias = "SELECT * FROM tipo_membresia WHERE Estado = 1 ORDER BY Cod_Tipo_Membresia ASC";
                $result_mem = $conexion->query($sql_membresias);

                if ($result_mem && $result_mem->num_rows > 0) {
                    while ($mem = $result_mem->fetch_assoc()) {
                        $cod_tipo = $mem['Cod_Tipo_Membresia'];
                        $nombre = $mem['Nombre'];
                        $precio_base = $mem['precio_base'] ?? 0;
                        $precio_oferta = $mem['precio_oferta'] ?? $precio_base;
                        $tiene_oferta = $mem['tiene_oferta'] ?? 0;
                        $porcentaje = $mem['porcentaje_descuento'] ?? 0;
                        
                        // Precio a mostrar
                        $precio_mostrar = $tiene_oferta ? $precio_oferta : $precio_base;
                        
                        echo '<div class="membresia-card" style="position: relative;">';
                        
                        // Badge de oferta si aplica
                        if ($tiene_oferta) {
                            echo '<div style="position: absolute; top: -10px; right: -10px; background: linear-gradient(135deg, #ff4444, #cc0000); color: white; padding: 8px 15px; border-radius: 20px; font-weight: bold; font-size: 14px; box-shadow: 0 4px 10px rgba(255,0,0,0.4); z-index: 10;">';
                            echo '🔥 -' . number_format($porcentaje, 0) . '%';
                            echo '</div>';
                        }
                        
                        echo '<h3>' . htmlspecialchars($nombre) . '</h3>';
                        
                        // Mostrar precio anterior tachado si hay oferta
                        if ($tiene_oferta) {
                            echo '<div style="text-decoration: line-through; color: #888; font-size: 1.2em; margin: 5px 0;">S/ ' . number_format($precio_base, 2) . '</div>';
                        }
                        
                        echo '<div class="precio">S/ ' . number_format($precio_mostrar, 2) . '</div>';
                        echo '<div class="duracion">⏰ 30 días</div>';
                        
                        // Beneficios según el tipo
                        echo '<div class="beneficios"><strong>Beneficios:</strong><ul>';
                        
                        switch($cod_tipo) {
                            case 3: // MODOFIT
                                echo '<li>✅ Acceso básico</li>';
                                echo '<li>✅ Área de cardio</li>';
                                echo '<li>❌ Sin musculación</li>';
                                echo '<li>✅ Lockers incluidos</li>';
                                echo '<li>❌ Sin asesoría</li>';
                                break;
                            case 2: // ESPECIAL
                                echo '<li>✅ Acceso ilimitado</li>';
                                echo '<li>✅ Área de musculación</li>';
                                echo '<li>✅ Área de cardio</li>';
                                echo '<li>✅ Lockers incluidos</li>';
                                echo '<li>❌ Sin asesoría</li>';
                                break;
                            case 1: // BLACK
                                echo '<li>✅ Acceso ilimitado</li>';
                                echo '<li>✅ Área de musculación</li>';
                                echo '<li>✅ Área de cardio</li>';
                                echo '<li>✅ Lockers incluidos</li>';
                                echo '<li>✅ Asesoría básica</li>';
                                break;
                            case 4: // PLATINIUM
                                echo '<li>✅ Acceso VIP</li>';
                                echo '<li>✅ Todas las áreas</li>';
                                echo '<li>✅ Entrenador personal</li>';
                                echo '<li>✅ Nutricionista</li>';
                                echo '<li>✅ Evaluación física</li>';
                                break;
                            case 5: // PREMIUM
                                echo '<li>✅ Acceso completo</li>';
                                echo '<li>✅ Todas las áreas</li>';
                                echo '<li>✅ Clases grupales</li>';
                                echo '<li>✅ Lockers premium</li>';
                                echo '<li>✅ Asesoría avanzada</li>';
                                break;
                            default:
                                echo '<li>✅ Acceso al gimnasio</li>';
                        }
                        
                        echo '</ul></div>';
                        
                        // Botones de acción
                        echo '<div style="display: grid; grid-template-columns: 1fr auto; gap: 10px; margin-top: 10px;">';
                        
                        $mem_json = htmlspecialchars(json_encode($mem), ENT_QUOTES, 'UTF-8');
echo '<button class="btn" style="background: linear-gradient(135deg, #6699ff, #4477dd); padding: 10px 15px; white-space: nowrap;" onclick=\'abrirModalEditar(' . $mem_json . ')\' title="Editar">✏️ Editar</button>';
echo '</div>';
                        
                        echo '</div>';
                    }
                } else {
                    echo '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #888;">';
                    echo '<h3>📭 No hay membresías disponibles</h3>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <div class="card">
            <h2>📊 Ventas del Día</h2>
            <div
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; text-align: center;">
                <div style="background: #2b1a0e; padding: 15px; border-radius: 10px; border: 2px solid #44ff44;">
                    <h3>🟢 Ventas Hoy</h3>
                    <div style="font-size: 1.5em; color: #44ff44;"><?php echo $ventas_hoy; ?></div>
                </div>
                <div style="background: #2b1a0e; padding: 15px; border-radius: 10px; border: 2px solid #d4af37;">
                    <h3>💰 Ingreso Hoy</h3>
                    <div style="font-size: 1.5em; color: #d4af37;">S/ <?php echo number_format($ingreso_hoy, 2); ?>
                    </div>
                </div>
                <div style="background: #2b1a0e; padding: 15px; border-radius: 10px; border: 2px solid #8888ff;">
                    <h3>🎫 Membresía Popular</h3>
                    <div style="font-size: 1.2em; color: #8888ff;"><?php echo htmlspecialchars($membresia_popular); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Membresía -->
    <div id="modalEditarMembresia" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModalEditar()">&times;</span>
            <h2>✏️ Editar Membresía</h2>

            <form method="POST" action="">
                <input type="hidden" name="cod_tipo" id="edit_cod">
                <input type="hidden" name="precio_anterior" id="edit_precio_anterior">

                <div class="form-group">
                    <label>Nombre de la Membresía *</label>
                    <input type="text" name="nombre" id="edit_nombre" required>
                </div>

                <div class="form-group">
                    <label>Precio Base Actual</label>
                    <input type="text" id="edit_precio_base_display" disabled>
                </div>

                <div class="form-group">
                    <label>Nuevo Precio (S/) *</label>
                    <input type="number" name="precio_nuevo" id="edit_precio_nuevo" step="0.01" min="0" required
                        oninput="calcularDescuento()">
                </div>

                <div id="preview_descuento" class="preview-descuento" style="display:none;">
                    <strong style="color: #ff4444; font-size: 1.3em;">🔥 ¡OFERTA!</strong><br>
                    <span style="font-size: 1.1em; color: white;">Descuento del <strong
                            id="porcentaje_preview">0</strong>%</span><br>
                    <span style="color: #ccc; font-size: 0.9em;">De S/ <span id="precio_antes">0</span> a S/ <span
                            id="precio_ahora">0</span></span>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" name="editar_membresia" class="btn" style="width: 100%;">💾 Guardar
                        Cambios</button>
                    <button type="button" class="btn btn-back" onclick="cerrarModalEditar()"
                        style="width: 100%; margin-top: 10px;">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Auto-ocultar mensaje de éxito
    if (document.getElementById('alertSuccess')) {
        setTimeout(function() {
            var msg = document.getElementById('alertSuccess');
            if (msg) {
                msg.style.opacity = '0';
                setTimeout(function() {
                    msg.remove();
                }, 500);
            }
        }, 3000);
    }

    function abrirModalEditar(membresia) {
        document.getElementById('edit_cod').value = membresia.Cod_Tipo_Membresia;
        document.getElementById('edit_nombre').value = membresia.Nombre;

        const precioBase = parseFloat(membresia.precio_base || 0);
        const precioOferta = parseFloat(membresia.precio_oferta || precioBase);
        const tieneOferta = parseInt(membresia.tiene_oferta || 0);

        const precioActual = tieneOferta ? precioOferta : precioBase;

        document.getElementById('edit_precio_anterior').value = precioActual;
        document.getElementById('edit_precio_base_display').value = 'S/ ' + precioActual.toFixed(2);
        document.getElementById('edit_precio_nuevo').value = precioActual;

        document.getElementById('preview_descuento').style.display = 'none';

        document.getElementById('modalEditarMembresia').style.display = 'block';
    }

    function calcularDescuento() {
        const precioAnterior = parseFloat(document.getElementById('edit_precio_anterior').value);
        const precioNuevo = parseFloat(document.getElementById('edit_precio_nuevo').value);

        if (precioNuevo < precioAnterior && precioNuevo > 0) {
            const porcentaje = ((precioAnterior - precioNuevo) / precioAnterior) * 100;

            document.getElementById('porcentaje_preview').innerText = porcentaje.toFixed(0);
            document.getElementById('precio_antes').innerText = precioAnterior.toFixed(2);
            document.getElementById('precio_ahora').innerText = precioNuevo.toFixed(2);
            document.getElementById('preview_descuento').style.display = 'block';
        } else {
            document.getElementById('preview_descuento').style.display = 'none';
        }
    }

    function cerrarModalEditar() {
        document.getElementById('modalEditarMembresia').style.display = 'none';
    }

    function venderMembresia(codTipo, nombre, precio) {
        alert('Función de venta para ' + nombre + ' - S/ ' + precio.toFixed(2) + '\n(Por implementar)');
    }

    window.onclick = function(event) {
        if (event.target.id === 'modalEditarMembresia') {
            cerrarModalEditar();
        }
    }
    </script>
</body>

</html>