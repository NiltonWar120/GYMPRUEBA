-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 14-12-2025 a las 19:45:36
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `profit`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cargo`
--

CREATE TABLE `cargo` (
  `Cod_Cargo` int(11) NOT NULL,
  `Nombre` varchar(20) DEFAULT NULL,
  `Estado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `DNI` char(8) NOT NULL,
  `Nombre` varchar(30) DEFAULT NULL,
  `Sexo` varchar(10) DEFAULT NULL,
  `Telefono` varchar(9) DEFAULT NULL,
  `Direccion` varchar(50) DEFAULT NULL,
  `Estado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`DNI`, `Nombre`, `Sexo`, `Telefono`, `Direccion`, `Estado`) VALUES
('16291212', 'Pierina Chavez', 'Femenino', '902340262', 'Santa Cuz', 1),
('37482391', 'Eloy Guzman', 'Masculino', '999329821', 'Santa Mario', 1),
('48277118', 'JEISON GARCIA', 'Masculino', '519168264', 'as', 1),
('60628573', 'JOHAO NICHO', 'Masculino', '519549884', '', 1),
('61113328', 'MIGUEL CAQUI VEGA', 'Masculino', '519063156', 'sa', 1),
('72384294', 'Maria Pezzini', 'Femenino', '986723451', 'Manzanares', 1),
('73742423', 'Emilia Orihuela', 'Masculino', '823813129', 'Cerro camote', 1),
('76652084', 'NATALY ECHEGARAY', 'Femenino', '519760514', 'dsd', 1),
('77374284', 'juan garcia', 'Masculino', '986723451', 'huacho', 1),
('77502453', 'BILLY SANTILLAN', 'Masculino', '519144706', 'ssss', 1),
('82384291', 'Patrick Huilca', 'Masculino', '986723451', 'Cruz Blanca', 1),
('82391012', 'Gerardo Pe', 'Masculino', '902340266', 'Huacho', 1),
('83293912', 'Pedro Rosas', 'Masculino', '986723451', 'Huaura', 1),
('83890120', 'Juan Perez', 'Masculino', '959272339', 'lima', 1),
('88234929', 'Mirko Vidal', 'Masculino', '959272339', 'Vegueta', 1),
('89230142', 'Micaela Galarza', 'Femenino', '902340266', 'Hualmay 203', 1),
('89239402', 'Pedro Palermo', 'Masculino', '986723451', 'Centenario', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `coach`
--

CREATE TABLE `coach` (
  `cod_coach` int(8) NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `Apellido` varchar(100) NOT NULL,
  `dni` varchar(8) NOT NULL,
  `telefono` varchar(9) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `especialidad` varchar(50) DEFAULT NULL,
  `fecha_contrato` date DEFAULT NULL,
  `sueldo` decimal(10,2) DEFAULT NULL,
  `estado` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `coach`
--

INSERT INTO `coach` (`cod_coach`, `Nombre`, `Apellido`, `dni`, `telefono`, `email`, `especialidad`, `fecha_contrato`, `sueldo`, `estado`) VALUES
(1, 'Carlosa', 'Rodriguez', '11111111', '987654321', 'carlos@profit.com', 'Musculacion', '2025-11-01', 2500.00, 1),
(2, 'Ana', 'Garcia', '22223333', '987654322', 'ana@profit.com', 'Cardio', '2025-11-05', 2300.00, 0),
(3, 'Nilton', 'Guerra', '74286225', '902340266', 'guerra@profit.com', 'crossfit', '2025-12-01', 3000.00, 1),
(4, 'Juliana', 'Rodriguez', '83992901', '902340266', 'juliana@profit.com', 'cardio', '2025-12-11', 22000.00, 1),
(5, 'Karol', 'Ramirez', '89237410', '902340262', 'karon@profit.com', 'Danza', '2025-12-11', 1900.00, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `colaboradores`
--

CREATE TABLE `colaboradores` (
  `DNI` char(8) NOT NULL,
  `Nombre` varchar(30) DEFAULT NULL,
  `Direccion` varchar(50) DEFAULT NULL,
  `Celular` varchar(9) DEFAULT NULL,
  `Cod_Cargo` int(11) DEFAULT NULL,
  `Estado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `colaborador_horario`
--

