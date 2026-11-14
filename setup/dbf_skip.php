<?php
/**
 * setup/dbf_skip.php
 * DBF de cierre / historial / exportación que NO se importan a MySQL.
 *
 * Pedido Luis 2026-11-14: saltar DETCLOSE2025, DETCLOSEOLD23, adetcon,
 * EXPOPREO, detconh, dosadef, dethbck, dosadefh, EXPOAyer, detbck2025,
 * detconsuM, detcobck, EXPOPRE9, detbck200731 y tablas similares 1–2 GB.
 *
 * Incluir este archivo en cualquier importador DBF → MySQL.
 * Dialecto PHP 5.6.
 */

/**
 * Nombres base (sin extensión) en minúsculas.
 *
 * @return string[]
 */
function dbf_skip_basenames()
{
    return array(
        'detclose2025',
        'detcloseold23',
        'adetcon',
        'expopreo',
        'detconh',
        'dosadef',
        'dethbck',
        'dosadefh',
        'expoayer',
        'detbck2025',
        'detconsum',
        'detcobck',
        'expopre9',
        'detbck200731',
        // similares (cierre / historial / export / backup)
        'detbckold',
        'detbck',
        'detconsu',
        'detconsu1',
        'detcon16',
        'expoprea',
        'expopreso',
        'expo19',
        'histpaum',
        'histpaum1',
        'histpesp',
        'histpesp1',
        'cosadef',
        'cosadefh',
        'dosef',
    );
}

/**
 * ¿Este archivo (DBF/CDX/FPT/IDX) está en la lista de skip?
 *
 * @param  string $filename Nombre o ruta
 * @return bool
 */
function dbf_should_skip($filename)
{
    $base = strtolower(pathinfo($filename, PATHINFO_FILENAME));
    if ($base === '') {
        return false;
    }

    if (in_array($base, dbf_skip_basenames(), true)) {
        return true;
    }

    // Familias de cierre / historial / exportación / backup (cualquier tamaño).
    if (preg_match('/^(detclose|adetcon|detcon|dosadef|dethbck|expo|detbck|detco|histp|cosadef|dosef)/', $base)) {
        return true;
    }

    return false;
}
