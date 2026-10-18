<?php

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../public/login.php");
    exit;
}

csrf_verify();

$action = $_POST["action"] ?? "";

if ($action === "register") {
    register($conn);
} elseif ($action === "login") {
    login($conn);
} elseif ($action === "logout") {
    logout($conn);
} else {
    header("Location: ../public/login.php?error=accion_invalida");
    exit;
}

function register($conn) {
    // Límite de registros por IP (anti-spam / creación masiva de cuentas).
    if (rl_bloqueado($conn, "registro", "", 5, 5, PHP_INT_MAX, 3600)) {
        header("Location: ../public/registro.php?error=demasiados_intentos");
        exit;
    }
    rl_registrar($conn, "registro", "");

    $nombre = limpiar_linea($_POST["nombre"] ?? "", 80);
    $apellido = limpiar_linea($_POST["apellido"] ?? "", 80);
    $telefono = limpiar_linea($_POST["telefono"] ?? "", 30);
    $email = limpiar_linea($_POST["email"] ?? "", 120);
    $password = is_string($_POST["password"] ?? null) ? $_POST["password"] : "";
    $password_confirm = is_string($_POST["password_confirm"] ?? null) ? $_POST["password_confirm"] : "";
    $terminos = isset($_POST["terminos"]);

    if (
        $nombre === "" ||
        $apellido === "" ||
        $email === "" ||
        $password === "" ||
        $password_confirm === ""
    ) {
        header("Location: ../public/registro.php?error=campos_vacios");
        exit;
    }

    if (mb_strlen($nombre, 'UTF-8') < 2 || mb_strlen($apellido, 'UTF-8') < 2) {
        header("Location: ../public/registro.php?error=campos_vacios");
        exit;
    }

    if ($telefono !== "" && !preg_match('/^[0-9+()\s-]{6,30}$/', $telefono)) {
        header("Location: ../public/registro.php?error=telefono_invalido");
        exit;
    }

    if (!$terminos) {
        header("Location: ../public/registro.php?error=terminos_no_aceptados");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../public/registro.php?error=email_invalido");
        exit;
    }

    if ($password !== $password_confirm) {
        header("Location: ../public/registro.php?error=password_no_coincide");
        exit;
    }

    $errorPassword = validar_password($password, $email);
    if ($errorPassword !== null) {
        header("Location: ../public/registro.php?error=" . $errorPassword);
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios
            (nombre, apellido, telefono, email, password, rol)
            VALUES
            (:nombre, :apellido, :telefono, :email, :password, 'general')";

    $stmt = $conn->prepare($sql);

    $stmt->bindParam(":nombre", $nombre);
    $stmt->bindParam(":apellido", $apellido);
    $stmt->bindParam(":telefono", $telefono);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":password", $hashedPassword);

    try {
        $stmt->execute();
    } catch (PDOException $e) {
        // El email duplicado se informa con un mensaje genérico (ver registro.php).
        $error = $e->getCode() === '23000' ? 'registro_fallido' : 'db_error';
        if ($error === 'db_error') {
            error_log('Haedo Barber: error al registrar: ' . $e->getMessage());
        }
        header("Location: ../public/registro.php?error=" . $error);
        exit;
    }

    $stmtUsuario = $conn->prepare("SELECT * FROM usuarios WHERE id = :id LIMIT 1");
    $stmtUsuario->bindValue(":id", (int) $conn->lastInsertId(), PDO::PARAM_INT);
    $stmtUsuario->execute();
    $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

    completar_login($conn, $usuario, isset($_POST["recordar"]));
}

function login($conn) {
    $email = limpiar_linea($_POST["email"] ?? "", 120);
    $password = is_string($_POST["password"] ?? null) ? $_POST["password"] : "";
    $recordar = isset($_POST["recordar"]);

    if ($email === "" || $password === "" || strlen($password) > 200) {
        header("Location: ../public/login.php?error=campos_vacios");
        exit;
    }

    // Bloqueo temporal por fuerza bruta: 5 fallos por IP+email, 20 por IP o 30 por email en 15 min.
    $ident = rl_ident($email);
    if (rl_bloqueado($conn, "login", $ident, 5, 20, 30, 900)) {
        header("Location: ../public/login.php?error=bloqueado");
        exit;
    }

    $sql = "SELECT * FROM usuarios WHERE email = :email LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":email", $email);
    $stmt->execute();

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se verifica siempre un hash (falso si el usuario no existe) para igualar tiempos
    // y no revelar qué emails están registrados.
    $hashAComparar = $usuario ? $usuario["password"] : HB_DUMMY_HASH;
    $passwordOk = password_verify($password, $hashAComparar);

    if (!$usuario || !$passwordOk) {
        rl_registrar($conn, "login", $ident);
        header("Location: ../public/login.php?error=credenciales_invalidas");
        exit;
    }

    rl_limpiar($conn, "login", $ident);

    if (password_needs_rehash($usuario["password"], PASSWORD_DEFAULT)) {
        $nuevoHash = password_hash($password, PASSWORD_DEFAULT);
        $stmtRehash = $conn->prepare("UPDATE usuarios SET password = :p WHERE id = :id");
        $stmtRehash->bindValue(":p", $nuevoHash);
        $stmtRehash->bindValue(":id", (int) $usuario["id"], PDO::PARAM_INT);
        $stmtRehash->execute();
    }

    completar_login($conn, $usuario, $recordar);
}

function completar_login($conn, $usuario, $recordar) {
    iniciar_sesion_usuario($usuario);

    $stmtUltimoAcceso = $conn->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id");
    $stmtUltimoAcceso->bindValue(":id", (int) $usuario["id"], PDO::PARAM_INT);
    $stmtUltimoAcceso->execute();

    try {
        if ($recordar) {
            recordar_emitir($conn, (int) $usuario["id"]);
        } else {
            recordar_revocar($conn, (int) $usuario["id"]);
            recordar_borrar_cookies();
        }
    } catch (PDOException $e) {
        error_log("Haedo Barber: recordarme no disponible (¿falta migrations_002_security.sql?): " . $e->getMessage());
    }

    if ($usuario["rol"] === "admin") {
        header("Location: ../public/admin/panel_admin.php");
        exit;
    }

    header("Location: ../public/index.php");
    exit;
}

function logout($conn) {
    $usuarioId = $_SESSION["usuario_id"] ?? null;

    if ($usuarioId) {
        try {
            recordar_revocar($conn, (int) $usuarioId);
        } catch (PDOException $e) {
            error_log("Haedo Barber: no se pudo revocar recordarme: " . $e->getMessage());
        }
    }

    recordar_borrar_cookies();
    destruir_sesion();

    header("Location: ../public/login.php?logout=exitoso");
    exit;
}
