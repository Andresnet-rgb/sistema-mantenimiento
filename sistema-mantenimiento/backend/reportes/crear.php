<?php

session_start();

// =========================================================
// VERIFICAR SESIÓN
// =========================================================

if (!isset($_SESSION["id_usuario"])) {
    die("Debe iniciar sesión.");
}

// =========================================================
// VERIFICAR QUE SEA REPORTANTE
// =========================================================

if ($_SESSION["id_rol"] != 2) {
    die("Acceso no permitido.");
}

// =========================================================
// CONECTAR CON LA BASE DE DATOS
// =========================================================

require_once "../config/database.php";

$database = new Database();
$db = $database->conectar();

// =========================================================
// VERIFICAR MÉTODO POST
// =========================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Método no permitido.");
}

// =========================================================
// 1. RECIBIR DATOS DEL FORMULARIO
// =========================================================

$titulo = trim($_POST["titulo"] ?? "");
$descripcion = trim($_POST["descripcion"] ?? "");

$id_categoria = intval($_POST["id_categoria"] ?? 0);
$id_ubicacion = intval($_POST["id_ubicacion"] ?? 0);
$id_prioridad = intval($_POST["id_prioridad"] ?? 0);

$id_usuario = $_SESSION["id_usuario"];

// =========================================================
// 2. VALIDAR DATOS
// =========================================================

if (
    empty($titulo) ||
    empty($descripcion) ||
    $id_categoria <= 0 ||
    $id_ubicacion <= 0 ||
    $id_prioridad <= 0
) {
    die("Todos los campos obligatorios deben estar completos.");
}

// =========================================================
// 3. CONFIGURACIÓN DEL ARCHIVO
// =========================================================

$archivo_subido = false;

$nombre_original = null;
$nombre_guardado = null;
$ruta_archivo = null;
$tipo_archivo = null;

$carpeta_uploads =
    dirname(__DIR__, 2)
    . DIRECTORY_SEPARATOR
    . "uploads";

// =========================================================
// 4. CREAR CARPETA uploads SI NO EXISTE
// =========================================================

if (!is_dir($carpeta_uploads)) {

    if (!mkdir($carpeta_uploads, 0755, true)) {
        die("No se pudo crear la carpeta uploads.");
    }
}

// =========================================================
// 5. PROCESAR EVIDENCIA
// =========================================================

if (
    isset($_FILES["evidencia"]) &&
    $_FILES["evidencia"]["error"] !== UPLOAD_ERR_NO_FILE
) {

    // ---------------------------------------------
    // Verificar errores
    // ---------------------------------------------

    if ($_FILES["evidencia"]["error"] !== UPLOAD_ERR_OK) {
        die("Ocurrió un error al subir la evidencia.");
    }

    // ---------------------------------------------
    // Información del archivo
    // ---------------------------------------------

    $nombre_original = $_FILES["evidencia"]["name"];
    $tipo_archivo = $_FILES["evidencia"]["type"];
    $tamano_archivo = $_FILES["evidencia"]["size"];
    $archivo_temporal = $_FILES["evidencia"]["tmp_name"];

    // ---------------------------------------------
    // Tamaño máximo: 5 MB
    // ---------------------------------------------

    $tamano_maximo = 5 * 1024 * 1024;

    if ($tamano_archivo > $tamano_maximo) {
        die(
            "El archivo es demasiado grande. "
            . "El tamaño máximo permitido es de 5 MB."
        );
    }

    // ---------------------------------------------
    // Obtener extensión
    // ---------------------------------------------

    $extension = strtolower(
        pathinfo(
            $nombre_original,
            PATHINFO_EXTENSION
        )
    );

    // ---------------------------------------------
    // Extensiones permitidas
    // ---------------------------------------------

    $extensiones_permitidas = [
        "jpg",
        "jpeg",
        "png",
        "gif",
        "webp",
        "pdf"
    ];

    if (
        !in_array(
            $extension,
            $extensiones_permitidas
        )
    ) {
        die("Tipo de archivo no permitido.");
    }

    // ---------------------------------------------
    // MIME types permitidos
    // ---------------------------------------------

    $tipos_permitidos = [
        "image/jpeg",
        "image/png",
        "image/gif",
        "image/webp",
        "application/pdf"
    ];

    if (
        !in_array(
            $tipo_archivo,
            $tipos_permitidos
        )
    ) {
        die("El tipo de archivo no está permitido.");
    }

    // ---------------------------------------------
    // Generar nombre único
    // ---------------------------------------------

    $nombre_guardado =
        uniqid("evidencia_", true)
        . "."
        . $extension;

    // ---------------------------------------------
    // Ruta física
    // ---------------------------------------------

    $ruta_fisica =
        $carpeta_uploads
        . DIRECTORY_SEPARATOR
        . $nombre_guardado;

    // ---------------------------------------------
    // Ruta guardada en BD
    // ---------------------------------------------

    $ruta_archivo =
        "uploads/"
        . $nombre_guardado;

    // ---------------------------------------------
    // Mover archivo
    // ---------------------------------------------

    if (
        !move_uploaded_file(
            $archivo_temporal,
            $ruta_fisica
        )
    ) {
        die("No se pudo guardar la evidencia.");
    }

    $archivo_subido = true;
}

