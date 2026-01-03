<?php
// conexion.php - 
$servername = "localhost";
$username = "root";
$password = "";
$database = "profit";

// Crear conexión
$conexion = new mysqli($servername, $username, $password);

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Crear base de datos si no existe
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if ($conexion->query($sql) === TRUE) {
    // Seleccionar la base de datos
    $conexion->select_db($database);
    
    // Crear tablas
    crearTablas($conexion);
    
    // Insertar datos iniciales
    insertarDatosIniciales($conexion);
    
    // Verificar y corregir campos faltantes
    verificarCampos($conexion);
    
} else {
    die("Error creando base de datos: " . $conexion->error);
}

$conexion->set_charset("utf8");

function crearTablas($conexion) {
    // Tabla CARGO
    $sql_cargo = "CREATE TABLE IF NOT EXISTS gimnasio_cargo (
        Cod_Cargo INT(11) PRIMARY KEY AUTO_INCREMENT,
        Nombre VARCHAR(20),
        Estado INT(11) DEFAULT 1
    )";
    $conexion->query($sql_cargo);
    
    // Tabla COLABORADORES - CON TODOS LOS CAMPOS
    $sql_colaboradores = "CREATE TABLE IF NOT EXISTS gimnasio_colaboradores (
        DNI CHAR(8) PRIMARY KEY,
        Nombre VARCHAR(30),
        Direccion VARCHAR(50),
        Celular VARCHAR(9),
        Email VARCHAR(100),
        Password VARCHAR(100),
        Cod_Cargo INT(11),
        Estado INT(11) DEFAULT 1,
        FOREIGN KEY (Cod_Cargo) REFERENCES gimnasio_cargo(Cod_Cargo)
    )";
    $conexion->query($sql_colaboradores);
    
    // ... (las otras tablas permanecen igual) ...
}

function insertarDatosIniciales($conexion) {
    // Verificar si ya existen datos en cargos
    $sql_check = "SELECT COUNT(*) as total FROM gimnasio_cargo";
    $result = $conexion->query($sql_check);
    
    if ($result) {
        $row = $result->fetch_assoc();
        
        if ($row['total'] == 0) {
            // Insertar cargos
            $conexion->query("INSERT INTO gimnasio_cargo (Cod_Cargo, Nombre, Estado) VALUES 
                (1, 'Administrador', 1), 
                (2, 'Recepcionista', 1),
                (3, 'Limpieza', 1)");
            
            // Insertar colaboradores CON TODOS LOS CAMPOS
            $conexion->query("INSERT IGNORE INTO gimnasio_colaboradores (DNI, Nombre, Direccion, Celular, Email, Password, Cod_Cargo, Estado) VALUES 
                ('12345678', 'Administrador Principal', 'Av. Principal 123', '987654321', 'admin@profit.com', 'admin123', 1, 1),
                ('87654321', 'Maria Recepcion', 'Av. Secundaria 456', '987654322', 'recepcion@profit.com', 'recepcion123', 2, 1),
                ('11111111', 'Juan Limpieza', 'Calle Limpia 789', '987654323', 'limpieza@profit.com', 'limpieza123', 3, 1)");
        }
    }
}

function verificarCampos($conexion) {
    // Verificar si la tabla colaboradores tiene los campos Email y Password
    $sql_check_fields = "SHOW COLUMNS FROM gimnasio_colaboradores LIKE 'Email'";
    $result_email = $conexion->query($sql_check_fields);
    
    if ($result_email->num_rows == 0) {
        // Agregar campo Email si no existe
        $conexion->query("ALTER TABLE gimnasio_colaboradores ADD COLUMN Email VARCHAR(100) AFTER Celular");
    }
    
    $sql_check_fields2 = "SHOW COLUMNS FROM gimnasio_colaboradores LIKE 'Password'";
    $result_password = $conexion->query($sql_check_fields2);
    
    if ($result_password->num_rows == 0) {
        // Agregar campo Password si no existe
        $conexion->query("ALTER TABLE gimnasio_colaboradores ADD COLUMN Password VARCHAR(100) AFTER Email");
        
        // Actualizar contraseñas para usuarios existentes
        $conexion->query("UPDATE gimnasio_colaboradores SET 
            Password = CASE 
                WHEN DNI = '12345678' THEN 'admin123'
                WHEN DNI = '87654321' THEN 'recepcion123' 
                WHEN DNI = '11111111' THEN 'limpieza123'
                ELSE 'password123'
            END,
            Email = CASE 
                WHEN DNI = '12345678' THEN 'admin@profit.com'
                WHEN DNI = '87654321' THEN 'recepcion@profit.com'
                WHEN DNI = '11111111' THEN 'limpieza@profit.com'
                ELSE CONCAT(DNI, '@profit.com')
            END
        WHERE Password IS NULL OR Email IS NULL");
    }
}
?>