<?php
// ========= Configuracion particular =============== //
/* La configuracion deberíamos obtenerla y el usuario debería poder configurarla y como tiene muchas opciones
 * debería se una clase en este modulo.*/
// Filtros es un array donde tendremos:
//          importar -> array ( nombre_campo,valor) donde nos indicar si queremos que lo importemos o no a Msyql.
//          fusionar -> array ( nombre_campo,valor,accion) donde nos va indicar que si queremos actualizar o crear ese registro.

Class ConfigImportar {
    public $campo_principal = 'CODIGO'; // El nombre del campo que debe ser unico y referencia de cruce

    //En filtros sin valor tanto en importar como fusionar no filtra nada.
    public $filtros = array ('importar' => array ( 'nombre_campo' => 'NULO',
                                               'valor' => '' // Nos indica cuales NO quieres importar a Mysql. 
                                        ),
                            'fusionar' => array(
												'actualizar' =>array ( 'nombre_campo' => 'NULO',
															'valor' => '', // Nos indica cuales NO quieres actualizar,crear o ambas.
														),
												'crear' => 	array ( 'nombre_campo' => 'NULO',
															'valor' => '1', // Nos indica cuales NO quieres actualizar,crear o ambas.
														),
												'ambos' =>array ( 'nombre_campo' => 'NULO',
															'valor' => '', // Nos indica cuales NO quieres actualizar,crear o ambas.
														),
											)
                            );
    // Campos que no añadimos a SQL si tiene valor indicado.
    public $campos_sindatos = array ( 'VACIOS'    => 'None',
                            'FECHA_UL'  => 'None'
                            );
    // Registrar en error_log
    public $reg_log = array( 'importar' => array('nulo'  => 'Si', // Mostramos codigos nulos, si tenmos un campo Nulo ,sino no habra
                                    'error' => 'Si', // Mostramos codigos errores
                                    'sql'   => 'No'  // Sql generamos que produce error
                                     ),
                 'fusionar' => array ('Codigo_y_estado' =>'Si', // Mostramos CODIGO y estados dos array 
                                      'diferencia_array' => 'Si' // Muestra los arrays de tabla modulo_importar_articulo y tpvfox
                                    )
                );
}

?>
