<?php
/**
 * favicon-gen.php
 * Genera un favicon SVG/PNG dinámico con la letra "C" de COMEDICA.
 * Acceder en: /sistema-medicos/public/img/favicon-gen.php
 *
 * Para usarlo como favicon, cambiar en header.php:
 *   <link rel="icon" href="/sistema-medicos/public/img/favicon-gen.php" type="image/svg+xml">
 */
header('Content-Type: image/svg+xml');
header('Cache-Control: public, max-age=86400');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="32" height="32">
  <rect width="32" height="32" rx="6" fill="#0d47a1"/>
  <text x="16" y="23" font-family="Arial, sans-serif" font-size="20"
        font-weight="bold" fill="white" text-anchor="middle">C</text>
</svg>
