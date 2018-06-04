<?php
/**
* Elimina los eventos cuya fecha de finalización es anterior a la actual.
*/
require 'datos/ConexionBD.php';
require 'controladores/eventos.php';
require 'controladores/usuarios.php';
require 'controladores/asistentes.php';
require 'vistas/VistaXML.php';
require 'vistas/VistaJson.php';
require 'utilidades/ExcepcionApi.php';


// Sentencia DELETE
$consulta = "DELETE E.*, A.* FROM evento E LEFT JOIN asistente A ON E.id_evento=A.id_evento WHERE datediff(date_format(now(),'%d-%m-%Y %H:%i'), STR_TO_DATE(E.fecha_fin_evento,'%d-%m-%Y %H:%i')) > 5";

// Preparar la sentencia
$sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

$sentencia->execute();

echo $sentencia->rowCount();


