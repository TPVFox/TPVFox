-- phpMyAdmin SQL Dump
-- version 4.6.6deb4
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 06, 2018 at 12:16 AM
-- Server version: 10.1.26-MariaDB-0+deb9u1
-- PHP Version: 7.0.27-0+deb9u1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tpv`
--

--
-- Dumping data for table `proveedores`
--

INSERT INTO `proveedores` (`idProveedor`, `nombrecomercial`, `razonsocial`, `nif`, `direccion`, `telefono`, `fax`, `movil`, `email`, `fecha_creado`, `estado`) VALUES
(1, 'DISGAL', '', '', '', '981795420', '', '', '', '2018-01-06 00:00:00', 'Activo'),
(2, 'DISTRIBUCIONES FROIZ,S.A.', 'DISTRIBUCIONES FROIZ,S.A.', 'A36036739', 'LOURIDO,15 POIO 36163', '', '', '', '', '0000-00-00 00:00:00', ''),
(3, 'ANA MARIA DOMINGUEZ BALI&Ntilde;O                                                                   ', 'ANA MARIA DOMINGUEZ BALI&Ntilde;O                                                                   ', '36155801P', 'RUA SAN CRISTOBO 109 VIGO 36317', '', '650673201 J', '', 'pilasdominguez@telefonica.net', '0000-00-00 00:00:00', ''),
(4, 'LECHE PASCUAL, S.A.', 'LECHE PASCUAL, S.A.', 'A09006172', 'CTRA. DE PALENCIA, S/N ARANDA DE DUERO 09400', '', '', '', '', '0000-00-00 00:00:00', ''),
(5, 'BACALAO COIMBA S.L.', 'BACALAO COIMBA S.L.', 'B36001899', 'C/ PREGUNTOIRO, 37-39 VILAXOAN - VILLAGARCIA DE AROUSA 36611', '', '', '', '', '0000-00-00 00:00:00', ''),
(6, 'PANADERIA BERTIN - MARGARITA RUBIN POSADO', 'PANADERIA BERTIN - MARGARITA RUBIN POSADO', '', 'C/SAGUNTO 55 BAJO VIGO', '', '', '', '', '0000-00-00 00:00:00', ''),
(7, 'CAMPOFRIO ALIMENTACION, S.A.', 'CAMPOFRIO ALIMENTACION, S.A.', 'A09000928', 'AVDA. DE EUROPA, 24 ALCOBENDAS 28109', '', '986486963', '', '', '0000-00-00 00:00:00', ''),
(8, 'WEAR &amp; TEAR (Bayeta desechable)', 'WEAR &amp; TEAR (Bayeta desechable)', '', 'C/AMSTERDAM,8 POL. IND. SECTOR II TORRES DE LA ALAMEDA MADRID 28813', '', '918868209', '', 'gpanaissa@gmail.com', '0000-00-00 00:00:00', ''),
(9, 'WEAR &amp; TEAR (Bayeta desechable)', 'WEAR &amp; TEAR (Bayeta desechable)', '', 'C/AMSTERDAM,8 POL. IND. SECTOR II TORRES DE LA ALAMEDA MADRID 28813', '', '918868209', '', 'gpanaissa@gmail.com', '0000-00-00 00:00:00', ''),
(10, '*** EL PINCEL DISTRIBUCIONES S.L.', '*** EL PINCEL DISTRIBUCIONES S.L.', 'B15685654', 'POLIG. DE POCOMACO PARCELA C14 MESOIRO- LA CORU&Ntilde;A                                            ', '', '981 17 48 9', '', '', '0000-00-00 00:00:00', ''),
(11, '*** PONDISA, S.L.', '*** PONDISA, S.L.', 'B15342900', 'POLIG. DE BENS, PARCELA 48 C/GAMBRIMUS 15008', '', '986468034', '', 'PONDISA@SILVALOGISTICA.COM', '0000-00-00 00:00:00', ''),
(12, 'REPRESENTACIONES CARAMELO S.L.', 'REPRESENTACIONES CARAMELO S.L.', 'B36708774', 'AVDA. CASTRELOS, 484 VIGO 36213', '', 'FAX:9864670', '', '', '0000-00-00 00:00:00', ''),
(13, 'DANONE, S.A.', 'DANONE, S.A.', 'A17000852', 'BUENOS AIRES, 21 BARCELONA 08029', '', '902757465', '', 'danpedidos@danpedidos.com', '0000-00-00 00:00:00', ''),
(14, 'RODIGA DISTRIBUCIONES S.L.', 'RODIGA DISTRIBUCIONES S.L.', 'B36436756', 'B&ordm; ALDEA C&Ntilde;O. CONDESA TORRECEDEIRA,25 BJ.                                               ', '', '986402148', '', '', '0000-00-00 00:00:00', ''),
(15, 'LAGUARDIST S.L.', 'LAGUARDIST S.L.', 'B36159572', 'C/J.A. LOMBA CAMI&Ntilde;A S/N                                                                      ', '', '986611785', '', 'laguasdist.sl@hotmail.com', '0000-00-00 00:00:00', ''),
(16, 'DIS- RIVAS, S.L.', 'DIS- RIVAS, S.L.', 'B36724128', ' POL. IND.  SEIXI&Ntilde;OS BLQ. 3 NAVE 15                                                          ', '986493344', '986420920', '', '', '0000-00-00 00:00:00', ''),
(17, 'IBERICA DE CONGELADOS, S.A.', 'IBERICA DE CONGELADOS, S.A.', 'A36620540', 'MUELLE COMERCIAL DE BOUZAS S/N VIGO 36200', '986213300', '986204669', '690682099', '', '0000-00-00 00:00:00', ''),
(18, 'MARAVI [ANA SUAREZ VAZQUEZ', 'MARAVI [ANA SUAREZ VAZQUEZ', '53196222M', 'CAMI&Ntilde;O DA QUINTANA 9 BAJO                                                                    ', '986233869', '986233869', '606884127', '', '0000-00-00 00:00:00', ''),
(19, 'INDUSTRIAS FRIGORIFICAS D', 'INDUSTRIAS FRIGORIFICAS D', 'A36001998', ' PUESTE DEL VAL, S/N PORRI&Ntilde;O                                                                 ', '986330100', '687553242', '986335255 F', '', '0000-00-00 00:00:00', ''),
(20, 'ANA MARIA DOMINGUEZ BALI&Ntilde;O                                                                   ', 'ANA MARIA DOMINGUEZ BALI&Ntilde;O                                                                   ', '36155801P', '', '986 252 776', '650673201 J', '', 'pilasdominguez@telefonica.net', '0000-00-00 00:00:00', ''),
(21, 'LECHE PASCUAL, S.A.', 'LECHE PASCUAL, S.A.', 'A09006172', '', '917685148', '', '', '', '0000-00-00 00:00:00', ''),
(26, 'ASTUR. SOCIEDAD DE VENTAS CLAS, S.L.', 'ASTUR. SOCIEDAD DE VENTAS CLAS, S.L.', 'B-33529371', '', '986487568', '986487464', '', '', '0000-00-00 00:00:00', ''),
(27, 'AVICOLAS AMOEIRO, S.L.', 'AVICOLAS AMOEIRO, S.L.', 'H32233942', '', '988279674', '', '653 955 865', '', '0000-00-00 00:00:00', ''),
(28, 'PANRICO S.L.U', 'PANRICO S.L.U', 'B15329311', '', '', '', '', '', '0000-00-00 00:00:00', ''),
(29, 'BIMBO,S.A.', 'BIMBO,S.A.', '', '', '986487047', '', '669512736', '', '0000-00-00 00:00:00', ''),
(30, 'CHOCOLATES CHAPARRO, S.L.', 'CHOCOLATES CHAPARRO, S.L.', 'B32004392', '', '', '', '', '', '0000-00-00 00:00:00', ''),
(31, 'SNACK VENTURES, S.A. (MATUTANO)', 'SNACK VENTURES, S.A. (MATUTANO)', 'A01001478', '', '986486125', '986486320', '616 37 73 8', '', '0000-00-00 00:00:00', ''),
(32, 'GASEOSAS J. FEIJOO,S.A.', 'GASEOSAS J. FEIJOO,S.A.', 'A36017275', '', '', '', '', '', '2018-01-12 00:00:00', 'importar'),
(33, 'GASEOSAS J. FEIJOO,S.A.', 'GASEOSAS J. FEIJOO,S.A.', 'A36017275', '', '', '', '', '', '2018-01-12 00:00:00', 'importar'),
(34, 'SANXINES-BODEGA CASA GONZALO', 'SANXINES-BODEGA CASA GONZALO', '', '', '607 702 198', '', '', '', '2018-01-12 00:00:00', 'importar'),
(35, '*** DIST. DE HOSTELERIA GALLEGA', '*** DIST. DE HOSTELERIA GALLEGA', 'A36010031', '', '986213771', '986213771', '', '', '2018-01-12 00:00:00', 'importar'),
(36, 'COMARTIN.', 'COMARTIN.', 'B32170177', '', '988426504', '988426602', '', '', '2018-01-12 00:00:00', 'importar'),
(37, 'REBOLO EIRAS, S.L.', 'REBOLO EIRAS, S.L.', 'B36852069', '', '986468117', '986468117', '651844464', '', '2018-01-12 00:00:00', 'importar'),
(38, 'GALLEGA DE ALIMENTACION', 'GALLEGA DE ALIMENTACION', '', '', '986423211', '', '', '', '2018-01-12 00:00:00', 'importar'),
(39, '*** COMERCIAL PAN- CAR', '*** COMERCIAL PAN- CAR', 'B36632537', '', '986 26 63 6', '', '', '', '2018-01-12 00:00:00', 'importar'),
(40, 'MIGUELA&Ntilde;EZ                                                                                   ', 'MIGUELA&Ntilde;EZ                                                                                   ', '', '', '917951261', '917956572', '', 'miguelanez219@gmail.com', '2018-01-12 00:00:00', 'importar'),
(41, 'CHOCOMI&Ntilde;O S.L.                                                                               ', 'CHOCOMI&Ntilde;O S.L.                                                                               ', 'b36364321', '', '986659185', '986664029', '677099233', 'cafesdonoso@jet.com', '2018-01-12 00:00:00', 'importar'),
(42, 'FINARREI S.L.', 'FINARREI S.L.', 'B32206245', '', '988 440 416', '988 44 04 1', '', 'demetrio@finarrei.com', '2018-01-12 00:00:00', 'importar'),
(43, 'PIZZA ITALIA,S.L.', 'PIZZA ITALIA,S.L.', 'B15430747', '', '981708791', '', '', '', '2018-01-12 00:00:00', 'importar'),
(44, 'PIZZA ITALIA,S.L.', 'PIZZA ITALIA,S.L.', 'B15430747', '', '981708791', '', '', '', '2018-01-12 00:00:00', 'importar'),
(45, 'PANADERIA LAVANDEIRA S.L.', 'PANADERIA LAVANDEIRA S.L.', 'B36625150', '', '986233935', '986235640', '', 'lavandeirapanaderia@gmail.com', '2018-01-12 00:00:00', 'importar'),
(46, 'DIST. DAVID VIDAL GONZALEZ', 'DIST. DAVID VIDAL GONZALEZ', '36094767Q', '', '660757872', '', '', '', '2018-01-12 00:00:00', 'importar'),
(47, 'CASH RECORD AUTOSERVICIO', 'CASH RECORD AUTOSERVICIO', 'B36007409', '  ', '986267842', '986262166', '659447671', '', '2018-01-12 00:00:00', 'importar'),
(48, 'COMERCIAL CARVE S.L.', 'COMERCIAL CARVE S.L.', 'B36867083', 'AVD.MARTINEZ GARRIDO 59 VIGO 36205', '986370753', '986274713', '', '', '2018-01-12 00:00:00', 'importar'),
(49, 'MANTEQUERIAS ARIAS, S.A.', 'MANTEQUERIAS ARIAS, S.A.', 'A79796587', 'C/ORENSE,2 MADRID 28020', '901.150905', 'FAX 915 55 ', '', '', '2018-01-13 00:00:00', 'importar'),
(50, 'MACASA Martinez y Castelo S.A.', 'MACASA Martinez y Castelo S.A.', 'A36620094', 'CARRETERA MOLEDO 7 VIGO 36214', '986 41 88 7', '986 41 55 5', '986 41 87 1', '', '0000-00-00 00:00:00', ''),
(51, 'SANTE IBERIA(NUTRITION &amp; SANTE IBERIA S.L.)', 'SANTE IBERIA(NUTRITION &amp; SANTE IBERIA S.L.)', '', '  ', '', '93 216 72 0', '', '', '0000-00-00 00:00:00', ''),
(52, 'CASH GALICIA-COMERCIAL MARTINEZ SANCHEZ S.L.', 'CASH GALICIA-COMERCIAL MARTINEZ SANCHEZ S.L.', 'B36010031', ' PONTEVEDRA 36003', '986213771', '986213771', '', '', '0000-00-00 00:00:00', ''),
(53, 'DIVERSOS ALIMENTACION', 'DIVERSOS ALIMENTACION', '', '  ', '', '', '', '', '0000-00-00 00:00:00', ''),
(54, 'FROIZ EKOAMA S.L.', 'FROIZ EKOAMA S.L.', 'B36641454', ' VIGO 36163', '986352191', '986353200', '986354750', 'recepcion.charcuteria@froiz.es', '0000-00-00 00:00:00', ''),
(55, 'MIEL O CASEIRO M&ordf; ANGELINA MAGALLANES RODRIGUEZ                                                ', 'MIEL O CASEIRO M&ordf; ANGELINA MAGALLANES RODRIGUEZ                                                ', '36131394G', 'MIEL *O CASEIRO* SAIANS-VIGO ', '986095679', '', '', '', '0000-00-00 00:00:00', ''),
(57, 'DICARVI  SA', 'DICARVI  SA', '', '  ', '986335599', '', '986335737', '', '0000-00-00 00:00:00', ''),
(58, 'GONZALEZ ESTALOTE Y OTRO CB', 'GONZALEZ ESTALOTE Y OTRO CB', 'E15653405', ' BEBA- MAZARICOS ( A CORU&Ntilde;A                                                                  ', '981 85 21 6', '981 85 24 4', '', '', '0000-00-00 00:00:00', ''),
(59, 'FROIZ CONGELADOS', 'FROIZ CONGELADOS', '', '  ', '986833010 O', '986 87 03 9', '', '', '0000-00-00 00:00:00', ''),
(60, 'JOSE LUIS TORRES (ESPECIAS)', 'JOSE LUIS TORRES (ESPECIAS)', '', '  ', '', '', '657959678', '', '0000-00-00 00:00:00', ''),
(61, 'PATATAS Y CEBOLLAS GANDARA S.C.', 'PATATAS Y CEBOLLAS GANDARA S.C.', 'J32441677', 'MOSQUEIRO VILAR DE SANTOS ORENSE 32651', '986413655', '', '', '', '0000-00-00 00:00:00', ''),
(62, 'ESPU&Ntilde;A ESTEBAN S.A.                                                                          ', 'ESPU&Ntilde;A ESTEBAN S.A.                                                                          ', 'A17008111', '  17800', '', '', '', '', '2018-02-05 23:44:34', 'importar'),
(63, 'EL POZO ALIMENTACION', 'EL POZO ALIMENTACION', 'A30014377', 'AVDA. ANTONIO FUENTES,1 ALHAMA DE MURCIA 30840', '981296824', '986353200', '618886385', '', '2018-02-06 00:03:38', 'importar'),
(64, 'FROIZ FRUTAS Y VERDURAS', 'FROIZ FRUTAS Y VERDURAS', '', '  36163', '986 874013', '986 873094', '', '', '2018-02-06 00:04:19', 'importar');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
