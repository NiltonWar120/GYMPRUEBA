<?php
session_start();
require_once '../conexion.php';

if (!isset($_SESSION['usuario'])) {
    echo '<p style="color: red;">Sesión expirada</p>';
    exit;
}

$cod_horario = isset($_POST['cod_horario']) ? intval($_POST['cod_horario']) : 0;

if ($cod_horario == 0) {
    echo '<p style="color: red;">Horario inválido</p>';
    exit;
}

// Consultar clientes asignados a este horario
$sql = "SELECT c.DNI, c.Nombre, c.Telefono, tm.Nombre as Membresia, m.Fecha_Fin
        FROM DETALLE_CLIENTE_H dch
        INNER JOIN CLIENTE c ON dch.DNI_Cliente = c.DNI
        LEFT JOIN MEMBRESIA m ON c.DNI = m.DNI_Cliente AND m.Estado = 1
        LEFT JOIN TIPO_MEMBRESIA tm ON m.Cod_Tipo_Membresia = tm.Cod_Tipo_Membresia
        WHERE dch.Cod_Horario = ? AND dch.Estado = 1
        ORDER BY c.Nombre ASC";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $cod_horario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo '<table class="modal-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>DNI</th>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Membresía</th>
                </tr>
            </thead>
            <tbody>';
    
    $contador = 1;
    while ($cliente = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $contador++ . "</td>";
        echo "<td>" . htmlspecialchars($cliente['DNI']) . "</td>";
        echo "<td>" . htmlspecialchars($cliente['Nombre']) . "</td>";
        echo "<td>" . htmlspecialchars($cliente['Telefono']) . "</td>";
        echo "<td>" . htmlspecialchars($cliente['Membresia'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    
    echo '</tbody></table>';
    echo '<p style="text-align: center; margin-top: 20px; color: #d4af37;">Total: <strong>' . ($contador - 1) . ' clientes</strong></p>';
} else {
    echo '<p style="text-align: center; padding: 30px; color: #888;">📭 No hay clientes asignados a este horario</p>';
}

$stmt->close();
?>