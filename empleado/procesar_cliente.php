<?php
session_start();
require_once '../conexion.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$success = "";
$error = "";

// PROCESAR REGISTRO DE CLIENTE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dni'])) {
    $dni = trim($_POST['dni']);
    $nombre = trim($_POST['nombre']);
    $sexo = trim($_POST['sexo']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $tipo_membresia = intval($_POST['tipo_membresia']);
    $metodo_pago = trim($_POST['metodo_pago']);
    $horario = intval($_POST['horario']);

    // Validaciones básicas
    if (empty($dni) || empty($nombre)) {
        $error = "DNI y Nombre son obligatorios";
    } elseif (!is_numeric($dni) || strlen($dni) != 8) {
        $error = "El DNI debe tener 8 dígitos numéricos";
    } else {
        // Verificar si el cliente ya existe
        $sql_check = "SELECT DNI FROM CLIENTE WHERE DNI = ?";
        $stmt_check = $conexion->prepare($sql_check);
        if ($stmt_check) {
            $stmt_check->bind_param("s", $dni);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            
            if ($result_check->num_rows > 0) {
                $error = "El DNI $dni ya está registrado";
            } else {
                // 1. Insertar en CLIENTE
                $sql_cliente = "INSERT INTO CLIENTE (DNI, Nombre, Sexo, Telefono, Direccion, Estado) VALUES (?, ?, ?, ?, ?, 1)";
                $stmt_cliente = $conexion->prepare($sql_cliente);
                if ($stmt_cliente) {
                    $stmt_cliente->bind_param("sssss", $dni, $nombre, $sexo, $telefono, $direccion);
                    if ($stmt_cliente->execute()) {
                        // 2. Configurar datos para MEMBRESIA
                        $fecha_inicio = date('Y-m-d');
                        $fecha_fin = date('Y-m-d', strtotime('+30 days'));
                        
                        $precios = [1 => 150.00, 2 => 100.00, 3 => 50.00, 4 => 250.00, 5 => 200.00];
                        $precio = $precios[$tipo_membresia] ?? 50.00;

                        $cod_pago_map = ['Efectivo' => 1, 'Yape' => 2, 'Transferencia' => 3, 'Tarjeta' => 4];
                        $cod_pago = $cod_pago_map[$metodo_pago] ?? 1;

                        // 3. Insertar en MEMBRESIA (sin especificar Cod_Membresia para que auto-incremente)
                        $sql_membresia = "INSERT INTO MEMBRESIA (Fecha_Inicio, Fecha_Fin, Precio, DNI_Cliente, Cod_Pago, Cod_Tipo_Membresia, Estado) VALUES (?, ?, ?, ?, ?, ?, 1)";
                        $stmt_membresia = $conexion->prepare($sql_membresia);
                        if ($stmt_membresia) {
                            $stmt_membresia->bind_param("ssdiii", $fecha_inicio, $fecha_fin, $precio, $dni, $cod_pago, $tipo_membresia);
                            if ($stmt_membresia->execute()) {
                                // 4. Insertar en DETALLE_CLIENTE_H (sin especificar Cod_Detalle_CH para que auto-incremente)
                                $sql_detalle = "INSERT INTO DETALLE_CLIENTE_H (Cod_Horario, DNI_Cliente, Estado) VALUES (?, ?, 1)";
                                $stmt_detalle = $conexion->prepare($sql_detalle);
                                if ($stmt_detalle) {
                                    $stmt_detalle->bind_param("is", $horario, $dni);
                                    if ($stmt_detalle->execute()) {
                                        $success = "Cliente registrado exitosamente (DNI: $dni)";
                                    } else {
                                        $error = "Error al insertar detalle horario: " . $conexion->error;
                                    }
                                } else {
                                    $error = "Error en la consulta de detalle horario: " . $conexion->error;
                                }
                            } else {
                                $error = "Error al insertar membresía: " . $conexion->error;
                            }
                        } else {
                            $error = "Error en la consulta de membresía: " . $conexion->error;
                        }
                    } else {
                        $error = "Error al insertar cliente: " . $conexion->error;
                    }
                } else {
                    $error = "Error en la consulta de cliente: " . $conexion->error;
                }
            }
        } else {
            $error = "Error en la consulta de verificación: " . $conexion->error;
        }
    }
}
?>

... (el HTML permanece igual) ...