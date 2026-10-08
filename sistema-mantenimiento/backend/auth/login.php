<?php

session_start();

require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Acceso no permitido.");
}

$correo = trim($_POST["correo"] ?? "");
$password = $_POST["password"] ?? "";

if ($correo === "" || $password === "") {
    die("Debe ingresar correo y contraseña.");
}

$database = new Database();
$db = $database->conectar();

$sql = "SELECT 
            u.id_usuario,
            u.nombre,
            u.apellido,
            u.correo,
            u.password,
            u.estado,
            u.id_rol,
            r.nombre AS rol
        FROM usuarios u
        INNER JOIN roles r 
            ON u.id_rol = r.id_rol
        WHERE u.correo = :correo
        LIMIT 1";

$stmt = $db->prepare($sql);

$stmt->bindParam(":correo", $correo);

$stmt->execute();

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    die("Correo o contraseña incorrectos.");
}

if (!$usuario["estado"]) {
    die("El usuario está inactivo.");
}

if ($password !== $usuario["password"]) {
    die("Correo o contraseña incorrectos.");
}

$_SESSION["id_usuario"] = $usuario["id_usuario"];
$_SESSION["nombre"] = $usuario["nombre"];
$_SESSION["apellido"] = $usuario["apellido"];
$_SESSION["correo"] = $usuario["correo"];
$_SESSION["id_rol"] = $usuario["id_rol"];
$_SESSION["rol"] = $usuario["rol"];

// Redirigir al usuario según su rol
if ($usuario["id_rol"] == 1) {

    header("Location: ../../frontend/dashboard/administrador.php");
    exit;

} elseif ($usuario["id_rol"] == 2) {

    header("Location: ../../frontend/dashboard/reportante.php");
    exit;

} elseif ($usuario["id_rol"] == 3) {

    header("Location: ../../frontend/dashboard/tecnico.php");
    exit;

} else {

    die("Rol de usuario no válido.");

}