CREATE TABLE `colaborador_horario` (
  `Cod_Colaborador_H` int(11) NOT NULL,
  `DNI_Colaboradores` char(8) DEFAULT NULL,
  `Cod_Horario` int(11) DEFAULT NULL,
  `Estado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_cliente_h`
--

CREATE TABLE `detalle_cliente_h` (
  `Cod_Detalle_CH` int(11) NOT NULL,
  `Cod_Horario` int(11) DEFAULT NULL,
  `DNI_Cliente` char(8) DEFAULT NULL,
  `Estado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `detalle_cliente_h`
--

INSERT INTO `detalle_cliente_h` (`Cod_Detalle_CH`, `Cod_Horario`, `DNI_Cliente`, `Estado`) VALUES
(1, 1, '83890120', 1),
(2, 14, '82391012', 1),
(3, 7, '77502453', 1),
(4, 1, '83293912', 1),
(5, 17, '73742423', 1),
(6, 15, '89230142', 1),
(7, 17, '37482391', 1),
(8, 10, '82384291', 1),
(9, 1, '88234929', 1),
(10, 15, '16291212', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado`
--

CREATE TABLE `estado` (
  `Cod_Estado` int(11) NOT NULL,
  `Nombre` varchar(12) DEFAULT NULL,
  `Estado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `estado`
--

INSERT INTO `estado` (`Cod_Estado`, `Nombre`, `Estado`) VALUES
(1, 'Activo', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gimnasio_cargo`
--

CREATE TABLE `gimnasio_cargo` (
  `Cod_Cargo` int(11) NOT NULL,
  `Nombre` varchar(20) DEFAULT NULL,
  `Estado` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `gimnasio_cargo`
--

INSERT INTO `gimnasio_cargo` (`Cod_Cargo`, `Nombre`, `Estado`) VALUES
(1, 'Administrador', 1),
(2, 'Recepcionista', 1),
(3, 'Limpieza', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gimnasio_colaboradores`
--

CREATE TABLE `gimnasio_colaboradores` (
  `DNI` char(8) NOT NULL,
  `Nombre` varchar(30) DEFAULT NULL,
  `Direccion` varchar(50) DEFAULT NULL,
  `Celular` varchar(9) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Password` varchar(100) DEFAULT NULL,
  `Cod_Cargo` int(11) DEFAULT NULL,
  `Estado` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `gimnasio_colaboradores`
--

INSERT INTO `gimnasio_colaboradores` (`DNI`, `Nombre`, `Direccion`, `Celular`, `Email`, `Password`, `Cod_Cargo`, `Estado`) VALUES
('12345678', 'Administrador Principal', 'Av. Principal 123', '987654321', 'administrador@profit.com', 'admin123', 1, 1),
('22222222', 'BENJAMIN ENCARNACION', 'SILA -78', '999999999', 'BENJA@LO.COM', '123456', 1, 1),
('33333333', 'ALEXANDER ESPINOZA', 'PLAZA DE ARMAS', '354444444', 'ALE@DDD.COM', '123456', 2, 1),
('74584358', 'gabriel rodriguez', 'jt huarl 78', '123456789', 'gab@gmail.com', '123456', 1, 1),
('82394201', 'Marce Quiroz', 'Vegueta', '920320312', 'marce@profit.com', '123456', 3, 1),
('87654321', 'Maria Recepcion', 'Av. Secundaria 456', '987654322', 'recepcion@profit.com', 'recepcion123', 2, 1),
('88123911', 'Juan Quineche', 'Huacho', '902340266', 'juan@profit.com', '123456', 2, 1),
('88392391', 'penelope Rous', 'Huaura', '981238118', 'pene@profit.com', '123456', 2, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horario`
--

CREATE TABLE `horario` (
  `Cod_Horario` int(11) NOT NULL,
  `Turno` varchar(25) DEFAULT NULL,
  `Fecha` date DEFAULT NULL,
  `Estado` int(11) DEFAULT NULL,
  `Cod_Coach` int(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `horario`
--

INSERT INTO `horario` (`Cod_Horario`, `Turno`, `Fecha`, `Estado`, `Cod_Coach`) VALUES
(1, '06:00 - 07:00', '2025-12-07', 1, 4),
(2, '07:00 - 08:00', '2025-12-07', 1, NULL),
(3, '08:00 - 09:00', '2025-12-07', 1, 5),
(4, '09:00 - 10:00', '2025-12-07', 1, NULL),
(5, '10:00 - 11:00', '2025-12-07', 1, 1),
(6, '11:00 - 12:00', '2025-12-06', 1, 5),
(7, '12:00 - 13:00', '2025-12-06', 1, 3),
(8, '13:00 - 14:00', '2025-12-06', 1, 1),
(9, '14:00 - 15:00', '2025-12-06', 1, NULL),
(10, '15:00 - 16:00', '2025-12-06', 1, 4),
(11, '16:00 - 17:00', '2025-12-06', 1, 4),
(12, '17:00 - 18:00', '2025-12-06', 1, NULL),
(13, '18:00 - 19:00', '2025-12-06', 1, 5),
(14, '19:00 - 20:00', '2025-12-06', 1, 3),
(15, '20:00 - 21:00', '2025-12-06', 1, NULL),
(16, '21:00 - 22:00', '2025-12-06', 1, 3),
(17, '22:00 - 23:00', '2025-12-06', 1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `membresia`
--

CREATE TABLE `membresia` (
  `Cod_Membresia` int(11) NOT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Precio` decimal(10,2) DEFAULT NULL,
  `DNI_Cliente` char(8) DEFAULT NULL,
  `Cod_Pago` int(11) DEFAULT NULL,
  `Cod_Documento` int(11) DEFAULT NULL,
  `Cod_Estado` int(11) DEFAULT NULL,
  `Cod_Tipo_Membresia` int(11) DEFAULT NULL,
  `Estado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `membresia`
--

INSERT INTO `membresia` (`Cod_Membresia`, `Fecha_Inicio`, `Fecha_Fin`, `Precio`, `DNI_Cliente`, `Cod_Pago`, `Cod_Documento`, `Cod_Estado`, `Cod_Tipo_Membresia`, `Estado`) VALUES
(1, '2025-11-15', '2025-12-15', 50.00, '61113328', 1, 1, 1, 3, 1),
(2, '2025-11-16', '2025-12-16', 150.00, '60628573', 1, NULL, NULL, 1, 1),
(3, '2025-11-16', '2025-12-16', 150.00, '48277118', 1, NULL, NULL, 1, 1),
(4, '2025-11-16', '2025-12-16', 150.00, '77502453', 1, NULL, NULL, 1, 1),
(5, '2025-11-16', '2025-12-16', 50.00, '76652084', 1, NULL, NULL, 3, 1),
(6, '2025-12-06', '2026-01-06', 50.00, '83890120', 1, NULL, NULL, 3, 1),
(7, '2025-12-06', '2026-01-06', 250.00, '82391012', 1, NULL, NULL, 4, 1),
(8, '2025-12-07', '2026-01-06', 200.00, '83293912', 3, NULL, NULL, 5, 1),
(9, '2025-12-07', '2026-01-06', 250.00, '73742423', 1, NULL, NULL, 4, 1),
(10, '2025-12-10', '2026-01-10', 200.00, '89230142', 2, NULL, NULL, 5, 1),
(11, '2025-12-11', '2026-01-10', 250.00, '37482391', 2, NULL, NULL, 4, 1),
(12, '2025-12-11', '2026-01-10', 50.00, '82384291', 2, NULL, NULL, 3, 1),
(13, '2025-12-10', '2026-01-09', 250.00, '72384294', 2, NULL, NULL, 4, 1),
(14, '2025-12-10', '2026-01-08', 100.00, '88234929', 1, NULL, NULL, 2, 1),
(15, '2025-12-10', '2026-01-09', 200.00, '89239402', 1, NULL, NULL, 5, 1),
(16, '2025-12-11', '2026-01-10', 100.00, '77374284', 1, NULL, NULL, 2, 1),
(17, '2025-12-14', '2026-01-13', 100.00, '16291212', 1, NULL, NULL, 2, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `renovacion`
--

CREATE TABLE `renovacion` (
  `Cod_Renovacion` int(11) NOT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Final` date DEFAULT NULL,
  `Cod_Membresia` int(11) DEFAULT NULL,
  `Estado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_de_pago`
--

CREATE TABLE `tipo_de_pago` (
  `Cod_Pago` int(11) NOT NULL,
  `Nombre` varchar(15) DEFAULT NULL,
  `Estado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `tipo_de_pago`
--

INSERT INTO `tipo_de_pago` (`Cod_Pago`, `Nombre`, `Estado`) VALUES
(1, 'Efectivo', 1),
(2, 'Yape', 1),
(3, 'Transferencia', 1),
(4, 'Tarjeta', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_documento`
--

CREATE TABLE `tipo_documento` (
  `Cod_Documento` int(11) NOT NULL,
  `Nombre` varchar(30) DEFAULT NULL,
  `Estado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `tipo_documento`
--

INSERT INTO `tipo_documento` (`Cod_Documento`, `Nombre`, `Estado`) VALUES
(1, 'DNI', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_membresia`
--

CREATE TABLE `tipo_membresia` (
  `Cod_Tipo_Membresia` int(11) NOT NULL,
  `Nombre` varchar(20) DEFAULT NULL,
  `Estado` int(11) DEFAULT NULL,
  `precio_base` decimal(10,2) DEFAULT 0.00,
  `precio_oferta` decimal(10,2) DEFAULT 0.00,
  `tiene_oferta` tinyint(1) DEFAULT 0,
  `porcentaje_descuento` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `tipo_membresia`
--

INSERT INTO `tipo_membresia` (`Cod_Tipo_Membresia`, `Nombre`, `Estado`, `precio_base`, `precio_oferta`, `tiene_oferta`, `porcentaje_descuento`) VALUES
(1, 'BLACK', 1, 150.00, 150.00, 0, 0.00),
(2, 'ESPECIAL', 1, 100.00, 100.00, 0, 0.00),
(3, 'MODOFIT', 1, 50.00, 50.00, 0, 0.00),
(4, 'PLATINIUM', 1, 250.00, 220.00, 1, 12.00),
(5, 'PREMIUM', 1, 200.00, 200.00, 0, 0.00);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cargo`
--
ALTER TABLE `cargo`
  ADD PRIMARY KEY (`Cod_Cargo`);

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`DNI`);

--
-- Indices de la tabla `coach`
--
ALTER TABLE `coach`
  ADD PRIMARY KEY (`cod_coach`),
  ADD UNIQUE KEY `dni` (`dni`);

--
-- Indices de la tabla `colaboradores`
--
ALTER TABLE `colaboradores`
  ADD PRIMARY KEY (`DNI`),
  ADD KEY `Cod_Cargo` (`Cod_Cargo`);

--
-- Indices de la tabla `colaborador_horario`
--
ALTER TABLE `colaborador_horario`
  ADD PRIMARY KEY (`Cod_Colaborador_H`),
  ADD KEY `DNI_Colaboradores` (`DNI_Colaboradores`),
  ADD KEY `Cod_Horario` (`Cod_Horario`);

--
-- Indices de la tabla `detalle_cliente_h`
--
ALTER TABLE `detalle_cliente_h`
  ADD PRIMARY KEY (`Cod_Detalle_CH`),
  ADD KEY `Cod_Horario` (`Cod_Horario`),
  ADD KEY `DNI_Cliente` (`DNI_Cliente`);

--
-- Indices de la tabla `estado`
--
ALTER TABLE `estado`
  ADD PRIMARY KEY (`Cod_Estado`);

--
-- Indices de la tabla `gimnasio_cargo`
--
ALTER TABLE `gimnasio_cargo`
  ADD PRIMARY KEY (`Cod_Cargo`);

--
-- Indices de la tabla `gimnasio_colaboradores`
--
ALTER TABLE `gimnasio_colaboradores`
  ADD PRIMARY KEY (`DNI`),
  ADD KEY `Cod_Cargo` (`Cod_Cargo`);

--
-- Indices de la tabla `horario`
--
ALTER TABLE `horario`
  ADD PRIMARY KEY (`Cod_Horario`),
  ADD KEY `Cod_Coach` (`Cod_Coach`);

--
-- Indices de la tabla `membresia`
--
ALTER TABLE `membresia`
  ADD PRIMARY KEY (`Cod_Membresia`),
  ADD KEY `DNI_Cliente` (`DNI_Cliente`),
  ADD KEY `Cod_Pago` (`Cod_Pago`),
  ADD KEY `Cod_Documento` (`Cod_Documento`),
  ADD KEY `Cod_Estado` (`Cod_Estado`),
  ADD KEY `Cod_Tipo_Membresia` (`Cod_Tipo_Membresia`);

--
-- Indices de la tabla `renovacion`
--
ALTER TABLE `renovacion`
  ADD PRIMARY KEY (`Cod_Renovacion`),
  ADD KEY `Cod_Membresia` (`Cod_Membresia`);

--
-- Indices de la tabla `tipo_de_pago`
--
ALTER TABLE `tipo_de_pago`
  ADD PRIMARY KEY (`Cod_Pago`);

--
-- Indices de la tabla `tipo_documento`
--
ALTER TABLE `tipo_documento`
  ADD PRIMARY KEY (`Cod_Documento`);

--
-- Indices de la tabla `tipo_membresia`
--
ALTER TABLE `tipo_membresia`
  ADD PRIMARY KEY (`Cod_Tipo_Membresia`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `coach`
--
ALTER TABLE `coach`
  MODIFY `cod_coach` int(8) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `detalle_cliente_h`
--
ALTER TABLE `detalle_cliente_h`
  MODIFY `Cod_Detalle_CH` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `gimnasio_cargo`
--
ALTER TABLE `gimnasio_cargo`
  MODIFY `Cod_Cargo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `membresia`
--
ALTER TABLE `membresia`
  MODIFY `Cod_Membresia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `colaboradores`
--
ALTER TABLE `colaboradores`
  ADD CONSTRAINT `colaboradores_ibfk_1` FOREIGN KEY (`Cod_Cargo`) REFERENCES `cargo` (`Cod_Cargo`);

--
-- Filtros para la tabla `colaborador_horario`
--
ALTER TABLE `colaborador_horario`
  ADD CONSTRAINT `colaborador_horario_ibfk_1` FOREIGN KEY (`DNI_Colaboradores`) REFERENCES `colaboradores` (`DNI`),
  ADD CONSTRAINT `colaborador_horario_ibfk_2` FOREIGN KEY (`Cod_Horario`) REFERENCES `horario` (`Cod_Horario`);

--
-- Filtros para la tabla `detalle_cliente_h`
--
ALTER TABLE `detalle_cliente_h`
  ADD CONSTRAINT `detalle_cliente_h_ibfk_1` FOREIGN KEY (`Cod_Horario`) REFERENCES `horario` (`Cod_Horario`),
  ADD CONSTRAINT `detalle_cliente_h_ibfk_2` FOREIGN KEY (`DNI_Cliente`) REFERENCES `cliente` (`DNI`);

--
-- Filtros para la tabla `gimnasio_colaboradores`
--
ALTER TABLE `gimnasio_colaboradores`
  ADD CONSTRAINT `gimnasio_colaboradores_ibfk_1` FOREIGN KEY (`Cod_Cargo`) REFERENCES `gimnasio_cargo` (`Cod_Cargo`);

--
-- Filtros para la tabla `horario`
--
ALTER TABLE `horario`
  ADD CONSTRAINT `horario_ibfk_coach` FOREIGN KEY (`Cod_Coach`) REFERENCES `coach` (`cod_coach`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `membresia`
--
ALTER TABLE `membresia`
  ADD CONSTRAINT `membresia_ibfk_1` FOREIGN KEY (`DNI_Cliente`) REFERENCES `cliente` (`DNI`),
  ADD CONSTRAINT `membresia_ibfk_2` FOREIGN KEY (`Cod_Pago`) REFERENCES `tipo_de_pago` (`Cod_Pago`),
  ADD CONSTRAINT `membresia_ibfk_3` FOREIGN KEY (`Cod_Documento`) REFERENCES `tipo_documento` (`Cod_Documento`),
  ADD CONSTRAINT `membresia_ibfk_4` FOREIGN KEY (`Cod_Estado`) REFERENCES `estado` (`Cod_Estado`),
  ADD CONSTRAINT `membresia_ibfk_5` FOREIGN KEY (`Cod_Tipo_Membresia`) REFERENCES `tipo_membresia` (`Cod_Tipo_Membresia`);

--
-- Filtros para la tabla `renovacion`
--
ALTER TABLE `renovacion`
  ADD CONSTRAINT `renovacion_ibfk_1` FOREIGN KEY (`Cod_Membresia`) REFERENCES `membresia` (`Cod_Membresia`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
