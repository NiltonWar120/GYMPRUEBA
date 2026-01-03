<?php
session_start();
require_once '../conexion.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['cod_cargo'] != 1) {
    header('Location: ../login.php');
    exit;
}

$usuario = $_SESSION['usuario'];

// Procesar filtros
$mes_filtro = isset($_GET['mes']) ? intval($_GET['mes']) : 0;
$anio_filtro = isset($_GET['anio']) ? intval($_GET['anio']) : date('Y');
$tipo_reporte = isset($_GET['tipo_reporte']) ? $_GET['tipo_reporte'] : '';

// Obtener ingresos mensuales del año actual
$sql_ingresos = "SELECT 
                    MONTH(Fecha_Inicio) as mes,
                    YEAR(Fecha_Inicio) as anio,
                    SUM(Precio) as total
                FROM MEMBRESIA
                WHERE Estado = 1 AND YEAR(Fecha_Inicio) = ?";
if ($mes_filtro > 0) {
    $sql_ingresos .= " AND MONTH(Fecha_Inicio) = ?";
}
$sql_ingresos .= " GROUP BY MONTH(Fecha_Inicio), YEAR(Fecha_Inicio) ORDER BY mes ASC";

$stmt_ingresos = $conexion->prepare($sql_ingresos);
if ($mes_filtro > 0) {
    $stmt_ingresos->bind_param("ii", $anio_filtro, $mes_filtro);
} else {
    $stmt_ingresos->bind_param("i", $anio_filtro);
}
$stmt_ingresos->execute();
$result_ingresos = $stmt_ingresos->get_result();
$ingresos_mensuales = [];
if ($result_ingresos && $result_ingresos->num_rows > 0) {
    $ingresos_mensuales = $result_ingresos->fetch_all(MYSQLI_ASSOC);
}

// Obtener distribución de membresías por tipo
$sql_distribucion = "SELECT 
                        tm.Nombre as tipo,
                        COUNT(*) as cantidad
                    FROM MEMBRESIA m
                    INNER JOIN TIPO_MEMBRESIA tm ON m.Cod_Tipo_Membresia = tm.Cod_Tipo_Membresia
                    WHERE m.Estado = 1 AND YEAR(m.Fecha_Inicio) = ?";
if ($mes_filtro > 0) {
    $sql_distribucion .= " AND MONTH(m.Fecha_Inicio) = ?";
}
$sql_distribucion .= " GROUP BY tm.Nombre ORDER BY cantidad DESC";

$stmt_dist = $conexion->prepare($sql_distribucion);
if ($mes_filtro > 0) {
    $stmt_dist->bind_param("ii", $anio_filtro, $mes_filtro);
} else {
    $stmt_dist->bind_param("i", $anio_filtro);
}
$stmt_dist->execute();
$result_distribucion = $stmt_dist->get_result();
$distribucion_membresias = [];
$total_membresias_dist = 0;
if ($result_distribucion && $result_distribucion->num_rows > 0) {
    $distribucion_membresias = $result_distribucion->fetch_all(MYSQLI_ASSOC);
    foreach ($distribucion_membresias as $dist) {
        $total_membresias_dist += $dist['cantidad'];
    }
}

// Estadísticas generales
$sql_clientes_totales = "SELECT COUNT(*) as total FROM CLIENTE WHERE Estado = 1";
$result_clientes = $conexion->query($sql_clientes_totales);
$clientes_totales = $result_clientes->fetch_assoc()['total'];

$sql_membresias_activas = "SELECT COUNT(*) as total FROM MEMBRESIA WHERE Estado = 1 AND Fecha_Fin >= CURDATE()";
$result_activas = $conexion->query($sql_membresias_activas);
$membresias_activas = $result_activas->fetch_assoc()['total'];

