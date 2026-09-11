<?php
/**
 * views/chat/index.php
 * Vista principal del módulo Chat de Telegram.
 * Incluida entre header.php y footer.php por ChatController::index().
 *
 * Variables disponibles:
 *   $chats  array  Lista de chats con metadatos (getChats()).
 *   $user   array  Usuario de sesión actual.
 */
?>

<!-- ══════════════════════════════════════════════════════════════════════════
     ESTILOS ESPECÍFICOS DEL CHAT
     ════════════════════════════════════════════════════════════════════════ -->
<style>
    /* Neutralizar padding del main-content para chat full-height */
    .main-content { padding: 0 !important; }

    /* ── Contenedor raíz del chat ───────────────────────────────────────── */
    #chat-app {
        display: flex;
        height: calc(100vh - 93px); /* navbar 56px + sub-header ~37px */
        background: #e5ddd5;
        overflow: hidden;
        font-family: 'Segoe UI', system-ui, sans-serif;
    }

    /* ══════════════════════════════════════════════════════════════════════
       PANEL IZQUIERDO — Lista de conversaciones
       ════════════════════════════════════════════════════════════════════ */
    #chat-sidebar {
        width: 330px;
        min-width: 260px;
        display: flex;
        flex-direction: column;
        background: #fff;
        border-right: 1px solid #dde3ea;
        flex-shrink: 0;
    }

    /* Cabecera del panel */
    #sidebar-header {
        background: linear-gradient(135deg, #0d47a1, #1565c0);
        color: #fff;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }

    #sidebar-header h6 {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
    }

    #sidebar-header small {
        font-size: 0.72rem;
        opacity: 0.8;
    }

    /* Barra de búsqueda */
    #sidebar-search {
        padding: 8px 12px;
        background: #f0f3f7;
        border-bottom: 1px solid #e5eaf2;
        flex-shrink: 0;
    }

    #sidebar-search input {
        width: 100%;
        border: 1px solid #cdd4dc;
        border-radius: 20px;
        padding: 6px 14px 6px 36px;
        font-size: 0.82rem;
        background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E") no-repeat 12px center;
        outline: none;
        transition: border-color 0.2s;
    }

    #sidebar-search input:focus {
        border-color: #1565c0;
    }

    /* Lista de chats */
    #chat-list {
        flex: 1;
        overflow-y: auto;
    }

    .chat-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        cursor: pointer;
        border-bottom: 1px solid #f0f2f5;
        transition: background 0.15s;
        position: relative;
    }

    .chat-item:hover   { background: #f5f7fa; }
    .chat-item.active  { background: #dbeafe; }
    .chat-item.active  .chat-item-name { color: #0d47a1; font-weight: 700; }

    /* Avatar del chat */
    .chat-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: linear-gradient(135deg, #1565c0, #42a5f5);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
        color: #fff;
        flex-shrink: 0;
        text-transform: uppercase;
    }

    .chat-item-body     { flex: 1; overflow: hidden; }
    .chat-item-name     { font-size: 0.875rem; font-weight: 600; color: #263238; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .chat-item-preview  { font-size: 0.78rem; color: #78909c; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
    .chat-item-preview.sent { color: #a5b4bd; }

    .chat-item-meta     { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex-shrink: 0; }
    .chat-item-time     { font-size: 0.7rem; color: #90a4ae; }
    .chat-item-badge    { background: #e53935; color: #fff; font-size: 0.65rem; font-weight: 700; border-radius: 50px; padding: 1px 6px; min-width: 18px; text-align: center; }

    /* Estado vacío de la lista */
    #chat-list-empty {
        padding: 40px 20px;
        text-align: center;
        color: #90a4ae;
        font-size: 0.82rem;
    }

    /* ══════════════════════════════════════════════════════════════════════
       PANEL DERECHO — Conversación activa
       ════════════════════════════════════════════════════════════════════ */
    #chat-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    /* Cabecera de la conversación */
    #chat-header {
        background: #fff;
        border-bottom: 1px solid #e0e7ef;
        padding: 10px 18px;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
        min-height: 58px;
    }

    #chat-header .chat-avatar { width: 38px; height: 38px; font-size: 0.88rem; }
    #chat-contact-name  { font-size: 0.94rem; font-weight: 700; color: #263238; }
    #chat-contact-sub   { font-size: 0.72rem; color: #78909c; }

    /* Estado vacío del chat principal */
    #chat-empty {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #90a4ae;
        background: #e5ddd5;
    }

    #chat-empty i { font-size: 3.5rem; margin-bottom: 12px; opacity: 0.5; }
    #chat-empty p { margin: 0; font-size: 0.9rem; }

    /* Área de mensajes */
    #chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 16px 20px;
        display: flex;
        flex-direction: column;
        gap: 4px;
        background: #e5ddd5;
        /* fondo sutil tipo Telegram */
        background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23c8d5dc' fill-opacity='0.25'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }

    /* Burbujas de mensajes */
    .msg-row {
        display: flex;
        margin-bottom: 2px;
    }

    .msg-row.out { justify-content: flex-end; }
    .msg-row.in  { justify-content: flex-start; }

    .msg-bubble {
        max-width: 68%;
        padding: 7px 12px 4px;
        border-radius: 12px;
        font-size: 0.875rem;
        line-height: 1.45;
        position: relative;
        word-wrap: break-word;
    }

    .msg-row.in  .msg-bubble {
        background: #fff;
        border-radius: 0 12px 12px 12px;
        color: #263238;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    .msg-row.out .msg-bubble {
        background: #dcf8c6;
        border-radius: 12px 0 12px 12px;
        color: #263238;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    .msg-time {
        font-size: 0.67rem;
        color: #90a4ae;
        text-align: right;
        margin-top: 3px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 3px;
    }

    .msg-row.out .msg-time { color: #78909c; }

    /* Adjuntos */
    .msg-img {
        max-width: 240px;
        max-height: 200px;
        border-radius: 8px;
        display: block;
        margin-bottom: 4px;
        cursor: pointer;
    }

    .msg-doc {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        background: rgba(0,0,0,0.06);
        border-radius: 8px;
        font-size: 0.8rem;
        margin-bottom: 4px;
    }

    /* Separador de fecha */
    .date-separator {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 10px 0;
        color: #78909c;
        font-size: 0.72rem;
    }

    .date-separator::before,
    .date-separator::after {
        content: '';
        flex: 1;
        height: 1px;
        background: rgba(0,0,0,0.12);
    }

    .date-separator span {
        background: #d0dde8;
        padding: 2px 10px;
        border-radius: 10px;
        white-space: nowrap;
    }

    /* Indicador de escritura */
    #typing-indicator {
        padding: 6px 18px;
        font-size: 0.75rem;
        color: #90a4ae;
        height: 26px;
        flex-shrink: 0;
    }

    /* ── Input area ──────────────────────────────────────────────────────── */
    #chat-input-area {
        background: #f0f2f5;
        border-top: 1px solid #dde3ea;
        padding: 10px 14px;
        display: flex;
        align-items: flex-end;
        gap: 10px;
        flex-shrink: 0;
    }

    #chat-input-area textarea {
        flex: 1;
        border: 1px solid #cdd4dc;
        border-radius: 20px;
        padding: 9px 16px;
        font-size: 0.875rem;
        resize: none;
        max-height: 120px;
        min-height: 40px;
        outline: none;
        transition: border-color 0.2s;
        font-family: inherit;
        background: #fff;
        line-height: 1.4;
    }

    #chat-input-area textarea:focus { border-color: #1565c0; }
    #chat-input-area textarea::placeholder { color: #b0bec5; }

    .btn-chat-action {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s, transform 0.15s;
        flex-shrink: 0;
        font-size: 1rem;
    }

    .btn-attach {
        background: #eef2f8;
        color: #546e7a;
    }
    .btn-attach:hover { background: #dce8f5; color: #1565c0; }

    .btn-send {
        background: #1565c0;
        color: #fff;
    }
    .btn-send:hover           { background: #0d47a1; transform: scale(1.05); }
    .btn-send:disabled        { background: #b0bec5; cursor: default; transform: none; }

    /* Preview de archivo adjunto */
    #file-preview {
        display: none;
        align-items: center;
        gap: 8px;
        padding: 6px 14px;
        background: #fff;
        border-top: 1px solid #e5eaf2;
        font-size: 0.8rem;
        color: #37474f;
    }

    #file-preview.show { display: flex; }
    #file-preview img  { max-height: 48px; border-radius: 6px; }
    #file-preview .file-name { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* ── Toast de notificación ───────────────────────────────────────────── */
    #chat-toast-area {
        position: fixed;
        bottom: 60px;
        right: 20px;
        z-index: 1080;
    }

    /* ── Botón + Nuevo Contacto ──────────────────────────────────────────── */
    #btn-nuevo-contacto {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        margin: 8px 12px;
        padding: 7px 14px;
        background: #e3f2fd;
        color: #1565c0;
        border: 1px dashed #90caf9;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s, border-color 0.2s;
        text-align: center;
    }
    #btn-nuevo-contacto:hover {
        background: #bbdefb;
        border-color: #42a5f5;
    }

    /* ── Responsive: colapsar sidebar en pantallas pequeñas ──────────────── */
    @media (max-width: 768px) {
        #chat-sidebar { width: 100%; position: absolute; z-index: 10; }
        #chat-sidebar.hidden { display: none; }
        #chat-main.full { width: 100%; }
    }
</style>

<!-- ══════════════════════════════════════════════════════════════════════════
     ESTRUCTURA HTML DEL CHAT
     ════════════════════════════════════════════════════════════════════════ -->
<div id="chat-app">

    <!-- ═══════════ SIDEBAR — Lista de conversaciones ══════════════════════ -->
    <div id="chat-sidebar">

        <!-- Header del sidebar -->
        <div id="sidebar-header">
            <i class="fa-brands fa-telegram fa-lg"></i>
            <div>
                <h6>Chat Telegram</h6>
                <small>Mesa Operativa — COMEDICA</small>
            </div>
        </div>

        <!-- Búsqueda -->
        <div id="sidebar-search">
            <input type="text" id="search-chats" placeholder="Buscar conversación..." autocomplete="off">
        </div>

        <!-- Botón Nuevo Contacto -->
        <button id="btn-nuevo-contacto"
                type="button"
                data-bs-toggle="modal"
                data-bs-target="#modalNuevoContacto">
            <i class="fa-solid fa-user-plus"></i>
            Nuevo Contacto
        </button>

        <!-- Lista de chats -->
        <div id="chat-list">
            <?php if (empty($chats)): ?>
                <div id="chat-list-empty">
                    <i class="fa-brands fa-telegram fa-2x d-block mb-3" style="color:#90a4ae;"></i>
                    <p class="mb-1">No hay conversaciones aún.</p>
                    <p>Los mensajes de Telegram<br>aparecerán aquí cuando<br>el webhook esté activo.</p>
                </div>
            <?php else: ?>
                <?php foreach ($chats as $chat):
                    $inicial   = strtoupper(mb_substr($chat['NOMBRE'] ?: ($chat['USERNAME'] ?: '?'), 0, 1));
                    $nombre    = htmlspecialchars($chat['NOMBRE'] ?: ('@' . $chat['USERNAME']));
                    $preview   = '';
                    $preClass  = '';
                    if (!empty($chat['ULTIMO_MENSAJE'])) {
                        $preview  = htmlspecialchars(mb_substr($chat['ULTIMO_MENSAJE'], 0, 55));
                        $preClass = ($chat['ULTIMO_DIR'] === 'OUT') ? ' sent' : '';
                    } elseif (!empty($chat['ULTIMO_TIPO'])) {
                        $icons = ['photo'=>'📷 Foto','document'=>'📄 Documento','voice'=>'🎤 Audio','video'=>'🎥 Video'];
                        $preview = $icons[$chat['ULTIMO_TIPO']] ?? '📎 Archivo';
                    }
                    $fecha    = '';
                    if (!empty($chat['ULTIMO_FECHA'])) {
                        $ts    = strtotime($chat['ULTIMO_FECHA']);
                        $hoy   = date('Y-m-d');
                        $fecha = (date('Y-m-d', $ts) === $hoy)
                                 ? date('H:i', $ts)
                                 : date('d/m', $ts);
                    }
                    $unread = (int) $chat['NO_LEIDOS'];
                ?>
                <div class="chat-item"
                     data-chat-id="<?= (int) $chat['CHAT_ID'] ?>"
                     data-nombre="<?= $nombre ?>"
                     data-username="<?= htmlspecialchars($chat['USERNAME'] ?? '') ?>">
                    <div class="chat-avatar"><?= $inicial ?></div>
                    <div class="chat-item-body">
                        <div class="chat-item-name"><?= $nombre ?></div>
                        <?php if ($preview): ?>
                            <div class="chat-item-preview<?= $preClass ?>">
                                <?php if ($preClass === ' sent'): ?>
                                    <i class="fa-solid fa-check" style="font-size:0.65rem;color:#90a4ae;"></i>
                                <?php endif; ?>
                                <?= $preview ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="chat-item-meta">
                        <?php if ($fecha): ?><span class="chat-item-time"><?= $fecha ?></span><?php endif; ?>
                        <?php if ($unread > 0): ?><span class="chat-item-badge"><?= $unread ?></span><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div><!-- /#chat-list -->

    </div><!-- /#chat-sidebar -->

    <!-- ═══════════ MAIN — Área de conversación activa ═════════════════════ -->
    <div id="chat-main">

        <!-- Pantalla vacía cuando no hay chat seleccionado -->
        <div id="chat-empty">
            <i class="fa-brands fa-telegram"></i>
            <p>Seleccioná una conversación<br>para comenzar</p>
        </div>

        <!-- Panel de conversación (oculto hasta seleccionar chat) -->
        <div id="chat-conversation" style="display:none; flex-direction:column; height:100%;">

            <!-- Header del chat -->
            <div id="chat-header">
                <div class="chat-avatar" id="chat-header-avatar">?</div>
                <div>
                    <div id="chat-contact-name">—</div>
                    <div id="chat-contact-sub"></div>
                </div>
                <div class="ms-auto d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" id="btn-refresh-chat" title="Actualizar mensajes">
                        <i class="fa-solid fa-rotate-right"></i>
                    </button>
                </div>
            </div>

            <!-- Indicador de escritura / estado -->
            <div id="typing-indicator"></div>

            <!-- Burbujas de mensajes -->
            <div id="chat-messages">
                <!-- Cargado dinámicamente por JS -->
            </div>

            <!-- Preview de archivo adjunto pendiente de enviar -->
            <div id="file-preview" class="px-3 py-2 border-top bg-white">
                <i class="fa-solid fa-paperclip text-muted"></i>
                <span class="file-name" id="file-preview-name">archivo.pdf</span>
                <img id="file-preview-img" src="" alt="" style="display:none;">
                <button type="button" class="btn btn-sm btn-outline-danger ms-auto" id="btn-remove-file">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Área de input -->
            <div id="chat-input-area">
                <!-- Adjuntar archivo -->
                <button type="button" class="btn-chat-action btn-attach" id="btn-attach" title="Adjuntar imagen o PDF">
                    <i class="fa-solid fa-paperclip"></i>
                </button>
                <input type="file" id="file-input" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" style="display:none;">

                <!-- Textarea de texto -->
                <textarea id="msg-input"
                          placeholder="Escribe un mensaje... (Enter para enviar, Ctrl+V para pegar imagen)"
                          rows="1"></textarea>

                <!-- Enviar -->
                <button type="button" class="btn-chat-action btn-send" id="btn-send" disabled>
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>

        </div><!-- /#chat-conversation -->

    </div><!-- /#chat-main -->

</div><!-- /#chat-app -->

<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL — Nuevo Contacto externo de Telegram
     ════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalNuevoContacto" tabindex="-1"
     aria-labelledby="modalNuevoContactoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px; overflow:hidden;">

            <!-- Header del modal -->
            <div class="modal-header text-white border-0"
                 style="background:linear-gradient(135deg,#0d47a1,#1565c0); padding:16px 20px;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-user-plus fa-lg"></i>
                    <h6 class="modal-title mb-0 fw-bold" id="modalNuevoContactoLabel">
                        Nuevo Contacto de Telegram
                    </h6>
                </div>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <!-- Body del modal -->
            <div class="modal-body px-4 py-3">
                <p class="text-muted mb-3" style="font-size:0.82rem;">
                    Registrá un contacto externo para poder enviarle mensajes desde la mesa operativa.
                    El <strong>Chat ID</strong> se obtiene con el bot
                    <code>@userinfobot</code> en Telegram.
                </p>

                <form id="form-nuevo-contacto" novalidate>

                    <!-- Nombre -->
                    <div class="mb-3">
                        <label for="nc-nombre" class="form-label fw-semibold" style="font-size:0.82rem;">
                            <i class="fa-solid fa-user me-1 text-primary"></i>Nombre completo <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-sm"
                               id="nc-nombre" name="nombre"
                               placeholder="Ej: Juan Pérez"
                               maxlength="200" required>
                        <div class="invalid-feedback">El nombre es obligatorio.</div>
                    </div>

                    <!-- Chat ID -->
                    <div class="mb-3">
                        <label for="nc-chat-id" class="form-label fw-semibold" style="font-size:0.82rem;">
                            <i class="fa-brands fa-telegram me-1 text-primary"></i>Chat ID de Telegram <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-sm"
                               id="nc-chat-id" name="chat_id"
                               placeholder="Ej: 1956619586"
                               pattern="-?[0-9]+" required>
                        <div class="form-text" style="font-size:0.75rem;">
                            Número entero, puede ser negativo para grupos.
                        </div>
                        <div class="invalid-feedback">Ingresá un Chat ID numérico válido.</div>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="nc-email" class="form-label fw-semibold" style="font-size:0.82rem;">
                            <i class="fa-solid fa-envelope me-1 text-primary"></i>Correo electrónico
                            <span class="text-muted fw-normal">(opcional)</span>
                        </label>
                        <input type="email" class="form-control form-control-sm"
                               id="nc-email" name="email"
                               placeholder="contacto@ejemplo.com"
                               maxlength="200">
                        <div class="invalid-feedback">Ingresá un correo válido.</div>
                    </div>

                    <!-- Celular -->
                    <div class="mb-1">
                        <label for="nc-celular" class="form-label fw-semibold" style="font-size:0.82rem;">
                            <i class="fa-solid fa-mobile-screen-button me-1 text-primary"></i>Celular
                            <span class="text-muted fw-normal">(opcional)</span>
                        </label>
                        <input type="tel" class="form-control form-control-sm"
                               id="nc-celular" name="celular"
                               placeholder="Ej: +54 9 11 1234-5678"
                               maxlength="50">
                    </div>

                    <!-- Alerta de error inline -->
                    <div id="nc-error" class="alert alert-danger py-2 mt-3 d-none" style="font-size:0.8rem;"></div>

                </form>
            </div>

            <!-- Footer del modal -->
            <div class="modal-footer border-0 px-4 pb-4 pt-0 gap-2">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-sm btn-primary px-4" id="btn-save-contact">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Guardar Contacto
                </button>
            </div>

        </div>
    </div>
</div>

<!-- Toast de notificación -->
<div id="chat-toast-area"></div>


<!-- ══════════════════════════════════════════════════════════════════════════
     JAVASCRIPT DEL CHAT
     ════════════════════════════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    /* ── Estado ─────────────────────────────────────────────────────────── */
    let activeChatId   = null;
    let activeChatName = '';
    let lastMsgId      = 0;
    let pollTimer      = null;
    let pendingFile    = null;

    /* ── Constantes ──────────────────────────────────────────────────────── */
    const BASE_URL   = 'index.php?route=chat';
    const POLL_MS    = 3000;   // polling activo cada 3 s
    const DIAS       = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
    const MESES      = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];

    /* ── Referencias DOM ─────────────────────────────────────────────────── */
    const $chatList       = document.getElementById('chat-list');
    const $chatEmpty      = document.getElementById('chat-empty');
    const $chatConvo      = document.getElementById('chat-conversation');
    const $chatMessages   = document.getElementById('chat-messages');
    const $contactName    = document.getElementById('chat-contact-name');
    const $contactSub     = document.getElementById('chat-contact-sub');
    const $headerAvatar   = document.getElementById('chat-header-avatar');
    const $msgInput       = document.getElementById('msg-input');
    const $btnSend        = document.getElementById('btn-send');
    const $btnAttach      = document.getElementById('btn-attach');
    const $fileInput      = document.getElementById('file-input');
    const $filePreview    = document.getElementById('file-preview');
    const $filePreviewName= document.getElementById('file-preview-name');
    const $filePreviewImg = document.getElementById('file-preview-img');
    const $btnRemoveFile  = document.getElementById('btn-remove-file');
    const $btnRefresh     = document.getElementById('btn-refresh-chat');
    const $searchInput    = document.getElementById('search-chats');
    const $typingIndicator= document.getElementById('typing-indicator');

    // ════════════════════════════════════════════════════════════════════════
    //  SELECCIÓN DE CHAT
    // ════════════════════════════════════════════════════════════════════════

    document.querySelectorAll('.chat-item').forEach(function (el) {
        el.addEventListener('click', function () {
            openChat(
                parseInt(el.dataset.chatId, 10),
                el.dataset.nombre || el.dataset.username || 'Sin nombre'
            );
        });
    });

    function openChat(chatId, nombre) {
        if (activeChatId === chatId) return;

        activeChatId   = chatId;
        activeChatName = nombre;
        lastMsgId      = 0;

        // Marcar activo en sidebar
        document.querySelectorAll('.chat-item').forEach(function (el) {
            el.classList.toggle('active', parseInt(el.dataset.chatId, 10) === chatId);
        });

        // Limpiar badge del item
        var activeItem = document.querySelector('.chat-item.active');
        if (activeItem) {
            var badge = activeItem.querySelector('.chat-item-badge');
            if (badge) badge.remove();
        }

        // Mostrar panel conversación
        $chatEmpty.style.display     = 'none';
        $chatConvo.style.display     = 'flex';

        // Actualizar header
        var inicial = nombre.charAt(0).toUpperCase();
        $headerAvatar.textContent = inicial;
        $contactName.textContent  = nombre;
        $contactSub.textContent   = '@' + (activeItem ? (activeItem.dataset.username || 'telegram') : 'telegram');

        // Limpiar mensajes y cargar
        $chatMessages.innerHTML = '';
        $typingIndicator.textContent = 'Cargando mensajes…';
        loadMessages(false);

        // Arrancar polling
        clearInterval(pollTimer);
        pollTimer = setInterval(function () { loadMessages(true); }, POLL_MS);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  CARGA DE MENSAJES (AJAX)
    // ════════════════════════════════════════════════════════════════════════

    function loadMessages(incremental) {
        if (!activeChatId) return;

        var url = BASE_URL + '&action=messages&chat_id=' + activeChatId;
        if (incremental && lastMsgId > 0) {
            url += '&after_id=' + lastMsgId;
        }

        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        .done(function (res) {
            if (!res.ok) { return; }

            var msgs = res.messages || [];
            if (msgs.length === 0) {
                if (!incremental) {
                    $chatMessages.innerHTML = '<div style="text-align:center;color:#90a4ae;padding:40px 0;font-size:0.82rem;"><i class="fa-solid fa-comments fa-2x d-block mb-2"></i>No hay mensajes aún.</div>';
                }
                $typingIndicator.textContent = '';
                return;
            }

            if (!incremental) {
                renderMessages(msgs);
            } else {
                appendMessages(msgs);
            }

            // Actualizar badge global en header
            if (typeof updateTelegramBadge === 'function' && res.unread !== undefined) {
                updateTelegramBadge(res.unread);
            }

            $typingIndicator.textContent = '';
        })
        .fail(function () {
            $typingIndicator.textContent = '';
        });
    }

    function renderMessages(msgs) {
        $chatMessages.innerHTML = '';
        var lastDate = '';
        msgs.forEach(function (msg) {
            var dateStr = msg.CREATED_AT ? msg.CREATED_AT.substring(0, 10) : '';
            if (dateStr && dateStr !== lastDate) {
                $chatMessages.insertAdjacentHTML('beforeend', buildDateSeparator(dateStr));
                lastDate = dateStr;
            }
            $chatMessages.insertAdjacentHTML('beforeend', buildBubble(msg));
            lastMsgId = Math.max(lastMsgId, parseInt(msg.ID, 10));
        });
        scrollToBottom();
    }

    function appendMessages(msgs) {
        var lastDate = '';
        // Obtener última fecha ya mostrada
        var existing = $chatMessages.querySelectorAll('.date-separator');
        if (existing.length) {
            lastDate = existing[existing.length - 1].dataset.date || '';
        }

        var added = false;
        msgs.forEach(function (msg) {
            var dateStr = msg.CREATED_AT ? msg.CREATED_AT.substring(0, 10) : '';
            if (dateStr && dateStr !== lastDate) {
                $chatMessages.insertAdjacentHTML('beforeend', buildDateSeparator(dateStr));
                lastDate = dateStr;
            }
            $chatMessages.insertAdjacentHTML('beforeend', buildBubble(msg));
            lastMsgId = Math.max(lastMsgId, parseInt(msg.ID, 10));
            added = true;
        });
        if (added) scrollToBottom();
    }

    // ════════════════════════════════════════════════════════════════════════
    //  CONSTRUCCIÓN DE BURBUJAS
    // ════════════════════════════════════════════════════════════════════════

    function buildBubble(msg) {
        var dir     = msg.DIRECCION === 'OUT' ? 'out' : 'in';
        var time    = formatTime(msg.CREATED_AT);
        var content = buildMsgContent(msg);
        var tick    = dir === 'out'
                    ? '<i class="fa-solid fa-check-double" style="font-size:0.65rem;color:#546e7a;"></i>'
                    : '';

        return '<div class="msg-row ' + dir + '">'
             +   '<div class="msg-bubble">'
             +     content
             +     '<div class="msg-time">' + time + tick + '</div>'
             +   '</div>'
             + '</div>';
    }

    function buildMsgContent(msg) {
        var tipo  = msg.TIPO || 'text';
        var texto = escHtml(msg.TEXTO || '');

        if (tipo === 'photo' && msg.ARCHIVO_PATH) {
            return '<img class="msg-img" src="' + escHtml(msg.ARCHIVO_PATH) + '" alt="imagen"'
                 + ' onclick="window.open(this.src,\'_blank\')">'
                 + (texto ? '<div>' + texto + '</div>' : '');
        }

        if ((tipo === 'document' || tipo === 'video') && msg.ARCHIVO_PATH) {
            var fname = msg.ARCHIVO_PATH.split('/').pop();
            var icon  = tipo === 'video' ? 'fa-file-video' : 'fa-file';
            return '<div class="msg-doc">'
                 +   '<i class="fa-solid ' + icon + '"></i>'
                 +   '<a href="' + escHtml(msg.ARCHIVO_PATH) + '" target="_blank">' + escHtml(fname) + '</a>'
                 + '</div>'
                 + (texto ? '<div>' + texto + '</div>' : '');
        }

        if (tipo === 'voice') {
            return '<div class="msg-doc"><i class="fa-solid fa-microphone"></i> Mensaje de voz</div>';
        }

        return '<div>' + texto.replace(/\n/g, '<br>') + '</div>';
    }

    function buildDateSeparator(dateStr) {
        var d   = new Date(dateStr + 'T00:00:00');
        var hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        var label;
        var diff = Math.round((hoy - d) / 86400000);
        if (diff === 0)      label = 'Hoy';
        else if (diff === 1) label = 'Ayer';
        else                 label = DIAS[d.getDay()] + ' ' + d.getDate() + ' ' + MESES[d.getMonth()];

        return '<div class="date-separator" data-date="' + dateStr + '"><span>' + label + '</span></div>';
    }

    // ════════════════════════════════════════════════════════════════════════
    //  ENVÍO DE MENSAJES
    // ════════════════════════════════════════════════════════════════════════

    $btnSend.addEventListener('click', sendMessage);

    $msgInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    $msgInput.addEventListener('input', function () {
        // Auto-resize
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        // Activar botón
        $btnSend.disabled = !this.value.trim() && !pendingFile;
    });

    function sendMessage() {
        var texto = $msgInput.value.trim();
        if (!texto && !pendingFile) return;
        if (!activeChatId) { showToast('Seleccioná un chat primero.', 'warning'); return; }

        var formData = new FormData();
        formData.append('chat_id', activeChatId);
        formData.append('message', texto);
        if (pendingFile) {
            formData.append('file', pendingFile);
        }

        // Deshabilitar temporalmente
        $btnSend.disabled  = true;
        $msgInput.disabled = true;

        // Burbuja optimista
        var optimisticId = 'opt-' + Date.now();
        var optMsg = {
            ID: optimisticId, DIRECCION: 'OUT', TIPO: pendingFile ? 'photo' : 'text',
            TEXTO: texto || (pendingFile ? pendingFile.name : ''),
            ARCHIVO_PATH: pendingFile ? URL.createObjectURL(pendingFile) : null,
            CREATED_AT: new Date().toISOString().replace('T', ' ').substring(0, 19)
        };
        $chatMessages.insertAdjacentHTML('beforeend', buildBubble(optMsg));
        scrollToBottom();

        // Limpiar input
        $msgInput.value    = '';
        $msgInput.style.height = 'auto';
        clearPendingFile();

        $.ajax({
            url: BASE_URL + '&action=send',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        .done(function (res) {
            if (res.ok) {
                if (!res.telegram) {
                    showToast('Mensaje guardado (sin conexión a Telegram)', 'warning');
                }
                // Recargar para obtener el ID real del mensaje
                loadMessages(true);
            } else {
                showToast('Error: ' + (res.error || 'No se pudo enviar'), 'danger');
            }
        })
        .fail(function () {
            showToast('Error de red al enviar', 'danger');
        })
        .always(function () {
            $msgInput.disabled = false;
            $msgInput.focus();
        });
    }

    // ════════════════════════════════════════════════════════════════════════
    //  ADJUNTOS
    // ════════════════════════════════════════════════════════════════════════

    $btnAttach.addEventListener('click', function () { $fileInput.click(); });

    $fileInput.addEventListener('change', function () {
        if (this.files.length > 0) {
            setFilePreview(this.files[0]);
        }
    });

    $btnRemoveFile.addEventListener('click', function () {
        clearPendingFile();
    });

    function setFilePreview(file) {
        pendingFile = file;
        $filePreviewName.textContent = file.name;
        $filePreviewImg.style.display = 'none';

        if (file.type.startsWith('image/')) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $filePreviewImg.src = e.target.result;
                $filePreviewImg.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }

        $filePreview.classList.add('show');
        $btnSend.disabled = false;
    }

    function clearPendingFile() {
        pendingFile = null;
        $fileInput.value = '';
        $filePreviewImg.src = '';
        $filePreviewImg.style.display = 'none';
        $filePreview.classList.remove('show');
        $btnSend.disabled = !$msgInput.value.trim();
    }

    // ── Pegar imagen con Ctrl+V ──────────────────────────────────────────
    document.addEventListener('paste', function (e) {
        if (!activeChatId) return;
        var items = (e.clipboardData || e.originalEvent.clipboardData).items;
        for (var i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                var file = items[i].getAsFile();
                if (file) {
                    // Renombrar el archivo con fecha
                    var blob  = file.slice(0, file.size, file.type);
                    var named = new File([blob], 'captura_' + Date.now() + '.png', { type: file.type });
                    setFilePreview(named);
                    showToast('Imagen pegada. Presioná Enviar para enviarla.', 'info');
                    e.preventDefault();
                    break;
                }
            }
        }
    });

    // ════════════════════════════════════════════════════════════════════════
    //  BÚSQUEDA EN SIDEBAR
    // ════════════════════════════════════════════════════════════════════════

    $searchInput.addEventListener('input', function () {
        var q = this.value.toLowerCase().trim();
        document.querySelectorAll('.chat-item').forEach(function (el) {
            var nombre = (el.dataset.nombre || '').toLowerCase();
            var user   = (el.dataset.username || '').toLowerCase();
            el.style.display = (nombre.indexOf(q) !== -1 || user.indexOf(q) !== -1) ? '' : 'none';
        });
    });

    // ════════════════════════════════════════════════════════════════════════
    //  BOTÓN REFRESCAR
    // ════════════════════════════════════════════════════════════════════════

    $btnRefresh.addEventListener('click', function () {
        if (!activeChatId) return;
        loadMessages(false);
    });

    // ════════════════════════════════════════════════════════════════════════
    //  UTILIDADES
    // ════════════════════════════════════════════════════════════════════════

    function scrollToBottom() {
        $chatMessages.scrollTop = $chatMessages.scrollHeight;
    }

    function formatTime(datetimeStr) {
        if (!datetimeStr) return '';
        var d  = new Date(datetimeStr.replace(' ', 'T'));
        var hh = String(d.getHours()).padStart(2, '0');
        var mm = String(d.getMinutes()).padStart(2, '0');
        return hh + ':' + mm;
    }

    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function showToast(msg, type) {
        type = type || 'info';
        var colors = {
            info:    '#1565c0',
            success: '#2e7d32',
            warning: '#e65100',
            danger:  '#c62828',
        };
        var el = document.createElement('div');
        el.style.cssText = [
            'background:' + (colors[type] || '#333'),
            'color:#fff',
            'padding:8px 16px',
            'border-radius:8px',
            'font-size:0.82rem',
            'box-shadow:0 2px 10px rgba(0,0,0,0.2)',
            'margin-top:6px',
            'opacity:1',
            'transition:opacity 0.3s',
        ].join(';');
        el.textContent = msg;
        document.getElementById('chat-toast-area').appendChild(el);
        setTimeout(function () { el.style.opacity = '0'; }, 3000);
        setTimeout(function () { el.remove(); }, 3400);
    }

    // Exponer la función globalmente para que footer.php pueda actualizar el badge
    window.openChatById = function (chatId, nombre) {
        openChat(chatId, nombre);
    };

    // ════════════════════════════════════════════════════════════════════════
    //  MODAL — NUEVO CONTACTO
    // ════════════════════════════════════════════════════════════════════════

    var $modalEl      = document.getElementById('modalNuevoContacto');
    var $formContacto = document.getElementById('form-nuevo-contacto');
    var $btnSaveCtc   = document.getElementById('btn-save-contact');
    var $ncError      = document.getElementById('nc-error');

    // Limpiar el formulario al abrir el modal
    if ($modalEl) {
        $modalEl.addEventListener('show.bs.modal', function () {
            $formContacto.reset();
            $formContacto.classList.remove('was-validated');
            $ncError.classList.add('d-none');
            $ncError.textContent = '';
            $btnSaveCtc.disabled = false;
            $btnSaveCtc.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i>Guardar Contacto';
        });
    }

    // Envío del formulario de nuevo contacto
    if ($btnSaveCtc) {
        $btnSaveCtc.addEventListener('click', function () {
            $formContacto.classList.add('was-validated');
            $ncError.classList.add('d-none');

            // Validación HTML5 nativa
            if (!$formContacto.checkValidity()) {
                return;
            }

            var nombre  = document.getElementById('nc-nombre').value.trim();
            var chatId  = document.getElementById('nc-chat-id').value.trim();
            var email   = document.getElementById('nc-email').value.trim();
            var celular = document.getElementById('nc-celular').value.trim();

            // Spinner
            $btnSaveCtc.disabled = true;
            $btnSaveCtc.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';

            $.ajax({
                url:     'index.php?route=chat&action=saveContact',
                method:  'POST',
                dataType:'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                data:    { nombre: nombre, chat_id: chatId, email: email, celular: celular },
            })
            .done(function (res) {
                if (res.ok) {
                    // Cerrar modal
                    bootstrap.Modal.getInstance($modalEl).hide();
                    showToast('Contacto "' + nombre + '" guardado correctamente.', 'success');

                    // Agregar el nuevo contacto al sidebar si no existe
                    var newChatId = parseInt(res.chat_id, 10);
                    var existing  = document.querySelector('.chat-item[data-chat-id="' + newChatId + '"]');
                    if (!existing) {
                        var inicial  = nombre.charAt(0).toUpperCase();
                        var itemHtml = '<div class="chat-item" data-chat-id="' + newChatId + '"'
                            + ' data-nombre="' + escHtml(nombre) + '" data-username="">'
                            + '<div class="chat-avatar">' + inicial + '</div>'
                            + '<div class="chat-item-body">'
                            + '<div class="chat-item-name">' + escHtml(nombre) + '</div>'
                            + '<div class="chat-item-preview" style="color:#90a4ae;font-style:italic;">Sin mensajes aún</div>'
                            + '</div>'
                            + '</div>';

                        var $list  = document.getElementById('chat-list');
                        var $empty = document.getElementById('chat-list-empty');
                        if ($empty) $empty.remove();
                        $list.insertAdjacentHTML('afterbegin', itemHtml);

                        // Adjuntar evento click al nuevo item
                        var newItem = $list.querySelector('.chat-item[data-chat-id="' + newChatId + '"]');
                        if (newItem) {
                            newItem.addEventListener('click', function () {
                                openChat(newChatId, nombre);
                            });
                        }
                    }

                    // Abrir el chat automáticamente
                    openChat(newChatId, nombre);

                } else {
                    $ncError.textContent = res.error || 'Error al guardar el contacto.';
                    $ncError.classList.remove('d-none');
                    $btnSaveCtc.disabled = false;
                    $btnSaveCtc.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i>Guardar Contacto';
                }
            })
            .fail(function (xhr) {
                var msg = 'Error de red al guardar.';
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r.error) msg = r.error;
                } catch (e) {}
                $ncError.textContent = msg;
                $ncError.classList.remove('d-none');
                $btnSaveCtc.disabled = false;
                $btnSaveCtc.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i>Guardar Contacto';
            });
        });
    }

})();
</script>
