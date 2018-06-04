<?php
/**
* Gestiona el acceso a las promociones en la base de datos.
*
* @package controladores
*/
class promociones
{
	const NOMBRE_TABLA_PROMOCION = "promocion";
    const ID_PROMOCION = "id_promocion";
    const NOMBRE_PROMOCION = "nombre_promocion";
    const DESCRIPCION = "descripcion_promocion";
    const IMAGEN = "imagen_promocion";
    const ID_USUARIO = "id_usuario";
    const ID_EVENTO = "id_evento";
    const NOMBRE_TABLA_USUARIO = "usuario";
    const NOMBRE_TABLA_ASISTENTE = "asistente";
	const NOMBRE_TABLA_PROMOCION_EVENTO = "promocion_evento";

    const EXITO = 1;
    const ERROR_BDD = 2;
    const ERROR_PARAMETROS =3;

    /**
    * Gestiona las peticiones GET: búsqueda de todas las promociones,
    * búsqueda de las promociones creadas por un usuario,
    * búsqueda de las promociones asociadas a un evento.
    *
    * @param mixed $peticion que se va a procesar
    */
	public static function get($peticion)
	{
	        
	    if (empty($peticion[0]))
	        return self::obtenerPromociones();  // Se obtienen todas las promociones
	    else{
	        $tipo_peticion = $peticion[0];

	        if(strcmp($tipo_peticion,"usuario")==0) {  // Se obtienen las promociones creadas por un usuario
	            $id_usuario = $peticion[1];

	            return self::obtenerPromocionesUsuario($id_usuario);
	        }
	        elseif(strcmp($tipo_peticion,"evento")==0) {  // Se obtienen las promociones asociadas a un evento
	            $id_evento = $peticion[1];

	            return self::obtenerPromocionesEvento($id_evento);
	        }
	        	
	    }
	}

     /**
    * Gestiona las peticiones POST: creación de una nueva promoción y
    * asociación de una promoción con un evento.
    *
    * @param mixed $peticion que se va a procesar
    */
	public static function post($peticion)
    {

    	if (empty($peticion[0])) { // Se crea una promocion

    		//Extrae el cuerpo de la petición en forma de obejto de PHP
        	$body = file_get_contents('php://input'); //es la manera de transferir el contenido de un fichero a una cadena //php//imput permite leer los datos de una petición post
        	$promocion= json_decode($body);

        	$id_promocion = self::crearPromocion($promocion);

        	http_response_code(201); //codigo de created
        	return [
            	"estado" => self::EXITO,
            	"datos" => "Promocion creada"
        	];
    	}	        
	    else{ // Se asocia una promocion a un evento

	        $id_promocion = $peticion[0];
	        $id_evento = $peticion[1];

	        self::asociar($id_promocion, $id_evento);

	        http_response_code(201); //codigo de created
	        return [
	            "estado" => self::EXITO,
	            "datos" => "Promocion asociada"
	        ];
	    }

    }

