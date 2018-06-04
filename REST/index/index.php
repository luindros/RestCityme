<?php
/**
* Gestiona el acceso a los recursos ofrecidos por el servicio REST
* y sigue los principios de dicha arquitectura.
*/
require 'datos/ConexionBD.php';
require 'controladores/eventos.php';
require 'controladores/usuarios.php';
require 'controladores/asistentes.php';
require 'controladores/promociones.php';
require 'vistas/VistaXML.php';
require 'vistas/VistaJson.php';
require 'utilidades/ExcepcionApi.php';

// Constantes de estado
const ERROR_URL_INCORRECTA = 4;
const ERROR_EXISTENCIA_RECURSO = 5;
const ERROR_METODO_NO_PERMITIDO = 6;

// Preparar manejo de excepciones
$formato = isset($_GET['formato']) ? $_GET['formato'] : 'json';

switch ($formato) {
    case 'xml':
        $vista = new VistaXML();
        break;
    case 'json':
    default:
        $vista = new VistaJson();
}

set_exception_handler(function ($exception) use ($vista) {
    $cuerpo = array(
        "estado" => $exception->estado,
        "datos" => $exception->getMessage()
    );
    if ($exception->getCode()) {
        $vista->estado = $exception->getCode();
    } else {
        $vista->estado = 500;
    }

    $vista->imprimir($cuerpo);
}
);

// Extraer segmento de la url
if (isset($_GET['PATH_INFO']))
    $peticion = explode('/', $_GET['PATH_INFO']);
else
    throw new ExcepcionApi(ERROR_URL_INCORRECTA, utf8_encode("No se reconoce la petición"));

// Obtener recurso
$recurso = array_shift($peticion);
$recursos_existentes = array('eventos', 'usuarios', 'asistentes', 'promociones');

// Comprobar si existe el recurso
if (!in_array($recurso, $recursos_existentes)) {
    throw new ExcepcionApi(ERROR_EXISTENCIA_RECURSO,
        "No se reconoce el recurso al que intentas acceder");
}

$metodo = strtolower($_SERVER['REQUEST_METHOD']);

// Filtrar método
switch ($metodo) {
    case 'get':
    case 'post':
    case 'put':
    case 'delete':
        if (method_exists($recurso, $metodo)) {
            $respuesta = call_user_func(array($recurso, $metodo), $peticion);
            $vista->imprimir($respuesta);
            break;
        }
    default:
        // Método no aceptado
        $vista->estado = 405;
        $cuerpo = [
            "estado" => ERROR_METODO_NO_PERMITIDO,
            "datos" => utf8_encode("Método no permitido")
        ];
        $vista->imprimir($cuerpo);

}


