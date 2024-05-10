<?php

class Juego
{
    use MagicProperties;

    /** @var int El ID único del juego. */
    private $id;
    /** @var string El nombre del juego. */
    private $nombreJuego;
    /** @var int El año de salida/publicacion del juego. */
    private $anioDeSalida;
    /** @var string El nombre del desarrollador del juego. */
    private $desarrollador;
    /** @var string El género del juego. */
    private $genero;
    /** @var int La nota o calificación del juego. */
    private $nota;
    private $nResenias;
    /** @var string Una breve descripción del juego. */
    private $descripcion;

    /**
     * Constructor de la clase Juego.
     *
     * Inicializa una nueva instancia de la clase Juego con los datos proporcionados.
     *
     * @param int $id El ID del juego.
     * @param string $nombreJuego El nombre del juego.
     * @param int $anioDeSalida El año de salida del juego.
     * @param string $desarrollador El desarrollador del juego.
     * @param string $genero El género del juego.
     * @param int $nota La nota o calificación del juego.
     * @param string $descripcion Una breve descripción del juego.
     */
    private function __construct($id, $nombreJuego, $anioDeSalida, $desarrollador, $genero, $nota, $descripcion, $nResenias)
    {
        $this->id = $id;
        $this->nombreJuego = $nombreJuego;
        $this->anioDeSalida = $anioDeSalida;
        $this->desarrollador = $desarrollador;
        $this->genero = $genero;
        $this->nota = $nota;
        $this->nResenias = $nResenias;
        $this->descripcion = $descripcion;
    }

    public function getId()
    {
        return $this->id;
    }
    public function getNombreJuego()
    {
        return $this->nombreJuego;
    }
    public function getAnioDeSalida()
    {
        return $this->anioDeSalida;
    }
    public function getDesarrollador()
    {
        return $this->desarrollador;
    }
    public function getGenero()
    {
        return $this->genero;
    }
    public function getNota()
    {
        return $this->nota;
    }
    public function getnResenias() 
    {
         return $this->nResenias; 
    }
    public function getDescripcion()
    {
        return $this->descripcion;
    }

    /**
     * Crea una nueva instancia de Juego y la guarda en la base de datos.
     * Si el juego es nuevo (id es null), lo inserta en las tablas de sugerencias (si no existe previamente)
     * y videojuegos. Si el juego ya existe (id no es null), lo actualiza en la tabla de videojuegos.
     * 
     * @param string $nombreJuego El nombre del juego.
     * @param int $anioDeSalida El año de salida del juego.
     * @param string $desarrollador El desarrollador del juego.
     * @param string $genero El género del juego.
     * @param int $nota La nota o calificación del juego.
     * @param string $descripcion Una breve descripción del juego.
     * @return bool True si el juego se guardó con éxito, false en caso contrario.
     */
    public static function crea($nombreJuego, $anioDeSalida, $desarrollador, $genero, $nota, $nResenias, $descripcion)
    {
        $juego = new Juego(null, $nombreJuego, $anioDeSalida, $desarrollador, $genero, $nota, $nResenias, $descripcion);
        return $juego->guarda();
    }

    //Funciones de gestion de la BD

    /**
     * Guarda el juego en la base de datos. Determina si debe insertar un nuevo juego o actualizar uno existente.
     * 
     * @return mixed juego->id si se creó con exito, True si el juego se actualizó con éxito, false si hubo un error.
     */
    public function guarda()
    {
        if ($this->id !== null) {
            return self::actualiza($this);
        }
        return self::inserta($this);
    }

    public function borrate()
    {
        if ($this->id !== null) {
            return self::borraDeVideojuegos($this->id);
        }
        return false;
    }

    /**
     * Inserta un nuevo juego en la base de datos. Verifica primero si el juego ya existe
     * y lo inserta si no existe. Luego inserta el juego en la tabla de videojuegos.
     * 
     * @param Juego $juego La instancia del juego a insertar.
     * @return mixed Retorna el id si el juego se insertó con éxito, false en caso contrario.
     */
    private static function inserta(Juego $juego)
    {
        $conn = BD::getInstance()->getConexionBd();
        if (!$conn) {
            return false;
        }
        //Todo: evitar insertar juegos con el mismo nombre
        // Insertar en Videojuegos
        $query = sprintf(
            "INSERT INTO videojuegos (Juego, `Año de salida`, Desarrollador, Genero, Nota, Descripcion) VALUES ('%s', '%s', '%s', '%s', '%f', '%s')",
            $conn->real_escape_string($juego->getNombreJuego()),
            $conn->real_escape_string($juego->getAnioDeSalida()),
            $conn->real_escape_string($juego->getDesarrollador()),
            $conn->real_escape_string($juego->getGenero()),
            $juego->getNota(),
            $juego->getnResenias(),
            $conn->real_escape_string($juego->getDescripcion())
        );

        if ($conn->query($query)) {
            $juego->id = $conn->insert_id; // Actualizar juego con el id generado automáticamente por la última inserción
            return $juego->id;
        } else {
            error_log("Error BD ({$conn->errno}): {$conn->error}");
            return false;
        }
    }

