<?php

session_start();
require_once '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../public/login.php");
    exit;
}

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
    $nombre = trim($_POST["nombre"] ?? "");
    $apellido = trim($_POST["apellido"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $password_confirm = $_POST["password_confirm"] ?? "";
    $terminos = isset($_POST["terminos"]);

    if (
        empty($nombre) ||
        empty($apellido) ||
        empty($email) ||
        empty($password) ||
        empty($password_confirm)
    ) {
        header("Location: ../public/registro.php?error=campos_vacios");
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

    if (strlen($password) < 8) {
        header("Location: ../public/registro.php?error=password_corta");
        exit;
    }

    $checkSql = "SELECT id FROM usuarios WHERE email = :email";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bindParam(":email", $email);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        header("Location: ../public/registro.php?error=email_existente");
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

    $stmt->execute();

    login($conn);
    exit;
}

function login($conn) {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $recordar = isset($_POST["recordar"]);

    if (empty($email) || empty($password)) {
        header("Location: ../public/login.php?error=campos_vacios");
        exit;
    }

    $sql = "SELECT * FROM usuarios WHERE email = :email LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":email", $email);
    $stmt->execute();

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || !password_verify($password, $usuario["password"])) {
        header("Location: ../public/login.php?error=credenciales_invalidas");
        exit;
    }

    $_SESSION["usuario_id"] = $usuario["id"];
    $_SESSION["usuario_nombre"] = $usuario["nombre"];
    $_SESSION["usuario_email"] = $usuario["email"];
    $_SESSION["usuario_rol"] = $usuario["rol"];

    $sqlUltimoAcceso = "UPDATE usuarios
                        SET ultimo_acceso = NOW()
                        WHERE id = :id";

    $stmtUltimoAcceso = $conn->prepare($sqlUltimoAcceso);
    $stmtUltimoAcceso->bindParam(":id", $usuario["id"]);
    $stmtUltimoAcceso->execute();

    if ($recordar) {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash("sha256", $token);

        $sqlToken = "UPDATE usuarios
                     SET remember_token_hash = :token_hash,
                         remember_token_expira = DATE_ADD(NOW(), INTERVAL 1 MONTH)
                     WHERE id = :id";

        $stmtToken = $conn->prepare($sqlToken);
        $stmtToken->bindParam(":token_hash", $tokenHash);
        $stmtToken->bindParam(":id", $usuario["id"]);
        $stmtToken->execute();

        setcookie(
            "remember_token",
            $token,
            time() + (30 * 24 * 60 * 60),
            "/",
            "",
            false,
            true
        );

        setcookie(
            "remember_user",
            $usuario["id"],
            time() + (30 * 24 * 60 * 60),
            "/",
            "",
            false,
            true
        );

    } else {
        $sqlLimpiarToken = "UPDATE usuarios
                            SET remember_token_hash = NULL,
                                remember_token_expira = NULL
                            WHERE id = :id";

        $stmtLimpiarToken = $conn->prepare($sqlLimpiarToken);
        $stmtLimpiarToken->bindParam(":id", $usuario["id"]);
        $stmtLimpiarToken->execute();

        setcookie("remember_token", "", time() - 3600, "/");
        setcookie("remember_user", "", time() - 3600, "/");
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
        $sql = "UPDATE usuarios
                SET remember_token_hash = NULL,
                    remember_token_expira = NULL
                WHERE id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":id", $usuarioId);
        $stmt->execute();
    }

    setcookie("remember_token", "", time() - 3600, "/");
    setcookie("remember_user", "", time() - 3600, "/");

    session_unset();
    session_destroy();

    header("Location: ../public/login.php?logout=exitoso");
    exit;
}
