<?php

session_start();

// ==========================================
// VERIFICAR SESIÓN
// ==========================================

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.html");
    exit;
}

// ==========================================
// VERIFICAR QUE SEA ADMINISTRADOR
// ==========================================

if ($_SESSION["id_rol"] != 1) {
    header("Location: ../login.html");
    exit;
}

// ==========================================
// CONEXIÓN A BASE DE DATOS
// ==========================================

require_once "../../backend/config/database.php";

$database = new Database();
$db = $database->conectar();


// ==========================================
// ESTADÍSTICAS DE REPORTES POR ESTADO
// ==========================================

$sqlEstados = "
    SELECT
        e.nombre AS estado,
        COUNT(r.id_reporte) AS cantidad
    FROM estados e
    LEFT JOIN reportes r
        ON e.id_estado = r.id_estado
    GROUP BY e.id_estado, e.nombre
    ORDER BY e.id_estado
";

$stmtEstados = $db->prepare($sqlEstados);
$stmtEstados->execute();

$estadisticasEstados = $stmtEstados->fetchAll(PDO::FETCH_ASSOC);


// ==========================================
// ESTADÍSTICAS DE REPORTES POR CATEGORÍA
// ==========================================

$sqlCategorias = "
    SELECT
        c.nombre AS categoria,
        COUNT(r.id_reporte) AS cantidad
    FROM categorias c
    LEFT JOIN reportes r
        ON c.id_categoria = r.id_categoria
    GROUP BY c.id_categoria, c.nombre
    ORDER BY c.id_categoria
";

$stmtCategorias = $db->prepare($sqlCategorias);
$stmtCategorias->execute();

$estadisticasCategorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);


// ==========================================
// ESTADÍSTICAS DE REPORTES POR PRIORIDAD
// ==========================================

$sqlPrioridades = "
    SELECT
        p.nombre AS prioridad,
        COUNT(r.id_reporte) AS cantidad
    FROM prioridades p
    LEFT JOIN reportes r
        ON p.id_prioridad = r.id_prioridad
    GROUP BY p.id_prioridad, p.nombre
    ORDER BY p.id_prioridad
";

$stmtPrioridades = $db->prepare($sqlPrioridades);
$stmtPrioridades->execute();

$estadisticasPrioridades = $stmtPrioridades->fetchAll(PDO::FETCH_ASSOC);


// ==========================================
// REPORTES POR TÉCNICO
// ==========================================

$sqlTecnicos = "
    SELECT
        CONCAT(u.nombre, ' ', u.apellido) AS tecnico,
        COUNT(a.id_reporte) AS cantidad
    FROM usuarios u
    LEFT JOIN asignaciones a
        ON u.id_usuario = a.id_tecnico
        AND a.activa = 1
    WHERE u.id_rol = 3
    GROUP BY u.id_usuario, u.nombre, u.apellido
    ORDER BY cantidad DESC
";

$stmtTecnicos = $db->prepare($sqlTecnicos);
$stmtTecnicos->execute();