    /**
     * Sugerir un nuevo juego insertándolo en la tabla de sugerenciasjuegos, siempre que no exista previamente.
     * 
     * @param string $nombreJuego El nombre del juego.
     * @param int $anioDeSalida El año de salida del juego.
     * @param string $desarrollador El desarrollador del juego.
     * @param string $genero El género del juego.
     * @param string $descripcion La descripción del juego.
     * @return bool True si la sugerencia se registró con éxito, false en caso contrario.
     */
    public static function sugiere($nombreJuego, $anioDeSalida, $desarrollador, $genero, $descripcion)
    {
        $conn = BD::getInstance()->getConexionBd();
        if (!$conn) {
            return false;
        }

        // Verificar si ya existe una sugerencia con el mismo nombre del juego
        $queryVerificacion = sprintf(
            "SELECT COUNT(*) AS cantidad FROM sugerenciasjuegos WHERE Juego = '%s'",
            $conn->real_escape_string($nombreJuego)
        );
        $resultadoVerificacion = $conn->query($queryVerificacion);
        $fila = $resultadoVerificacion->fetch_assoc();
        $resultadoVerificacion->free();

        if ($fila['cantidad'] > 0) {
            // Ya existe una sugerencia con este nombre, no insertar duplicados
            return false;
        }

        $query = sprintf(
            "INSERT INTO sugerenciasjuegos (Juego, `Año de salida`, Desarrollador, Genero, Descripcion) VALUES ('%s', '%s', '%s', '%s', '%s')",
            $conn->real_escape_string($nombreJuego),
            $conn->real_escape_string($anioDeSalida),
            $conn->real_escape_string($desarrollador),
            $conn->real_escape_string($genero),
            $conn->real_escape_string($descripcion)
        );

        if ($conn->query($query)) {
            return $conn->insert_id; //Devolver id
        } else {
            error_log("Error BD ({$conn->errno}): {$conn->error}");
            return false;
        }
    }

    /**
     * Actualiza los datos de un juego existente en la base de datos en la tabla videojuegos.
     * 
     * @param Juego $juego La instancia del juego a actualizar.
     * @return bool True si el juego se actualizó con éxito, false en caso contrario.
     */
    private static function actualiza(Juego $juego)
    {
        $conn = BD::getInstance()->getConexionBd();
        if (!$conn) {
            return false;
        }

        $query = sprintf(
            "UPDATE videojuegos SET Juego='%s', `Año de salida`='%s', Desarrollador='%s', Genero='%s', Nota=%f, Descripcion='%s' WHERE ID=%d",
            $conn->real_escape_string($juego->getNombreJuego()),
            $conn->real_escape_string($juego->getAnioDeSalida()),
            $conn->real_escape_string($juego->getDesarrollador()),
            $conn->real_escape_string($juego->getGenero()),
            $juego->getNota(),
            $juego->getnResenias(),
            $conn->real_escape_string($juego->getDescripcion()),
            $juego->getId()
        );

        if ($conn->query($query)) {
            return true;
        } else {
            error_log("Error al actualizar el juego en la BD ({$conn->errno}): {$conn->error}");
            return false;
        }
    }

    public static function nuevaResenia($idJuego, $nuevaNota)
{
    $conn = BD::getInstance()->getConexionBd();
    if (!$conn) {
        return false;
    }

    $juegoActual = self::obtenerJuego($idJuego);
    $nReseniasAntes = $juegoActual->getnResenias();
    $notaActual = $juegoActual->getNota();

    // Calcular la nueva nota media
    $nuevaNotaMedia = ($notaActual * $nReseniasAntes + $nuevaNota) / ($nReseniasAntes + 1);

    // Actualizar la base de datos con los nuevos valores
    $query = sprintf(
        "UPDATE videojuegos SET Nota = %f, nResenias = nResenias + 1 WHERE ID = %d",
        $nuevaNotaMedia,
        $idJuego
    );

    if ($conn->query($query)) {
        return true;
    } else {
        error_log("Error al actualizar la nueva resenia del juego en la BD ({$conn->errno}): {$conn->error}");
        return false;
    }
}


