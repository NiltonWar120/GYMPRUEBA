<?php
session_start();
require_once '../conexion.php';

// editar membresia
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['cod_cargo'], [1, 2])) {
    header('Location: ../login.php');
    exit;
}


$usuario = $_SESSION['usuario'];

// PROCESAR EDICIÓN DE MEMBRESÍA
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar_membresia'])) {
    $cod_tipo = intval($_POST['cod_tipo']);
    $nombre = trim($_POST['nombre']);
    $precio_nuevo = floatval($_POST['precio_nuevo']);
    $precio_anterior = floatval($_POST['precio_anterior']);
    
    // Calcular si hay oferta
    $tiene_oferta = 0;
    $porcentaje_descuento = 0;
    
    if ($precio_nuevo < $precio_anterior && $precio_anterior > 0) {
        $tiene_oferta = 1;
        $porcentaje_descuento = round((($precio_anterior - $precio_nuevo) / $precio_anterior) * 100, 2);
    }
    
    $sql_update = "UPDATE tipo_membresia SET Nombre=?, precio_base=?, precio_oferta=?, tiene_oferta=?, porcentaje_descuento=? WHERE Cod_Tipo_Membresia=?";
    $stmt = $conexion->prepare($sql_update);
    $stmt->bind_param("sddidd", $nombre, $precio_anterior, $precio_nuevo, $tiene_oferta, $porcentaje_descuento, $cod_tipo);
    
    if ($stmt->execute()) {
        header("Location: gestionar_membresias.php?success=1");
        exit;
    }
}

// Consultar membresías
$sql = "SELECT * FROM tipo_membresia WHERE Estado = 1 ORDER BY Cod_Tipo_Membresia ASC";
$result = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Membresías - PRO FIT Gym</title>
    <link rel="icon" type="image/png" href="../fit.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
            box-shadow: 0 8px 25px rgba(0,0,0,0.5);
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
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .membresia-card {
            background: #2b1a0e;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #b8860b;
            position: relative;
        }
        .oferta-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background: linear-gradient(135deg, #ff4444, #cc0000);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            box-shadow: 0 4px 10px rgba(255,0,0,0.4);
        }
        .precio-container {
            margin: 15px 0;
            text-align: center;
        }
        .precio-anterior {
            text-decoration: line-through;
            color: #888;
            font-size: 1.2em;
        }
        .precio-actual {
            font-size: 2em;
            font-weight: bold;
            color: #d4af37;
        }
        .alert-success {
            background: #4CAF50;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
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
        <h1>⚙️ Gestionar Membresías - PRO FIT Gym</h1>
        <div>
            <a href="../dashboard.php" class="btn btn-back">← REGRESAR</a>
        </div>
    </div>
    
    <div class="container">
        <?php 
        if (isset($_GET['success'])) {
            echo '<div class="alert-success" id="alertSuccess">✅ Membresía actualizada exitosamente</div>';
        }
        ?>
        
        <div class="card">
            <h2>Listado de Membresías</h2>
            
            <div class="membresias-grid">
                <?php
                if ($result && $result->num_rows > 0) {
                    while ($membresia = $result->fetch_assoc()) {
                        $cod = $membresia['Cod_Tipo_Membresia'];
                        $nombre = $membresia['Nombre'];
                        $precio_base = $membresia['precio_base'] ?? 0;
                        $precio_oferta = $membresia['precio_oferta'] ?? $precio_base;
                        $tiene_oferta = $membresia['tiene_oferta'] ?? 0;
                        $porcentaje = $membresia['porcentaje_descuento'] ?? 0;
                        
                        $precio_actual = $tiene_oferta ? $precio_oferta : $precio_base;
                        
                        echo '<div class="membresia-card">';
                        
                        if ($tiene_oferta) {
                            echo '<div class="oferta-badge">🔥 -' . number_format($porcentaje, 0) . '%</div>';
                        }
                        
                        echo '<h3>' . htmlspecialchars($nombre) . '</h3>';
                        
                        echo '<div class="precio-container">';
                        if ($tiene_oferta) {
                            echo '<div class="precio-anterior">S/ ' . number_format($precio_base, 2) . '</div>';
                        }
                        echo '<div class="precio-actual">S/ ' . number_format($precio_actual, 2) . '</div>';
                        echo '</div>';
                        
                        $mem_json = htmlspecialchars(json_encode($membresia), ENT_QUOTES, 'UTF-8');
                        
                        echo '<button class="btn" onclick=\'abrirModalEditar(' . $mem_json . ')\' style="width: 100%;">✏️ Editar Membresía</button>';
                        
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Modal Editar -->
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal()">&times;</span>
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
                    <input type="text" id="edit_precio_base_display" disabled style="background: #333;">
                </div>
                
                <div class="form-group">
                    <label>Nuevo Precio (S/) *</label>
                    <input type="number" name="precio_nuevo" id="edit_precio_nuevo" step="0.01" min="0" required oninput="calcularDescuento()">
                </div>
                
                <div id="preview_descuento" style="display:none;" class="preview-descuento">
                    <strong style="color: #ff4444; font-size: 1.3em;">🔥 ¡OFERTA!</strong><br>
                    <span style="font-size: 1.1em; color: white;">Descuento del <strong id="porcentaje_preview">0</strong>%</span><br>
                    <span style="color: #ccc; font-size: 0.9em;">De S/ <span id="precio_antes">0</span> a S/ <span id="precio_ahora">0</span></span>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" name="editar_membresia" class="btn">💾 Guardar Cambios</button>
                    <button type="button" class="btn btn-back" onclick="cerrarModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    setTimeout(function() {
        var msg = document.getElementById('alertSuccess');
        if (msg) {
            msg.style.opacity = '0';
            setTimeout(function() { msg.remove(); }, 500);
        }
    }, 3000);

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
        
        document.getElementById('modalEditar').style.display = 'block';
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

    function cerrarModal() {
        document.getElementById('modalEditar').style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            cerrarModal();
        }
    }
    </script>
</body>
</html>
