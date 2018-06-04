<?php
/**
* Gestiona el acceso a los usuarios en la base de datos.
*
* @package controladores
*/
class usuarios
{
    // Datos de la tabla "usuario"
    const NOMBRE_TABLA_USUARIO = "usuario";
    const ID_USUARIO = "id_usuario";
    const NOMBRE = "nombre_usuario";
    const EMAIL = "email_usuario";
    const LINK = "link_usuario";
    const TOKEN = "token_usuario";
    const IMAGEN_URL = "imagenUrl_usuario";
    const NOMBRE_TABLA_EVENTO = "evento";
    const NOMBRE_TABLA_ASISTENTE = "asistente";
    const NOMBRE_TABLA_PROMOCION = "promocion";
    const NOMBRE_TABLA_PROMOCION_EVENTO = "promocion_evento";
    const NOMBRE_TABLA_IMAGENES_EVENTO = "imagenes_anteriores_evento";

    const EXITO = 1;
    const ERROR_BDD = 2;
    const ERROR_PARAMETROS =3;


    /**
    * Gestiona las peticiones POST: login/registro de un usuario.
    *
    * @param mixed $peticion que se va a procesar
    */
    public static function post($peticion)
    {
        //Extrae el cuerpo de la petición en forma de obejto de PHP
        $body = file_get_contents('php://input'); //es la manera de transferir el contenido de un fichero a una cadena //php//imput permite leer los datos de una petición post
        $usuario= json_decode($body);

        $resultado = self::comprobarExistenciaUsuario($usuario->id_usuario);
        
        if(!($resultado > 0)) { // si no hay usuario con ese id hay que registrarle en la bdd
            return self::registrar($usuario);

        } else {
            http_response_code(200);
            return [
                "estado" => self::EXITO,
                "datos" => "Login realizado correctamente"
            ];
        }

    }

    /**
    * Gestiona las peticiones DELETE: eliminación de un usuario.
    *
    * @param mixed $peticion que se va a procesar
    * @throws ExcepcionApi
    */
    public static function delete($peticion)
    {

        if (!empty($peticion[0])) {

            $id_usuario = $peticion[0];
            if (self::eliminar($id_usuario) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::EXITO,
                    "datos" => "Registro eliminado correctamente"
                ];
            } else {
                throw new ExcepcionApi(self::ERROR_PARAMETROS,
                    "El usuario al que intentas acceder no existe", 404);
            }
        } else {
            throw new ExcepcionApi(self::ERROR_PARAMETROS, "Falta id", 422);
        }

    }


    /**
     * Registra un nuevo usuario 
     * @param object $usuario 
     * @throws ExcepcionApi
     */
    private function registrar($usuario)
    {
        $resultado = self::crear($usuario);

        switch ($resultado) {
            case self::EXITO:
                http_response_code(200);
                return
                    [
                        "estado" => self::EXITO,
                        "datos" => utf8_encode("¡Registro con éxito!")
                    ];
                break;
            case self::ERROR_BDD:
                throw new ExcepcionApi(self::ERROR_BDD, "Ha ocurrido un error");
                break;
            default:
                throw new ExcepcionApi(self::ERROR_PARAMETROS, "Falla desconocida", 400);
        }
    }

    /**
     * Crea un nuevo usuario en la base de datos.
     * @param object $datosUsuario datps del usuario
     * @return int codigo para determinar si la inserción fue exitosa
     * @throws ExcepcionApi
     */
    private function crear($datosUsuario)
    {

        try {

            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia INSERT
            $consulta = "INSERT INTO " . self::NOMBRE_TABLA_USUARIO . " ( " .
                self::ID_USUARIO . "," .
                self::NOMBRE . "," .
                self::EMAIL . "," .
                self::LINK . "," .
                self::TOKEN . "," .
                self::IMAGEN_URL . ")" .
                " VALUES(?,?,?,?,?,?)";

            $sentencia = $pdo->prepare($consulta);

            $sentencia->bindParam(1, $datosUsuario->id_usuario);
            $sentencia->bindParam(2, $datosUsuario->nombre_usuario);
            $sentencia->bindParam(3, $datosUsuario->email_usuario);
            $sentencia->bindParam(4, $datosUsuario->link_usuario);
            $sentencia->bindParam(5, $datosUsuario->token_usuario);
            $sentencia->bindParam(6, $datosUsuario->imagenUrl_usuario);

            $resultado = $sentencia->execute();

            if ($resultado) {
                return self::EXITO;
            } else {
                return self::ERROR_BDD;
            }
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ERROR_BDD, $e->getMessage());
        }

    }

    /**
     * Elimina un usuario
     * @param string $id_usuario identificador del usuario
     * @return número de registros eliminados
     * @throws ExceptionApi
     */
    private static function eliminar($id_usuario)
    {
        //asistentes::eliminarTodaLaAsistencia($id_usuario);
        //eventos::eliminarEventosCreados($id_usuario);

        try {
            // Sentencia DELETE
            //$consulta = "DELETE FROM " . self::NOMBRE_TABLA_USUARIO . " WHERE " . self::ID_USUARIO . "=?";
            $consulta = "DELETE E.*, A.*, U.*, P_E.*, P.*, I.* FROM " . self::NOMBRE_TABLA_EVENTO . " E LEFT JOIN " . self::NOMBRE_TABLA_ASISTENTE . " A ON E.id_usuario=A.id_usuario LEFT JOIN " . self::NOMBRE_TABLA_USUARIO . " U ON A.id_usuario=U.id_usuario LEFT JOIN " . self::NOMBRE_TABLA_PROMOCION . " P ON P.id_usuario=U.id_usuario LEFT JOIN " . self::NOMBRE_TABLA_PROMOCION_EVENTO . " P_E ON P_E.id_promocion=P.id_promocion LEFT JOIN " . self::NOMBRE_TABLA_IMAGENES_EVENTO . " I ON I.id_evento=E.id_evento WHERE U.id_usuario=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            $sentencia->bindParam(1, $id_usuario);

            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ERROR_BDD, $e->getMessage());
        }
    }

    /**
     * Comrpueba si un usuario está registrado o no.
     * @param string $id_usuario identificador del usuario
     * @return boolen que indicará la existencia o no del usuario 
     * @throws ExceptionApi
     */
    public static function comprobarExistenciaUsuario($id_usuario) {
        //comprobar si el usuario ya esta en la bdd o si no lo esta
        $consulta = "SELECT * FROM " . self::NOMBRE_TABLA_USUARIO .
                     " WHERE " . self::ID_USUARIO . "=?";

        // Preparar sentencia
        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
        
        $sentencia->bindParam(1, $id_usuario);

        // Ejecutar sentencia preparada
        if ($sentencia->execute()) {
            return $sentencia->rowCount();
            } else
                throw new ExcepcionApi(self::ERROR_BDD, "Se ha producido un error");

    }

}