    /**
     * Borra un juego específico de la base de datos en la tabla videojuegos utilizando su ID único.
     * 
     * @param int $id El ID del juego a borrar.
     * @return bool True si el juego se borró con éxito, false en caso contrario.
     */

    public static function borraDeVideojuegos($id)
    {
        $conn = BD::getInstance()->getConexionBd();
        if (!$conn) {
            return false;
        }

        $query = sprintf("DELETE FROM videojuegos WHERE ID = %d", $id);

        if ($conn->query($query)) {
            return true;
        } else {
            error_log("Error al borrar el juego de videojuegos ({$conn->errno}): {$conn->error}");
            return false;
        }
    }

    public static function borraDeSugerenciasJuegos($id)
    {
        $conn = BD::getInstance()->getConexionBd();
        if (!$conn) {
            return false;
        }

        $query = sprintf("DELETE FROM sugerenciasjuegos WHERE ID = %d", $id);

        if ($conn->query($query)) {
            return true;
        } else {
            error_log("Error al borrar la sugerencia de juego ({$conn->errno}): {$conn->error}");
            return false;
        }
    }


    public static function obtenerIdJuego($nombre)
    {
        $conn = BD::getInstance()->getConexionBd();
        // Se usa la función mysqli_real_escape_string para evitar inyección SQL
        $nombre = $conn->real_escape_string($nombre);
        $query = sprintf("SELECT ID FROM videojuegos WHERE Juego = '%s'", $nombre);
        $result = $conn->query($query);

        if ($result && $result->num_rows > 0) {
            $fila = $result->fetch_assoc();
            $idJuego = $fila['ID'];
            $result->free();
            return $idJuego; // Devuelve solo la ID del juego
        } else {
            error_log("Error BD ({$conn->errno}): {$conn->error}");
            return null;
        }
    }


    public static function obtenerJuego($id)
    {
        $conn = BD::getInstance()->getConexionBd();
        $query = sprintf("SELECT * FROM videojuegos WHERE ID = %d", $id);
        $result = $conn->query($query);

        if ($result && $result->num_rows > 0) {
            $fila = $result->fetch_assoc();
            $juego = new Juego(
                $fila['ID'],
                $fila['Juego'],
                $fila['Año de salida'],
                $fila['Desarrollador'],
                $fila['Genero'],
                $fila['Nota'],
                $fila['nResenias'],
                $fila['Descripcion']
            );
            $result->free();
            return $juego;
        } else {
            error_log("Error BD ({$conn->errno}): {$conn->error}");
            return null;
        }
    }

    public static function obtenerSugerencia($id)
    {
        $conn = BD::getInstance()->getConexionBd();
        $query = sprintf("SELECT * FROM sugerenciasjuegos WHERE ID = %d", $id);
        $result = $conn->query($query);

        if ($result && $result->num_rows > 0) {
            $nota = 0;
            $fila = $result->fetch_assoc();
            $juego = new Juego(
                $fila['ID'],
                $fila['Juego'],
                $fila['Año de salida'],
                $fila['Desarrollador'],
                $fila['Genero'],
                $nota,
                $fila['nResenias'], //TODO: Investigar si esto es correcto
                $fila['Descripcion']
            );
            $result->free();
            return $juego;
        } else {
            error_log("Error BD ({$conn->errno}): {$conn->error}");
            return null;
        }
    }

    /**
     * Obtiene una lista de juegos de la base de datos ordenados por su nota de mayor a menor.
     *
     * @return Juego[] Array de objetos Juego ordenados por nota.
     */
    public static function obtenerTopJuegos()
    {
        $conn = BD::getInstance()->getConexionBd();
        $query = "SELECT * FROM videojuegos ORDER BY Nota DESC";
        $result = $conn->query($query);

        $juegos = [];
        if ($result) {
            while ($fila = $result->fetch_assoc()) {
                $juegos[] = new Juego(
                    $fila['ID'],
                    $fila['Juego'],
                    $fila['Año de salida'],
                    $fila['Desarrollador'],
                    $fila['Genero'],
                    $fila['Nota'],
                    $fila['nResenias'],
                    $fila['Descripcion']
                );
            }
            $result->free();
        } else {
            error_log("Error BD ({$conn->errno}): {$conn->error}");
        }

        return $juegos;
    }