$estadisticasTecnicos = $stmtTecnicos->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Estadísticas - Sistema de Mantenimiento</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>

        /* ==========================================
           CONFIGURACIÓN GENERAL
        ========================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: #f3f4f6;
            color: #1f2937;
        }

        .contenedor {
            display: flex;
            min-height: 100vh;
        }


        /* ==========================================
           MENÚ LATERAL
        ========================================== */

        .menu {
    width: 260px;
    height: 100vh;

    background: linear-gradient(
        180deg,
        #243b88 0%,
        #2563eb 100%
    );

    color: white;

    padding: 25px 18px;

    position: fixed;

    left: 0;
    top: 0;

    flex-shrink: 0;

    z-index: 1000;
}

        .menu h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 22px;
        }

        .menu ul {
            list-style: none;
        }

        .menu li {
            margin-bottom: 8px;
        }

        .menu a {
            display: block;
            text-decoration: none;
            color: white;
            padding: 13px 15px;
            border-radius: 8px;
            transition: 0.2s;
            font-size: 15px;
        }

        .menu a:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .menu .activo {
            background: rgba(255, 255, 255, 0.20);
            font-weight: bold;
        }

        .menu .cerrar {
            margin-top: 25px;
            background: rgba(220, 38, 38, 0.85);
        }

        .menu .cerrar:hover {
            background: #dc2626;
        }


        /* ==========================================
           CONTENIDO PRINCIPAL
        ========================================== */

        .contenido {
    margin-left: 260px;

    width: calc(100% - 260px);

    min-height: 100vh;

    padding: 35px;

    background-color: #f3f6fa;
}


        /* ==========================================
           ENCABEZADO
        ========================================== */

        .encabezado-principal {
            margin-bottom: 28px;
        }

        .encabezado-principal h1 {
            font-size: 28px;
            color: #111827;
            margin-bottom: 7px;
        }

        .encabezado-principal p {
            color: #6b7280;
            font-size: 15px;
        }


        /* ==========================================
           SECCIONES
        ========================================== */

        .seccion {
            margin-bottom: 32px;
        }

        .encabezado-seccion {
            margin-bottom: 18px;
        }

        .encabezado-seccion h2 {
            font-size: 20px;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .encabezado-seccion p {
            color: #6b7280;
            font-size: 14px;
        }


        /* ==========================================
           TARJETAS DE ESTADÍSTICAS
        ========================================== */

        .tarjetas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 18px;
        }

        .tarjeta-estadistica {
            background: white;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            border: 1px solid #f1f5f9;
            transition: 0.2s;
        }

        .tarjeta-estadistica:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
        }

        .tarjeta-estadistica h3 {
            font-size: 15px;
            color: #4b5563;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .tarjeta-estadistica .numero {
            font-size: 30px;
            font-weight: bold;
            color: #2563eb;
        }


        /* ==========================================
           TARJETAS DE GRÁFICOS
        ========================================== */

        .tarjeta-grafico {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 22px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            border: 1px solid #f1f5f9;
        }

        .tarjeta-grafico h2 {
            font-size: 18px;
            color: #1f2937;
            margin-bottom: 20px;
        }

        .grafico-contenedor {
            position: relative;
            width: 100%;
            height: 320px;
        }


        /* ==========================================
           RESPONSIVE
        ========================================== */

        @media (max-width: 900px) {

            .menu {
                width: 210px;
            }

            .contenido {
                padding: 20px;
            }

        }


        @media (max-width: 700px) {

            .contenedor {
                flex-direction: column;
            }

            .menu {
                width: 100%;
                min-height: auto;
            }

            .menu ul {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
            }

            .menu li {
                margin-bottom: 0;
            }

            .menu a {
                padding: 10px 12px;
            }

            .contenido {
                padding: 15px;
            }

            .tarjetas {
                grid-template-columns: 1fr 1fr;
            }

            .grafico-contenedor {
                height: 280px;
            }

        }


        @media (max-width: 480px) {

            .tarjetas {
                grid-template-columns: 1fr;
            }

            .encabezado-principal h1 {
                font-size: 24px;
            }

        }

    </style>

</head>


<body>

