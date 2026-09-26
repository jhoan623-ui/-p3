<?php
// logout.php
session_start();
$_SESSION = array(); // Limpia todas las variables de sesión

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy(); // Destruye la sesión en el servidor
header("Location: login.php");
exit();
?>