$sql_ingreso_anual = "SELECT SUM(Precio) as total FROM MEMBRESIA WHERE Estado = 1 AND YEAR(Fecha_Inicio) = ?";
$stmt_anual = $conexion->prepare($sql_ingreso_anual);
$stmt_anual->bind_param("i", $anio_filtro);
$stmt_anual->execute();
$result_anual = $stmt_anual->get_result();
$ingreso_anual = $result_anual->fetch_assoc()['total'] ?? 0;

// Array de meses en español
$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

// Calcular el máximo ingreso para las barras proporcionales
$max_ingreso = 0;
foreach ($ingresos_mensuales as $ingreso) {
    if ($ingreso['total'] > $max_ingreso) {
        $max_ingreso = $ingreso['total'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes y Estadísticas - PRO FIT Gym</title>
    <link rel="icon" type="image/png" href="../fit.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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
        box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
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
        padding: 12px 25px;
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
        font-size: 15px;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(212, 175, 55, 0.4);
    }

    .btn-back {
        background: linear-gradient(135deg, #666, #888);
        color: white;
    }

    .btn-pdf {
        background: linear-gradient(135deg, #ff0000, #cc0000);
        color: white;
    }

    .filtros {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr auto auto;
        gap: 15px;
        margin-bottom: 20px;
        align-items: center;
    }

    /* Diseño mejorado para SELECT */
    .filtros select {
        padding: 12px 40px 12px 15px;
        background: linear-gradient(135deg, #fffefeff, #1a1a1a);
        border: 2px solid #b8860b;
        border-radius: 10px;
        color: #d4af37;
        font-size: 15px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23d4af37' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 10px center;
        background-size: 20px;
    }

    .filtros select:hover {
        border-color: #d4af37;
        box-shadow: 0 0 15px rgba(212, 175, 55, 0.3);
        transform: translateY(-2px);
    }

    .filtros select:focus {
        border-color: #d4af37;
        outline: none;
        box-shadow: 0 0 20px rgba(212, 175, 55, 0.5);
    }

    .filtros select option {
        background: #1a1a1a;
        color: #d4af37;
        padding: 10px;
        font-weight: bold;
    }

    .filtros select option:hover {
        background: #d4af37;
        color: #1a1a1a;
    }

    .grafico-container {
        background: #2b1a0e;
        padding: 20px;
        border-radius: 10px;
        border: 1px solid #b8860b;
        margin: 10px 0;
        position: relative;
    }

    .grafico-selector {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
        padding: 15px;
        background: rgba(235, 233, 220, 0.1);
        border-radius: 8px;
        border: 1px solid rgba(212, 175, 55, 0.3);
    }

    .grafico-selector label {
        color: #d4af37;
        font-weight: bold;
        font-size: 16px;
    }

    /* SELECT dentro de gráficos con diseño especial */
    .grafico-selector select {
        padding: 10px 40px 10px 15px;
        background: linear-gradient(135deg, #fffefcff, #fcd87eff);
        border: 2px solid #1a1a1a;
        border-radius: 10px;
        color: #edededff;
        font-size: 15px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%231a1a1a' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 10px center;
        background-size: 20px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    }

    .grafico-selector select:hover {
        background: linear-gradient(135deg, #b8860b, #d4af37);
        transform: scale(1.05);
        box-shadow: 0 6px 15px rgba(212, 175, 55, 0.5);
    }

    .grafico-selector select:focus {
        outline: none;
        box-shadow: 0 0 20px rgba(212, 175, 55, 0.7);
    }

    .grafico-selector select option {
        background: #1a1a1a;
        color: #d4af37;
        padding: 12px;
        font-weight: bold;
    }

    .canvas-container {
        max-height: 400px;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .estadisticas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .estadistica-card {
        background: linear-gradient(135deg, #2b1a0e, #1a1a1a);
        padding: 25px;
        border-radius: 15px;
        border: 2px solid #b8860b;
        text-align: center;
        transition: all 0.3s ease;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    }

    .estadistica-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 8px 20px rgba(212, 175, 55, 0.4);
        border-color: #d4af37;
    }

    .numero {
        font-size: 2.5em;
        font-weight: bold;
        color: #d4af37;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    }

    h2 {
        color: #d4af37;
        margin-bottom: 20px;
        font-size: 1.8em;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    }

    /* Animación de carga */
    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }

    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        animation: pulse 1.5s ease-in-out infinite;
    }
    
    </style>
</head>

<body>
    <div class="header">
        <h1>📈 Reportes y Estadísticas - PRO FIT Gym</h1>
        <div>
            <a href="../dashboard.php" class="btn btn-back">← REGRESAR</a>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <h2>🔍 Filtros de Reportes</h2>
            <form method="GET" action="">
                <div class="filtros">
                    <select name="mes">
                        <option value="0">📅 Todos los meses</option>
                        <?php foreach ($meses as $num => $nombre): ?>
                        <option value="<?php echo $num; ?>" <?php echo $mes_filtro == $num ? 'selected' : ''; ?>>
                            <?php echo $nombre; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="anio">
                        <option value="2023" <?php echo $anio_filtro == 2023 ? 'selected' : ''; ?>>📆 2023</option>
                        <option value="2024" <?php echo $anio_filtro == 2024 ? 'selected' : ''; ?>>📆 2024</option>
                        <option value="2025" <?php echo $anio_filtro == 2025 ? 'selected' : ''; ?>>📆 2025</option>
                    </select>
                    <select name="tipo_reporte">
                        <option value="">📋 Todos los tipos</option>
                        <option value="ingresos" <?php echo $tipo_reporte == 'ingresos' ? 'selected' : ''; ?>>💰 Ingresos
                        </option>
                        <option value="membresias" <?php echo $tipo_reporte == 'membresias' ? 'selected' : ''; ?>>
                            🎫 Membresías</option>
                        <option value="clientes" <?php echo $tipo_reporte == 'clientes' ? 'selected' : ''; ?>>👥 Clientes
                        </option>
                    </select>
                    <button type="submit" class="btn">🔍 Filtrar</button>
                    <button type="button" class="btn btn-pdf" onclick="generarPDF()">📄 Generar PDF</button>
                </div>
            </form>
        </div>

        <div id="reporte-contenido">
            <div class="card">
                <h2>💰 Ingresos Mensuales - <?php echo $anio_filtro; ?></h2>
                <div class="grafico-container">
                    <div class="grafico-selector">
                        <label>🎨 Tipo de gráfico:</label>
                        <select id="tipoGraficoIngresos" onchange="cambiarGraficoIngresos()">
                            <option value="bar">📊 Barras</option>
                            <option value="pie">🥧 Pastel</option>
                            <option value="line">📈 Línea</option>
                        </select>
                    </div>
                    <div class="canvas-container">
                        <canvas id="chartIngresos" style="max-height: 400px;"></canvas>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2>🎫 Distribución de Membresías</h2>
                <div class="grafico-container">
                    <div class="grafico-selector">
                        <label>🎨 Tipo de gráfico:</label>
                        <select id="tipoGraficoMembresias" onchange="cambiarGraficoMembresias()">
                            <option value="bar">📊 Barras</option>
                            <option value="pie" selected>🥧 Pastel</option>
                            <option value="doughnut">🍩 Dona</option>
                        </select>
                    </div>
                    <div class="canvas-container">
                        <canvas id="chartMembresias" style="max-height: 400px;"></canvas>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2>📊 Estadísticas Generales</h2>
                <div class="estadisticas-grid">
                    <div class="estadistica-card">
                        <div class="numero"><?php echo $clientes_totales; ?></div>
                        <div style="color: #fff; margin-top: 10px; font-size: 1.1em;">👥 Clientes Totales</div>
                    </div>
                    <div class="estadistica-card">
                        <div class="numero"><?php echo $membresias_activas; ?></div>
                        <div style="color: #fff; margin-top: 10px; font-size: 1.1em;">✅ Membresías Activas</div>
                    </div>
                    <div class="estadistica-card">
                        <div class="numero">S/ <?php echo number_format($ingreso_anual, 2); ?></div>
                        <div style="color: #fff; margin-top: 10px; font-size: 1.1em;">💵 Ingreso Anual <?php echo $anio_filtro; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
// Datos PHP para JavaScript
const datosIngresos = <?php echo json_encode($ingresos_mensuales); ?>;
const datosMembresias = <?php echo json_encode($distribucion_membresias); ?>;
const meses = <?php echo json_encode($meses); ?>;

let chartIngresos = null;
let chartMembresias = null;

// Inicializar gráficos
document.addEventListener('DOMContentLoaded', function() {
    // Validar datos antes de crear gráficos
    if (datosIngresos && datosIngresos.length > 0) {
        crearGraficoIngresos('bar');
    } else {
        mostrarMensajeNoData('chartIngresos', 'No hay datos de ingresos para mostrar');
    }
    
    if (datosMembresias && datosMembresias.length > 0) {
        crearGraficoMembresias('pie');
    } else {
        mostrarMensajeNoData('chartMembresias', 'No hay datos de membresías para mostrar');
    }
});

function mostrarMensajeNoData(canvasId, mensaje) {
    const canvas = document.getElementById(canvasId);
    const ctx = canvas.getContext('2d');
    canvas.width = 600;
    canvas.height = 300;
    
    ctx.fillStyle = '#d4af37';
    ctx.font = 'bold 18px Arial';
    ctx.textAlign = 'center';
    ctx.fillText(mensaje, canvas.width / 2, canvas.height / 2);
}

function crearGraficoIngresos(tipo) {
    const canvas = document.getElementById('chartIngresos');
    if (!canvas) {
        console.error('Canvas chartIngresos no encontrado');
        return;
    }
    
    const ctx = canvas.getContext('2d');
    if (!ctx) {
        console.error('No se pudo obtener el contexto 2D');
        return;
    }

    if (chartIngresos) {
        chartIngresos.destroy();
    }

    // Validar que hay datos
    if (!datosIngresos || datosIngresos.length === 0) {
        mostrarMensajeNoData('chartIngresos', 'No hay datos de ingresos disponibles');
        return;
    }

    const labels = datosIngresos.map(item => meses[item.mes] || 'Mes desconocido');
    const data = datosIngresos.map(item => parseFloat(item.total) || 0);

    const colores = tipo === 'pie' ? [
        '#d4af37', '#44ff44', '#ffaa00', '#8888ff', '#ff4444', '#ff88ff',
        '#44ffff', '#ff8844', '#88ff44', '#ff44ff', '#4488ff', '#ffff44'
    ] : '#d4af37';

    try {
        chartIngresos = new Chart(ctx, {
            type: tipo,
            data: {
                labels: labels,
                datasets: [{
                    label: 'Ingresos (S/)',
                    data: data,
                    backgroundColor: colores,
                    borderColor: tipo === 'line' ? '#d4af37' : 'rgba(0,0,0,0.8)',
                    borderWidth: 2,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: tipo === 'pie',
                        labels: {
                            color: '#d4af37',
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let value = context.parsed.y !== undefined ? context.parsed.y : context.parsed;
                                return 'S/ ' + (value || 0).toFixed(2);
                            }
                        }
                    }
                },
                scales: tipo !== 'pie' ? {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#d4af37',
                            callback: function(value) {
                                return 'S/ ' + value.toFixed(0);
                            }
                        },
                        grid: {
                            color: 'rgba(212, 175, 55, 0.2)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#d4af37'
                        },
                        grid: {
                            color: 'rgba(212, 175, 55, 0.2)'
                        }
                    }
                } : {}
            }
        });
    } catch (error) {
        console.error('Error al crear gráfico de ingresos:', error);
        mostrarMensajeNoData('chartIngresos', 'Error al cargar el gráfico');
    }
}

function crearGraficoMembresias(tipo) {
    const canvas = document.getElementById('chartMembresias');
    if (!canvas) {
        console.error('Canvas chartMembresias no encontrado');
        return;
    }
    
    const ctx = canvas.getContext('2d');
    if (!ctx) {
        console.error('No se pudo obtener el contexto 2D');
        return;
    }

    if (chartMembresias) {
        chartMembresias.destroy();
    }

    // Validar que hay datos
    if (!datosMembresias || datosMembresias.length === 0) {
        mostrarMensajeNoData('chartMembresias', 'No hay datos de membresías disponibles');
        return;
    }

    const labels = datosMembresias.map(item => item.tipo || 'Sin nombre');
    const data = datosMembresias.map(item => parseInt(item.cantidad) || 0);

    const colores = ['#d4af37', '#44ff44', '#ffaa00', '#8888ff', '#ff4444', '#ff88ff'];

    try {
        chartMembresias = new Chart(ctx, {
            type: tipo,
            data: {
                labels: labels,
                datasets: [{
                    label: 'Cantidad de Membresías',
                    data: data,
                    backgroundColor: colores,
                    borderColor: 'rgba(0,0,0,0.8)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            color: '#d4af37',
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                // CORRECCIÓN AQUÍ: usar context.parsed correctamente según el tipo de gráfico
                                let value;
                                if (tipo === 'bar') {
                                    value = context.parsed.y;
                                } else {
                                    value = context.parsed;
                                }
                                return context.label + ': ' + value + ' clientes';
                            }
                        }
                    }
                },
                scales: tipo === 'bar' ? {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#d4af37',
                            stepSize: 1
                        },
                        grid: {
                            color: 'rgba(212, 175, 55, 0.2)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#d4af37'
                        },
                        grid: {
                            color: 'rgba(212, 175, 55, 0.2)'
                        }
                    }
                } : {}
            }
        });
    } catch (error) {
        console.error('Error al crear gráfico de membresías:', error);
        mostrarMensajeNoData('chartMembresias', 'Error al cargar el gráfico');
    }
}


