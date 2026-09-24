<?php
/**
 * Compatibilidad: el módulo PDF ahora vive en archivos de entrada propios.
 */
class RegistrfPdfController
{
    public function index()
    {
        header('Location: registro_facturas_pdf.php');
        exit;
    }
}
