<?php

/**
 * Clase base para la representaci�n de las vistas
 *
 * @package vistas
 */
abstract class VistaApi{

    // C�digo de error
    public $estado;

    /**
    * Funci�n abstracta que imprime el cuerpo de la respuesta
    *
    * @param mixed $cuerpo de la respuesta a enviar
    */
    public abstract function imprimir($cuerpo);
}