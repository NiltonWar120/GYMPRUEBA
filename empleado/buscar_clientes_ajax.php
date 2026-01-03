<?php
session_start();
require_once '../conexion.php';

if (!isset($_SESSION['usuario'])) {
    echo '<tr><td colspan="5" style="text-align: center; color: red;">Sesión expirada</td></tr>';
    exit;
}

$busqueda = isset($_POST['busqueda']) ? trim($_POST['busqueda']) : '';

$sql_sin_horario = "SELECT c.DNI, c.Nombre, tm.Nombre as Membresia, m.Fecha_Fin,
                   DATEDIFF(m.Fecha_Fin, CURDATE()) as Dias_Restantes
                   FROM CLIENTE c
                   INNER JOIN MEMBRESIA m ON c.DNI = m.DNI_Cliente AND m.Estado = 1
                   LEFT JOIN TIPO_MEMBRESIA tm ON m.Cod_Tipo_Membresia = tm.Cod_Tipo_Membresia
                   LEFT JOIN DETALLE_CLIENTE_H dch ON c.DNI = dch.DNI_Cliente AND dch.Estado = 1
                   WHERE c.Estado = 1 
                   AND dch.DNI_Cliente IS NULL";

if (!empty($busqueda)) {
    $sql_sin_horario .= " AND (c.DNI LIKE ? OR c.Nombre LIKE ?)";
}

$sql_sin_horario .= " ORDER BY c.Nombre ASC";

if (!empty($busqueda)) {
    $stmt = $conexion->prepare($sql_sin_horario);
    $buscar_like = "%" . $busqueda . "%";
    $stmt->bind_param("ss", $buscar_like, $buscar_like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conexion->query($sql_sin_horario);
}

echo '<table style="width: 100%; border-collapse: collapse; margin-top: 15px; color: white;">
        <thead>
            <tr>
                <th style="background: #d4af37; color: #1a1a1a; padding: 12px;">DNI</th>
                <th style="background: #d4af37; color: #1a1a1a; padding: 12px;">Nombre</th>
                <th style="background: #d4af37; color: #1a1a1a; padding: 12px;">Membresía</th>
                <th style="background: #d4af37; color: #1a1a1a; padding: 12px;">Estado</th>
                <th style="background: #d4af37; color: #1a1a1a; padding: 12px;">Acción</th>
            </tr>
        </thead>
        <tbody>';

if ($result && $result->num_rows > 0) {
    while ($cliente = $result->fetch_assoc()) {
        $dias = $cliente['Dias_Restantes'];
        
        if ($dias < 0) {
            $estado_texto = '🔴 Vencido';
        } elseif ($dias <= 7) {
            $estado_texto = '🟡 ' . $dias . ' días';
        } else {
            $estado_texto = '🟢 Activo';
        }
        
        echo "<tr>";
        echo "<td style='padding: 10px; border-bottom: 1px solid #b8860b;'>" . htmlspecialchars($cliente['DNI']) . "</td>";
        echo "<td style='padding: 10px; border-bottom: 1px solid #b8860b;'>" . htmlspecialchars($cliente['Nombre']) . "</td>";
        echo "<td style='padding: 10px; border-bottom: 1px solid #b8860b;'>" . htmlspecialchars($cliente['Membresia']) . "</td>";
        echo "<td style='padding: 10px; border-bottom: 1px solid #b8860b;'>" . $estado_texto . "</td>";
        echo "<td style='padding: 10px; border-bottom: 1px solid #b8860b;'>";
        echo "<button class='btn'>🕐 Asignar Horario</button>";
        echo "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5' style='text-align: center; padding: 20px; color: #888;'>❌ No se encontraron clientes</td></tr>";
}

echo '</tbody></table>';

if (isset($stmt)) {
    $stmt->close();
}
?>