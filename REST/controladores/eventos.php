<?php
/**
* Gestiona el acceso a los eventos en la base de datos.
*
* @package controladores
*/
class eventos
{

    const NOMBRE_TABLA_EVENTO = "evento";
    const ID_EVENTO = "id_evento";
    const NOMBRE_EVENTO = "nombre_evento";
    const DESCRIPCION = "descripcion_evento";
    const CIUDAD = "ciudad_evento";
    const IMAGEN = "imagen_evento";
    const LATITUD = "latitud_evento";
    const LONGITUD = "longitud_evento";
    const LUGAR = "lugar_evento";
    const ID_USUARIO = "id_usuario";
    const FECHA_INICIO ="fecha_inicio_evento";
    const FECHA_FIN ="fecha_fin_evento";
    const NOMBRE_TABLA_USUARIO = "usuario";
    const NOMBRE_TABLA_ASISTENTE = "asistente";
    const NOMBRE_TABLA_PROMOCION_EVENTO = "promocion_evento";
    const NOMBRE_TABLA_IMAGENES_EVENTO = "imagenes_anteriores_evento";
    const IMAGEN_EVENTO_ANTERIOR = "imagen";

    const EXITO = 1;
    const ERROR_BDD = 2;
    const ERROR_PARAMETROS =3;

    /**
    * Gestiona las peticiones GET: búsqueda de todos los eventos,
    * búsqueda de los eventos creados por un usuario,
    * búsqueda de los eventos por radar y búsqueda de las
    * imágenes de ediciones anterios de un evento.
    *
    * @param mixed $peticion que se va a procesar
    */
    public static function get($peticion)
    {
        
        if (empty($peticion[0]))
            return self::obtenerEventos();  // Se obtienen todos los eventos
        else{
            $tipo_peticion = $peticion[0];

            if(strcmp($tipo_peticion,"usuario")==0) {  // Se obtienen los eventos creados por un usuario
                $id_usuario = $peticion[1];

                return self::obtenerEventosUsuario($id_usuario);
            }
            elseif (strcmp($tipo_peticion,"radar")==0) {  // Se obtienen los eventos por radar (radio respecto a un punto)
                $lat = $peticion[1];
                $lon = $peticion[2];
                $dist = $peticion[3];

                return self::obtenerEventosRadar($lat,$lon,$dist);
            }
            elseif (strcmp($tipo_peticion,"imagenes")==0) {
                $id_evento = $peticion[1];

                return self::obtenerImagenesAnterioresEvento($id_evento);
            }
        	
        }
    }

    /**
    * Gestiona las peticiones POST: creación de un nuevo evento y
    * asociación de una imagen de una edición anterior con un evento.
    *
    * @param mixed $peticion que se va a procesar
    */
    public static function post($peticion)
    {
        if (empty($peticion[0])) { // Creación de un nuevo evento

            //Extrae el cuerpo de la petición en forma de obejto de PHP
            $body = file_get_contents('php://input'); //es la manera de transferir el contenido de un fichero a una cadena //php//imput permite leer los datos de una petición post
            $evento= json_decode($body);

            $id_evento = self::crear($evento);

            http_response_code(201); //codigo de created
            return [
                "estado" => self::EXITO,
                "datos" => $id_evento
            ];
        }
        else{

            $tipo_peticion = $peticion[0];

            if(strcmp($tipo_peticion,"imagenes")==0) { // Se asocia una imagen con el id del evento
                $id_evento = $peticion[1];

                $body = file_get_contents('php://input');
                $imagen_evento = json_decode($body);

                self::asociarImagenAnteriorEvento($id_evento,$imagen_evento);

                http_response_code(201); //codigo de created
                return [
                    "estado" => self::EXITO,
                    "datos" => "Imagen asociada"
                ];

            }
        }
        

    }