function cambiarGraficoIngresos() {
    const tipo = document.getElementById('tipoGraficoIngresos').value;
    crearGraficoIngresos(tipo);
}

function cambiarGraficoMembresias() {
    const tipo = document.getElementById('tipoGraficoMembresias').value;
    crearGraficoMembresias(tipo);
}

function generarPDF() {
    const {
        jsPDF
    } = window.jspdf;

    const btnPDF = event.target;
    const textoOriginal = btnPDF.innerHTML;
    btnPDF.innerHTML = '⏳ Generando PDF...';
    btnPDF.disabled = true;

    html2canvas(document.getElementById('reporte-contenido'), {
        scale: 2,
        backgroundColor: '#2b1a0e'
    }).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF('p', 'mm', 'a4');

        const imgWidth = 190;
        const pageHeight = 277;
        const imgHeight = (canvas.height * imgWidth) / canvas.width;
        let heightLeft = imgHeight;
        let position = 10;

        pdf.setFontSize(18);
        pdf.setTextColor(212, 175, 55);
        pdf.text('Reporte PRO FIT Gym - <?php echo $anio_filtro; ?>', 105, position, {
            align: 'center'
        });
        position += 10;

        pdf.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;

        while (heightLeft >= 0) {
            position = heightLeft - imgHeight + 10;
            pdf.addPage();
            pdf.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
        }

        pdf.save('Reporte_PRO_FIT_<?php echo $anio_filtro; ?>.pdf');

        btnPDF.innerHTML = textoOriginal;
        btnPDF.disabled = false;
    }).catch(error => {
        console.error('Error al generar PDF:', error);
        alert('Error al generar el PDF');
        btnPDF.innerHTML = textoOriginal;
        btnPDF.disabled = false;
    });
}
</script>


</body>

</html>
