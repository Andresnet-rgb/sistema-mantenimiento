<?php

session_start();

// Mostrar errores durante el desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// ==========================================
// VERIFICAR SESIÓN
// ==========================================

if (!isset($_SESSION["id_usuario"])) {

    die("Debe iniciar sesión.");

}


// ==========================================
// VERIFICAR ADMINISTRADOR
// ==========================================

if ($_SESSION["id_rol"] != 1) {

    die("Acceso no autorizado.");

}


// ==========================================
// VERIFICAR MÉTODO POST
// ==========================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    die("Método no permitido.");

}


// ==========================================
// RECIBIR DATOS
// ==========================================

$id_usuario = intval($_POST["id_usuario"] ?? 0);

$nombre = trim($_POST["nombre"] ?? "");

$apellido = trim($_POST["apellido"] ?? "");

$correo = trim($_POST["correo"] ?? "");

$telefono = trim($_POST["telefono"] ?? "");

$id_rol = intval($_POST["id_rol"] ?? 0);

$estado = intval($_POST["estado"] ?? 0);


// ==========================================
// VALIDAR DATOS
// ==========================================

if (
    $id_usuario <= 0 ||
    empty($nombre) ||
    empty($apellido) ||
    empty($correo) ||
    $id_rol <= 0
) {

    die("Los datos obligatorios no fueron completados.");

}


// ==========================================
// CONECTAR CON LA BASE DE DATOS
// ==========================================

require_once "../config/database.php";

$database = new Database();

$db = $database->conectar();


// ==========================================
// VERIFICAR QUE EL USUARIO EXISTA
// ==========================================

$sql = "SELECT id_usuario
        FROM usuarios
        WHERE id_usuario = :id_usuario";

$stmt = $db->prepare($sql);

$stmt->execute([
    ":id_usuario" => $id_usuario
]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$usuario) {

    die("El usuario no existe.");

}


// ==========================================
// VERIFICAR QUE EL CORREO NO PERTENEZCA
// A OTRO USUARIO
// ==========================================

$sql = "SELECT id_usuario
        FROM usuarios
        WHERE correo = :correo
        AND id_usuario != :id_usuario";

$stmt = $db->prepare($sql);

$stmt->execute([
    ":correo" => $correo,
    ":id_usuario" => $id_usuario
]);

$correoExistente = $stmt->fetch(PDO::FETCH_ASSOC);


if ($correoExistente) {

    die("
        <h2>❌ El correo ya está registrado.</h2>

        <p>
            El correo
            <strong>
                " . htmlspecialchars($correo) . "
            </strong>
            pertenece a otro usuario.
        </p>

        <a href='../../frontend/dashboard/editar_usuario.php?id=" . $id_usuario . "'>
            Volver al formulario
        </a>
    ");

}


// ==========================================
// VERIFICAR QUE EL ROL EXISTA
// ==========================================

$sql = "SELECT id_rol
        FROM roles
        WHERE id_rol = :id_rol";

$stmt = $db->prepare($sql);

$stmt->execute([
    ":id_rol" => $id_rol
]);

$rol = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$rol) {

    die("El rol seleccionado no existe.");

}


// ==========================================
// ACTUALIZAR USUARIO
// ==========================================

$sql = "UPDATE usuarios
        SET
            nombre = :nombre,
            apellido = :apellido,
            correo = :correo,
            telefono = :telefono,
            id_rol = :id_rol,
            estado = :estado
        WHERE id_usuario = :id_usuario";

$stmt = $db->prepare($sql);


// ==========================================
// EJECUTAR ACTUALIZACIÓN
// ==========================================

$stmt->execute([

    ":nombre" => $nombre,

    ":apellido" => $apellido,

    ":correo" => $correo,

    ":telefono" => $telefono,

    ":id_rol" => $id_rol,

    ":estado" => $estado,

    ":id_usuario" => $id_usuario

]);


// ==========================================
// CONFIRMACIÓN
// ==========================================

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>Usuario actualizado</title>

</head>


<body>

    <h2>
        ✅ Usuario actualizado correctamente
    </h2>


    <p>

        Los datos de
        <strong>

            <?php

            echo htmlspecialchars(
                $nombre . " " . $apellido
            );

            ?>

        </strong>

        fueron actualizados correctamente.

    </p>


    <br>


    <a href="../../frontend/dashboard/usuarios.php">

        👥 Volver a usuarios

    </a>


    <br>
    <br>


    <a
        href="../../frontend/dashboard/editar_usuario.php?id=<?php echo $id_usuario; ?>"
    >

        ✏️ Seguir editando

    </a>

</body>

</html>