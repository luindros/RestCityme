<?php
/**
* Gestiona el acceso a los asistentes a un evento en la base de datos.
*
* @package controladores
*/
class asistentes
{
    // Datos de la tabla "usuario"
    const NOMBRE_TABLA_ASISTENTES = "asistente";
    const NOMBRE_TABLA_EVENTOS = "evento";
    const NOMBRE_TABLA_USUARIOS = "usuario";
    const ID_USUARIO = "id_usuario";
    const ID_EVENTO = "id_evento";


    const EXITO = 1;
    const ERROR_BDD = 2;
    const ERROR_PARAMETROS =3;

	public static function get($peticion)
	{
	    $tipo_peticion = $peticion[0];

        if(strcmp($tipo_peticion,"evento")==0) { // se obtienen los asistentes a un evento
            $id_evento = $peticion[1];

            return self::obtenerAsistentes($id_evento);
        }
        elseif (strcmp($tipo_peticion,"usuario")==0) { // se obtienen los eventos a los que asiste un usuario
            $id_usuario = $peticion[1];

            return self::obtenerEventosAsistidos($id_usuario);
        }
        elseif (strcmp($tipo_peticion,"evento_usuario")==0) {
            $id_evento=$peticion[1];
            $id_usuario=$peticion[2];

            return self::comprobarAsistenciaEvento($id_evento, $id_usuario);
        }
	   
	    

	}

    /**
    * Gestiona las peticiones POST: creación de un nuevo asistente a un evento.
    *
    * @param mixed $peticion que se va a procesar
    */
    public static function post($peticion)
    {

        $id_evento = $peticion[0];
        $id_usuario= $peticion[1];

        self::crear($id_evento, $id_usuario);

        http_response_code(201); //codigo de created
        return [
            "estado" => self::EXITO,
            "datos" => "Asistente creado"
        ];

    }

    /**
    * Gestiona las peticiones DELETE: eliminación de un asistente a un evento.
    *
    * @param mixed $peticion que se va a procesar
    * @throws ExcepcionApi
    */
    public static function delete($peticion)
    {

            $id_evento = $peticion[0];
            $id_usuario = $peticion[1];
            if (self::eliminar($id_evento,$id_usuario) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::EXITO,
                    "datos" => "Registro eliminado correctamente"
                ];
            } else {
                throw new ExcepcionApi(self::ERROR_PARAMETROS,
                    "El asistente al que intentas acceder no asiste al evento", 404);
            }

    }


    /**
    * Obtiene todos los asistentes a un  determinado evento.
    * @param int $id_evento identificador del evento
    * @throws ExcepcionApi
    */
    private static function obtenerAsistentes($id_evento)
    {
        try {
            
            //se obtienen los eventos propios
            $consulta = "SELECT U.id_usuario, U.nombre_usuario, U.email_usuario, U.link_usuario, U.token_usuario, U.imagenUrl_usuario FROM " . self::NOMBRE_TABLA_ASISTENTES . " A JOIN " . self::NOMBRE_TABLA_USUARIOS . " U ON A.id_usuario=U.id_usuario "
            . " JOIN " . self::NOMBRE_TABLA_EVENTOS ." E ON E.id_evento=A.id_evento WHERE A.id_evento=?";
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
    * Obtiene todos los eventos a los que asiste un determinado usuario.
    * @param string $id_usuario identificador del usuario
    * @throws ExcepcionApi
    */
    private static function obtenerEventosAsistidos($id_usuario)
    {
        try {
            
            //se obtienen los eventos propios
            $consulta = "SELECT * FROM " . self::NOMBRE_TABLA_ASISTENTES . " A JOIN " . self::NOMBRE_TABLA_USUARIOS . " U ON A.id_usuario=U.id_usuario " . " JOIN " . self::NOMBRE_TABLA_EVENTOS ." E ON E.id_evento=A.id_evento WHERE A.id_usuario=?";
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
    * Comprueba si un usuario asiste o no a un evento.
    * @param int $id_evento identificador del evento
    * @param string $id_usuario identificador del usuario
    * @throws ExcepcionApi
    */
    private static function comprobarAsistenciaEvento($id_evento, $id_usuario)
        {
            try {
                
                $consulta = "SELECT * FROM " . self::NOMBRE_TABLA_ASISTENTES . " A WHERE A.id_usuario=? AND A.id_evento=?";
                // Preparar sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
                $sentencia->bindParam(1, $id_usuario);
                $sentencia->bindParam(2, $id_evento);

                // Ejecutar sentencia preparada
                if ($sentencia->execute()) {
                    if($sentencia->rowCount()>0){
                        http_response_code(200);
                        return
                            [
                                "estado" => self::EXITO,
                                "datos" => "yes"
                            ];
                    }
                    else{
                        http_response_code(200);
                        return
                            [
                                "estado" => self::EXITO,
                                "datos" => "no"
                            ];
                    }
                    
                } else
                    throw new ExcepcionApi(self::ERROR_BDD, "Se ha producido un error");

            } catch (PDOException $e) {
                throw new ExcepcionApi(self::ERROR_BDD, $e->getMessage());
            }
        }


    /**
     * Añade un nuevo asistente al evento
     * @param int $id_evento identificador del evento
     * @param string $id_usuario identificador del usuario
     * @throws ExcepcionApi
     */    
    private static function crear($id_evento, $id_usuario)
    {
            try {

                $pdo = ConexionBD::obtenerInstancia()->obtenerBD();


                // Sentencia INSERT
                $consulta = "INSERT INTO " . self::NOMBRE_TABLA_ASISTENTES . " ( " .
                self::ID_USUARIO . "," .
                self::ID_EVENTO . ")" .
                " VALUES(?,?)";

	            // Preparar la sentencia
	            $sentencia = $pdo->prepare($consulta);

	            $sentencia->bindParam(1, $id_usuario);
	            $sentencia->bindParam(2, $id_evento);

	            $sentencia->execute();

            } catch (PDOException $e) {
                throw new ExcepcionApi(self::ERROR_BDD, $e->getMessage());
            }

    }

    /**
     * Elimina un asistente de un evento.
     * @param int $id_evento identificador del evento
     * @param string $id_usuario identificador del usuario
     * @throws ExcepcionApi
     */ 
    private static function eliminar($id_evento,$id_usuario)
    {
        try {
            // Sentencia DELETE
            $consulta = "DELETE FROM " . self::NOMBRE_TABLA_ASISTENTES .
                " WHERE " . self::ID_EVENTO . "=?" . " AND " . self::ID_USUARIO . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            $sentencia->bindParam(1, $id_evento);
            $sentencia->bindParam(2, $id_usuario);

            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ERROR_BDD, $e->getMessage());
        }
    }

    
}