<div class="contenedor">


    <!-- ==========================================
         MENÚ LATERAL
    ========================================== -->

    <aside class="menu">

        <h2>🛠️ Mantenimiento</h2>

        <ul>

            <li>
                <a href="administrador.php">
                    📊 Dashboard
                </a>
            </li>

            <li>
                <a href="usuarios.php">
                    👥 Usuarios
                </a>
            </li>

            <li>
                <a href="reportes_admin.php">
                    📋 Reportes
                </a>
            </li>

            <li>
                <a href="tecnicos.php">
                    🔧 Técnicos
                </a>
            </li>

            <li>
                <a href="estadisticas.php" class="activo">
                    📈 Estadísticas
                </a>
            </li>

            <li>
                <a href="../../backend/auth/logout.php" class="cerrar">
                    🚪 Cerrar sesión
                </a>
            </li>

        </ul>

    </aside>


    <!-- ==========================================
         CONTENIDO
    ========================================== -->

    <main class="contenido">


        <!-- ENCABEZADO PRINCIPAL -->

        <div class="encabezado-principal">

            <h1>📊 Estadísticas</h1>

            <p>
                Consulte información general sobre los reportes de mantenimiento.
            </p>

        </div>


        <!-- ==========================================
             REPORTES POR ESTADO
        ========================================== -->

        <section class="seccion">

            <div class="encabezado-seccion">

                <h2>📋 Reportes por estado</h2>

                <p>
                    Cantidad de reportes registrados en cada estado.
                </p>

            </div>


            <div class="tarjetas">

                <?php foreach ($estadisticasEstados as $estadistica): ?>

                    <div class="tarjeta-estadistica">

                        <h3>
                            <?php
                            echo htmlspecialchars(
                                $estadistica["estado"]
                            );
                            ?>
                        </h3>

                        <div class="numero">

                            <?php
                            echo $estadistica["cantidad"];
                            ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </section>


        <!-- ==========================================
             REPORTES POR CATEGORÍA
        ========================================== -->

        <section class="seccion">

            <div class="encabezado-seccion">

                <h2>🏷️ Reportes por categoría</h2>

                <p>
                    Cantidad de reportes registrados según el tipo de mantenimiento.
                </p>

            </div>


            <div class="tarjetas">

                <?php foreach ($estadisticasCategorias as $estadistica): ?>

                    <div class="tarjeta-estadistica">

                        <h3>
                            <?php
                            echo htmlspecialchars(
                                $estadistica["categoria"]
                            );
                            ?>
                        </h3>

                        <div class="numero">

                            <?php
                            echo $estadistica["cantidad"];
                            ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </section>


        <!-- ==========================================
             REPORTES POR PRIORIDAD
        ========================================== -->

        <section class="seccion">

            <div class="encabezado-seccion">

                <h2>⚠️ Reportes por prioridad</h2>

                <p>
                    Cantidad de reportes registrados según su nivel de prioridad.
                </p>

            </div>


            <div class="tarjetas">

                <?php foreach ($estadisticasPrioridades as $estadistica): ?>

                    <div class="tarjeta-estadistica">

                        <h3>
                            <?php
                            echo htmlspecialchars(
                                $estadistica["prioridad"]
                            );
                            ?>
                        </h3>

                        <div class="numero">

                            <?php
                            echo $estadistica["cantidad"];
                            ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </section>


        <!-- ==========================================
             GRÁFICO POR ESTADO
        ========================================== -->

        <section class="tarjeta-grafico">

            <h2>
                📊 Gráfico de reportes por estado
            </h2>

            <div class="grafico-contenedor">

                <canvas id="graficoEstados"></canvas>

            </div>

        </section>


        <!-- ==========================================
             GRÁFICO POR CATEGORÍA
        ========================================== -->

        <section class="tarjeta-grafico">

            <h2>
                📊 Gráfico de reportes por categoría
            </h2>

            <div class="grafico-contenedor">

                <canvas id="graficoCategorias"></canvas>

            </div>

        </section>


        <!-- ==========================================
             GRÁFICO POR PRIORIDAD
        ========================================== -->

        <section class="tarjeta-grafico">

            <h2>
                📊 Gráfico de reportes por prioridad
            </h2>

            <div class="grafico-contenedor">

                <canvas id="graficoPrioridades"></canvas>

            </div>

        </section>


        <!-- ==========================================
             GRÁFICO POR TÉCNICO
        ========================================== -->

        <section class="tarjeta-grafico">

            <h2>
                📊 Reportes asignados por técnico
            </h2>

            <div class="grafico-contenedor">

                <canvas id="graficoTecnicos"></canvas>

            </div>

        </section>


        <!-- ==========================================
             ESTADÍSTICAS POR TÉCNICO
        ========================================== -->

        <section class="seccion">

            <div class="encabezado-seccion">

                <h2>👨‍🔧 Reportes por técnico</h2>

                <p>
                    Cantidad de reportes actualmente asignados a cada técnico.
                </p>

            </div>


            <div class="tarjetas">

                <?php foreach ($estadisticasTecnicos as $estadistica): ?>

                    <div class="tarjeta-estadistica">

                        <h3>
                            <?php
                            echo htmlspecialchars(
                                $estadistica["tecnico"]
                            );
                            ?>
                        </h3>

                        <div class="numero">

                            <?php
                            echo $estadistica["cantidad"];
                            ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </section>


    </main>

</div>


<!-- ==========================================
     JAVASCRIPT - GRÁFICO POR ESTADO
========================================== -->

<script>

