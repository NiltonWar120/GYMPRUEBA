<?php
session_start();
require_once '../conexion.php';

if (!isset($_SESSION['usuario'])) {
    echo '<p style="color: red;">Sesión expirada</p>';
    exit;
}

$sql_horarios = "SELECT Cod_Horario, Turno 
                 FROM horario 
                 WHERE (Cod_Coach IS NULL OR Cod_Coach = 0) AND Estado = 1 
                 ORDER BY Cod_Horario ASC";
$result = $conexion->query($sql_horarios);

if ($result && $result->num_rows > 0) {
    while ($horario = $result->fetch_assoc()) {
        echo '<div class="checkbox-item">';
        echo '<input type="checkbox" name="horarios[]" value="' . $horario['Cod_Horario'] . '" id="h_' . $horario['Cod_Horario'] . '">';
        echo '<label for="h_' . $horario['Cod_Horario'] . '" style="display: inline; color: white;">⏰ ' . htmlspecialchars($horario['Turno']) . '</label>';
        echo '</div>';
    }
} else {
    echo '<p style="color: #888;">No hay horarios disponibles para asignar</p>';
}
?>