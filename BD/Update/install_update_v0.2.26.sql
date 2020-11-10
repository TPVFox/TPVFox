
--
-- Estructura de tabla para la tabla `modulo_importar_registro`
--

CREATE TABLE `modulo_importar_registro` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `datos_fichero` mediumtext NOT NULL,
  `token` text NOT NULL,
  `type` text NOT NULL,
  `fecha_inicio` datetime NOT NULL,
  `estado` varchar(250) NOT NULL,
  `Registros_originales` int(11) NOT NULL,
  `nulos` int(11) NOT NULL DEFAULT 0,
  `errores` int(11) NOT NULL DEFAULT 0,
  `campos` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `modulo_importar_registro`
--
ALTER TABLE `modulo_importar_registro`
  ADD PRIMARY KEY (`id`);


--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `modulo_importar_registro`
--
ALTER TABLE `modulo_importar_registro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
