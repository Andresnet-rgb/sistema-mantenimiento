<?php

session_start();

// Eliminar todas las variables de sesión
session_unset();

// Destruir la sesión
session_destroy();

// Regresar al login
header("Location: ../../frontend/login.html");
exit;

?>