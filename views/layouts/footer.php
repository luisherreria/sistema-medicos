</div><!-- /.main-content -->

<!-- ═══════════════ RELOJ FLOTANTE FOOTER ══════════════════════════════ -->
<div id="floating-clock" title="Fecha y hora actual">
    <i class="fa-solid fa-clock"></i>
    <span id="clock-display">--:--:-- | Cargando...</span>
</div>
<!-- ════════════════════════════════════════════════════════════════════ -->

<!-- jQuery 3 -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/sistema-medicos/public/js/validaciones.js"></script>
<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables 1.13 -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<!-- DataTables Buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<!-- JSZip (para Excel) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<!-- pdfmake (para PDF) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<!-- Botones de exportación -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<!-- Grid Helper reutilizable -->
<script src="/sistema-medicos/public/js/grid-helper.js"></script>
<!-- Exportación global: SheetJS + html2pdf + helper -->
<script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="/sistema-medicos/public/js/exportador.js"></script>

<style>
    /* ── Reloj flotante ─────────────────────────────────────────────────── */
    #floating-clock {
        position: fixed;
        bottom: 0;
        right: 0;
        background: linear-gradient(135deg, #0d47a1, #1565c0);
        color: #ffffff;
        padding: 6px 16px 6px 12px;
        font-size: 0.78rem;
        font-weight: 600;
        border-radius: 8px 0 0 0;
        box-shadow: -2px -2px 10px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        gap: 7px;
        z-index: 1055;
        letter-spacing: 0.3px;
        white-space: nowrap;
        cursor: default;
        user-select: none;
    }

    #floating-clock i {
        font-size: 0.85rem;
        opacity: 0.85;
    }
</style>

<script>
    /* ══════════════════════════════════════════════════════════════════════
     * TELEGRAM BADGE POLLING
     * Actualiza el contador de mensajes no leídos en el ícono de Telegram
     * de la barra superior cada 5 segundos (AJAX liviano).
     * ════════════════════════════════════════════════════════════════════ */
    (function () {
        var $badge = document.getElementById('telegram-unread-badge');
        if (!$badge) return;

        /**
         * Actualiza el badge con el valor N.
         * Exportada como window.updateTelegramBadge para que la vista de
         * chat también pueda llamarla tras leer mensajes.
         */
        window.updateTelegramBadge = function (n) {
            n = parseInt(n, 10) || 0;
            if (n > 0) {
                $badge.textContent    = n > 99 ? '99+' : n;
                $badge.style.display  = 'block';
            } else {
                $badge.style.display  = 'none';
            }
        };

        function fetchUnread() {
            $.ajax({
                url:      'index.php?route=chat&action=unread',
                method:   'GET',
                dataType: 'json',
                headers:  { 'X-Requested-With': 'XMLHttpRequest' },
            })
            .done(function (res) {
                if (res && typeof res.unread !== 'undefined') {
                    window.updateTelegramBadge(res.unread);
                }
            });
        }

        // Primera llamada al cargar la página
        fetchUnread();
        // Polling cada 5 segundos
        setInterval(fetchUnread, 5000);
    })();
</script>

<script>
    /* ══════════════════════════════════════════════════════════════════════
     * RELOJ DINÁMICO — actualiza cada segundo en formato:
     *   HH:MM:SS | Día, DD de Mes de YYYY
     * ══════════════════════════════════════════════════════════════════════ */
    (function () {
        const dias  = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
        const meses = ['enero','febrero','marzo','abril','mayo','junio',
                       'julio','agosto','septiembre','octubre','noviembre','diciembre'];
        const el    = document.getElementById('clock-display');

        function tick() {
            const now = new Date();
            const hh  = String(now.getHours()).padStart(2, '0');
            const mm  = String(now.getMinutes()).padStart(2, '0');
            const ss  = String(now.getSeconds()).padStart(2, '0');
            const dia = dias[now.getDay()];
            const d   = now.getDate();
            const mes = meses[now.getMonth()];
            const yr  = now.getFullYear();
            el.textContent = `${hh}:${mm}:${ss} | ${dia}, ${d} de ${mes} de ${yr}`;
        }

        tick();
        setInterval(tick, 1000);
    })();
</script>

</body>
</html>
