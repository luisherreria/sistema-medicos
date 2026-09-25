<?php
/**
 * classes/DateHelper.php
 * Períodos de facturación (AA/MM). Compatible PHP 5.6.
 */
class DateHelper
{
    /**
     * Mes anterior a $fecha (Y-m-d) en formato YY/MM.
     * Ej: 2026-09-25 → 26/08; 2026-01-10 → 25/12.
     *
     * @param string|null $fecha
     * @return string
     */
    public static function getPeriodoAnterior($fecha = null)
    {
        if ($fecha === null || $fecha === '') {
            $fecha = date('Y-m-d');
        }
        $dt = date_create($fecha);
        if (!$dt) {
            $dt = date_create(date('Y-m-d'));
        }
        if (!$dt) {
            return '';
        }
        date_modify($dt, '-1 month');
        return date_format($dt, 'y/m');
    }
}
