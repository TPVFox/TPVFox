CREATE TABLE `articulosStocks` (
  `id` int(11) NOT NULL,
  `idArticulo` int(11) NOT NULL,
  `idTienda` int(11) NOT NULL,
  `stockMin` decimal(17,6) NOT NULL,
  `stockMax` decimal(17,6) NOT NULL,
  `stockOn` decimal(17,6) NOT NULL,
  `fecha_modificado` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `articulosStocks`
--
ALTER TABLE `articulosStocks`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `articulosStocks`
--
ALTER TABLE `articulosStocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

