<?php
/**
 * views/prestadores/_modal_practicas.php
 * Modal: Prácticas del Prestador (tabla pracespe)
 * Botones: Agregar · Borrar seleccionados · Borrar grupo · Imprimir
 */
$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<!-- ═══════════ MODAL PRINCIPAL: PRÁCTICAS DEL PRESTADOR ═══════════ -->
<div class="modal fade" id="modalPracticas" tabindex="-1"
     aria-labelledby="modalPracticasLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
<div class="modal-content" style="border-radius:12px; overflow:hidden;">

    <!-- Header -->
    <div class="modal-header py-2 px-4"
         style="background:linear-gradient(90deg,#1b5e20,#388e3c); color:#fff; border-bottom:none;">
        <div class="d-flex align-items-center gap-3">
            <div style="width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,.2);
                        display:flex;align-items:center;justify-content:center;">
                <i class="fa-solid fa-stethoscope"></i>
            </div>
            <div>
                <h6 class="modal-title mb-0 fw-bold" id="modalPracticasLabel">Prácticas del Prestador</h6>
                <small style="opacity:.85; font-size:.73rem;">
                    Prestador: <span id="prac-modal-nombre" class="fw-semibold">—</span>
                    &nbsp;|&nbsp; Código: <code id="prac-modal-codigo" style="color:#a5d6a7; font-size:.8rem;">—</code>
                </small>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
    </div>

    <!-- Body -->
    <div class="modal-body p-0">

        <!-- Toolbar -->
        <div class="d-flex align-items-center gap-2 flex-wrap px-3 py-2"
             style="background:#f0f7f1; border-bottom:1px solid #c8e6c9;">

            <!-- Busqueda -->
            <div class="input-group input-group-sm" style="max-width:240px;">
                <span class="input-group-text bg-white"><i class="fa-solid fa-search text-muted" style="font-size:.75rem;"></i></span>
                <input type="text" id="prac-busqueda" class="form-control"
                       placeholder="Buscar práctica…" autocomplete="off" style="font-size:.8rem;">
            </div>

            <span id="prac-total-badge" class="badge bg-success bg-opacity-75" style="font-size:.72rem;">—</span>

            <div class="ms-auto d-flex gap-1 flex-wrap">
                <button id="prac-btn-agregar" class="btn btn-sm btn-success" style="font-size:.78rem;">
                    <i class="fa-solid fa-plus me-1"></i>Agregar
                </button>
                <button id="prac-btn-borrar" class="btn btn-sm btn-danger" style="font-size:.78rem;" disabled>
                    <i class="fa-solid fa-trash me-1"></i>Borrar sel.
                </button>
                <button id="prac-btn-borrar-grupo" class="btn btn-sm btn-warning text-dark" style="font-size:.78rem;">
                    <i class="fa-solid fa-layer-group me-1"></i>Borrar grupo
                </button>
                <button id="prac-btn-imprimir" class="btn btn-sm btn-outline-secondary" style="font-size:.78rem;">
                    <i class="fa-solid fa-print me-1"></i>Imprimir
                </button>
            </div>
        </div>

        <!-- Spinner -->
        <div id="prac-loading" class="text-center py-5">
            <div class="spinner-border text-success spinner-border-sm" role="status"></div>
            <p class="text-muted mt-2 mb-0" style="font-size:.82rem;">Cargando prácticas…</p>
        </div>

        <!-- Tabla -->
        <div id="prac-table-wrap" style="display:none;">
            <div class="table-responsive" style="max-height:420px; overflow-y:auto;">
                <table class="table table-hover table-bordered table-sm mb-0"
                       id="tbl-pracespe"
                       style="font-size:.76rem; white-space:nowrap;">
                    <thead style="position:sticky; top:0; z-index:1;">
                        <tr style="background:#e8f5e9; font-size:.72rem;">
                            <th class="px-2 py-1 text-center" style="width:32px;">
                                <input type="checkbox" id="prac-chk-all" title="Seleccionar todos">
                            </th>
                            <th class="px-2 py-1" style="width:90px;">Código</th>
                            <th class="px-2 py-1">Nombre Práctica</th>
                            <th class="px-2 py-1" style="width:90px;">Grupo</th>
                            <th class="px-2 py-1" style="width:50px;">Tipo</th>
                            <th class="px-2 py-1 text-end" style="width:100px;">Importe</th>
                            <th class="px-2 py-1 text-center" style="width:90px;">Desde</th>
                            <th class="px-2 py-1 text-center" style="width:90px;">Hasta</th>
                        </tr>
                    </thead>
                    <tbody id="prac-tbody"></tbody>
                </table>
            </div>

            <div id="prac-empty" class="text-center py-5" style="display:none;">
                <i class="fa-solid fa-stethoscope fa-2x text-muted mb-2 d-block" style="opacity:.3;"></i>
                <p class="text-muted mb-0" style="font-size:.85rem;">No se encontraron prácticas.</p>
            </div>
        </div>

        <!-- Error -->
        <div id="prac-error" class="text-center py-5" style="display:none;">
            <i class="fa-solid fa-triangle-exclamation fa-2x text-warning mb-2 d-block"></i>
            <p id="prac-error-msg" class="text-muted mb-0" style="font-size:.85rem;"></p>
        </div>

        <!-- Paginación -->
        <div id="prac-footer"
             class="d-flex align-items-center justify-content-between px-3 py-2 border-top"
             style="background:#f8fdf9; font-size:.78rem; display:none !important;">
            <span id="prac-footer-info" class="text-muted">—</span>
            <ul class="pagination pagination-sm mb-0" id="prac-pagination"></ul>
        </div>
    </div><!-- /.modal-body -->

    <!-- Footer -->
    <div class="modal-footer py-2" style="background:#f0f7f1; border-top:1px solid #c8e6c9;">
        <span class="text-muted me-auto" style="font-size:.75rem;">
            <i class="fa-solid fa-circle-info me-1"></i>Prácticas habilitadas para el prestador
        </span>
        <button type="button" class="btn btn-sm btn-outline-secondary"
                data-bs-dismiss="modal">Cerrar</button>
    </div>

