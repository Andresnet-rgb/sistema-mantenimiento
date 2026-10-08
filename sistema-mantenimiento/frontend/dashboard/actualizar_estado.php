<?php

session_start();

// ==========================================
// 1. VERIFICAR SESIÓN
// ==========================================

if (!isset($_SESSION["id_usuario"])) {
    die("Debe iniciar sesión.");
}

// ==========================================
// 2. VERIFICAR QUE SEA TÉCNICO
// ==========================================

if ($_SESSION["id_rol"] != 3) {
    die("Acceso no permitido.");
}

// ==========================================
// 3. VERIFICAR DATOS RECIBIDOS
// ==========================================

if (!isset($_GET["id"]) || !isset($_GET["estado"])) {
    die("Datos incompletos.");
}

$id_reporte = $_GET["id"];
$id_estado = $_GET["estado"];

// ==========================================
// 4. CONECTAR CON LA BASE DE DATOS
// ==========================================

require_once "../../backend/config/database.php";

$database = new Database();
$db = $database->conectar();

// ==========================================
// 5. VERIFICAR QUE EL REPORTE ESTÉ ASIGNADO
//    AL TÉCNICO ACTUAL
// ==========================================

$sql = "SELECT id_reporte
        FROM asignaciones
        WHERE id_reporte = :id_reporte
        AND id_tecnico = :id_tecnico
        AND activa = 1
        LIMIT 1";

$stmt = $db->prepare($sql);

$stmt->bindParam(
    ":id_reporte",
    $id_reporte,
    PDO::PARAM_INT
);

$stmt->bindParam(
    ":id_tecnico",
    $_SESSION["id_usuario"],
    PDO::PARAM_INT
);

$stmt->execute();

$asignacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$asignacion) {
    die("Este reporte no está asignado a este técnico.");
}

// ==========================================
// 6. ACTUALIZAR ESTADO DEL REPORTE
// ==========================================

$sql = "UPDATE reportes
        SET id_estado = :id_estado
        WHERE id_reporte = :id_reporte";

$stmt = $db->prepare($sql);

$stmt->bindParam(
    ":id_estado",
    $id_estado,
    PDO::PARAM_INT
);

$stmt->bindParam(
    ":id_reporte",
    $id_reporte,
    PDO::PARAM_INT
);

$stmt->execute();

// ==========================================
// 7. OBTENER NOMBRE DEL NUEVO ESTADO
// ==========================================

$sql = "SELECT nombre
        FROM estados
        WHERE id_estado = :id_estado";

$stmt = $db->prepare($sql);

$stmt->bindParam(
    ":id_estado",
    $id_estado,
    PDO::PARAM_INT
);

$stmt->execute();

$estado = $stmt->fetch(PDO::FETCH_ASSOC);

// ==========================================
// 8. REGISTRAR EL CAMBIO EN SEGUIMIENTOS
// ==========================================

$comentario = "El técnico actualizó el estado del reporte a: "
              . $estado["nombre"];

$sql = "INSERT INTO seguimientos
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
            :id_estado,
            :comentario
        )";

$stmt = $db->prepare($sql);

$stmt->bindParam(
    ":id_reporte",
    $id_reporte,
    PDO::PARAM_INT
);

$stmt->bindParam(
    ":id_usuario",
    $_SESSION["id_usuario"],
    PDO::PARAM_INT
);

$stmt->bindParam(
    ":id_estado",
    $id_estado,
    PDO::PARAM_INT
);

$stmt->bindParam(
    ":comentario",
    $comentario
);

$stmt->execute();

