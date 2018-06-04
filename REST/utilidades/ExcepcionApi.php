<?php

/**
 * Excepción personalizada para el envío del estado
 *
 * @package utilidades
 */
class ExcepcionApi extends Exception
{
    public $estado;

	/**
	* Constructor
	*
	* @param int $estado que indica el tipo de excepción
	* @param string $mensaje de la excepción
	* @param int $codigo de estado HTTP
	*/
    public function __construct($estado, $mensaje, $codigo = 400)
    {
        $this->estado = $estado;
        $this->message = $mensaje;
        $this->code = $codigo;
    }

}