</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<!-- ═══════════ MINI-MODAL: AGREGAR PRÁCTICA ═══════════ -->
<div class="modal fade" id="modalPracAgregarPr" tabindex="-1"
     data-bs-backdrop="static" data-bs-keyboard="false">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content">
    <div class="modal-header py-2" style="background:#2e7d32; color:#fff;">
        <h6 class="modal-title mb-0 fw-bold">
            <i class="fa-solid fa-plus-circle me-2"></i>Agregar Práctica
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <div class="input-group input-group-sm mb-2">
            <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
            <input type="text" id="prac-add-busqueda" class="form-control"
                   placeholder="Buscar por código o nombre…" autocomplete="off">
            <button id="prac-add-buscar-btn" class="btn btn-outline-success btn-sm">Buscar</button>
        </div>
        <div id="prac-add-resultados" style="max-height:280px; overflow-y:auto;">
            <p class="text-muted text-center small mt-3">Ingrese al menos 2 caracteres para buscar.</p>
        </div>
        <div id="prac-add-seleccionada" class="mt-2 p-2 rounded border border-success bg-light" style="display:none; font-size:.82rem;">
            <strong>Seleccionada:</strong>
            <span id="prac-add-sel-codigo" class="font-monospace text-success me-2"></span>
            <span id="prac-add-sel-nombre"></span>
        </div>
    </div>
    <div class="modal-footer py-2">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button id="prac-add-confirmar-btn" class="btn btn-sm btn-success" disabled>
            <i class="fa-solid fa-plus me-1"></i>Agregar
        </button>
    </div>
</div>
</div>
</div>

<!-- ═══════════ MINI-MODAL: BORRAR GRUPO ═══════════ -->
<div class="modal fade" id="modalPracBorrarGrupo" tabindex="-1"
     data-bs-backdrop="static" data-bs-keyboard="false">
