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
// VERIFICAR QUE SEA ADMINISTRADOR
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
// RECIBIR DATOS DEL FORMULARIO
// ==========================================

$nombre = trim($_POST["nombre"] ?? "");
$apellido = trim($_POST["apellido"] ?? "");
$correo = trim($_POST["correo"] ?? "");
$password = $_POST["password"] ?? "";
$telefono = trim($_POST["telefono"] ?? "");
$id_rol = intval($_POST["id_rol"] ?? 0);


// ==========================================
// VALIDAR DATOS
// ==========================================

if (
    empty($nombre) ||
    empty($apellido) ||
    empty($correo) ||
    empty($password) ||
    $id_rol <= 0
) {
    die("Todos los campos obligatorios deben ser completados.");
}


// ==========================================
// CONECTAR CON LA BASE DE DATOS
// ==========================================

require_once "../config/database.php";

$database = new Database();
$db = $database->conectar();


// ==========================================
// VERIFICAR QUE EL CORREO NO EXISTA
// ==========================================

$sql = "SELECT id_usuario
        FROM usuarios
        WHERE correo = :correo";

$stmt = $db->prepare($sql);

$stmt->execute([
    ":correo" => $correo
]);

$usuarioExistente = $stmt->fetch(PDO::FETCH_ASSOC);


if ($usuarioExistente) {

    die("
        <h2>❌ El correo ya está registrado.</h2>

        <p>
            El correo <strong>$correo</strong>
            ya pertenece a un usuario.
        </p>

        <a href='../../frontend/dashboard/crear_usuario.php'>
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
// INSERTAR USUARIO
// ==========================================

$sql = "INSERT INTO usuarios
        (
            nombre,
            apellido,
            correo,
            password,
            telefono,
            id_rol,
            estado
        )
        VALUES
        (
            :nombre,
            :apellido,
            :correo,
            :password,
            :telefono,
            :id_rol,
            1
        )";

$stmt = $db->prepare($sql);


// ==========================================
// EJECUTAR INSERT
// ==========================================

$stmt->execute([

    ":nombre" => $nombre,

    ":apellido" => $apellido,

    ":correo" => $correo,

    ":password" => $password,

    ":telefono" => $telefono,

    ":id_rol" => $id_rol

]);


// ==========================================
// CONFIRMACIÓN
// ==========================================

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>Usuario creado</title>

</head>

<body>

    <h2>✅ Usuario registrado correctamente</h2>

    <p>
        El usuario
        <strong>
            <?php echo htmlspecialchars($nombre . " " . $apellido); ?>
        </strong>
        fue registrado correctamente.
    </p>

    <br>

    <a href="../../frontend/dashboard/usuarios.php">
        👥 Ver usuarios
    </a>

    <br>
    <br>

    <a href="../../frontend/dashboard/crear_usuario.php">
        ➕ Registrar otro usuario
    </a>

</body>

</html>