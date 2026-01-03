<?php
session_start();
require_once '../conexion.php';

if (!isset($_SESSION['usuario']) || !isset($_GET['cod_coach'])) {
    echo '<p style="color: red; text-align: center;">❌ Acceso denegado</p>';
    exit;
}

$cod_coach = intval($_GET['cod_coach']);

$sql = "SELECT Cod_Horario, Turno FROM horario WHERE Cod_Coach = ? AND Estado = 1 ORDER BY Cod_Horario ASC";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $cod_coach);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    echo '<div style="max-height: 400px; overflow-y: auto;">';
    while ($row = $result->fetch_assoc()) {
        echo '<div class="horario-item">';
        echo '<div><strong style="color: #d4af37;">⏰ ' . htmlspecialchars($row['Turno']) . '</strong></div>';
        echo '<button class="btn" style="background: linear-gradient(135deg, #ff6b6b, #ee5a52); color: white;" onclick="quitarHorario(' . $row['Cod_Horario'] . ', \'' . htmlspecialchars($row['Turno']) . '\')">❌ Quitar</button>';
        echo '</div>';
    }
    echo '</div>';
} else {
    echo '<p style="text-align: center; color: #888; padding: 30px;">📭 Este coach no tiene horarios asignados</p>';
}

$stmt->close();
?>
