<?php
    require_once 'Core/ajaxHelper.php';
    if(session_status() == PHP_SESSION_NONE){
        session_start(); 
    }
    if(!isset($_SESSION['usuario_id'])){
        header('Location: index.php?action=login');
        exit(); 
    }
    if($_SESSION['usuario_rol'] !== 'admin'){
        header('Location: index.php?action=home_usuario');
        exit();
    }
    $nombre = htmlspecialchars($_SESSION['usuario_nombre']);
    $flash_success = $_SESSION['flash_success'] ?? null;
    $flash_error = $_SESSION['flash_error'] ?? null;
    unset($_SESSION['flash_success'], $_SESSION['flash_error']);

    if(!isset($usuarios)){
        $usuarios = [];
    }

    $total_usuarios = isset($total_usuarios) ? (int)$total_usuarios : count($usuarios);
    $activos = isset($activos) ? (int)$activos : 0;
    $admin = isset($admin) ? (int)$admin : 0;
    $desactivados = isset($desactivados) ? (int)$desactivados : 0;
    $cajeros = isset($cajeros) ? (int)$cajeros : 0;
    $repartidores = isset($repartidores) ? (int)$repartidores : 0;
    $clientes = isset($clientes) ? (int)$clientes : 0;
    $csrf_token = isset($csrf_token) ? $csrf_token : ($_SESSION['csrf_token'] ?? '');
?>
<?php if (!AjaxHelper::isAjax()):?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios — Admin</title>
    <link rel="icon" type="image/png" href="Images/logo.png">
    <link rel="stylesheet" href="Styles/global.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Styles/styles-home.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Styles/styles-users-admin.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Styles/admin-spa.css?v=<?php echo time(); ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="Scripts/admin-spa.js?v=<?php echo time(); ?>" defer></script>
</head>
<body>
    
    <div class="admin-layout">
        <?php include __DIR__ . '/../Admin/partials/admin_sidebar.php'; ?>

        
        <div class="admin-main">
            
            <header class="admin-topbar">
                <div class="admin-topbar-left">
                    <button type="button" class="admin-hamburger-btn hide-on-desktop" id="openAdminSidebar" aria-label="Abrir Menú">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                    <div class="admin-topbar-brand">
                        <span class="brand-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </span>
                        <span class="brand-title">Gestión de Usuarios</span>
                    </div>
                </div>
                <div class="admin-topbar-actions">
                    <?php include __DIR__ . '/../Admin/partials/admin_topbar_actions.php'; ?>
                </div>
            </header>

            <div id="admin-container">
