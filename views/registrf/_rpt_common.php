<?php
/**
 * Helpers de impresión para listados de Registración.
 *
 * @param mixed $v
 */
function rfRptEsc($v): string
{
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function rfRptFecha($s): string
{
    $s = (string) $s;
    if ($s === '' || strpos($s, '0000-00-00') === 0) {
        return '—';
    }
    $p = explode('-', substr($s, 0, 10));
    return count($p) === 3 ? $p[2] . '/' . $p[1] . '/' . $p[0] : $s;
}

function rfRptPeriodo($p): string
{
    $p = trim((string) $p);
    if (preg_match('/^(\d{2})\/(\d{2})$/', $p)) {
        return $p;
    }
    if (strlen($p) === 4 && ctype_digit($p)) {
        return substr($p, 0, 2) . '/' . substr($p, 2, 2);
    }
    return $p !== '' ? $p : '—';
}

function rfRptNum($n): string
{
    return number_format((float) $n, 2, ',', '.');
}

function rfRptEntero($n): string
{
    return number_format((float) $n, 0, ',', '.');
}

/**
 * @param array<string,string> $filtros
 */
function rfRptCabecera(string $titulo, array $filtros): void
{
    $hoy = date('d/m/Y H:i');
    echo '<div class="rpt-top">';
    echo '<div class="rpt-org">SERVICIOS MEDICOS</div>';
    echo '<h1>' . rfRptEsc($titulo) . '</h1>';
    echo '<div class="rpt-meta">Fecha: <strong>' . rfRptEsc($hoy) . '</strong>';
    echo ' &nbsp;|&nbsp; Período: <strong>' . rfRptEsc($filtros['desde_fmt'] ?? '') . '</strong>';
    echo ' a <strong>' . rfRptEsc($filtros['hasta_fmt'] ?? '') . '</strong>';
    echo ' &nbsp;|&nbsp; Obra Social: <strong>' . rfRptEsc($filtros['os_lbl'] ?? 'Todas') . '</strong>';
    echo ' &nbsp;|&nbsp; Prestador: <strong>' . rfRptEsc($filtros['prest_lbl'] ?? 'Todos') . '</strong>';
    echo ' &nbsp;|&nbsp; Tipo: <strong>' . rfRptEsc($filtros['tipo_lbl'] ?? 'Todas') . '</strong>';
    echo '</div></div>';
}

function rfRptEstilos(): void
{
    echo '<style>
        body{font-family:"Segoe UI",Arial,sans-serif;font-size:11px;color:#1e293b;margin:16px;background:#fff;}
        .rpt-top{border-bottom:2px solid #0d47a1;margin-bottom:10px;padding-bottom:6px;}
        .rpt-org{font-size:11px;letter-spacing:.12em;font-weight:700;color:#0d47a1;}
        h1{font-size:15px;margin:2px 0 4px;color:#0f172a;}
        .rpt-meta{color:#475569;font-size:11px;}
        .rpt-grupo{margin:14px 0 6px;background:#1e293b;color:#fff;padding:4px 8px;font-size:11px;font-weight:700;letter-spacing:.04em;}
        .rpt-os{margin:16px 0 6px;background:#0d47a1;color:#fff;padding:6px 8px;font-size:12px;font-weight:700;}
        table{width:100%;border-collapse:collapse;}
        th{background:#334155;color:#fff;font-size:9px;letter-spacing:.03em;text-align:left;padding:4px 5px;white-space:nowrap;}
        td{border-bottom:1px solid #e2e8f0;padding:3px 5px;font-size:10px;vertical-align:top;}
        tr:nth-child(even) td{background:#f8fafc;}
        .num{text-align:right;font-family:ui-monospace,Consolas,monospace;white-space:nowrap;}
        .mono{font-family:ui-monospace,Consolas,monospace;}
        .totales td{background:#e2e8f0 !important;font-weight:700;border-top:2px solid #64748b;}
        .tot-gral td{background:#dbeafe !important;font-weight:700;border-top:2px solid #1d4ed8;}
        .empty{padding:28px;text-align:center;color:#64748b;}
        .no-print{margin-bottom:10px;}
        .rpt-toolbar{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px;}
        .rpt-toolbar button,.rpt-toolbar a.rpt-btn{
            padding:6px 12px;font-size:12px;border-radius:6px;cursor:pointer;
            border:1px solid #94a3b8;background:#fff;color:#0f172a;
            text-decoration:none;display:inline-block;line-height:1.3;box-sizing:border-box;
        }
        .rpt-toolbar button.rpt-btn-pri,.rpt-toolbar a.rpt-btn-pri{background:#0d47a1;border-color:#0d47a1;color:#fff;}
        .rpt-toolbar button.rpt-btn-ok,.rpt-toolbar a.rpt-btn-ok{background:#15803d;border-color:#15803d;color:#fff;}
        .rpt-toolbar form{display:inline;margin:0;}
        @media print{
            .no-print{display:none;}
            body{margin:8px;}
            @page{size:landscape;margin:10mm;}
        }
    </style>';
}

function rfRptAssetBase(): string
{
    $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])) : '';
    return rtrim($script, '/');
}

function rfRptExportUrl(string $format): string
{
    $qs = $_GET;
    $qs['format'] = $format;
    $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']) : 'index.php';
    if ($script === '' || $script === '/') {
        $script = 'index.php';
    }
    return $script . '?' . http_build_query($qs);
}

function rfRptToolbar(string $nombreArchivo, string $orientacion = 'landscape'): void
{
    $pdfUrl = htmlspecialchars(rfRptExportUrl('pdf'), ENT_QUOTES, 'UTF-8');
    $xlsUrl = htmlspecialchars(rfRptExportUrl('xlsx'), ENT_QUOTES, 'UTF-8');
    echo '<div class="no-print rpt-toolbar">';
    echo '<button type="button" class="rpt-btn-pri" onclick="window.print()">Imprimir</button>';
    echo '<button type="button" onclick="window.open(\'' . $pdfUrl . '\',\'_blank\')">PDF</button>';
    echo '<a class="rpt-btn rpt-btn-ok" href="' . $xlsUrl . '" target="_blank" rel="noopener">Excel</a>';
    echo '<button type="button" onclick="window.close()">Cerrar</button>';
    echo '</div>';
}

function rfRptScripts(): void
{
}