// ==========================================
// 9. MOSTRAR CONFIRMACIÓN MODERNIZADA
// ==========================================

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Estado actualizado</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f3f6fa;
            min-height: 100vh;
        }

        /* ==========================================
           SIDEBAR
           ========================================== */

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;

            width: 260px;
            height: 100vh;

            background: linear-gradient(
                180deg,
                #243b88 0%,
                #2563eb 100%
            );

            color: white;

            padding: 25px 18px;

            display: flex;
            flex-direction: column;

            z-index: 1000;
        }

        .logo {
            font-size: 21px;
            font-weight: bold;

            text-align: center;

            margin-bottom: 35px;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .menu a {
            text-decoration: none;
            color: white;

            padding: 13px 15px;

            border-radius: 9px;

            font-size: 15px;

            transition: 0.2s;
        }

        .menu a:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .menu a.activo {
            background: rgba(255, 255, 255, 0.18);
            font-weight: 600;
        }

        .menu .logout {
            background: #dc2626;

            margin-top: 20px;

            text-align: center;

            font-weight: 600;
        }

        .menu .logout:hover {
            background: #b91c1c;
        }

        /* ==========================================
           CONTENIDO
           ========================================== */

        .contenido {
            margin-left: 260px;

            width: calc(100% - 260px);

            min-height: 100vh;

            padding: 40px;
        }

        /* ==========================================
           TARJETA DE CONFIRMACIÓN
           ========================================== */

        .contenedor-confirmacion {
            max-width: 850px;

            margin: 60px auto;
        }

        .tarjeta {
            background: white;

            border-radius: 16px;

            padding: 45px;

            border: 1px solid #e5e7eb;

            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.06);

            text-align: center;
        }

        .icono {
            width: 75px;
            height: 75px;

            margin: 0 auto 20px;

            border-radius: 50%;

            background: #dcfce7;

            color: #16a34a;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 38px;
            font-weight: bold;
        }

        .tarjeta h1 {
            color: #1f2937;

            font-size: 28px;

            margin-bottom: 12px;
        }

        .descripcion {
            color: #6b7280;

            font-size: 16px;

            margin-bottom: 30px;
        }

        /* ==========================================
           INFORMACIÓN DEL REPORTE
           ========================================== */

        .informacion {
            background: #f8fafc;

            border: 1px solid #e5e7eb;

            border-radius: 12px;

            padding: 25px;

            margin-bottom: 30px;

            text-align: left;
        }

        .fila {
            display: flex;

            justify-content: space-between;
            align-items: center;

            padding: 12px 0;

            border-bottom: 1px solid #e5e7eb;
        }

        .fila:last-child {
            border-bottom: none;
        }

        .etiqueta {
            color: #6b7280;

            font-size: 14px;

            font-weight: 600;
        }

        .valor {
            color: #1f2937;

            font-size: 15px;

            font-weight: 600;
        }

        /* ==========================================
           BADGE ESTADO
           ========================================== */

        .estado {
            display: inline-block;

            padding: 7px 14px;

            border-radius: 20px;

            background: #dbeafe;

            color: #1d4ed8;

            font-size: 14px;

            font-weight: 600;
        }

        /* ==========================================
           BOTONES
           ========================================== */

        .acciones {
            display: flex;

            justify-content: center;

            gap: 12px;

            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;

            text-decoration: none;

            padding: 12px 20px;

            border-radius: 9px;

            font-size: 14px;

            font-weight: 600;

            transition: 0.2s;
        }

        .btn-principal {
            background: #2563eb;

            color: white;
        }

        .btn-principal:hover {
            background: #1d4ed8;
        }

        .btn-secundario {
            background: #e5e7eb;

            color: #374151;
        }

        .btn-secundario:hover {
            background: #d1d5db;
        }

        /* ==========================================
           RESPONSIVE
           ========================================== */

        @media (max-width: 768px) {

            .sidebar {
                position: relative;

                width: 100%;
                height: auto;

                padding: 20px;
            }

            .logo {
                margin-bottom: 20px;
            }

            .menu {
                gap: 5px;
            }

            .contenido {
                margin-left: 0;

                width: 100%;

                padding: 20px;
            }

            .contenedor-confirmacion {
                margin: 20px auto;
            }

            .tarjeta {
                padding: 30px 20px;
            }

            .fila {
                flex-direction: column;

                align-items: flex-start;

                gap: 5px;
            }

            .acciones {
                flex-direction: column;
            }

            .btn {
                width: 100%;

                text-align: center;
            }

        }

    </style>

</head>

<body>

    <!-- ==========================================
         BARRA LATERAL
         ========================================== -->

    <aside class="sidebar">

        <div class="logo">
            🛠️ Mantenimiento
        </div>

        <nav class="menu">

            <a href="../../frontend/dashboard/tecnico.php">
                📊 Dashboard
            </a>

            <a href="../../frontend/dashboard/reportes_asignados.php"
               class="activo">
                📋 Mis reportes
            </a>

            <a href="../../backend/auth/logout.php"
               class="logout">
                🚪 Cerrar sesión
            </a>

        </nav>

    </aside>


    <!-- ==========================================
         CONTENIDO PRINCIPAL
         ========================================== -->

    <main class="contenido">

        <div class="contenedor-confirmacion">

            <div class="tarjeta">

                <div class="icono">
                    ✓
                </div>

                <h1>
                    Estado actualizado correctamente
                </h1>

                <p class="descripcion">
                    El estado del reporte ha sido actualizado y
                    el cambio quedó registrado en el historial.
                </p>


                <!-- ==========================================
                     INFORMACIÓN
                     ========================================== -->

                <div class="informacion">

                    <div class="fila">

                        <span class="etiqueta">
                            Reporte
                        </span>

                        <span class="valor">
                            #<?php echo htmlspecialchars($id_reporte); ?>
                        </span>

                    </div>


                    <div class="fila">

                        <span class="etiqueta">
                            Nuevo estado
                        </span>

                        <span class="estado">
                            <?php
                            echo htmlspecialchars($estado["nombre"]);
                            ?>
                        </span>

                    </div>

                </div>


                <!-- ==========================================
                     BOTONES
                     ========================================== -->

                <div class="acciones">

                    <a
                        href="../../frontend/dashboard/detalle_reporte.php?id=<?php echo htmlspecialchars($id_reporte); ?>"
                        class="btn btn-principal"
                    >
                        📋 Ver reporte
                    </a>

                    <a
                        href="../../frontend/dashboard/reportes_asignados.php"
                        class="btn btn-secundario"
                    >
                        🔧 Mis reportes
                    </a>

                </div>

            </div>

        </div>

    </main>

</body>

</html>