<?php
session_start();

// Verificación robusta de sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario']['dni'])) {
    header('Location: login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$usuario['nombre'] = isset($usuario['nombre']) ? $usuario['nombre'] : 'Usuario';
$usuario['rol'] = isset($usuario['rol']) ? $usuario['rol'] : 'Sin rol';
$usuario['email'] = isset($usuario['email']) ? $usuario['email'] : 'Sin email';
$usuario['cod_cargo'] = isset($usuario['cod_cargo']) ? $usuario['cod_cargo'] : 2;

require_once 'conexion.php';

// Obtener estadísticas del gimnasio
$stats = array(
    'total_clientes' => 0,
    'membresias_activas' => 0,
    'por_vencer' => 0,
    'vencidas' => 0
);

// Total de clientes
$sql_total = "SELECT COUNT(*) as total FROM CLIENTE WHERE Estado = 1";
$result = $conexion->query($sql_total);
if ($result) {
    $stats['total_clientes'] = $result->fetch_assoc()['total'];
}

// Membresías activas (más de 10 días)
$sql_activas = "SELECT COUNT(*) as total FROM MEMBRESIA m 
                WHERE m.Estado = 1 AND DATEDIFF(m.Fecha_Fin, CURDATE()) > 10";
$result = $conexion->query($sql_activas);
if ($result) {
    $stats['membresias_activas'] = $result->fetch_assoc()['total'];
}

// Por vencer (1-10 días)
$sql_por_vencer = "SELECT COUNT(*) as total FROM MEMBRESIA m 
                   WHERE m.Estado = 1 AND DATEDIFF(m.Fecha_Fin, CURDATE()) BETWEEN 1 AND 10";
$result = $conexion->query($sql_por_vencer);
if ($result) {
    $stats['por_vencer'] = $result->fetch_assoc()['total'];
}

// Vencidas
$sql_vencidas = "SELECT COUNT(*) as total FROM MEMBRESIA m 
                 WHERE m.Estado = 1 AND DATEDIFF(m.Fecha_Fin, CURDATE()) < 0";
$result = $conexion->query($sql_vencidas);
if ($result) {
    $stats['vencidas'] = $result->fetch_assoc()['total'];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRO FIT Gym - Dashboard</title>
    <link rel="icon" type="image/png" href="fit.png">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background: linear-gradient(135deg, #1a1a1a 0%, #2b1a0e 100%);
        min-height: 100vh;
        color: #d4af37;
    }

    .header {
        background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%);
        color: #1a1a1a;
        padding: 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
    }

    .user-info {
        text-align: right;
        background: rgba(26, 26, 26, 0.9);
        padding: 15px 20px;
        border-radius: 10px;
        color: #d4af37;
        border: 2px solid #d4af37;
    }

    .user-info strong {
        color: #fff;
        font-size: 18px;
    }

    .nav {
        background: #2b1a0e;
        padding: 20px;
        border-bottom: 3px solid #d4af37;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .nav a {
        color: #d4af37;
        text-decoration: none;
        padding: 15px 25px;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-weight: bold;
        border: 2px solid transparent;
    }

    .nav a:hover {
        background: #d4af37;
        color: #1a1a1a;
        transform: translateY(-2px);
    }

    .container {
        padding: 30px;
        background: rgba(43, 26, 14, 0.8);
        min-height: calc(100vh - 160px);
    }

    .hero-section {
        background: linear-gradient(135deg, rgba(212, 175, 55, 0.9), rgba(184, 134, 11, 0.9)),
                    url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(0,0,0,0.1)" stroke-width="1"/></pattern></defs><rect width="1200" height="600" fill="url(%23grid)"/></svg>');
        padding: 60px;
        border-radius: 20px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        color: #1a1a1a;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .hero-section::before {
        content: '💪';
        position: absolute;
        font-size: 200px;
        opacity: 0.1;
        top: -50px;
        right: 50px;
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
    }

    .hero-content h1 {
        font-size: 3.5em;
        margin-bottom: 20px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        animation: slideIn 0.8s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .hero-content p {
        font-size: 1.4em;
        margin-bottom: 10px;
        opacity: 0.9;
    }

    .hero-motto {
        font-size: 1.8em;
        font-weight: bold;
        margin-top: 20px;
        padding: 20px;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 10px;
        display: inline-block;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 30px;
    }

    .stat-card {
        background: linear-gradient(135deg, #1a1a1a, #2b1a0e);
        padding: 30px;
        border-radius: 15px;
        border: 2px solid #d4af37;
        text-align: center;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(212, 175, 55, 0.1) 0%, transparent 70%);
        animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); opacity: 0.5; }
        50% { transform: scale(1.1); opacity: 0.8; }
    }

    .stat-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(212, 175, 55, 0.4);
        border-color: #fff;
    }

    .stat-icon {
        font-size: 3em;
        margin-bottom: 15px;
    }

    .stat-number {
        font-size: 3em;
        font-weight: bold;
        color: #d4af37;
        position: relative;
        z-index: 1;
    }

    .stat-label {
        color: #fff;
        margin-top: 10px;
        font-size: 1.1em;
        position: relative;
        z-index: 1;
    }

    .alert-card {
        background: linear-gradient(135deg, #8b0000, #ff0000);
        border: 2px solid #ff4444;
    }

    .alert-card .stat-number {
        color: #fff;
    }

    .warning-card {
        background: linear-gradient(135deg, #ff8c00, #ffa500);
        border: 2px solid #ffd700;
    }

    .warning-card .stat-number {
        color: #1a1a1a;
    }

    .btn {
        padding: 15px 30px;
        background: linear-gradient(135deg, #d4af37, #b8860b);
        color: #1a1a1a;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
        font-size: 16px;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(212, 175, 55, 0.5);
    }

    .btn-logout {
        background: linear-gradient(135deg, #8b0000, #ff0000);
        color: white;
    }

    .motivational-banner {
        background: linear-gradient(45deg, #1a1a1a, #2b1a0e);
        padding: 40px;
        border-radius: 15px;
        border: 3px solid #d4af37;
        text-align: center;
        margin-top: 30px;
        position: relative;
        overflow: hidden;
    }

    .motivational-banner::before {
        content: '🏆';
        position: absolute;
        font-size: 150px;
        opacity: 0.05;
        left: -30px;
        top: -30px;
    }

    .motivational-banner::after {
        content: '🔥';
        position: absolute;
        font-size: 150px;
        opacity: 0.05;
        right: -30px;
        bottom: -30px;
    }

    .motivational-text {
        font-size: 2em;
        color: #d4af37;
        font-weight: bold;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        position: relative;
        z-index: 1;
    }

    .feature-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
        margin-top: 30px;
    }

    .feature-card {
        background: linear-gradient(135deg, #2b1a0e, #1a1a1a);
        padding: 30px;
        border-radius: 15px;
        border: 2px solid #d4af37;
        transition: all 0.3s ease;
    }

    .feature-card:hover {
        transform: scale(1.05);
        box-shadow: 0 10px 30px rgba(212, 175, 55, 0.3);
    }

    .feature-icon {
        font-size: 3.5em;
        margin-bottom: 15px;
        display: block;
    }

    .feature-title {
        color: #d4af37;
        font-size: 1.5em;
        margin-bottom: 10px;
        font-weight: bold;
    }

    .feature-desc {
        color: #fff;
        line-height: 1.6;
    }
    </style>
</head>

<body>
    <div class="header">
        <h1>🏋️ PRO FIT Gym</h1>
        <div class="user-info">
            <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong><br>
            <span><?php echo htmlspecialchars($usuario['rol']); ?></span><br>
            <a href="logout.php" class="btn btn-logout" style="margin-top: 10px;">Cerrar Sesión</a>
        </div>
    </div>

    <div class="nav">
        <?php if ($usuario['cod_cargo'] == 1): // Administrador ?>
        <a href="admin/personal.php">👥 Gestión de Personal</a>
        <a href="admin/horarios.php">🕐 Gestión de Horarios</a>
        <a href="admin/seguimiento.php">📊 Seguimiento de Membresías</a>
        <a href="admin/reportes.php">📈 Reportes y Estadísticas</a>
        <a href="admin/coaches.php">💪 Gestión de Coaches</a>
        <?php else: // Empleado (Recepcionista/Limpieza) ?>
        <a href="empleado/clientes.php">👥 Registrar Cliente</a>
        <a href="empleado/ventas.php">🎫 Vender Membresía</a>
        <a href="empleado/horarios.php">🕐 Asignar Horarios</a>
        <a href="empleado/consultas.php">🔍 Consultar Clientes</a>
        <?php endif; ?>
    </div>

    <div class="container">
        <!-- Hero Section -->
        <div class="hero-section">
            <div class="hero-content">
                <h1>¡Bienvenido a PRO FIT Gym!</h1>
                <p>Tu destino para transformar tu cuerpo y mente</p>
                <p style="font-size: 1.2em; margin-top: 15px;">
                    Hola, <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong>
                </p>
                <div class="hero-motto">
                    "NO PAIN, NO GAIN - SIN DOLOR, NO HAY GANANCIA" 💪
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number"><?php echo $stats['total_clientes']; ?></div>
                <div class="stat-label">Total de Clientes</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-number"><?php echo $stats['membresias_activas']; ?></div>
                <div class="stat-label">Membresías Activas</div>
            </div>

            <div class="stat-card warning-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-number"><?php echo $stats['por_vencer']; ?></div>
                <div class="stat-label">Por Vencer (1-10 días)</div>
            </div>

            <div class="stat-card alert-card">
                <div class="stat-icon">❌</div>
                <div class="stat-number"><?php echo $stats['vencidas']; ?></div>
                <div class="stat-label">Membresías Vencidas</div>
            </div>
        </div>

        <!-- Banner Motivacional -->
        <div class="motivational-banner">
            <div class="motivational-text">
                "El único mal entrenamiento es el que no se hace"
            </div>
            <p style="color: #fff; margin-top: 15px; font-size: 1.2em;">
                - Anónimo
            </p>
        </div>

        <!-- Características del Gimnasio -->
        <div class="feature-grid">
            <div class="feature-card">
                <span class="feature-icon">🏋️‍♂️</span>
                <div class="feature-title">Entrenamiento Personalizado</div>
                <div class="feature-desc">
                    Coaches profesionales que te guiarán en cada paso de tu transformación física
                </div>
            </div>

            <div class="feature-card">
                <span class="feature-icon">⏰</span>
                <div class="feature-title">Horarios Flexibles</div>
                <div class="feature-desc">
                    Abierto de 6:00 AM a 11:00 PM para adaptarnos a tu rutina diaria
                </div>
            </div>

            <div class="feature-card">
                <span class="feature-icon">💎</span>
                <div class="feature-title">Equipamiento Premium</div>
                <div class="feature-desc">
                    Máquinas de última generación y equipamiento de primera calidad
                </div>
            </div>
        </div>
    </div>

    <script>
    // Animación de números contador
    document.addEventListener('DOMContentLoaded', function() {
        const statNumbers = document.querySelectorAll('.stat-number');
        
        statNumbers.forEach(num => {
            const finalValue = parseInt(num.textContent);
            let currentValue = 0;
            const increment = finalValue / 50;
            const duration = 1500;
            const stepTime = duration / 50;
            
            const counter = setInterval(() => {
                currentValue += increment;
                if (currentValue >= finalValue) {
                    num.textContent = finalValue;
                    clearInterval(counter);
                } else {
                    num.textContent = Math.floor(currentValue);
                }
            }, stepTime);
        });

        // Animación de entrada para las tarjetas
        const cards = document.querySelectorAll('.stat-card, .feature-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease-out';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });
    </script>
</body>

</html>