// =========================================================
// 6. INICIAR TRANSACCIÓN
// =========================================================

try {

    $db->beginTransaction();

    // =====================================================
    // 7. CREAR REPORTE
    // =====================================================

    $sql = "
        INSERT INTO reportes
        (
            titulo,
            descripcion,
            id_usuario,
            id_categoria,
            id_ubicacion,
            id_prioridad,
            id_estado
        )
        VALUES
        (
            :titulo,
            :descripcion,
            :id_usuario,
            :id_categoria,
            :id_ubicacion,
            :id_prioridad,
            1
        )
    ";

    $stmt = $db->prepare($sql);

    $stmt->execute([
        ":titulo" => $titulo,
        ":descripcion" => $descripcion,
        ":id_usuario" => $id_usuario,
        ":id_categoria" => $id_categoria,
        ":id_ubicacion" => $id_ubicacion,
        ":id_prioridad" => $id_prioridad
    ]);

    // =====================================================
    // 8. OBTENER ID DEL REPORTE
    // =====================================================

    $id_reporte = $db->lastInsertId();

    // =====================================================
    // 9. REGISTRAR HISTORIAL INICIAL
    // =====================================================

    $sql = "
        INSERT INTO seguimientos
        (
            id_reporte,
            id_usuario,
            id_estado,
            comentario
        )
        VALUES
        (
            :id_reporte,
            :id_usuario,
            1,
            'Se registró el reporte de mantenimiento.'
        )
    ";

    $stmt = $db->prepare($sql);

    $stmt->execute([
        ":id_reporte" => $id_reporte,
        ":id_usuario" => $id_usuario
    ]);

    // =====================================================
    // 10. GUARDAR EVIDENCIA EN LA BASE DE DATOS
    // =====================================================

    if ($archivo_subido) {

        $sql = "
            INSERT INTO evidencias
            (
                id_reporte,
                id_usuario,
                nombre_archivo,
                ruta_archivo,
                tipo
            )
            VALUES
            (
                :id_reporte,
                :id_usuario,
                :nombre_archivo,
                :ruta_archivo,
                :tipo
            )
        ";

        $stmt = $db->prepare($sql);

        $stmt->execute([
            ":id_reporte" => $id_reporte,
            ":id_usuario" => $id_usuario,
            ":nombre_archivo" => $nombre_original,
            ":ruta_archivo" => $ruta_archivo,
            ":tipo" => $tipo_archivo
        ]);
    }

    // =====================================================
    // 11. CONFIRMAR TRANSACCIÓN
    // =====================================================

    $db->commit();

    // =====================================================
    // 12. PREPARAR MENSAJES
    // =====================================================

    $nombre_usuario = htmlspecialchars(
        $_SESSION["nombre"]
        . " "
        . $_SESSION["apellido"]
    );

    if ($archivo_subido) {

        $mensaje_evidencia =
            "📎 La evidencia fue adjuntada correctamente.";

        $clase_evidencia = "exito";

    } else {

        $mensaje_evidencia =
            "ℹ️ El reporte fue creado sin evidencia.";

        $clase_evidencia = "informacion";
    }

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Reporte registrado
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background-color: #f3f6fa;

            color: #1f2937;
        }

        /* =========================================
           MENÚ LATERAL
           ========================================= */

        .menu {
            position: fixed;

            top: 0;
            left: 0;

            width: 260px;
            height: 100vh;

            padding: 28px 18px;

            background:
                linear-gradient(
                    180deg,
                    #243b88 0%,
                    #2563eb 100%
                );

            color: white;
        }

        .menu h2 {
            margin:
                0
                0
                25px
                18px;

            font-size: 23px;

            font-weight: 700;
        }

        .menu ul {
            list-style: none;

            padding: 0;
            margin: 0;
        }

        .menu li {
            margin-bottom: 8px;
        }

        .menu a {
            display: block;

            padding: 14px 18px;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-size: 15px;

            transition:
                background-color 0.2s ease;
        }

        .menu a:hover {
            background-color:
                rgba(255,255,255,0.12);
        }

        .menu a.activo {
            background-color:
                rgba(255,255,255,0.20);

            font-weight: 600;
        }

        .menu a.cerrar {
            margin-top: 20px;

            background-color: #dc2626;
        }

        .menu a.cerrar:hover {
            background-color: #b91c1c;
        }

        /* =========================================
           CONTENIDO
           ========================================= */

        .contenido {
            margin-left: 260px;

            width:
                calc(100% - 260px);

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 40px;
        }

        /* =========================================
           TARJETA
           ========================================= */

        .tarjeta-confirmacion {
            width: 100%;

            max-width: 700px;

            background-color: white;

            border:
                1px solid
                #e5e7eb;

            border-radius: 16px;

            padding: 45px 50px;

            text-align: center;

            box-shadow:
                0 8px 25px
                rgba(15,23,42,0.08);
        }

        .icono-exito {
            width: 70px;
            height: 70px;

            margin:
                0
                auto
                20px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background-color: #dcfce7;

            color: #16a34a;

            font-size: 36px;
        }

        .tarjeta-confirmacion h1 {
            margin:
                0
                0
                12px;

            font-size: 28px;

            color: #1f2937;
        }

        .subtitulo {
            margin:
                0
                0
                25px;

            color: #6b7280;

            font-size: 16px;

            line-height: 1.6;
        }

        .reporte-creado {
            padding: 18px;

            margin-bottom: 18px;

            background-color: #eff6ff;

            border:
                1px solid
                #bfdbfe;

            border-radius: 10px;

            color: #1e40af;

            font-size: 17px;
        }

        .reporte-creado strong {
            font-size: 21px;
        }

        .mensaje-evidencia {
            padding: 14px 18px;

            margin-bottom: 28px;

            border-radius: 9px;

            font-size: 15px;
        }

        .mensaje-evidencia.exito {
            background-color: #f0fdf4;

            border:
                1px solid
                #bbf7d0;

            color: #166534;
        }

        .mensaje-evidencia.informacion {
            background-color: #eff6ff;

            border:
                1px solid
                #bfdbfe;

            color: #1e40af;
        }

        .usuario {
            margin-bottom: 25px;

            color: #6b7280;

            font-size: 14px;
        }

        /* =========================================
           BOTONES
           ========================================= */

        .botones {
            display: flex;

            justify-content: center;

            gap: 12px;

            flex-wrap: wrap;
        }

        .boton {
            display: inline-block;

            padding:
                12px
                20px;

            border-radius: 8px;

            text-decoration: none;

            font-size: 15px;

            font-weight: 600;

            transition:
                background-color 0.2s ease;
        }

        .boton-principal {
            background-color: #2563eb;

            color: white;
        }

        .boton-principal:hover {
            background-color: #1d4ed8;
        }

        .boton-secundario {
            background-color: #e5e7eb;

            color: #374151;
        }

        .boton-secundario:hover {
            background-color: #d1d5db;
        }

        /* =========================================
           RESPONSIVE
           ========================================= */

        @media (max-width: 768px) {

            .menu {
                position: relative;

                width: 100%;
                height: auto;

                padding: 20px;
            }

            .menu h2 {
                margin-left: 5px;
            }

            .menu ul {
                display: flex;

                flex-wrap: wrap;

                gap: 6px;
            }

            .menu li {
                margin-bottom: 0;
            }

            .menu a {
                padding:
                    10px
                    12px;
            }

            .contenido {
                margin-left: 0;

                width: 100%;

                min-height: auto;

                padding:
                    25px
                    15px;
            }

            .tarjeta-confirmacion {
                padding:
                    35px
                    22px;
            }

            .tarjeta-confirmacion h1 {
                font-size: 24px;
            }
        }

    </style>

