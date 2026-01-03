<?php
session_start();
require_once 'conexion.php';

// Verificar si ya está logueado
if (isset($_SESSION['usuario']) && isset($_SESSION['usuario']['dni'])) {
    header('Location: dashboard.php');
    exit;
}

// Procesar login
$error = '';
if ($_POST && isset($_POST['usuario']) && isset($_POST['password'])) {
    $usuario_input = trim($_POST['usuario']);
    $password = $_POST['password'];
    $tipo_usuario = isset($_POST['tipo_usuario']) ? $_POST['tipo_usuario'] : 'recepcionista';
    
    // Consulta CORRECTA con campos existentes
    if ($tipo_usuario === 'recepcionista') {
        $sql = "SELECT c.DNI, c.Nombre, c.Password, c.Email, ca.Nombre as rol, c.Cod_Cargo 
                FROM gimnasio_colaboradores c 
                INNER JOIN gimnasio_cargo ca ON c.Cod_Cargo = ca.Cod_Cargo 
                WHERE (c.DNI = ? OR c.Email = ?) AND c.Estado = 1 AND c.Cod_Cargo IN (2, 3)";
    } else {
        $sql = "SELECT c.DNI, c.Nombre, c.Password, c.Email, ca.Nombre as rol, c.Cod_Cargo 
                FROM gimnasio_colaboradores c 
                INNER JOIN gimnasio_cargo ca ON c.Cod_Cargo = ca.Cod_Cargo 
                WHERE (c.DNI = ? OR c.Email = ?) AND c.Estado = 1 AND c.Cod_Cargo = 1";
    }
    
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $usuario_input, $usuario_input);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $usuario = $result->fetch_assoc();
            
            // Verificar contraseña
            if ($usuario['Password'] === $password) {
                // Crear sesión
                $_SESSION['usuario'] = [
                    'dni' => $usuario['DNI'],
                    'nombre' => $usuario['Nombre'],
                    'email' => $usuario['Email'],
                    'rol' => $usuario['rol'],
                    'cod_cargo' => $usuario['Cod_Cargo']
                ];
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = "Contraseña incorrecta";
            }
        } else {
            $error = "Usuario no encontrado o no tiene acceso con este tipo de cuenta";
        }
    } else {
        $error = "Error en la consulta de base de datos: " . $conexion->error;
    }
}
?>

<!-- El HTML permanece igual -->

<!-- El resto del HTML permanece igual -->
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PRO FIT Gym</title>
    <link rel="icon" type="image/png" href="fit.png">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: Arial, sans-serif;
        background: linear-gradient(rgba(26, 26, 26, 0.7), rgba(43, 26, 14, 0.7)),
            url('reecpcion01.jpg') no-repeat center center fixed;
        background-size: cover;
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .login-container {
        background: rgba(255, 255, 255, 0.95);
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        width: 400px;
        border: 3px solid #d4af37;
        backdrop-filter: blur(5px);
    }

    h2 {
        text-align: center;
        margin-bottom: 10px;
        color: #1a1a1a;
    }

    .subtitle {
        text-align: center;
        color: #666;
        margin-bottom: 30px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        margin-bottom: 5px;
        color: #555;
        font-weight: bold;
    }

    input,
    select {
        width: 100%;
        padding: 12px;
        border: 2px solid #ddd;
        border-radius: 8px;
        font-size: 16px;
    }

    input:focus,
    select:focus {
        border-color: #d4af37;
        outline: none;
    }

    button {
        width: 100%;
        padding: 15px;
        background: #d4af37;
        color: #1a1a1a;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
        font-weight: bold;
        transition: all 0.3s ease;
    }

    button:hover {
        background: #b8860b;
        transform: translateY(-2px);
    }

    .error {
        color: red;
        text-align: center;
        margin-top: 10px;
        padding: 10px;
        background: #ffe6e6;
        border-radius: 5px;
    }

    .role-selector {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
    }

    .role-btn {
        flex: 1;
        padding: 20px;
        text-align: center;
        border: 3px solid #ddd;
        border-radius: 10px;
        cursor: pointer;
        font-weight: bold;
        transition: all 0.3s ease;
        background: #f8f9fa;
        color: #555;
        font-size: 14px;
    }

    .role-btn.active {
        border-color: #d4af37;
        background: #d4af37;
        color: #1a1a1a;
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
    }

    .role-btn:hover {
        border-color: #b8860b;
        transform: translateY(-2px);
    }

    .role-icon {
        font-size: 24px;
        display: block;
        margin-bottom: 8px;
    }

    .credential-box {
        margin-top: 25px;
        text-align: center;
        font-size: 12px;
        color: #666;
        background: #f5f5f5;
        padding: 15px;
        border-radius: 8px;
        border-left: 4px solid #d4af37;
    }

    /* Media queries para responsividad */
    @media (max-width: 480px) {
        .login-container {
            width: 90%;
            padding: 25px;
        }

        .role-selector {
            flex-direction: column;
        }

        .credential-box>div {
            flex-direction: column;
        }
    }
    </style>
</head>

<body>
    <div class="login-container">
        <h2> PRO FIT GYM</h2>
        <p class="subtitle">Sistema de Gestión Integral</p>

        <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label>Tipo de Usuario:</label>
                <div class="role-selector">
                    <div class="role-btn active" id="btnRecepcionista" onclick="selectTipoUsuario('recepcionista')">
                        <span class="role-icon"></span>
                        RECEPCIONISTA
                    </div>
                    <div class="role-btn" id="btnAdministrador" onclick="selectTipoUsuario('administrador')">
                        <span class="role-icon"></span>
                        ADMINISTRADOR
                    </div>
                </div>
                <input type="hidden" name="tipo_usuario" id="tipoUsuario" value="recepcionista" required>
            </div>

            <div class="form-group">
                <label>Usuario:</label>
                <input type="text" name="usuario" autocomplete="off" maxlength="8" placeholder="DNI " required>
            </div>

            <div class="form-group">
                <label>Contraseña:</label>
                <input type="password" name="password" autocomplete="off" placeholder="••••••••" required>
            </div>

            <button type="submit">ACCEDER AL SISTEMA</button>
        </form>


        <script>
        function selectTipoUsuario(tipo) {
            document.getElementById('tipoUsuario').value = tipo;

            // Remover clase active de todos los botones
            document.querySelectorAll('.role-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Agregar clase active al botón clickeado
            event.target.classList.add('active');

            // Actualizar placeholder según el tipo de usuario
            const usuarioInput = document.querySelector('input[name="usuario"]');
            if (tipo === 'administrador') {
                usuarioInput.placeholder = "Ej: 12345678 o admin@profit.com";
            } else {
                usuarioInput.placeholder = "Ej: 87654321 o recepcion@profit.com";
            }
        }

        // Inicializar con recepcionista seleccionado
        document.addEventListener('DOMContentLoaded', function() {
            selectTipoUsuario('recepcionista');
        });
        </script>
</body>

</html>