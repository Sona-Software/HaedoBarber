<?php
// Sesión endurecida + CSRF + helpers (ver config/security.php).
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/database.php';

// Restaura la sesión desde la cookie "remember" (selector + validator hasheado).
recordar_login($conn);