    public static function obtenerNombresJuegos()
    {
        $conn = BD::getInstance()->getConexionBd();
        $query = "SELECT Juego FROM videojuegos ORDER BY Nota DESC";
        $result = $conn->query($query);

        $juegos = [];
        if ($result) {
            while ($fila = $result->fetch_assoc()) {
                $juegos[] = $fila['Juego'];
            }
            $result->free();
        } else {
            error_log("Error BD ({$conn->errno}): {$conn->error}");
        }

        return $juegos;
    }

    /**
     * Obtiene los juegos ordenados por año de salida de menor a mayor (ascendente).
     *
     * @return Juego[] Array de objetos Juego ordenados por año de salida ascendente.
     */
    public static function obtenerJuegosPorAnioAscendente()
    {
        $conn = BD::getInstance()->getConexionBd();
        $query = "SELECT * FROM videojuegos ORDER BY `Año de salida` ASC";
        $result = $conn->query($query);

        $juegos = [];
        if ($result) {
            while ($fila = $result->fetch_assoc()) {
                $juegos[] = new Juego(
                    $fila['ID'],
                    $fila['Juego'],
                    $fila['Año de salida'],
                    $fila['Desarrollador'],
                    $fila['Genero'],
                    $fila['Nota'],
                    $fila['nResenias'],
                    $fila['Descripcion']
                );
            }
            $result->free();
        } else {
            error_log("Error BD ({$conn->errno}): {$conn->error}");
        }

        return $juegos;
    }

    /**
     * Obtiene los juegos ordenados por año de salida de mayor a menor (descendente).
     *
     * @return Juego[] Array de objetos Juego ordenados por año de salida descendente.
     */
    public static function obtenerJuegosPorAnioDescendente()
    {
        $conn = BD::getInstance()->getConexionBd();
        $query = "SELECT * FROM videojuegos ORDER BY `Año de salida` DESC";
        $result = $conn->query($query);

        $juegos = [];
        if ($result) {
            while ($fila = $result->fetch_assoc()) {
                $juegos[] = new Juego(
                    $fila['ID'],
                    $fila['Juego'],
                    $fila['Año de salida'],
                    $fila['Desarrollador'],
                    $fila['Genero'],
                    $fila['Nota'],
                    $fila['nResenias'],
                    $fila['Descripcion']
                );
            }
            $result->free();
        } else {
            error_log("Error BD ({$conn->errno}): {$conn->error}");
        }

        return $juegos;
    }
    /**
     * Obtiene una lista de juegos de la base de datos ordenados por su nota de menor a mayor.
     *
     * @return Juego[] Array de objetos Juego ordenados por nota de manera ascendente.
     */
    public static function obtenerJuegosPorNotaAscendente()
    {
        $conn = BD::getInstance()->getConexionBd();
        if (!$conn) {
            error_log("Error al conectar a la base de datos");
            return [];
        }

        $query = "SELECT * FROM videojuegos ORDER BY Nota ASC";
        $result = $conn->query($query);

        $juegos = [];
        if ($result) {
            while ($fila = $result->fetch_assoc()) {
                $juegos[] = new Juego(
                    $fila['ID'],
                    $fila['Juego'],
                    $fila['Año de salida'],
                    $fila['Desarrollador'],
                    $fila['Genero'],
                    $fila['Nota'],
                    $fila['nResenias'],
                    $fila['Descripcion']
                );
            }
            $result->free();
        } else {
            error_log("Error al obtener juegos por nota ascendente: ({$conn->errno}): {$conn->error}");
        }

        return $juegos;
    }

    public static function obtenerSugerenciasJuegos()
    {
        $conn = BD::getInstance()->getConexionBd();
        if (!$conn) {
            error_log("Error al conectar a la base de datos");
            return [];
        }

        $query = "SELECT * FROM sugerenciasjuegos";
        $result = $conn->query($query);

        $sugerenciasJuegos = [];
        if ($result) {
            while ($fila = $result->fetch_assoc()) {
                $sugerenciasJuegos[] = new Juego(
                    $fila['ID'],
                    $fila['Juego'],
                    $fila['Año de salida'],
                    $fila['Desarrollador'],
                    $fila['Genero'],
                    null,
                    null,
                    $fila['Descripcion']
                );
            }
            $result->free();
        } else {
            error_log("Error al obtener sugerencias de juegos: ({$conn->errno}): {$conn->error}");
        }

        return $sugerenciasJuegos;
    }

}