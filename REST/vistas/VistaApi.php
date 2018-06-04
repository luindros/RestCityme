<?php

/**
 * Clase base para la representación de las vistas
 *
 * @package vistas
 */
abstract class VistaApi{

    // Código de error
    public $estado;

    /**
    * Función abstracta que imprime el cuerpo de la respuesta
    *
    * @param mixed $cuerpo de la respuesta a enviar
    */
    public abstract function imprimir($cuerpo);
}