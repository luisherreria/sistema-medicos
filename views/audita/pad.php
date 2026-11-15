<?php
/**
 * views/audita/pad.php
 * Pantallas pad Audit. Facturac. (autscerr, VERLIQ, LIQSCERR, REAUDIFACT, AVIDEB).
 */
if (!function_exists('audita_h')) {
    function audita_h($s)
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('audita_caption')) {
    function audita_caption($s)
    {
        return str_replace('\\', '', (string) $s);
    }
}

$cfg       = isset($cfg) ? $cfg : array();
$caption   = isset($cfg['caption']) ? $cfg['caption'] : '';
$menu      = isset($cfg['menu']) ? $cfg['menu'] : $caption;
$icon      = isset($cfg['icon']) ? $cfg['icon'] : 'fa-solid fa-file';
$slug      = isset($cfg['slug']) ? $cfg['slug'] : '';
$formName  = isset($cfg['form']) ? $cfg['form'] : '';
$fields    = isset($cfg['fields']) ? $cfg['fields'] : array();
$hidden    = isset($cfg['hidden']) ? $cfg['hidden'] : array();
$checks    = isset($cfg['checks']) ? $cfg['checks'] : array();
$buttons   = isset($cfg['buttons']) ? $cfg['buttons'] : array();
$csrfToken = isset($csrfToken) ? (string) $csrfToken : '';

$pageTitle  = isset($pageTitle) ? $pageTitle : ('COMEDICA — ' . $caption);
$breadcrumb = isset($breadcrumb) ? $breadcrumb : array(
    array('label' => 'Audit. Facturac.'),
    array('label' => $menu),
);

require_once __DIR__ . '/../../models/Permission.php';
require_once __DIR__ . '/../../views/layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h5 class="mb-1 fw-bold text-primary">
            <i class="<?= audita_h($icon) ?> me-2"></i><?= audita_h($caption) ?>
        </h5>
        <p class="text-muted mb-0" style="font-size:0.82rem;">
            Audit. Facturac. &rsaquo; <?= audita_h($menu) ?> &mdash; form <?= audita_h($formName) ?>
        </p>
    </div>
</div>

<div id="pad-alert" class="alert d-none mb-3 py-2" role="alert"></div>

<div class="card border-0 shadow-sm" style="border-radius:10px; max-width:720px;">
    <div class="card-header py-2 text-white" style="background:#2b579a;">
        <span class="small fw-semibold"><?= audita_h($caption) ?></span>
    </div>
    <div class="card-body">
        <form id="form-pad">
            <input type="hidden" name="csrf_token" value="<?= audita_h($csrfToken) ?>">
            <?php
            foreach ($hidden as $hn => $hv) {
                echo '<input type="hidden" name="' . audita_h($hn) . '" value="' . audita_h($hv) . '">';
            }
            $fi = 0;
            $fn = count($fields);
            for ($fi = 0; $fi < $fn; $fi++) {
                $f = $fields[$fi];
                $fname = isset($f['name']) ? $f['name'] : '';
                $flab  = isset($f['label']) ? $f['label'] : '';
                $fval  = isset($f['value']) ? $f['value'] : '';
                $fmax  = isset($f['max']) ? (int) $f['max'] : 0;
                $ro    = !empty($f['readonly']);
                $dis   = !empty($f['disabled']);
                $up    = !empty($f['upper']);
                $fid   = isset($f['id']) ? $f['id'] : ('pad-' . $fname);
                echo '<div class="row g-2 mb-2 align-items-center">';
                echo '<div class="col-sm-4"><label class="small mb-0" for="' . audita_h($fid) . '">' . audita_h($flab) . '</label></div>';
                echo '<div class="col-sm-8">';
                echo '<input class="form-control form-control-sm' . ($up ? ' text-uppercase' : '') . '"';
                echo ' name="' . audita_h($fname) . '" id="' . audita_h($fid) . '"';
                echo ' value="' . audita_h($fval) . '"';
                if ($fmax > 0) {
                    echo ' maxlength="' . $fmax . '"';
                }
                if ($ro) {
                    echo ' readonly';
                }
                if ($dis) {
                    echo ' disabled';
                }
                echo '>';
                echo '</div></div>';
            }
            if (!empty($checks)) {
                echo '<div class="mb-3">';
                $ci = 0;
                $cn = count($checks);
                for ($ci = 0; $ci < $cn; $ci++) {
                    $c = $checks[$ci];
                    $cid = isset($c['id']) ? $c['id'] : ('pad-' . $c['name']);
                    echo '<div class="form-check">';
                    echo '<input class="form-check-input" type="checkbox" name="' . audita_h($c['name']) . '" id="' . audita_h($cid) . '" value="1"';
                    if (!empty($c['checked'])) {
                        echo ' checked';
                    }
                    echo '>';
                    echo '<label class="form-check-label small" for="' . audita_h($cid) . '">' . audita_h($c['caption']) . '</label>';
                    echo '</div>';
                }
                echo '</div>';
            }
            ?>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <?php
                $bi = 0;
                $bn = count($buttons);
                for ($bi = 0; $bi < $bn; $bi++) {
                    $b = $buttons[$bi];
                    $bid = isset($b['id']) ? $b['id'] : ('pad-btn-' . $b['boton']);
                    $cls = !empty($b['primary']) ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline-secondary';
                    if (!empty($b['salir'])) {
                        $cls = 'btn btn-sm btn-outline-danger';
                    }
                    $hide = !empty($b['hidden']) ? ' d-none' : '';
                    echo '<button type="button" class="' . $cls . $hide . ' pad-btn" id="' . audita_h($bid) . '"';
                    echo ' data-boton="' . audita_h($b['boton']) . '"';
                    echo ' data-salir="' . (!empty($b['salir']) ? '1' : '0') . '">';
                    echo audita_h(audita_caption($b['caption']));
                    echo '</button>';
                }
                ?>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    var $ = window.jQuery;
    if (!$) return;
    var CSRF = <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE) ?>;
    var SLUG = <?= json_encode($slug, JSON_UNESCAPED_UNICODE) ?>;

    function apiUrl(action) {
        return 'index.php?route=' + encodeURIComponent(SLUG) + '&action=' + encodeURIComponent(action);
    }
    function showAlert(msg, ok) {
        var el = $('#pad-alert');
        el.removeClass('d-none alert-success alert-danger')
            .addClass(ok ? 'alert-success' : 'alert-danger')
            .text(msg);
    }

    $('#pad-check1').on('change', function () {
        var on = this.checked;
        $('#pad-periodo').prop('disabled', !on);
        $('#pad-btn-enviar').toggleClass('d-none', on);
        $('#pad-btn-enviados').toggleClass('d-none', !on);
    });

    $('.pad-btn').on('click', function () {
        if ($(this).attr('data-salir') === '1') {
            window.location.href = 'index.php?route=dashboard';
            return;
        }
        var data = {};
        $('#form-pad').serializeArray().forEach(function (x) { data[x.name] = x.value; });
        $('#form-pad input[type=checkbox]').each(function () {
            data[this.name] = this.checked ? 1 : 0;
        });
        if ($('#pad-periodo').length && $('#pad-periodo').prop('disabled')) {
            data.periodo = $('#pad-periodo').val();
        }
        data.boton = $(this).attr('data-boton');
        data.csrf_token = CSRF;
        $.ajax({
            url: apiUrl('accion'),
            method: 'POST',
            data: data,
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF }
        }).done(function (res) {
            showAlert((res && res.error) || 'Acción no migrada.', false);
        }).fail(function () {
            showAlert('Error de comunicación.', false);
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../views/layouts/footer.php'; ?>