</head>

<body>

    <div class="contenedor">

        <!-- ======================================
             MENÚ LATERAL
             ====================================== -->

        <aside class="menu">

            <h2>
                🛠️ Mantenimiento
            </h2>

            <ul>

                <li>
                    <a
                        href="../../frontend/dashboard/reportante.php"
                    >
                        📊 Dashboard
                    </a>
                </li>

                <li>
                    <a
                        href="../../frontend/dashboard/crear_reporte.php"
                        class="activo"
                    >
                        📝 Crear reporte
                    </a>
                </li>

                <li>
                    <a
                        href="../../frontend/dashboard/mis_reportes.php"
                    >
                        📋 Mis reportes
                    </a>
                </li>

                <li>
                    <a
                        href="../../frontend/dashboard/historial.php"
                    >
                        📜 Historial
                    </a>
                </li>

                <li>
                    <a
                        href="../auth/logout.php"
                        class="cerrar"
                    >
                        🚪 Cerrar sesión
                    </a>
                </li>

            </ul>

        </aside>

        <!-- ======================================
             CONTENIDO PRINCIPAL
             ====================================== -->

        <main class="contenido">

            <div class="tarjeta-confirmacion">

                <div class="icono-exito">
                    ✓
                </div>

                <h1>
                    Reporte registrado correctamente
                </h1>

                <p class="subtitulo">
                    Tu solicitud de mantenimiento fue
                    registrada correctamente en el sistema.
                </p>

                <div class="reporte-creado">

                    El reporte

                    <strong>
                        #<?php
                        echo htmlspecialchars($id_reporte);
                        ?>
                    </strong>

                    fue creado correctamente.

                </div>

                <div
                    class="mensaje-evidencia
                    <?php
                    echo $clase_evidencia;
                    ?>"
                >

                    <?php
                    echo htmlspecialchars(
                        $mensaje_evidencia
                    );
                    ?>

                </div>

                <p class="usuario">

                    Reportante:

                    <strong>
                        <?php
                        echo $nombre_usuario;
                        ?>
                    </strong>

                </p>

                <div class="botones">

                    <a
                        href="../../frontend/dashboard/mis_reportes.php"
                        class="boton boton-principal"
                    >
                        📋 Ver mis reportes
                    </a>

                    <a
                        href="../../frontend/dashboard/crear_reporte.php"
                        class="boton boton-secundario"
                    >
                        📝 Crear otro reporte
                    </a>

                </div>

            </div>

        </main>

    </div>

</body>

</html>

<?php

} catch (PDOException $e) {

    // ---------------------------------------------
    // Deshacer cambios si ocurrió un error
    // ---------------------------------------------

    if ($db->inTransaction()) {
        $db->rollBack();
    }

    // ---------------------------------------------
    // Eliminar archivo si ya había sido guardado
    // ---------------------------------------------

    if (
        $archivo_subido &&
        isset($ruta_fisica) &&
        file_exists($ruta_fisica)
    ) {
        unlink($ruta_fisica);
    }

    die(
        "Error al registrar el reporte: "
        . $e->getMessage()
    );
}

?>