<?php

/**
 * Excepci�n personalizada para el env�o del estado
 *
 * @package utilidades
 */
class ExcepcionApi extends Exception
{
    public $estado;

	/**
	* Constructor
	*
	* @param int $estado que indica el tipo de excepci�n
	* @param string $mensaje de la excepci�n
	* @param int $codigo de estado HTTP
	*/
    public function __construct($estado, $mensaje, $codigo = 400)
    {
        $this->estado = $estado;
        $this->message = $mensaje;
        $this->code = $codigo;
    }

}