<?php endif; ?>

    <div class="main-content" style="padding-top: 30px; padding-bottom: 50px;">
        <div class="container-admin">
            <?php if ($flash_success || $flash_error): 
                $esError = !empty($flash_error);
                $msgFlash = $flash_error ?: $flash_success;
            ?>
                <script>
                    (function() {
                        const tipo = <?= json_encode($esError ? 'error' : 'success'); ?>;
                        const mensaje = <?= json_encode($msgFlash, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
                        function lanzarUserToast() {
                            if (typeof window.mostrarAvisoToast === 'function') {
                                window.mostrarAvisoToast(tipo, tipo === 'error' ? 'Error' : 'Éxito', mensaje);
                            } else if (typeof SPA !== 'undefined' && typeof SPA.toast === 'function') {
                                if (typeof SPA.createToastContainer === 'function') SPA.createToastContainer();
                                SPA.toast(tipo, tipo === 'error' ? 'Error' : 'Éxito', mensaje, 4000);
                            }
                        }
                        if (document.readyState === 'loading') {
                            document.addEventListener('DOMContentLoaded', lanzarUserToast, { once: true });
                        } else {
                            lanzarUserToast();
                        }
                    })();
                </script>
            <?php endif; ?>

            <div class="admin-header">
                <div class="admin-header-titles">
                    <h1>Gestión de Usuarios</h1>
                    <p>Administra clientes, cajeros POS y cuentas administrativas de la tienda</p>
                </div>
                <div class="admin-header-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button class="btn btn-primary" onclick="abrirModalUsuario('nuevo')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                        Crear Usuario
                    </button>
                </div>
            </div>

            <!-- TARJETAS FUNCIONALES SIN ICONOS: TOTAL, ACTIVOS, INACTIVOS, ADMINS, CAJA POS, REPARTIDOR, CLIENTE -->
            <div class="stats-grid users-stats-grid">
                <div class="stat-card active-stat-card" id="cardFilterTotal" onclick="filtrarUsuariosPor('todos')" title="Ver todos los usuarios">
                    <div class="stat-info">
                        <h3>Total</h3>
                        <p><?php echo $total_usuarios; ?></p>
                    </div>
                </div>
                <div class="stat-card" id="cardFilterActivos" onclick="filtrarUsuariosPor('activos')" title="Filtrar usuarios activos">
                    <div class="stat-info">
                        <h3>Activos</h3>
                        <p><?php echo $activos; ?></p>
                    </div>
                </div>
                <div class="stat-card" id="cardFilterInactivos" onclick="filtrarUsuariosPor('inactivos')" title="Filtrar usuarios inactivos">
                    <div class="stat-info">
                        <h3>Inactivos</h3>
                        <p><?php echo $desactivados; ?></p>
                    </div>
                </div>
                <div class="stat-card" id="cardFilterAdmins" onclick="filtrarUsuariosPor('admins')" title="Filtrar administradores">
                    <div class="stat-info">
                        <h3>Admins</h3>
                        <p><?php echo $admin; ?></p>
                    </div>
                </div>
                <div class="stat-card" id="cardFilterCajeros" onclick="filtrarUsuariosPor('cajeros')" title="Filtrar cajeros POS">
                    <div class="stat-info">
                        <h3>Caja POS</h3>
                        <p><?php echo $cajeros; ?></p>
                    </div>
                </div>
                <div class="stat-card" id="cardFilterRepartidores" onclick="filtrarUsuariosPor('repartidores')" title="Filtrar repartidores">
                    <div class="stat-info">
                        <h3>Repartidor</h3>
                        <p><?php echo $repartidores; ?></p>
                    </div>
                </div>
                <div class="stat-card" id="cardFilterClientes" onclick="filtrarUsuariosPor('clientes')" title="Filtrar clientes">
                    <div class="stat-info">
                        <h3>Cliente</h3>
                        <p><?php echo $clientes; ?></p>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h2>Lista de Usuarios</h2>
                    <div class="panel-filters">
                        <input type="text" id="searchUser" class="search-input" placeholder="Buscar por nombre o correo...">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table" id="usersTable">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Correo</th>
                                <th>Teléfono</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Registro</th>
                                <th style="text-align: right;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $u): 
                                $rol = isset($u['rol']) ? strtolower($u['rol']) : 'cliente';
                                $rolNombre = 'Cliente';
                                if ($rol === 'admin') $rolNombre = 'Administrador';
                                elseif ($rol === 'cajero') $rolNombre = 'Cajero POS';
                                elseif ($rol === 'repartidor') $rolNombre = 'Repartidor Delivery';

                                $activo = isset($u['activo']) ? (int)$u['activo'] : 1;
                                $totalActividad = (int)($u['total_actividad'] ?? 0);
                                $tieneActividad = $totalActividad > 0;
                                $esPropiaCuenta = ((int)$_SESSION['usuario_id'] === (int)$u['id_usuario']);
                                $nombreCompleto = trim($u['nombre'] . ' ' . $u['apellido']);
                                $searchData = strtolower($nombreCompleto . ' ' . $u['correo'] . ' ' . ($u['telefono'] ?? '') . ' #' . $u['id_usuario']);
                            ?>
                            <tr class="user-row" 
                                data-id="<?= $u['id_usuario']; ?>" 
                                data-estado="<?= $activo === 1 ? 'activo' : 'inactivo'; ?>" 
                                data-rol="<?= htmlspecialchars($rol); ?>" 
                                data-search="<?= htmlspecialchars($searchData); ?>">
                                <td>
                                    <div class="user-avatar-group">
                                        <div class="avatar"><?php echo strtoupper(substr($u['nombre'], 0, 1)); ?></div>
                                        <div class="user-details">
                                            <strong><?php echo htmlspecialchars($nombreCompleto); ?></strong>
                                            <span class="user-id">ID: #<?php echo $u['id_usuario']; ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($u['correo']); ?></td>
                                <td><?php echo htmlspecialchars($u['telefono'] ?? '-'); ?></td>
                                <td>
                                    <!-- ROL SIN BACKGROUND NI COLOR -->
                                    <span style="font-weight: 600; color: #0f172a; font-size: 0.875rem;"><?= $rolNombre; ?></span>
                                </td>
                                <td>
                                    <!-- ESTADO PUNTO SIN BACKGROUND (Diseño anterior) -->
                                    <span style="display:inline-flex; align-items:center; gap:6px; font-weight:600; color:#0f172a; font-size:0.875rem;">
                                        <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background-color: <?= $activo === 1 ? '#16a34a' : '#dc2626'; ?>;"></span>
                                        <?= $activo === 1 ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($u['fecha_registro'])); ?></td>
                                <td class="actions-col" style="text-align: right; white-space: nowrap;">
                                    <div class="desktop-actions" style="display: inline-flex; gap: 6px; align-items: center; justify-content: flex-end;">
                                        <!-- Editar: Verde (#15803d) 36x36 -->
                                        <button type="button" class="btn-action-icon success" onclick='editarUsuario(<?php echo json_encode($u); ?>)' title="Editar">
                                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                        </button>

                                        <?php if (!$esPropiaCuenta): ?>
                                            <?php if ($tieneActividad): ?>
                                                <!-- Con actividad: Inactivar (Rojo con prohibición Imagen 3) o Activar (Verde con círculo check Imagen 2) -->
                                                <?php if ($activo === 1): ?>
                                                    <button type="button" class="btn-action-icon danger" onclick='confirmarToggleUsuario(<?= (int)$u['id_usuario']; ?>, 0, <?= json_encode($nombreCompleto); ?>)' title="Inactivar">
                                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn-action-icon success" onclick='confirmarToggleUsuario(<?= (int)$u['id_usuario']; ?>, 1, <?= json_encode($nombreCompleto); ?>)' title="Activar">
                                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="16 9 10 15 8 13"/></svg>
                                                    </button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <!-- Sin actividad: Eliminar (Rojo #dc2626 con papelera - Imagen 3 derecha) -->
                                                <button type="button" class="btn-action-icon danger" onclick='confirmarEliminarUsuario(<?= (int)$u['id_usuario']; ?>, <?= json_encode($nombreCompleto); ?>)' title="Eliminar">
                                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr id="noUsersRow" style="<?= empty($usuarios) ? '' : 'display:none;'; ?>">
                                <td colspan="7" class="text-center" style="padding: 40px; color:#64748b; text-align:center;">No se encontraron usuarios coincidentes.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL CREAR / EDITAR USUARIO -->
    <div class="modal-overlay" id="modalUsuario">
        <div class="modal-content">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button type="button" class="btn-modal-back" id="btnBackModalUsuario" onclick="regresarAModalDetalles()" title="Volver" style="display: none;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    </button>
                    <h3 id="modalUsuarioTitle" style="margin: 0;">Crear Administrador</h3>
                </div>
                <button type="button" class="modal-close" onclick="cerrarModalUsuario()" aria-label="Cerrar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div class="modal-body">
                <form id="formUsuario" method="POST" action="index.php?action=crear_administrador">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="id_usuario" id="formIdUsuario" value="">
                    
                    <div class="form-grid">
                        <div class="grupo">
                            <label class="label">Nombre *</label>
                            <input type="text" name="nombre" id="formNombre" class="input-modern" required>
                        </div>
                        <div class="grupo">
                            <label class="label">Apellido</label>
                            <input type="text" name="apellido" id="formApellido" class="input-modern">
                        </div>
                    </div>

                    <div class="grupo">
                        <label class="label">Correo Electrónico *</label>
                        <input type="email" name="correo" id="formCorreo" class="input-modern" required>
                    </div>

                    <div class="grupo">
                        <label class="label">Teléfono</label>
                        <input type="text" name="telefono" id="formTelefono" class="input-modern">
                    </div>

                    <div class="form-grid">
                        <div class="grupo">
                            <label class="label">Rol</label>
                            <select name="rol" id="formRol" class="input-modern">
                                <option value="admin">Administrador</option>
                                <option value="cajero">Cajero POS</option>
                                <option value="repartidor">Repartidor Delivery</option>
                                <option value="cliente">Cliente</option>
                            </select>
                            <small class="helper-text" id="rolHelper">Por seguridad, crearás un Administrador.</small>
                        </div>
                        <div class="grupo" id="estadoGroup" style="display:none;">
                            <label class="label">Estado</label>
                            <select name="activo" id="formActivo" class="input-modern">
                                <option value="1">Activo</option>
                                <option value="0">Desactivado</option>
                            </select>
                        </div>
                    </div>

                    <div id="passwordSection">
                        <div class="grupo">
                            <label class="label" id="passwordLabel">Contraseña *</label>
                            <input type="password" name="password" id="formPassword" class="input-modern" required>
                        </div>

                        <div class="grupo" id="confirmPasswordGroup">
                            <label class="label">Confirmar Contraseña *</label>
                            <input type="password" name="confirm_password" id="formConfirmPassword" class="input-modern" required>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModalUsuario()">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnSubmitUsuario">Guardar Administrador</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL CONFIRMAR ACTIVAR / INACTIVAR USUARIO -->
    <div class="modal-overlay" id="modalConfirmarEstadoUser">
        <div class="modal-content" style="max-width: 420px; text-align: center; padding: 28px 24px;">
            <div style="width: 58px; height: 58px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;" id="confEstadoUserIconWrapper">
                <svg id="confEstadoUserIcon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></svg>
            </div>
            <h3 id="confEstadoUserTitulo" style="margin: 0 0 8px; color: #0f172a; font-size: 1.2rem;">Inactivar usuario</h3>
            <p id="confEstadoUserMensaje" style="margin: 0 0 22px; color: #64748b; font-size: 0.92rem;">¿Confirmas inactivar a este usuario?</p>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalConfirmarEstado()" style="flex: 1; padding: 10px 16px; border-radius: 8px;">Cancelar</button>
                <button type="button" class="btn" id="confBtnProcederEstado" onclick="ejecutarCambioEstadoUsuario()" style="flex: 1; padding: 10px 16px; border-radius: 8px; color: #ffffff; border: none; font-weight: 700; cursor: pointer;">Proceder</button>
            </div>
        </div>
    </div>

    <!-- MODAL CONFIRMAR ELIMINAR USUARIO (SIN ACTIVIDAD) -->
    <div class="modal-overlay" id="modalConfirmarEliminarUser">
        <div class="modal-content" style="max-width: 420px; text-align: center; padding: 28px 24px;">
            <div style="width: 58px; height: 58px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
            </div>
            <h3 style="margin: 0 0 8px; color: #0f172a; font-size: 1.2rem;">Eliminar usuario</h3>
            <p id="confEliminarUserMensaje" style="margin: 0 0 22px; color: #64748b; font-size: 0.92rem;">¿Confirmas eliminar permanentemente a este usuario? Esta acción no se puede deshacer.</p>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalEliminarUsuario()" style="flex: 1; padding: 10px 16px; border-radius: 8px;">Cancelar</button>
                <button type="button" class="btn" id="confBtnProcederEliminar" onclick="ejecutarEliminacionUsuario()" style="flex: 1; padding: 10px 16px; border-radius: 8px; background: #dc2626; color: #ffffff; border: none; font-weight: 700; cursor: pointer;">Sí, eliminar</button>
            </div>
        </div>
    </div>

    <!-- MODAL DETALLES USUARIO -->
    <div class="modal-overlay" id="modalDetallesUsuario">
        <div class="modal-content" style="max-width: 460px;">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button type="button" class="btn-modal-back" onclick="cerrarModalDetalles()" title="Volver">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    </button>
                    <h3 style="margin: 0;">Ficha de Usuario</h3>
                </div>
                <button type="button" class="modal-close" onclick="cerrarModalDetalles()" aria-label="Cerrar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div class="modal-body" id="detallesUsuarioBody"></div>
        </div>
    </div>

    <script src="Scripts/users-admin.js?v=<?php echo time(); ?>"></script>

    <script>
    (function() {
        const csrfToken = <?= json_encode($csrf_token); ?>;
        let filtroActual = 'todos';
        let userEstadoPendiente = null;
        let userEliminarPendiente = null;

        window.mostrarAvisoToast = function(tipo, titulo, mensaje) {
            if (typeof SPA !== 'undefined' && typeof SPA.toast === 'function') {
                if (typeof SPA.createToastContainer === 'function') SPA.createToastContainer();
                SPA.toast(tipo, titulo, mensaje, 4000);
                return;
            }
            let container = document.getElementById('toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toast-container';
                document.body.appendChild(container);
            }
            const toast = document.createElement('div');
            toast.className = 'custom-toast toast-' + tipo + ' toast-show';
            toast.style.cssText = 'position:fixed;right:20px;top:20px;z-index:99999;display:flex;align-items:center;gap:12px;width:min(420px,calc(100vw - 32px));padding:14px 18px;background:#fff;border-radius:14px;box-shadow:0 12px 32px rgba(15,23,42,.2);border:1px solid ' + (tipo === 'error' ? '#fecaca' : '#bbf7d0') + ';color:#0f172a;';
            const icon = document.createElement('span');
            icon.className = 'toast-icon';
            icon.innerHTML = tipo === 'error'
                ? '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>'
                : '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>';
            const content = document.createElement('div');
            content.style.cssText = 'flex:1;';
            const t = document.createElement('div');
            t.style.cssText = 'font-weight:700;font-size:0.95rem;color:#0f172a;margin-bottom:2px;';
            t.textContent = titulo;
            const desc = document.createElement('div');
            desc.style.cssText = 'font-size:0.86rem;color:#475569;';
            desc.textContent = mensaje;
            content.append(t, desc);
            const close = document.createElement('button');
            close.type = 'button';
            close.style.cssText = 'background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;display:flex;';
            close.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';
            toast.append(icon, content, close);
            container.appendChild(toast);
            const removeFn = function() {
                if (removeTimer) clearTimeout(removeTimer);
                removeTimer = null;
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-10px)';
                toast.style.transition = 'all 0.3s ease';
                setTimeout(function() { toast.remove(); }, 300);
            };
            close.addEventListener('click', removeFn);
            let remaining = 4500;
            let startedAt = Date.now();
            let removeTimer = setTimeout(removeFn, remaining);
            const pauseToast = function() {
                if (!removeTimer) return;
                clearTimeout(removeTimer);
                removeTimer = null;
                remaining = Math.max(0, remaining - (Date.now() - startedAt));
            };
            const resumeToast = function() {
                if (removeTimer || remaining <= 0) return;
                startedAt = Date.now();
                removeTimer = setTimeout(removeFn, remaining);
            };
            toast.addEventListener('pointerenter', pauseToast);
            toast.addEventListener('pointerleave', resumeToast);
            toast.addEventListener('focusin', pauseToast);
            toast.addEventListener('focusout', function(event) {
                if (!toast.contains(event.relatedTarget)) resumeToast();
            });
        };

        // FILTRADO DINÁMICO POR TARJETAS Y BUSCADOR
        window.filtrarUsuariosPor = function(tipo) {
            filtroActual = tipo;
            document.querySelectorAll('.stat-card').forEach(c => c.classList.remove('active-stat-card'));
            const map = {
                'todos': 'cardFilterTotal',
                'activos': 'cardFilterActivos',
                'inactivos': 'cardFilterInactivos',
                'admins': 'cardFilterAdmins',
                'cajeros': 'cardFilterCajeros',
                'repartidores': 'cardFilterRepartidores',
                'clientes': 'cardFilterClientes'
            };
            const target = document.getElementById(map[tipo]);
            if (target) target.classList.add('active-stat-card');
            aplicarFiltrosTabla();
        };

        function aplicarFiltrosTabla() {
            const searchInput = document.getElementById('searchUser');
            const busqueda = (searchInput ? searchInput.value : '').toLowerCase().trim();
            const rows = document.querySelectorAll('#usersTable tbody tr.user-row');
            let visibles = 0;

            rows.forEach(row => {
                const estado = row.getAttribute('data-estado');
                const rol = row.getAttribute('data-rol');
                const searchData = row.getAttribute('data-search') || '';

                let matchFiltro = true;
                if (filtroActual === 'activos' && estado !== 'activo') matchFiltro = false;
                if (filtroActual === 'inactivos' && estado !== 'inactivo') matchFiltro = false;
                if (filtroActual === 'admins' && rol !== 'admin') matchFiltro = false;
                if (filtroActual === 'cajeros' && rol !== 'cajero') matchFiltro = false;
                if (filtroActual === 'repartidores' && rol !== 'repartidor') matchFiltro = false;
                if (filtroActual === 'clientes' && rol !== 'cliente') matchFiltro = false;

                let matchSearch = true;
                if (busqueda !== '' && !searchData.includes(busqueda)) {
                    matchSearch = false;
                }

                if (matchFiltro && matchSearch) {
                    row.style.display = '';
                    visibles++;
                } else {
                    row.style.display = 'none';
                }
            });

            const noRow = document.getElementById('noUsersRow');
            if (noRow) noRow.style.display = visibles === 0 ? '' : 'none';
        }

        const inputSearch = document.getElementById('searchUser');
        if (inputSearch) {
            inputSearch.addEventListener('input', aplicarFiltrosTabla);
        }

        // CONFIRMAR ACTIVAR / INACTIVAR
        window.confirmarToggleUsuario = function(id, nuevoEstado, nombre) {
            userEstadoPendiente = { id: id, activo: nuevoEstado };
            const esInactivar = (nuevoEstado === 0);

            const iconWrap = document.getElementById('confEstadoUserIconWrapper');
            const icon = document.getElementById('confEstadoUserIcon');
            const titulo = document.getElementById('confEstadoUserTitulo');
            const msg = document.getElementById('confEstadoUserMensaje');
            const btn = document.getElementById('confBtnProcederEstado');

            if (esInactivar) {
                if (iconWrap) iconWrap.style.background = '#fee2e2';
                if (icon) {
                    icon.setAttribute('stroke', '#dc2626');
                    icon.innerHTML = '<circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>';
                }
                if (titulo) titulo.textContent = 'Inactivar usuario';
                if (msg) msg.textContent = `¿Confirmas inactivar a "${nombre}"? El usuario no podrá ingresar a la tienda.`;
                if (btn) {
                    btn.textContent = 'Inactivar';
                    btn.style.background = '#dc2626';
                }
            } else {
                if (iconWrap) iconWrap.style.background = '#dcfce7';
                if (icon) {
                    icon.setAttribute('stroke', '#15803d');
                    icon.innerHTML = '<circle cx="12" cy="12" r="10"/><polyline points="16 9 10 15 8 13"/>';
                }
                if (titulo) titulo.textContent = 'Activar usuario';
                if (msg) msg.textContent = `¿Confirmas reactivar a "${nombre}"?`;
                if (btn) {
                    btn.textContent = 'Activar';
                    btn.style.background = '#15803d';
                }
            }

            const modal = document.getElementById('modalConfirmarEstadoUser');
            if (modal) modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        };

        window.cerrarModalConfirmarEstado = function() {
            const modal = document.getElementById('modalConfirmarEstadoUser');
            if (modal) modal.classList.remove('active');
            userEstadoPendiente = null;
            if (!document.querySelector('.modal-overlay.active')) {
                document.body.style.overflow = '';
            }
        };

        window.ejecutarCambioEstadoUsuario = function() {
            if (!userEstadoPendiente) return;
            const btn = document.getElementById('confBtnProcederEstado');
            if (btn) { btn.disabled = true; btn.textContent = 'Procesando...'; }

            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('id_usuario', userEstadoPendiente.id);
            formData.append('activo', userEstadoPendiente.activo);

            fetch('index.php?action=cambiar_estado_usuario', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(response => {
                cerrarModalConfirmarEstado();
                if (response.success) {
                    window.mostrarAvisoToast('success', 'Éxito', response.message);
                    if (typeof SPA !== 'undefined' && typeof SPA.loadModule === 'function') {
                        SPA.loadModule('index.php?action=usuarios_admin', false);
                    } else {
                        setTimeout(() => window.location.reload(), 600);
                    }
                } else {
                    const msg = response.message || 'Error al cambiar estado.';
                    window.mostrarAvisoToast('error', 'Error', msg);
                }
            })
            .catch(err => {
                console.error(err);
                window.location.reload();
            })
            .finally(() => {
                if (btn) btn.disabled = false;
            });
        };

        // CONFIRMAR ELIMINAR (USUARIOS SIN ACTIVIDAD)
        window.confirmarEliminarUsuario = function(id, nombre) {
            userEliminarPendiente = id;
            const msg = document.getElementById('confEliminarUserMensaje');
            if (msg) msg.textContent = `¿Confirmas eliminar permanentemente al usuario "${nombre}"? Esta acción no se puede deshacer.`;
            const modal = document.getElementById('modalConfirmarEliminarUser');
            if (modal) modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        };

        window.cerrarModalEliminarUsuario = function() {
            const modal = document.getElementById('modalConfirmarEliminarUser');
            if (modal) modal.classList.remove('active');
            userEliminarPendiente = null;
            if (!document.querySelector('.modal-overlay.active')) {
                document.body.style.overflow = '';
            }
        };

        window.ejecutarEliminacionUsuario = function() {
            if (!userEliminarPendiente) return;
            const btn = document.getElementById('confBtnProcederEliminar');
            if (btn) { btn.disabled = true; btn.textContent = 'Eliminando...'; }

            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('id_usuario', userEliminarPendiente);

            fetch('index.php?action=eliminar_usuario', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(response => {
                cerrarModalEliminarUsuario();
                if (response.success) {
                    window.mostrarAvisoToast('success', 'Éxito', response.message);
                    if (typeof SPA !== 'undefined' && typeof SPA.loadModule === 'function') {
                        SPA.loadModule('index.php?action=usuarios_admin', false);
                    } else {
                        setTimeout(() => window.location.reload(), 600);
                    }
                } else {
                    const msg = response.message || 'Error al eliminar usuario.';
                    window.mostrarAvisoToast('error', 'Error', msg);
                }
            })
            .catch(err => {
                console.error(err);
                window.location.reload();
            })
            .finally(() => {
                if (btn) btn.disabled = false;
            });
        };
    })();
    </script>

<?php if (!AjaxHelper::isAjax()): ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php endif; ?>