<div class="modal-dialog modal-sm modal-dialog-centered">
<div class="modal-content">
    <div class="modal-header py-2" style="background:#b71c1c; color:#fff;">
        <h6 class="modal-title mb-0 fw-bold">
            <i class="fa-solid fa-layer-group me-2"></i>Borrar Grupo
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <label class="form-label small fw-semibold">Seleccionar grupo a borrar:</label>
        <select id="prac-bor-grp-sel" class="form-select form-select-sm">
            <option value="">— seleccione —</option>
        </select>
        <p class="text-danger small mt-2 mb-0">
            <i class="fa-solid fa-triangle-exclamation me-1"></i>
            Se borrarán TODAS las prácticas del grupo seleccionado.
        </p>
    </div>
    <div class="modal-footer py-2">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button id="prac-bor-grp-confirmar" class="btn btn-sm btn-danger" disabled>
            <i class="fa-solid fa-trash me-1"></i>Borrar grupo
        </button>
    </div>
</div>
</div>
</div>

<script>
(function () {
    'use strict';

    var codigoPrestador = '';
    var POR_PAG   = 100;
    var state     = { pagina:1, total:0, paginas:1, busqueda:'', cargando:false };
    var _selCods  = new Set();   // prácticas con checkbox marcado
    var _addSel   = null;        // práctica seleccionada en mini-modal Agregar

    var CSRF = <?= json_encode($csrfToken) ?>;

    /* ── helpers ────────────────────────────────────────────────────── */
    function esc(s){ var d=document.createElement('div'); d.appendChild(document.createTextNode(s||'')); return d.innerHTML; }
    function fmtFecha(s){ if(!s||s==='0000-00-00'||s===''){ return '—'; }
        var d=new Date(s); if(isNaN(d)) return s;
        return ('0'+d.getDate()).slice(-2)+'/'+('0'+(d.getMonth()+1)).slice(-2)+'/'+d.getFullYear(); }
    function fmtImp(v){ var n=parseFloat(v||0); return isNaN(n)?'—':'$ '+n.toLocaleString('es-AR',{minimumFractionDigits:2}); }

    function mostrarLoading(){ document.getElementById('prac-loading').style.display='';
        document.getElementById('prac-table-wrap').style.display='none';
        document.getElementById('prac-error').style.display='none'; }
    function mostrarError(msg){ document.getElementById('prac-loading').style.display='none';
        document.getElementById('prac-table-wrap').style.display='none';
        document.getElementById('prac-error').style.display='';
        document.getElementById('prac-error-msg').textContent=msg; }

    function actualizarBtnBorrar(){
        document.getElementById('prac-btn-borrar').disabled = (_selCods.size===0);
    }

    /* ── Abrir modal ────────────────────────────────────────────────── */
    window.abrirModalPracticas = function(codigo, nombre){
        codigoPrestador = codigo;
        _selCods.clear();
        document.getElementById('prac-modal-codigo').textContent = codigo;
        document.getElementById('prac-modal-nombre').textContent = nombre;
        document.getElementById('prac-busqueda').value = '';
        actualizarBtnBorrar();
        state.pagina=1; state.busqueda='';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPracticas')).show();
        cargar(1);
    };

    /* ── Carga principal ────────────────────────────────────────────── */
    function cargar(pagina){
        if(state.cargando) return;
        state.cargando=true; state.pagina=pagina||1;
        state.busqueda = document.getElementById('prac-busqueda').value.trim();
        mostrarLoading();
        _selCods.clear(); actualizarBtnBorrar();
        document.getElementById('prac-chk-all').checked=false;

        var qs = 'route=prestadores&action=pracespe_listar'
               + '&codigo='    + encodeURIComponent(codigoPrestador)
               + '&busqueda='  + encodeURIComponent(state.busqueda)
               + '&pagina='    + state.pagina
               + '&por_pagina='+ POR_PAG;

        fetch('index.php?'+qs, {headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(function(r){ return r.json(); })
        .then(function(res){
            state.cargando=false;
            if(!res.ok){ mostrarError(res.error||'Error'); return; }
            state.total   = res.total  ||0;
            state.paginas = res.paginas||1;
            document.getElementById('prac-total-badge').textContent = state.total+' prácticas';
            renderTabla(res.datos||[]);
            renderPaginacion();
        })
        .catch(function(){ state.cargando=false; mostrarError('Error de comunicación.'); });
    }

    function renderTabla(rows){
        var tbody = document.getElementById('prac-tbody');
        tbody.innerHTML='';
        document.getElementById('prac-loading').style.display='none';
        document.getElementById('prac-error').style.display='none';
        document.getElementById('prac-table-wrap').style.display='';

        if(!rows.length){
            document.getElementById('prac-empty').style.display='';
            document.getElementById('tbl-pracespe').style.display='none';
            return;
        }
        document.getElementById('prac-empty').style.display='none';
        document.getElementById('tbl-pracespe').style.display='';

        rows.forEach(function(r,i){
            var cod = (r.PEPRACTICA||'').trim();
            var tr  = document.createElement('tr');
            tr.style.background = i%2===0?'#fff':'#f1f8f2';
            tr.innerHTML =
                '<td class="px-2 py-1 text-center">'
                    +'<input type="checkbox" class="prac-chk" data-cod="'+esc(cod)+'" '
                    +(_selCods.has(cod)?'checked':'')+' style="cursor:pointer;">'
                +'</td>'
                +'<td class="px-2 py-1 font-monospace fw-semibold text-success">'+esc(cod)+'</td>'
                +'<td class="px-2 py-1">'+esc(r.PENOMBPRAC||'')+'</td>'
                +'<td class="px-2 py-1 text-center">'
                    +(r.PEGRUPO?'<span class="badge bg-light border text-dark" style="font-size:.69rem;">'+esc(r.PEGRUPO)+'</span>':'<span class="text-muted">—</span>')
                +'</td>'
                +'<td class="px-2 py-1 text-center"><small class="text-muted">'+esc(r.PETIPO||'—')+'</small></td>'
                +'<td class="px-2 py-1 text-end font-monospace">'+fmtImp(r.PEIMPORTE)+'</td>'
                +'<td class="px-2 py-1 text-center">'+fmtFecha(r.PEDESDE)+'</td>'
                +'<td class="px-2 py-1 text-center">'+fmtFecha(r.PEHASTA)+'</td>';
            tbody.appendChild(tr);
        });

        // Checkboxes individuales
        tbody.querySelectorAll('.prac-chk').forEach(function(chk){
            chk.addEventListener('change', function(){
                if(this.checked) _selCods.add(this.dataset.cod);
                else             _selCods.delete(this.dataset.cod);
                actualizarBtnBorrar();
                document.getElementById('prac-chk-all').checked =
                    (tbody.querySelectorAll('.prac-chk').length === _selCods.size);
            });
        });

        var footer=document.getElementById('prac-footer');
        footer.style.removeProperty('display'); footer.style.display='';
    }

    // Checkbox "todos"
    document.getElementById('prac-chk-all').addEventListener('change', function(){
        var chks = document.getElementById('prac-tbody').querySelectorAll('.prac-chk');
        chks.forEach(function(c){
            c.checked = this.checked;
            if(this.checked) _selCods.add(c.dataset.cod);
            else             _selCods.delete(c.dataset.cod);
        }, this);
        actualizarBtnBorrar();
    });

    function renderPaginacion(){
        var $pag  = document.getElementById('prac-pagination');
        var $info = document.getElementById('prac-footer-info');
        var desde=(state.pagina-1)*POR_PAG+1, hasta=Math.min(state.pagina*POR_PAG, state.total);
        $info.textContent='Mostrando '+desde+'–'+hasta+' de '+state.total;
        $pag.innerHTML='';
        if(state.paginas<=1){ document.getElementById('prac-footer').style.display='none'; return; }
        function addBtn(lbl,pg,dis,act){
            var li=document.createElement('li');
            li.className='page-item'+(dis?' disabled':'')+(act?' active':'');
            var a=document.createElement('a'); a.className='page-link'; a.href='#';
            a.innerHTML=lbl;
            if(pg&&!dis&&!act) a.addEventListener('click',function(e){e.preventDefault();cargar(pg);});
            li.appendChild(a); $pag.appendChild(li);
        }
        addBtn('&laquo;', state.pagina>1?state.pagina-1:null, state.pagina<=1);
        var s=Math.max(1,state.pagina-3), e=Math.min(state.paginas,s+6);
        for(var p=s;p<=e;p++) addBtn(p,p,false,p===state.pagina);
        addBtn('&raquo;', state.pagina<state.paginas?state.pagina+1:null, state.pagina>=state.paginas);
    }

    // Búsqueda con debounce
    var _debTimer;
    document.getElementById('prac-busqueda').addEventListener('input', function(){
        clearTimeout(_debTimer);
        _debTimer = setTimeout(function(){ cargar(1); }, 400);
    });

    /* ── BORRAR SELECCIONADOS ───────────────────────────────────────── */
    document.getElementById('prac-btn-borrar').addEventListener('click', function(){
        if(_selCods.size===0) return;
        if(!confirm('¿Borrar '+_selCods.size+' práctica(s) seleccionada(s)?')) return;
        fetch('index.php?route=prestadores&action=pracespe_borrar', {
            method:'POST',
            headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify({csrf_token:CSRF, codigo:codigoPrestador, practicas:Array.from(_selCods)})
        }).then(function(r){ return r.json(); })
        .then(function(res){
            if(!res.ok){ alert('Error: '+(res.error||'')); return; }
            _selCods.clear(); actualizarBtnBorrar();
            cargar(state.pagina);
            if(window.recargarGrillaPrestadores) window.recargarGrillaPrestadores();
        })
        .catch(function(){ alert('Error de comunicación.'); });
    });

    /* ── BORRAR GRUPO ───────────────────────────────────────────────── */
    document.getElementById('prac-btn-borrar-grupo').addEventListener('click', function(){
        // Cargar grupos disponibles
        fetch('index.php?route=prestadores&action=pracespe_grupos&codigo='+encodeURIComponent(codigoPrestador),
              {headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(function(r){ return r.json(); })
        .then(function(res){
            if(!res.ok){ alert('Error al cargar grupos.'); return; }
            var sel = document.getElementById('prac-bor-grp-sel');
            sel.innerHTML='<option value="">— seleccione —</option>';
            (res.grupos||[]).forEach(function(g){
                var o=document.createElement('option'); o.value=g.grupo; o.textContent=g.grupo;
                sel.appendChild(o);
            });
            document.getElementById('prac-bor-grp-confirmar').disabled=true;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPracBorrarGrupo')).show();
        });
    });
    document.getElementById('prac-bor-grp-sel').addEventListener('change', function(){
        document.getElementById('prac-bor-grp-confirmar').disabled = (this.value==='');
    });
    document.getElementById('prac-bor-grp-confirmar').addEventListener('click', function(){
        var grupo = document.getElementById('prac-bor-grp-sel').value;
        if(!grupo) return;
        if(!confirm('¿Borrar TODAS las prácticas del grupo «'+grupo+'»?')) return;
        fetch('index.php?route=prestadores&action=pracespe_borrar_grupo', {
            method:'POST',
            headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify({csrf_token:CSRF, codigo:codigoPrestador, grupo:grupo})
        }).then(function(r){ return r.json(); })
        .then(function(res){
            bootstrap.Modal.getInstance(document.getElementById('modalPracBorrarGrupo')).hide();
            if(!res.ok){ alert('Error: '+(res.error||'')); return; }
            alert('Se borraron '+res.borrados+' prácticas del grupo «'+grupo+'».');
            cargar(1);
            if(window.recargarGrillaPrestadores) window.recargarGrillaPrestadores();
        })
        .catch(function(){ alert('Error de comunicación.'); });
    });

    /* ── IMPRIMIR ───────────────────────────────────────────────────── */
    document.getElementById('prac-btn-imprimir').addEventListener('click', function(){
        fetch('index.php?route=prestadores&action=pracespe_imprimir&codigo='+encodeURIComponent(codigoPrestador),
              {headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(function(r){ return r.json(); })
        .then(function(res){
            if(!res.ok){ alert('Error al cargar datos.'); return; }
            var win = window.open('','_blank','width=900,height=700');
            var rows = (res.datos||[]).map(function(r){
                return '<tr><td>'+esc(r.PEPRACTICA)+'</td>'
                       +'<td>'+esc(r.PENOMBPRAC)+'</td>'
                       +'<td>'+esc(r.PEGRUPO)+'</td>'
                       +'<td>'+esc(r.PETIPO)+'</td>'
                       +'<td style="text-align:right">'+fmtImp(r.PEIMPORTE)+'</td>'
                       +'<td>'+esc(r.PEDESDE)+'</td>'
                       +'<td>'+esc(r.PEHASTA)+'</td></tr>';
            }).join('');
            win.document.write(
                '<!DOCTYPE html><html><head><meta charset="UTF-8">'
                +'<title>Prácticas – '+esc(res.prestNombre)+'</title>'
                +'<style>body{font-family:Arial,sans-serif;font-size:11px;margin:16px}'
                +'h2{font-size:13px;margin-bottom:4px}'
                +'table{border-collapse:collapse;width:100%}'
                +'th,td{border:1px solid #ccc;padding:3px 6px}'
                +'th{background:#2e7d32;color:#fff}'
                +'tr:nth-child(even){background:#f1f8f2}'
                +'</style></head><body>'
                +'<h2>Prácticas del Prestador: '+esc(res.prestNombre)+'</h2>'
                +'<p style="font-size:10px;color:#555;margin:0 0 8px">Código: '+esc(codigoPrestador)+'</p>'
                +'<table><thead><tr>'
                +'<th>Código</th><th>Nombre Práctica</th><th>Grupo</th><th>Tipo</th>'
                +'<th>Importe</th><th>Desde</th><th>Hasta</th>'
                +'</tr></thead><tbody>'+rows+'</tbody></table>'
                +'<p style="font-size:9px;color:#999;margin-top:8px">Total: '+(res.datos||[]).length+' prácticas</p>'
                +'</body></html>'
            );
            win.document.close(); win.print();
        })
        .catch(function(){ alert('Error de comunicación.'); });
    });

    /* ── AGREGAR PRÁCTICA ───────────────────────────────────────────── */
    document.getElementById('prac-btn-agregar').addEventListener('click', function(){
        _addSel=null;
        document.getElementById('prac-add-busqueda').value='';
        document.getElementById('prac-add-resultados').innerHTML='<p class="text-muted text-center small mt-3">Ingrese al menos 2 caracteres para buscar.</p>';
        document.getElementById('prac-add-seleccionada').style.display='none';
        document.getElementById('prac-add-confirmar-btn').disabled=true;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPracAgregarPr')).show();
    });

    document.getElementById('prac-add-buscar-btn').addEventListener('click', buscarCatalogo);
    document.getElementById('prac-add-busqueda').addEventListener('keydown', function(e){
        if(e.key==='Enter') buscarCatalogo();
    });

    function buscarCatalogo(){
        var q = document.getElementById('prac-add-busqueda').value.trim();
        if(q.length<2){ return; }
        var div = document.getElementById('prac-add-resultados');
        div.innerHTML='<p class="text-center small text-muted mt-2"><i class="fa-solid fa-spinner fa-spin me-1"></i>Buscando…</p>';
        fetch('index.php?route=prestadores&action=pracespe_buscar_catalogo&q='+encodeURIComponent(q),
              {headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(function(r){ return r.json(); })
        .then(function(res){
            if(!res.ok||!res.data.length){
                div.innerHTML='<p class="text-muted text-center small mt-2">Sin resultados.</p>'; return;
            }
            var html='<table class="table table-hover table-sm mb-0" style="font-size:.76rem;">'
                     +'<thead><tr style="background:#e8f5e9;">'
                     +'<th class="px-2 py-1">Código</th><th class="px-2 py-1">Nombre</th>'
                     +'<th class="px-2 py-1">Grupo</th><th class="px-2 py-1 text-end">Importe</th>'
                     +'</tr></thead><tbody>';
            res.data.forEach(function(r){
                html+='<tr style="cursor:pointer;" class="prac-cat-row"'
                    +' data-cod="'+esc((r.PEPRACTICA||'').trim())+'"'
                    +' data-nom="'+esc((r.PENOMBPRAC||'').trim())+'"'
                    +' data-grp="'+esc((r.PEGRUPO||'').trim())+'"'
                    +' data-tipo="'+esc((r.PETIPO||'').trim())+'"'
                    +' data-imp="'+esc(String(r.PEIMPORTE||'0'))+'">'
                    +'<td class="px-2 py-1 font-monospace text-success">'+esc((r.PEPRACTICA||'').trim())+'</td>'
                    +'<td class="px-2 py-1">'+esc((r.PENOMBPRAC||'').trim())+'</td>'
                    +'<td class="px-2 py-1">'+esc((r.PEGRUPO||'').trim())+'</td>'
                    +'<td class="px-2 py-1 text-end">'+fmtImp(r.PEIMPORTE)+'</td>'
                    +'</tr>';
            });
            html+='</tbody></table>';
            div.innerHTML=html;
            div.querySelectorAll('.prac-cat-row').forEach(function(tr){
                tr.addEventListener('click', function(){
                    div.querySelectorAll('.prac-cat-row').forEach(function(t){ t.style.background=''; });
                    this.style.background='#c8e6c9';
                    _addSel = { PEPRACTICA:this.dataset.cod, PENOMBPRAC:this.dataset.nom,
                                PEGRUPO:this.dataset.grp, PETIPO:this.dataset.tipo, PEIMPORTE:this.dataset.imp };
                    document.getElementById('prac-add-sel-codigo').textContent=_addSel.PEPRACTICA;
                    document.getElementById('prac-add-sel-nombre').textContent=_addSel.PENOMBPRAC;
                    document.getElementById('prac-add-seleccionada').style.display='';
                    document.getElementById('prac-add-confirmar-btn').disabled=false;
                });
            });
        })
        .catch(function(){ div.innerHTML='<p class="text-danger text-center small mt-2">Error de comunicación.</p>'; });
    }

    document.getElementById('prac-add-confirmar-btn').addEventListener('click', function(){
        if(!_addSel) return;
        // Verificar que no exista ya
        fetch('index.php?route=prestadores&action=pracespe_buscar_catalogo&q='+encodeURIComponent(_addSel.PEPRACTICA),
              {headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(function(){ })
        .catch(function(){ });

        // INSERT via ruta dedicada (si no existe agregar)
        fetch('index.php?route=prestadores&action=pracespe_agregar', {
            method:'POST',
            headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify({csrf_token:CSRF, codigo:codigoPrestador,
                                  pepractica:_addSel.PEPRACTICA, penombprac:_addSel.PENOMBPRAC,
                                  pegrupo:_addSel.PEGRUPO, petipo:_addSel.PETIPO, peimporte:_addSel.PEIMPORTE})
        }).then(function(r){ return r.json(); })
        .then(function(res){
            bootstrap.Modal.getInstance(document.getElementById('modalPracAgregarPr')).hide();
            if(!res.ok){ alert('Error: '+(res.error||'')); return; }
            cargar(state.pagina);
            if(window.recargarGrillaPrestadores) window.recargarGrillaPrestadores();
        })
        .catch(function(){ alert('Error de comunicación.'); });
    });

})();
</script>
