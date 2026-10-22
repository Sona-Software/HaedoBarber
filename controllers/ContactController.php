<?php

/**
 * Obsoleto: el formulario de contacto por mail fue reemplazado por contacto por WhatsApp.
 * Este endpoint ya no procesa ni guarda nada; cualquier request (GET/POST) vuelve a contacto.php.
 * La tabla `contactos` y el panel admin "Mensajes" se conservan como historial.
 */
header("Location: ../public/contacto.php", true, 303);
exit;