    /**
    * Gestiona las peticiones PUT: modificación de la información de una promoción.
    *
    * @param mixed $peticion que se va a procesar
    * @throws ExcepcionApi
    */
    public static function put($peticion)
    {

        if (!empty($peticion[0])) {
            $body = file_get_contents('php://input');
            $promocion = json_decode($body);

            $id_promocion = $peticion[0];


            if (self::actualizar($promocion, $id_promocion) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::EXITO,
                    "datos" => "Registro actualizado correctamente"
                ];
            } else {
                throw new ExcepcionApi(self::ERROR_PARAMETROS,
                    "La promocion a la que intentas acceder no existe", 404);
            }
        } else {
            throw new ExcepcionApi(self::ERROR_PARAMETROS, "Falta id", 422);
        }
    }

    /**
    * Gestiona las peticiones DELETE: eliminación de una promoción, eliminación de la asociación
    * de todas las promociones de un evento y eliminación de una asociación de una promoción con un evento.
    *
    * @param mixed $peticion que se va a procesar
    * @throws ExcepcionApi
    */
    public static function delete($peticion)
    {

        if (!empty($peticion[0])) {

            $tipo_peticion = $peticion[0];

            if(strcmp($tipo_peticion,"promocion")==0) { // se elimina una promocion

                $id_promocion = $peticion[1];
                if (self::eliminarPromocion($id_promocion) > 0) {
                    http_response_code(200);
                    return [
                        "estado" => self::EXITO,
                        "datos" => "Registro eliminado correctamente"
                    ];
                } else {
                    throw new ExcepcionApi(self::ERROR_PARAMETROS,
                        "La promocion a la que intentas acceder no existe", 404);
                }
            }
            elseif(strcmp($tipo_peticion,"evento")==0) { // se eliminan todas las promociones asociadas a un evento

                $id_evento = $peticion[1];
                if (self::eliminarPromocionesAsociadasAEvento($id_evento ) > 0) {
                    http_response_code(200);
                    return [
                        "estado" => self::EXITO,
                        "datos" => "Registros eliminados correctamente"
                    ];
                } else {
                    http_response_code(200);
                    return [
                        "estado" => self::EXITO,
                        "datos" => "El evento no tiene promociones asociadas"
                    ];
                }
            }
            else { // se elimina la asociacion de una promocion con un evento

                $id_promocion = $peticion[0];
                $id_evento = $peticion[1];
                if (self::eliminarAsociacion($id_promocion,$id_evento) > 0) {
                    http_response_code(200);
                    return [
                        "estado" => self::EXITO,
                        "datos" => "Registro eliminado correctamente"
                    ];
                } else {
                    throw new ExcepcionApi(self::ERROR_PARAMETROS,
                        "La promocion no está asociada al evento", 404);
                }
            }

            
        } else {
            throw new ExcepcionApi(self::ERROR_PARAMETROS, "Falta id", 422);
        }

    }

    /**
    * Obtiene todas las promociones que hay en la base de datos.
    * @throws ExcepcionApi
    */
	private static function obtenerPromociones()
    {
        try {
            
            $consulta = "SELECT * FROM " . self::NOMBRE_TABLA_PROMOCION . " P JOIN " . self::NOMBRE_TABLA_USUARIO . " U ON U.id_usuario=P.id_usuario";

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
    * Obtiene las promociones creadas por un determinado usuario.
    *
    * @param string $id_usuario identificador del usuario 
    * @throws ExcepcionApi
    */
    private static function obtenerPromocionesUsuario($id_usuario)
    {
        try {
            
            //se obtienen las promociones propios
            $consulta = "SELECT * FROM " . self::NOMBRE_TABLA_PROMOCION . " P JOIN " . self::NOMBRE_TABLA_USUARIO . " U ON U.id_usuario=P.id_usuario WHERE P.id_usuario=?";
            
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
    * Obtiene las promociones asociadas a un determinado evento.
    *
    * @param int $id_evento identificador del evento
    * @throws ExcepcionApi
    */
    private static function obtenerPromocionesEvento($id_evento)
    {
        try {
            
            
            $consulta = "SELECT P.id_promocion, P.nombre_promocion, P.descripcion_promocion, P.imagen_promocion, U.* FROM " . self::NOMBRE_TABLA_PROMOCION_EVENTO . " P_E JOIN " . self::NOMBRE_TABLA_PROMOCION . " P ON P_E.id_promocion=P.id_promocion JOIN " . self::NOMBRE_TABLA_USUARIO . " U ON U.id_usuario=P.id_usuario WHERE P_E.id_evento=?";
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
     * Añade una nueva promoción
     * @param object $promocion datos de la promoción
     * @throws ExcepcionApi
     */
    private static function crearPromocion($promocion)
    {
        if ($promocion) {
            try {

                $pdo = ConexionBD::obtenerInstancia()->obtenerBD();


                $resultado = usuarios::comprobarExistenciaUsuario($promocion->id_usuario);
                if($resultado > 0) {
                	// Sentencia INSERT
                	$consulta = "INSERT INTO " . self::NOMBRE_TABLA_PROMOCION . " ( " .
                    self::NOMBRE_PROMOCION . "," .
                    self::DESCRIPCION . "," .
                    self::IMAGEN . "," .
                    self::ID_USUARIO . ")" .
                    " VALUES(?,?,?,?)";

	                // Preparar la sentencia
	                $sentencia = $pdo->prepare($consulta);

	                $sentencia->bindParam(1, $promocion->nombre_promocion);
	                $sentencia->bindParam(2, $promocion->descripcion_promocion);
	                $sentencia->bindParam(3, $promocion->imagen_promocion);
	                $sentencia->bindParam(4, $promocion->id_usuario);

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
     * Asocia una promoción a un evento
     * @param int $id_promocion identificador de la promoción
     * @param int $id_evento identificador del evento
     * @throws ExcepcionApi
     */
    private static function asociar($id_promocion, $id_evento)
    {
            try {

                $pdo = ConexionBD::obtenerInstancia()->obtenerBD();


                // Sentencia INSERT
                $consulta = "INSERT INTO " . self::NOMBRE_TABLA_PROMOCION_EVENTO . " ( " .
                self::ID_PROMOCION . "," .
                self::ID_EVENTO . ")" .
                " VALUES(?,?)";

	            // Preparar la sentencia
	            $sentencia = $pdo->prepare($consulta);

	            $sentencia->bindParam(1, $id_promocion);
	            $sentencia->bindParam(2, $id_evento);

	            $sentencia->execute();

            } catch (PDOException $e) {
                throw new ExcepcionApi(self::ERROR_BDD, $e->getMessage());
            }

    }

    /**
     * Actualiza la información de una promoción
     * @param object $promocion objeto que contiene la información de la promoción
     * @param int $id_promocion identificador de la promoción
     * @return número de registros actualizados
     * @throws ExceptionApi
     */
    private static function actualizar($promocion, $id_promocion)
    {
        try {
            // Creando consulta UPDATE
            $consulta = "UPDATE " . self::NOMBRE_TABLA_PROMOCION .
                " SET " . self::NOMBRE_PROMOCION . "=?," .
                self::DESCRIPCION . "=?," .
                self::IMAGEN . "=?" .
                " WHERE " . self::ID_PROMOCION . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            $sentencia->bindParam(1, $promocion->nombre_promocion);
            $sentencia->bindParam(2, $promocion->descripcion_promocion);
            $sentencia->bindParam(3, $promocion->imagen_promocion);
            $sentencia->bindParam(4, $id_promocion);

            // Ejecutar la sentencia
            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ERROR_BDD, $e->getMessage());
        }
    }

    /**
     * Elimina la asociación de una promoción con un determinado evento.
     * @param int $id_promocion identificador de la promoción
     * @param int $id_evento identificador del evento
     * @return número de registros eliminados
     * @throws ExceptionApi
     */
    private static function eliminarAsociacion($id_promocion,$id_evento)
    {
        try {
            // Sentencia DELETE
            $consulta = "DELETE FROM " . self::NOMBRE_TABLA_PROMOCION_EVENTO .
                " WHERE " . self::ID_PROMOCION . "=?" . " AND " . self::ID_EVENTO . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            $sentencia->bindParam(1, $id_promocion);
            $sentencia->bindParam(2, $id_evento);

            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ERROR_BDD, $e->getMessage());
        }
    }

    /**
     * Elimina la asociación de todas las promociones con un determinado evento.
     * @param int $id_evento identificador del evento
     * @return número de registros eliminados
     * @throws ExceptionApi
     */
    private static function eliminarPromocionesAsociadasAEvento($id_evento)
    {
        try {
            // Sentencia DELETE
            $consulta = "DELETE FROM " . self::NOMBRE_TABLA_PROMOCION_EVENTO .
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

    /**
     * Elimina una promoción
     * @param int $id_promocion identificador de la promoción
     * @return número de registros eliminados
     * @throws ExceptionApi
     */
    private static function eliminarPromocion($id_promocion)
    {
        //asistentes::eliminarTodosAsistentesAlEvento($id_evento);

        try {
            // Sentencia DELETE
            //$consulta = "DELETE FROM " . self::NOMBRE_TABLA_EVENTO . " WHERE " . self::ID_EVENTO . "=?";
            $consulta = "DELETE P_E.*, P.* FROM " . self::NOMBRE_TABLA_PROMOCION . " P LEFT JOIN " . self::NOMBRE_TABLA_PROMOCION_EVENTO . " P_E ON P.id_promocion=P_E.id_promocion WHERE P.id_promocion=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            $sentencia->bindParam(1, $id_promocion);

            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ERROR_BDD, $e->getMessage());
        }
    }

}