const estados = [

    <?php foreach ($estadisticasEstados as $estadistica): ?>

        "<?php echo htmlspecialchars($estadistica["estado"]); ?>",

    <?php endforeach; ?>

];


const cantidades = [

    <?php foreach ($estadisticasEstados as $estadistica): ?>

        <?php echo $estadistica["cantidad"]; ?>,

    <?php endforeach; ?>

];


const ctxEstados =
    document.getElementById("graficoEstados");


new Chart(ctxEstados, {

    type: "bar",

    data: {

        labels: estados,

        datasets: [

            {
                label: "Cantidad de reportes",
                data: cantidades,
                backgroundColor: "#2563eb",
                borderRadius: 6
            }

        ]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        scales: {

            y: {

                beginAtZero: true,

                ticks: {
                    stepSize: 1
                }

            }

        },

        plugins: {

            legend: {
                display: true
            }

        }

    }

});

</script>


<!-- ==========================================
     JAVASCRIPT - GRÁFICO POR CATEGORÍA
========================================== -->

<script>

const categorias = [

    <?php foreach ($estadisticasCategorias as $estadistica): ?>

        "<?php echo htmlspecialchars($estadistica["categoria"]); ?>",

    <?php endforeach; ?>

];


const cantidadesCategorias = [

    <?php foreach ($estadisticasCategorias as $estadistica): ?>

        <?php echo $estadistica["cantidad"]; ?>,

    <?php endforeach; ?>

];


const ctxCategorias =
    document.getElementById("graficoCategorias");


new Chart(ctxCategorias, {

    type: "bar",

    data: {

        labels: categorias,

        datasets: [

            {
                label: "Cantidad de reportes",
                data: cantidadesCategorias,
                backgroundColor: "#2563eb",
                borderRadius: 6
            }

        ]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        scales: {

            y: {

                beginAtZero: true,

                ticks: {
                    stepSize: 1
                }

            }

        },

        plugins: {

            legend: {
                display: true
            }

        }

    }

});

</script>


<!-- ==========================================
     JAVASCRIPT - GRÁFICO POR PRIORIDAD
========================================== -->

<script>

const prioridades = [

    <?php foreach ($estadisticasPrioridades as $estadistica): ?>

        "<?php echo htmlspecialchars($estadistica["prioridad"]); ?>",

    <?php endforeach; ?>

];


const cantidadesPrioridades = [

    <?php foreach ($estadisticasPrioridades as $estadistica): ?>

        <?php echo $estadistica["cantidad"]; ?>,

    <?php endforeach; ?>

];


const ctxPrioridades =
    document.getElementById("graficoPrioridades");


new Chart(ctxPrioridades, {

    type: "bar",

    data: {

        labels: prioridades,

        datasets: [

            {
                label: "Cantidad de reportes",
                data: cantidadesPrioridades,
                backgroundColor: "#2563eb",
                borderRadius: 6
            }

        ]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        scales: {

            y: {

                beginAtZero: true,

                ticks: {
                    stepSize: 1
                }

            }

        },

        plugins: {

            legend: {
                display: true
            }

        }

    }

});

</script>


<!-- ==========================================
     JAVASCRIPT - GRÁFICO POR TÉCNICO
========================================== -->

<script>

const tecnicos = [

    <?php foreach ($estadisticasTecnicos as $estadistica): ?>

        "<?php echo htmlspecialchars($estadistica["tecnico"]); ?>",

    <?php endforeach; ?>

];


const cantidadesTecnicos = [

    <?php foreach ($estadisticasTecnicos as $estadistica): ?>

        <?php echo $estadistica["cantidad"]; ?>,

    <?php endforeach; ?>

];


const ctxTecnicos =
    document.getElementById("graficoTecnicos");


new Chart(ctxTecnicos, {

    type: "bar",

    data: {

        labels: tecnicos,

        datasets: [

            {
                label: "Reportes asignados",
                data: cantidadesTecnicos,
                backgroundColor: "#2563eb",
                borderRadius: 6
            }

        ]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        scales: {

            y: {

                beginAtZero: true,

                ticks: {
                    stepSize: 1
                }

            }

        },

        plugins: {

            legend: {
                display: true
            }

        }

    }

});

</script>


</body>

</html>