    /**
    * Gestiona las peticiones PUT: modificación de la información de un evento.
    *
    * @param mixed $peticion que se va a procesar
    * @throws ExcepcionApi
    */
    public static function put($peticion)
    {

        if (!empty($peticion[0])) {
            $body = file_get_contents('php://input');
            $evento = json_decode($body);

            $id_evento = $peticion[0];


            if (self::actualizar($evento, $id_evento) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::EXITO,
                    "datos" => "Registro actualizado correctamente"
                ];
            } else {
                throw new ExcepcionApi(self::ERROR_PARAMETROS,
                    "El evento al que intentas acceder no existe", 404);
            }
        } else {
            throw new ExcepcionApi(self::ERROR_PARAMETROS, "Falta id", 422);
        }
    }

    /**
    * Gestiona las peticiones DELETE: eliminación de un evento y eliminación de todas las
    * imágenes de ediciones anteriores de un evento.
    *
    * @param mixed $peticion que se va a procesar
    * @throws ExcepcionApi
    */
    public static function delete($peticion)
    {

        if (!empty($peticion[0])) {

            $tipo_peticion = $peticion[0];

            if(strcmp($tipo_peticion,"imagenes")==0) { // Se eliminan todas las imagenes anteriores del evento

                $id_evento = $peticion[1];
                if (self::eliminarImagenesAnterioresEvento($id_evento ) > 0) {
                    http_response_code(200);
                    return [
                        "estado" => self::EXITO,
                        "datos" => "Registros eliminados correctamente"
                    ];
                } else {
                    http_response_code(200);
                    return [
                        "estado" => self::EXITO,
                        "datos" => "El evento no tiene imagenes asociadas"
                    ];
                }
            }
            else { // Se elimina un evento

                $id_evento = $peticion[0];
                if (self::eliminar($id_evento) > 0) {
                    http_response_code(200);
                    return [
                        "estado" => self::EXITO,
                        "datos" => "Registro eliminado correctamente"
                    ];
                } else {
                    throw new ExcepcionApi(self::ERROR_PARAMETROS,
                        "El evento al que intentas acceder no existe", 404);
                }
            }

        } else {
            throw new ExcepcionApi(self::ERROR_PARAMETROS, "Falta id", 422);
        }

    }

    /**
    * Obtiene todos los eventos que hay en la base de datos.
    * @throws ExcepcionApi
    */
    private static function obtenerEventos()
    {
        try {
            
            $consulta = "SELECT * FROM " . self::NOMBRE_TABLA_EVENTO . " E JOIN " . self::NOMBRE_TABLA_USUARIO . " U ON U.id_usuario=E.id_usuario";

            // Preparar sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);


            // Ejecutar sentencia preparada
            if ($sentencia->execute()) {
                http_response_code(200);
                return
                    [
                        "estado" => self::EXITO,
                        "datos" => $sentencia->fetchAll(PDO::FETCH_ASSOC)
                    ];
            } else
                throw new ExcepcionApi(self::ERROR_BDD, "Se ha producido un error");

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ERROR_BDD, $e->getMessage());
        }
    }

    /**
    * Obtiene los eventos creados por un determinado usuario.
    *
    * @param string $id_usuario identificador del usuario
    * @throws ExcepcionApi
    */
    private static function obtenerEventosUsuario($id_usuario)
    {
        try {
            
            //se obtienen los eventos propios
            $consulta = "SELECT * FROM " . self::NOMBRE_TABLA_EVENTO . " E JOIN " . self::NOMBRE_TABLA_USUARIO . " U ON U.id_usuario=E.id_usuario WHERE E.id_usuario=?";
            
            // Preparar sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
            $sentencia->bindParam(1, $id_usuario);
              

            // Ejecutar sentencia preparada
            if ($sentencia->execute()) {
                http_response_code(200);
                return
                    [
                        "estado" => self::EXITO,
                        "datos" => $sentencia->fetchAll(PDO::FETCH_ASSOC)
                    ];
            } else
                throw new ExcepcionApi(self::ERROR_BDD, "Se ha producido un error");

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ERROR_BDD, $e->getMessage());
        }
    }

    /**
    * Obtiene los eventos que se encuentran dentro los rangos establecidos.
    *
    * @param double $lat latitud de la ubicación indicada por el usuario
    * @param double $lon longitud de la ubicación indicada por el usuario
    * @param int $dist radio dentro del cual se desean obtener los eventos
    * @throws ExcepcionApi
    */
    private static function obtenerEventosRadar($lat, $lon, $dist)
    {

        $R = 6371; // Radio de la tierra en kilometros
        $r = $dist / $R;

        $latRad = deg2rad($lat);
        $lonRad = deg2rad($lon);

        //Para hacer la consulta más eficiente se utiliza un cuadro delimitador para hacer un primer corte inicial
        $lat_min = $lat - rad2deg($r);
        $lat_max = $lat + rad2deg($r);
        $lon_min = $lon - rad2deg(asin($r) / cos(deg2rad($lat)));
        $lon_max = $lon + rad2deg(asin($r) / cos(deg2rad($lat)));

        try {
            
            $consulta = "SELECT * FROM ( SELECT E.id_evento,E.nombre_evento,E.ciudad_evento,E.descripcion_evento,E.imagen_evento,E.latitud_evento,E.longitud_evento,E.lugar_evento,E.id_usuario,E.fecha_inicio_evento,E.fecha_fin_evento,U.nombre_usuario,U.email_usuario,U.link_usuario,U.token_usuario,U.imagenUrl_usuario FROM " . self::NOMBRE_TABLA_EVENTO . " E JOIN " . self::NOMBRE_TABLA_USUARIO . " U ON U.id_usuario=E.id_usuario WHERE (E.latitud_evento>=? AND E.latitud_evento<=?) AND (E.longitud_evento>=? AND E.longitud_evento <=?)) AS PRIMERCORTE WHERE acos(sin(?)*sin(radians(PRIMERCORTE.latitud_evento))+cos(?)*cos(radians(PRIMERCORTE.latitud_evento))*cos(radians(PRIMERCORTE.longitud_evento)-?))*?<?";
            
            // Preparar sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
            $sentencia->bindParam(1, $lat_min);
            $sentencia->bindParam(2, $lat_max);
            $sentencia->bindParam(3, $lon_min);
            $sentencia->bindParam(4, $lon_max);
            $sentencia->bindParam(5, $latRad);
            $sentencia->bindParam(6, $latRad);
            $sentencia->bindParam(7, $lonRad);
            $sentencia->bindParam(8, $R);
            $sentencia->bindParam(9, $dist);
              

            // Ejecutar sentencia preparada
            if ($sentencia->execute()) {
                http_response_code(200);
                return
                    [
                        "estado" => self::EXITO,
                        "datos" => $sentencia->fetchAll(PDO::FETCH_ASSOC)
                    ];
            } else
                throw new ExcepcionApi(self::ERROR_BDD, "Se ha producido un error");

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ERROR_BDD, $e->getMessage());
        }
    }

    /**
    * Obtiene llas imágenes de ediciones anteriores de un evento.
    *
    * @param int $id_evento identificador del evento 
    * @throws ExcepcionApi
    */
    private static function obtenerImagenesAnterioresEvento($id_evento)
    {
        try {
            
            $consulta = "SELECT * FROM " . self::NOMBRE_TABLA_IMAGENES_EVENTO . " I WHERE I.id_evento=?";
            
            // Preparar sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
            $sentencia->bindParam(1, $id_evento);
              

            // Ejecutar sentencia preparada
            if ($sentencia->execute()) {
                http_response_code(200);
                return
                    [
                        "estado" => self::EXITO,
                        "datos" => $sentencia->fetchAll(PDO::FETCH_ASSOC)
                    ];
            } else
                throw new ExcepcionApi(self::ERROR_BDD, "Se ha producido un error");

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ERROR_BDD, $e->getMessage());
        }
    }

    /**
     * Añade un nuevo evento
     * @param object $evento datos del evento
     * @throws ExcepcionApi
     */
    private static function crear($evento)
    {
        if ($evento) {
            try {

                $pdo = ConexionBD::obtenerInstancia()->obtenerBD();


                $resultado = usuarios::comprobarExistenciaUsuario($evento->id_usuario);
                if($resultado > 0) {
                	// Sentencia INSERT
                	$consulta = "INSERT INTO " . self::NOMBRE_TABLA_EVENTO . " ( " .
                    self::NOMBRE_EVENTO . "," .
                    self::DESCRIPCION . "," .
                    self::CIUDAD . "," .
                    self::IMAGEN . "," .
                    self::LATITUD . "," .
                    self::LONGITUD . "," .
                    self::LUGAR . "," .
                    self::ID_USUARIO . "," .
                    self::FECHA_INICIO . "," .
                    self::FECHA_FIN . ")" .
                    " VALUES(?,?,?,?,?,?,?,?,?,?)";

	                // Preparar la sentencia
	                $sentencia = $pdo->prepare($consulta);

                    $latitudDouble = doubleval($evento->latitud_evento);
                    $longitudDouble = doubleval($evento->longitud_evento);

	                $sentencia->bindParam(1, $evento->nombre_evento);
	                $sentencia->bindParam(2, $evento->descripcion_evento);
	                $sentencia->bindParam(3, $evento->ciudad_evento);
	                $sentencia->bindParam(4, $evento->imagen_evento);
	                $sentencia->bindParam(5, $latitudDouble);
                    $sentencia->bindParam(6, $longitudDouble);
                    $sentencia->bindParam(7, $evento->lugar_evento);
	                $sentencia->bindParam(8, $evento->id_usuario);
                    $sentencia->bindParam(9, $evento->fecha_inicio_evento);
                    $sentencia->bindParam(10, $evento->fecha_fin_evento);

	                $sentencia->execute();

	                // Retornar en el último id insertado
	                return $pdo->lastInsertId();
                }
                else{
                	throw new ExcepcionApi(
                		self::ERROR_PARAMETROS,
                		utf8_encode("Error: El usuario no está registrado"));
                }

                

            } catch (PDOException $e) {
                throw new ExcepcionApi(self::ERROR_BDD, $e->getMessage());
            }
        } else {
            throw new ExcepcionApi(
                self::ERROR_PARAMETROS,
                utf8_encode("Error en existencia o sintaxis de parámetros"));
        }

    }

    /**
     * Asocia una imagen de ediciones anteriores a un evento
     * @param int $id_evento identificador del evento
     * @param mixed $imagen_evento imagen en Base64
     * @throws ExcepcionApi
     */
    private static function asociarImagenAnteriorEvento($id_evento, $imagen_evento)
    {
            try {

                $pdo = ConexionBD::obtenerInstancia()->obtenerBD();


                // Sentencia INSERT
                $consulta = "INSERT INTO " . self::NOMBRE_TABLA_IMAGENES_EVENTO . " ( " .
                self::ID_EVENTO . "," .
                self::IMAGEN_EVENTO_ANTERIOR . ")" .
                " VALUES(?,?)";

                // Preparar la sentencia
                $sentencia = $pdo->prepare($consulta);

                $sentencia->bindParam(1, $id_evento);
                $sentencia->bindParam(2, $imagen_evento->imagen);

                $sentencia->execute();

            } catch (PDOException $e) {
                throw new ExcepcionApi(self::ERROR_BDD, $e->getMessage());
            }

    }

    /**
     * Actualiza la información de un evento
     * @param object $evento objeto que contiene la información del evento
     * @param int $id_evento identificador del evento
     * @return número de registros actualizados
     * @throws ExceptionApi
     */
    private static function actualizar($evento, $id_evento)
    {
        try {
            // Creando consulta UPDATE
            $consulta = "UPDATE " . self::NOMBRE_TABLA_EVENTO .
                " SET " . self::NOMBRE_EVENTO . "=?," .
                self::DESCRIPCION . "=?," .
                self::CIUDAD . "=?," .
                self::IMAGEN . "=?," .
                self::LATITUD . "=?," .
                self::LONGITUD . "=?," .
                self::LUGAR . "=?," .
                self::FECHA_INICIO . "=?," .
                self::FECHA_FIN . "=?" .
                " WHERE " . self::ID_EVENTO . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            $latitudDouble = doubleval($evento->latitud_evento);
            $longitudDouble = doubleval($evento->longitud_evento);

            $sentencia->bindParam(1, $evento->nombre_evento);
            $sentencia->bindParam(2, $evento->descripcion_evento);
            $sentencia->bindParam(3, $evento->ciudad_evento);
            $sentencia->bindParam(4, $evento->imagen_evento);
            $sentencia->bindParam(5, $latitudDouble);
            $sentencia->bindParam(6, $longitudDouble);
            $sentencia->bindParam(7, $evento->lugar_evento);
            $sentencia->bindParam(8, $evento->fecha_inicio_evento);
            $sentencia->bindParam(9, $evento->fecha_fin_evento);
            $sentencia->bindParam(10, $id_evento);

            // Ejecutar la sentencia
            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ERROR_BDD, $e->getMessage());
        }
    }


    /**
     * Elimina un evento
     * @param int $id_evento identificador del evento
     * @return número de registros eliminados
     * @throws ExceptionApi
     */
    private static function eliminar($id_evento)
    {

        try {
            // Sentencia DELETE
            $consulta = "DELETE E.*, A.*, P_E.*, I.* FROM " . self::NOMBRE_TABLA_EVENTO . " E LEFT JOIN " . self::NOMBRE_TABLA_ASISTENTE . " A ON E.id_evento=A.id_evento LEFT JOIN " . self::NOMBRE_TABLA_PROMOCION_EVENTO . " P_E ON P_E.id_evento=E.id_evento LEFT JOIN " . self::NOMBRE_TABLA_IMAGENES_EVENTO . " I ON I.id_evento=E.id_evento WHERE E.id_evento=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            $sentencia->bindParam(1, $id_evento);

            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ERROR_BDD, $e->getMessage());
        }
    }

    /**
     * Elimina todas las imágenes de ediciones anteriores de un evento
     * @param int $id_evento identificador del evento
     * @return número de registros eliminados
     * @throws ExceptionApi
     */
    private static function eliminarImagenesAnterioresEvento($id_evento)
    {
        try {
            // Sentencia DELETE
            $consulta = "DELETE FROM " . self::NOMBRE_TABLA_IMAGENES_EVENTO .
                " WHERE " . self::ID_EVENTO . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            $sentencia->bindParam(1, $id_evento);

            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ERROR_BDD, $e->getMessage());
        }
    }
}

		