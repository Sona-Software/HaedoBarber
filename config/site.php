<?php

declare(strict_types=1);

/**
 * Datos públicos del negocio, centralizados.
 * Cada getter lee $_ENV / getenv() (.env) y, si no hay valor, usa el default de abajo.
 * Para ocultar un dato, definir la clave en .env con valor "-" no es necesario: dejarla vacía
 * hace caer al default; para quitarlo del todo, borrar el default aquí.
 */
function sitio_defaults(): array
{
    return [
        'CONTACT_EMAIL' => '',
        'CONTACT_PHONE' => '+54 9 11 2544-4383',
        'CONTACT_ADDRESS' => 'Las Bases 199, B1706 Haedo, Provincia de Buenos Aires',
        'HORARIO_TEXTO' => 'Lun a Sáb · 10:30 a 20:30',
        'INSTAGRAM_HANDLE' => '@haedobarber',
        'INSTAGRAM_URL' => 'https://www.instagram.com/haedobarber/',
        'TIKTOK_URL' => 'https://www.tiktok.com/@haedobarber',
        'WHATSAPP_URL' => 'https://wa.me/5491125444383',
        'GOOGLE_MAPS_EMBED_URL' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3282.3086728593485!2d-58.595735887891095!3d-34.64690675966904!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x95bcc77c83f32afb%3A0x5d7c4ae06630433b!2sHaedo%20Barber!5e0!3m2!1ses-419!2sar!4v1792430456703!5m2!1ses-419!2sar',
    ];
}

function sitio_config(string $clave): string
{
    $valor = $_ENV[$clave] ?? getenv($clave);
    $valor = is_string($valor) ? trim($valor) : '';
    if ($valor === '') {
        $valor = sitio_defaults()[$clave] ?? '';
    }
    return $valor;
}

/** URL externa permitida sólo si es http(s); si no, cadena vacía. */
function sitio_url(string $clave): string
{
    $url = sitio_config($clave);
    return preg_match('#^https?://#i', $url) === 1 ? $url : '';
}

function sitio_email(): string
{
    $v = sitio_config('CONTACT_EMAIL');
    return filter_var($v, FILTER_VALIDATE_EMAIL) ? $v : '';
}

function sitio_telefono(): string
{
    return sitio_config('CONTACT_PHONE');
}

/** Teléfono para href="tel:" (sólo + y dígitos). */
function sitio_telefono_tel(): string
{
    return preg_replace('/[^\d+]/', '', sitio_telefono()) ?? '';
}

function sitio_direccion(): string
{
    return sitio_config('CONTACT_ADDRESS');
}

/** Horario de atención (texto público, sin días de cierre). */
function sitio_horario(): string
{
    return sitio_config('HORARIO_TEXTO');
}

function sitio_direccion_url(): string
{
    $d = sitio_direccion();
    return $d === '' ? '' : 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($d);
}

function sitio_instagram_url(): string { return sitio_url('INSTAGRAM_URL'); }
function sitio_instagram_handle(): string { return sitio_config('INSTAGRAM_HANDLE'); }
function sitio_tiktok_url(): string { return sitio_url('TIKTOK_URL'); }
function sitio_whatsapp_url(): string { return sitio_url('WHATSAPP_URL'); }

/** URL del mapa validada con mapa_embed_url() (config/security.php) o ''. */
function sitio_mapa_url(): string
{
    require_once __DIR__ . '/security.php';
    return mapa_embed_url(sitio_config('GOOGLE_MAPS_EMBED_URL')) ?? '';
}

function sitio_h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
