<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['usuario_id'])) {
        header('Location: index.php?action=login');
        exit();
    }

    $usuario = isset($usuario) && is_array($usuario)
        ? $usuario
        : [];

    $direcciones = isset($direcciones) && is_array($direcciones)
        ? $direcciones
        : [];

    $pedidos = isset($pedidos) && is_array($pedidos)
        ? $pedidos
        : [];

    $csrf_token = isset($csrf_token)
        ? $csrf_token
        : ($_SESSION['csrf_token'] ?? '');

    $flash_success = $flash_success ?? null;
    $flash_error = $flash_error ?? null;

    $nombreUsuario = trim(
        ($usuario['nombre'] ?? '') . ' ' .
            ($usuario['apellido'] ?? '')
    );

    if ($nombreUsuario === '') {
        $nombreUsuario = 'Usuario';
    }

    $correoUsuario = $usuario['correo'] ?? '';
    $telefonoUsuario = $usuario['telefono'] ?? '';
    $fechaRegistro = $usuario['fecha_registro'] ?? '';

    $inicial = strtoupper(
        substr(
            trim($usuario['nombre'] ?? 'U'),
            0,
            1
        )
    );

    function escapar($valor)
    {
        return htmlspecialchars(
            (string)$valor,
            ENT_QUOTES,
            'UTF-8'
        );
    }

    function fechaCuenta($fecha)
    {
        if (empty($fecha)) {
            return 'Sin fecha';
        }

        $timestamp = strtotime($fecha);

        if (!$timestamp) {
            return escapar($fecha);
        }

        return date('d/m/Y', $timestamp);
    }

    function fechaPedido($fecha)
    {
        if (empty($fecha)) {
            return 'Sin fecha';
        }

        $timestamp = strtotime($fecha);

        if (!$timestamp) {
            return escapar($fecha);
        }

        return date('d/m/Y H:i', $timestamp);
    }

    function claseEstadoPedido($estado)
    {
        $clases = [
            'pendiente'  => 'pedido-estado-pendiente',
            'confirmado' => 'pedido-estado-confirmado',
            'en_camino'  => 'pedido-estado-camino',
            'entregado'  => 'pedido-estado-entregado',
            'cancelado'  => 'pedido-estado-cancelado'
        ];

        return $clases[$estado] ?? 'pedido-estado-pendiente';
    }

    function textoEstadoPedido($estado)
    {
        $textos = [
            'pendiente'  => 'Pendiente',
            'confirmado' => 'Confirmado',
            'en_camino'  => 'En camino',
            'entregado'  => 'Entregado',
            'cancelado'  => 'Cancelado'
        ];

        return $textos[$estado] ?? ucfirst(
            str_replace('_', ' ', $estado)
        );
    }
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Mi Cuenta — La Caserita</title>

    <link
        rel="icon"
        type="image/png"
        href="Images/logo.png">

    <link
        rel="stylesheet"
        href="Styles/global.css?v=<?= time(); ?>">

    <link
        rel="stylesheet"
        href="Styles/styles-cuenta.css?v=<?= time(); ?>">
    <script src="Scripts/normalizador-modales.js?v=<?= filemtime(__DIR__ . '/../../Scripts/normalizador-modales.js'); ?>" defer></script>
    <link rel="icon" type="image/png" href="Images/logo.png">
</head>

<body>

    



    <header class="cuenta-header">
        <div class="cuenta-header-container">
            <div class="cuenta-header-left">
                <a href="index.php?action=home_usuario" class="cuenta-brand" title="Volver a la tienda">
                    <div class="cuenta-brand-icon">
                        <img src="Images/logo_backup.png" alt="La Caserita" onerror="this.onerror=null;this.src='Images/logo.png';">
                    </div>
                    <span class="cuenta-brand-name">La Caserita</span>
                </a>
            </div>
            
            <div class="cuenta-header-center">
                <h1 class="cuenta-header-title">Mi Cuenta</h1>
            </div>

            <div class="cuenta-header-right"></div>
        </div>
    </header>

    <main class="cuenta-page">
        <?php if ($flash_success): ?>
            <div class="cuenta-alert cuenta-alert-success">
                <?= escapar($flash_success); ?>
            </div>
        <?php endif; ?>

        <?php if ($flash_error): ?>
            <div class="cuenta-alert cuenta-alert-error">
                <?= escapar($flash_error); ?>
            </div>
        <?php endif; ?>

        <div style="margin-bottom: 20px;">
            <a href="index.php?action=home_usuario" class="link-volver">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Volver a la tienda
            </a>
        </div>

        <section class="cuenta-profile-card">
            <div class="cuenta-avatar">
                <?= escapar($inicial); ?>
            </div>

            <div class="cuenta-profile-info">
                <div class="cuenta-profile-name-row">
                    <div>
                        <h2 id="perfilNombreVisual">
                            <?= escapar($nombreUsuario); ?>
                        </h2>
                        <p id="perfilCorreoVisual">
                            <?= escapar($correoUsuario); ?>
                        </p>
                    </div>

                    <span class="cuenta-status-badge">
                        <span class="cuenta-status-dot"></span>
                        Cuenta activa
                    </span>
                </div>

                <div class="cuenta-profile-meta">
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        <span id="perfilTelefonoVisual">
                            <?= escapar($telefonoUsuario !== '' ? $telefonoUsuario : 'Sin teléfono registrado'); ?>
                        </span>
                    </span>

                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        Miembro desde <?= escapar(fechaCuenta($fechaRegistro)); ?>
                    </span>
                </div>
            </div>
        </section>

        <div class="cuenta-layout">
            <aside class="cuenta-sidebar">
                <nav class="cuenta-menu">
                    <button type="button" class="cuenta-menu-item active" data-tab="pedidos">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                        <span>
                            <strong>Mis pedidos</strong>
                            <small>Historial de compras</small>
                        </span>
                    </button>

                    <button type="button" class="cuenta-menu-item" data-tab="direcciones">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 12-9 12S3 17 3 10a9 9 0 1 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <span>
                            <strong>Direcciones</strong>
                            <small>Direcciones de entrega</small>
                        </span>
                    </button>

                    <button type="button" class="cuenta-menu-item" data-tab="informacion">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span>
                            <strong>Mi información</strong>
                            <small>Datos personales</small>
                        </span>
                    </button>

                    <button type="button" class="cuenta-menu-item" data-tab="seguridad">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <span>
                            <strong>Seguridad</strong>
                            <small>Cambiar contraseña</small>
                        </span>
                    </button>

                    <form method="POST" action="index.php?action=logout" style="margin-top: 10px;">
                        <button type="submit" class="cuenta-menu-item cuenta-logout-sidebar-btn" style="width: 100%; text-align: left; background: #c62828; border: none; border-radius: 14px; cursor: pointer; color: #ffffff;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            <span>
                                <strong style="color: #ffffff;">Cerrar sesión</strong>
                                <small style="color: rgba(255, 255, 255, 0.85);">Salir de tu cuenta</small>
                            </span>
                        </button>
                    </form>
                </nav>
            </aside>

            

            <section class="cuenta-content">

                



                <div
                    class="cuenta-tab active"
                    id="tab-pedidos">

                    <div class="cuenta-section-header">

                        <div>
                            <span class="cuenta-section-eyebrow">
                                Actividad
                            </span>

                            <h2>Historial de compras</h2>

                            <p>
                                Consulta el estado y los detalles de tus pedidos.
                            </p>
                        </div>

                        <div class="cuenta-count-badge">
                            <?= count($pedidos); ?>
                            <?= count($pedidos) === 1
                                ? 'pedido'
                                : 'pedidos'; ?>
                        </div>

                    </div>

                    <?php if (!empty($pedidos)): ?>

                        <div class="pedidos-toolbar">
                            <div class="pedidos-search-box">
                                <i class="fa fa-search"></i>
                                <input type="text" id="busquedaPedidos" placeholder="Buscar por N° de pedido...">
                            </div>
                            <div class="pedidos-filters-container">
                                <div class="pedidos-filter-box date-filter">
                                    <input type="date" id="filtroFechaPedidos" title="Filtrar por fecha">
                                </div>
                                <div class="pedidos-filter-box">
                                    <select id="filtroEstadoPedidos">
                                        <option value="todos">Todos los estados</option>
                                        <option value="pendiente">Pendiente</option>
                                        <option value="procesando">Procesando</option>
                                        <option value="enviado">Enviado</option>
                                        <option value="entregado">Entregado</option>
                                        <option value="cancelado">Cancelado</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive pedidos-table-container">
                            <table class="pedidos-table" id="tablaMisPedidos">
                                <thead>
                                    <tr>
                                        <th>N° Pedido</th>
                                        <th>Fecha</th>
                                        <th>Tipo Entrega</th>
                                        <th>Pago</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pedidos as $pedido): ?>
                                        <tr class="pedido-row" data-estado="<?= htmlspecialchars($pedido['estado'] ?? 'pendiente', ENT_QUOTES, 'UTF-8'); ?>">
                                            <td data-label="N° Pedido" class="pedido-id-cell">
                                                <span class="pedido-id-text font-weight-bold">
                                                    #<?= str_pad((int)$pedido['id_pedido'], 5, '0', STR_PAD_LEFT); ?>
                                                </span>
                                            </td>
                                            <td data-label="Fecha" class="pedido-fecha-cell">
                                                <span class="pedido-fecha">
                                                    <?= escapar(fechaPedido($pedido['fecha'] ?? '')); ?>
                                                </span>
                                            </td>
                                            <td class="pedido-fecha-raw" style="display:none;" data-raw-date="<?= date('Y-m-d', strtotime($pedido['fecha'])); ?>"></td>
                                            <td data-label="Entrega" class="pedido-entrega-cell">
                                                <span class="badge-tipo-entrega" style="display:inline-flex; align-items:center; gap:4px;">
                                                    <?php if (($pedido['tipo_entrega'] ?? 'delivery') === 'delivery'): ?>
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>Delivery
                                                    <?php else: ?>
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>Recojo
                                                    <?php endif; ?>
                                                </span>
                                            </td>
                                            <td data-label="Pago" class="pedido-pago-cell">
                                                <span class="badge-metodo-pago" style="display:inline-flex; align-items:center; gap:4px;">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                                                    <?= escapar($pedido['metodo_pago'] ?? 'No especificado'); ?>
                                                </span>
                                            </td>
                                            <td data-label="Total" class="pedido-total-cell">
                                                <span class="pedido-total font-weight-bold">
                                                    S/ <?= number_format((float)($pedido['total'] ?? 0), 2); ?>
                                                </span>
                                            </td>
                                            <td data-label="Estado" class="pedido-estado-cell">
                                                <span class="pedido-estado <?= claseEstadoPedido($pedido['estado'] ?? 'pendiente'); ?>">
                                                    <?= escapar(textoEstadoPedido($pedido['estado'] ?? 'pendiente')); ?>
                                                </span>
                                            </td>
                                            <td data-label="Acciones" class="pedido-acciones-cell text-center">
                                                <a class="pedido-btn-detalle-texto" href="index.php?action=detalle_pedido_usuario&id=<?= (int)$pedido['id_pedido']; ?>">
                                                    Ver detalle
                                                   </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php else: ?>

                        <div class="cuenta-empty-state">

                            <div class="cuenta-empty-icon">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="54"
                                    height="54"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.5">
                                    <path
                                        d="M6 2L3 6v14a2 2 0 0 0
                                           2 2h14a2 2 0 0 0
                                           2-2V6l-3-4z"></path>

                                    <line
                                        x1="3"
                                        y1="6"
                                        x2="21"
                                        y2="6"></line>

                                    <path
                                        d="M16 10a4 4 0 0 1-8 0"></path>
                                </svg>
                            </div>

                            <h3>Aún no tienes pedidos</h3>

                            <p>
                                Cuando realices una compra, aparecerá aquí
                                junto con su estado y sus detalles.
                            </p>

                            <a
                                href="index.php?action=home_usuario"
                                class="cuenta-primary-link">
                                Ir a comprar
                            </a>

                        </div>

                    <?php endif; ?>

                </div>

                



                <div
                    class="cuenta-tab"
                    id="tab-direcciones">

                    <div class="cuenta-section-header">

                        <div>
                            <span class="cuenta-section-eyebrow">
                                Entregas
                            </span>

                            <h2>Mis direcciones</h2>

                            <p>
                                Administra las direcciones que usarás
                                al realizar tus pedidos.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="cuenta-primary-button"
                            id="btnNuevaDireccion">
                            <span>+</span>
                            Nueva dirección
                        </button>

                    </div>

                    <div
                        class="direcciones-grid"
                        id="direccionesGrid">

                        <?php if (!empty($direcciones)): ?>

                            <?php foreach ($direcciones as $direccion): ?>

                                <article
                                    class="direccion-card <?=
                                                            (int)$direccion['es_principal'] === 1
                                                                ? 'principal'
                                                                : '';
                                                            ?>"
                                    data-direccion='<?=
                                                    escapar(
                                                        json_encode(
                                                            $direccion,
                                                            JSON_UNESCAPED_UNICODE
                                                                | JSON_UNESCAPED_SLASHES
                                                        )
                                                    );
                                                    ?>'>

                                    <div class="direccion-card-header">

                                        <div class="direccion-icon">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="22"
                                                height="22"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2">
                                                <path
                                                    d="M21 10c0 7-9 12-9 12
                                                       S3 17 3 10
                                                       a9 9 0 1 1 18 0z"></path>

                                                <circle
                                                    cx="12"
                                                    cy="10"
                                                    r="3"></circle>
                                            </svg>
                                        </div>

                                        <?php if (
                                            (int)$direccion['es_principal'] === 1
                                        ): ?>

                                            <span class="direccion-main-badge">
                                                Principal
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                    <h3>
                                        <?= escapar(
                                            $direccion['calle']
                                                ?? 'Dirección'
                                        ); ?>
                                    </h3>

                                    <p>
                                        <?= escapar(
                                            ($direccion['distrito'] ?? '')
                                                . ', '
                                                . ($direccion['provincia'] ?? '')
                                        ); ?>
                                    </p>

                                    <p>
                                        <?= escapar(
                                            $direccion['departamento']
                                                ?? ''
                                        ); ?>
                                    </p>

                                    <?php if (
                                        !empty($direccion['referencia'])
                                    ): ?>

                                        <small>
                                            Referencia:
                                            <?= escapar(
                                                $direccion['referencia']
                                            ); ?>
                                        </small>

                                    <?php endif; ?>

                                    <div class="direccion-actions">

                                        <button
                                            type="button"
                                            class="direccion-action editar-direccion">
                                            Editar
                                        </button>

                                        <?php if (
                                            (int)$direccion['es_principal'] !== 1
                                        ): ?>

                                            <button
                                                type="button"
                                                class="direccion-action principal-direccion"
                                                data-id-direccion="<?=
                                                                    (int)$direccion['id_direccion'];
                                                                    ?>">
                                                Hacer principal
                                            </button>

                                        <?php endif; ?>

                                        <button
                                            type="button"
                                            class="direccion-action danger eliminar-direccion"
                                            data-id-direccion="<?=
                                                                (int)$direccion['id_direccion'];
                                                                ?>">
                                            Eliminar
                                        </button>

                                    </div>

                                </article>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <div
                                class="cuenta-empty-state direccion-empty"
                                id="direccionEmpty">
                                <div class="cuenta-empty-icon">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="52"
                                        height="52"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5">
                                        <path
                                            d="M21 10c0 7-9 12-9 12
                                               S3 17 3 10
                                               a9 9 0 1 1 18 0z"></path>

                                        <circle
                                            cx="12"
                                            cy="10"
                                            r="3"></circle>
                                    </svg>
                                </div>

                                <h3>No tienes direcciones registradas</h3>

                                <p>
                                    Registra una dirección para poder
                                    recibir tus pedidos mediante delivery.
                                </p>

                                <button
                                    type="button"
                                    class="cuenta-primary-button"
                                    id="btnNuevaDireccionEmpty">
                                    Agregar dirección
                                </button>
                            </div>

                        <?php endif; ?>

                    </div>

                </div>

                <div
                    class="cuenta-tab"
                    id="tab-informacion">

                    <div class="cuenta-section-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <span class="cuenta-section-eyebrow">
                                Perfil
                            </span>

                            <h2>Mi información personal</h2>

                            <p>
                                Mantén actualizados tus datos de contacto.
                            </p>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 8px; margin-top: 10px;">
                            <span style="font-size: 0.9rem; font-weight: 500; color: #374151;">Editar</span>
                            <label style="position: relative; display: inline-block; width: 44px; height: 24px;">
                                <input type="checkbox" id="toggleEditPerfil" style="opacity: 0; width: 0; height: 0;">
                                <span style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #d1d5db; transition: .4s; border-radius: 24px;" id="sliderPerfil"></span>
                                <span style="position: absolute; content: ''; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; pointer-events: none;" id="sliderCirclePerfil"></span>
                            </label>
                        </div>
                    </div>

                    <form
                        id="formActualizarPerfil"
                        class="cuenta-form"
                        action="index.php?action=actualizar_perfil"
                        method="POST"
                        autocomplete="off">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= escapar($csrf_token); ?>">

                        <div class="cuenta-form-grid">

                            <div class="cuenta-form-group">

                                <label for="perfilNombre">
                                    Nombre
                                </label>

                                <input
                                    type="text"
                                    name="nombre"
                                    id="perfilNombre"
                                    value="<?= escapar(
                                                $usuario['nombre'] ?? ''
                                            ); ?>"
                                    maxlength="80"
                                    readonly
                                    required>

                                <small
                                    class="field-error"
                                    data-error-for="nombre"></small>

                            </div>

                            <div class="cuenta-form-group">

                                <label for="perfilApellido">
                                    Apellido
                                </label>

                                <input
                                    type="text"
                                    name="apellido"
                                    id="perfilApellido"
                                    value="<?= escapar(
                                                $usuario['apellido'] ?? ''
                                            ); ?>"
                                    maxlength="80"
                                    readonly
                                    required>

                                <small
                                    class="field-error"
                                    data-error-for="apellido"></small>

                            </div>

                            <div class="cuenta-form-group">

                                <label for="perfilCorreo">
                                    Correo electrónico
                                </label>

                                <div style="position: relative;">
                                    <input
                                        type="email"
                                        name="correo"
                                        id="perfilCorreo"
                                        value="<?= escapar(
                                                    $usuario['correo'] ?? ''
                                                ); ?>"
                                        maxlength="120"
                                        readonly
                                        style="background-color: #f3f4f6; cursor: not-allowed; color: #6b7280; padding-right: 35px;"
                                        required>
                                    <svg style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>

                                <small
                                    class="field-error"
                                    data-error-for="correo"></small>

                            </div>

                            <div class="cuenta-form-group">

                                <label for="perfilTelefono">
                                    Teléfono
                                </label>

                                <input
                                    type="tel"
                                    name="telefono"
                                    id="perfilTelefono"
                                    value="<?= escapar(
                                                $usuario['telefono'] ?? ''
                                            ); ?>"
                                    maxlength="15"
                                    readonly
                                    placeholder="Ej. 987654321">

                                <small
                                    class="field-error"
                                    data-error-for="telefono"></small>

                            </div>

                        </div>

                        <div class="cuenta-form-actions" id="accionesPerfil" style="display: none; transition: opacity 0.3s ease;">

                            <button
                                type="submit"
                                class="cuenta-primary-button">
                                Guardar cambios
                            </button>

                        </div>

                    </form>

                </div>

                



                <div
                    class="cuenta-tab"
                    id="tab-seguridad">

                    <div class="cuenta-section-header">

                        <div>
                            <span class="cuenta-section-eyebrow">
                                Seguridad
                            </span>

                            <h2>Cambiar contraseña</h2>

                            <p>
                                Protege tu cuenta utilizando una contraseña segura.
                            </p>
                        </div>

                    </div>

                    <div class="cuenta-security-layout">

                        <form
                            id="formCambiarPassword"
                            class="cuenta-form security-form"
                            action="index.php?action=cambiar_password"
                            method="POST"
                            autocomplete="off">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= escapar($csrf_token); ?>">

                            <!-- Banner de Solicitud de PIN por Correo -->
                            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 14px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
                                <div>
                                    <strong style="color: #15803d; font-size: 0.92rem; display: block; margin-bottom: 2px;">Verificación por Correo Electrónico</strong>
                                    <span style="font-size: 0.83rem; color: #475569;">Para cambiar tu contraseña enviaremos un código PIN de 6 dígitos a <strong><?= escapar($correoUsuario); ?></strong>.</span>
                                </div>
                                <button type="button" id="btnSolicitarPinSeguridad" class="cuenta-primary-button" style="padding: 10px 18px; white-space: nowrap; font-size: 0.85rem;">
                                    Enviar Código PIN
                                </button>
                            </div>

                            <div id="alertPinSeguridad" class="cuenta-alert" style="display: none; margin-bottom: 16px;"></div>

                            <!-- Campo 1: PIN de 6 dígitos -->
                            <div class="cuenta-form-group">
                                <label for="seguridad_pin">
                                    Código PIN de 6 dígitos
                                </label>
                                <input
                                    type="text"
                                    name="pin"
                                    id="seguridad_pin"
                                    placeholder="Ingresa el PIN recibido en tu correo"
                                    maxlength="6"
                                    required
                                    autocomplete="off">
                                <small class="field-error" data-error-for="pin"></small>
                            </div>

                            <!-- Campo 2: Nueva contraseña -->
                            <div class="cuenta-form-group">
                                <label for="seguridad_pass_nueva">
                                    Nueva contraseña
                                </label>
                                <div class="password-wrapper">
                                    <span class="password-field-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg></span>
                                    <input
                                        type="password"
                                        name="password_nueva"
                                        id="seguridad_pass_nueva"
                                        placeholder="Nueva contraseña"
                                        required
                                        minlength="8"
                                        autocomplete="new-password"
                                        oninput="validarSeguridadPassModal(this.value)"
                                        onfocus="mostrarSeguridadPassChecklist()"
                                        onblur="ocultarSeguridadPassChecklist()">
                                    <button
                                        type="button"
                                        class="toggle-password toggle-password-btn"
                                        data-target="seguridad_pass_nueva"
                                        aria-label="Mostrar contraseña"
                                        title="Mostrar contraseña">
                                        <svg class="eye-closed" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                                        <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </button>
                                </div>
                                <small class="field-error" data-error-for="password_nueva"></small>
                            </div>

                            <!-- Panel Compacto de Requisitos (Estilo Crear Cuenta) -->
                            <div class="auth-pass-checklist-card" id="seguridadPassChecklist" style="display: none; margin-bottom: 12px;">
                                <div class="auth-pass-checklist-title">La contraseña debe tener:</div>
                                <div class="auth-pass-checklist-item" id="segChkMinChar">
                                    <span class="auth-chk-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    </span>
                                    <span class="auth-chk-text">Mínimo 8 caracteres</span>
                                </div>
                                <div class="auth-pass-checklist-item" id="segChkUpper">
                                    <span class="auth-chk-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    </span>
                                    <span class="auth-chk-text">Al menos 1 letra mayúscula</span>
                                </div>
                                <div class="auth-pass-checklist-item" id="segChkNum">
                                    <span class="auth-chk-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    </span>
                                    <span class="auth-chk-text">Al menos 1 número</span>
                                </div>
                                <div class="auth-pass-checklist-item" id="segChkSpecial">
                                    <span class="auth-chk-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    </span>
                                    <span class="auth-chk-text">Al menos 1 carácter especial (@, #, $, %, etc.)</span>
                                </div>
                            </div>

                            <!-- Campo 3: Confirmar contraseña -->
                            <div class="cuenta-form-group" style="margin-bottom: 4px;">
                                <label for="seguridad_pass_confirm">
                                    Confirmar nueva contraseña
                                </label>
                                <div class="password-wrapper">
                                    <span class="password-field-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg></span>
                                    <input
                                        type="password"
                                        name="password_confirmacion"
                                        id="seguridad_pass_confirm"
                                        placeholder="Confirmar nueva contraseña"
                                        required
                                        minlength="8"
                                        autocomplete="new-password"
                                        oninput="validarSeguridadConfirmModal()">
                                    <button
                                        type="button"
                                        class="toggle-password toggle-password-btn"
                                        data-target="seguridad_pass_confirm"
                                        aria-label="Mostrar contraseña"
                                        title="Mostrar contraseña">
                                        <svg class="eye-closed" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                                        <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </button>
                                </div>
                                <small class="field-error" data-error-for="password_confirmacion"></small>
                            </div>

                            <!-- Feedback dinámico de coincidencia -->
                            <div id="seguridadConfirmFeedback" class="auth-confirm-msg" style="display: none; margin-bottom: 14px;"></div>

                            <div class="cuenta-form-actions" style="display: flex; margin-top: 16px;">
                                <button
                                    type="submit"
                                    id="btnActualizarPassword"
                                    class="cuenta-primary-button">
                                    Actualizar contraseña
                                </button>
                            </div>

                        </form>

                    </div>

                </div>

            </section>

        </div>

    </main>

    <!-- FOOTER OSCURO CANÓNICO -->
    <?php include 'Views/Home/partials/footer.php'; ?>

    



    <div
        class="cuenta-modal-overlay"
        id="modalDireccion"
        aria-hidden="true">

        <div class="cuenta-modal">

            <div class="cuenta-modal-header">

                <div>
                    <span class="cuenta-section-eyebrow">
                        Entrega
                    </span>

                    <h2 id="tituloModalDireccion">
                        Nueva dirección
                    </h2>
                </div>

                <button type="button" class="btn-close-universal cuenta-modal-close" data-close-modal="modalDireccion">&times;</button>

            </div>

            <form
                id="formDireccion"
                action="index.php?action=crear_direccion"
                method="POST"
                autocomplete="off">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= escapar($csrf_token); ?>">

                <input
                    type="hidden"
                    name="id_direccion"
                    id="direccionId">

                <div class="cuenta-modal-body">

                    <div class="cuenta-form-grid">

                        <div class="cuenta-form-group">

                            <label for="direccionDepartamento">
                                Departamento
                            </label>

                            <input
                                type="text"
                                name="departamento"
                                id="direccionDepartamento"
                                maxlength="60"
                                placeholder="Ej. Lambayeque"
                                required>

                            <small
                                class="field-error"
                                data-error-for="departamento"></small>

                        </div>

                        <div class="cuenta-form-group">

                            <label for="direccionProvincia">
                                Provincia
                            </label>

                            <input
                                type="text"
                                name="provincia"
                                id="direccionProvincia"
                                maxlength="60"
                                placeholder="Ej. Chiclayo"
                                required>

                            <small
                                class="field-error"
                                data-error-for="provincia"></small>

                        </div>

                        <div class="cuenta-form-group">

                            <label for="direccionDistrito">
                                Distrito
                            </label>

                            <input
                                type="text"
                                name="distrito"
                                id="direccionDistrito"
                                maxlength="60"
                                placeholder="Ej. La Victoria"
                                required>

                            <small
                                class="field-error"
                                data-error-for="distrito"></small>

                        </div>

                        <div class="cuenta-form-group">

                            <label for="direccionCalle">
                                Dirección completa
                            </label>

                            <input
                                type="text"
                                name="calle"
                                id="direccionCalle"
                                maxlength="200"
                                placeholder="Av., calle, número y urbanización"
                                required>

                            <small
                                class="field-error"
                                data-error-for="calle"></small>

                        </div>

                        <div class="cuenta-form-group cuenta-form-full">

                            <label for="direccionReferencia">
                                Referencia
                            </label>

                            <textarea
                                name="referencia"
                                id="direccionReferencia"
                                rows="3"
                                maxlength="200"
                                placeholder="Ej. Frente al parque principal"></textarea>

                        </div>

                        <label class="principal-checkbox cuenta-form-full">

                            <input
                                type="checkbox"
                                name="es_principal"
                                id="direccionPrincipal"
                                value="1">

                            <span class="principal-checkmark"></span>

                            <span>
                                <strong>
                                    Usar como dirección principal
                                </strong>

                                <small>
                                    Se seleccionará por defecto al comprar.
                                </small>
                            </span>

                        </label>

                    </div>

                </div>

                <div class="cuenta-modal-footer">

                    <button
                        type="button"
                        class="cuenta-secondary-button"
                        data-close-modal="modalDireccion">
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="cuenta-primary-button"
                        id="btnGuardarDireccion">
                        Guardar dirección
                    </button>

                </div>

            </form>

        </div>

    </div>

    



    <div
        class="cuenta-modal-overlay"
        id="modalDetallePedido"
        aria-hidden="true">

        <div class="cuenta-modal cuenta-modal-large">

            <div class="cuenta-modal-header">

                <div>
                    <span class="cuenta-section-eyebrow">
                        Compra
                    </span>

                    <h2 id="detallePedidoTitulo">
                        Detalle del pedido
                    </h2>
                </div>

                <button type="button" class="btn-close-universal cuenta-modal-close" data-close-modal="modalDetallePedido">&times;</button>

            </div>

            <div
                class="cuenta-modal-body"
                id="detallePedidoContenido">
                <div class="cuenta-loading">
                    Cargando pedido...
                </div>
            </div>

        </div>

    </div>

    



    <div
        class="cuenta-modal-overlay"
        id="modalEliminarDireccion"
        aria-hidden="true">

        <div class="cuenta-confirm-modal">

            <div class="confirm-icon" style="display:flex; align-items:center; justify-content:center; color:#ef4444;">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
            </div>

            <h2>¿Eliminar dirección?</h2>

            <p>
                La dirección dejará de aparecer entre tus
                opciones de entrega.
            </p>

            <div class="confirm-actions">

                <button
                    type="button"
                    class="cuenta-secondary-button"
                    data-close-modal="modalEliminarDireccion">
                    Cancelar
                </button>

                <button
                    type="button"
                    class="cuenta-danger-button"
                    id="confirmarEliminarDireccion">
                    Eliminar
                </button>

            </div>

        </div>

    </div>

    

    <div
        class="cuenta-toast-container"
        id="cuentaToastContainer"></div>

    <script>
        const initialLockTime = <?= $remainingLock ?? 0 ?>;

        document.addEventListener('DOMContentLoaded', function() {
            const btnPin = document.getElementById('btnSolicitarPinSeguridad');
            if (btnPin) {
                btnPin.addEventListener('click', async function() {
                    const alertBox = document.getElementById('alertPinSeguridad');
                    btnPin.disabled = true;
                    btnPin.textContent = 'Enviando PIN...';
                    if (alertBox) alertBox.style.display = 'none';

                    try {
                        const formData = new FormData();
                        formData.append('csrf_token', '<?= $csrf_token; ?>');
                        const resp = await fetch('index.php?action=enviar_pin_seguridad', {
                            method: 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            body: formData
                        });
                        const data = await resp.json();
                        if (data.success || data.ok) {
                            if (alertBox) {
                                alertBox.className = 'cuenta-alert cuenta-alert-success';
                                alertBox.textContent = data.message || 'Código PIN enviado a tu correo.';
                                alertBox.style.display = 'block';
                            }
                            btnPin.textContent = 'Reenviar PIN';
                            btnPin.disabled = false;
                        } else {
                            throw new Error(data.message || 'No se pudo enviar el PIN.');
                        }
                    } catch (err) {
                        if (alertBox) {
                            alertBox.className = 'cuenta-alert cuenta-alert-error';
                            alertBox.textContent = err.message || 'Error enviando el PIN.';
                            alertBox.style.display = 'block';
                        }
                        btnPin.disabled = false;
                        btnPin.textContent = 'Enviar Código PIN';
                    }
                });
            }
        });

        window.validarSeguridadPassModal = function(val) {
            const card = document.getElementById('seguridadPassChecklist');
            const passInput = document.getElementById('seguridad_pass_nueva');

            const hasMin = val.length >= 8;
            const hasUpper = /[A-Z]/.test(val);
            const hasNum = /[0-9]/.test(val);
            const hasSpecial = /[^A-Za-z0-9]/.test(val);

            const updateItem = (id, valid) => {
                const el = document.getElementById(id);
                if (!el) return;
                if (valid) {
                    el.classList.add('valid');
                } else {
                    el.classList.remove('valid');
                }
            };

            updateItem('segChkMinChar', hasMin);
            updateItem('segChkUpper', hasUpper);
            updateItem('segChkNum', hasNum);
            updateItem('segChkSpecial', hasSpecial);

            const todoCompleto = hasMin && hasUpper && hasNum && hasSpecial;

            if (card) {
                if (todoCompleto) {
                    card.style.display = 'none';
                } else if (document.activeElement === passInput) {
                    card.style.display = 'block';
                }
            }

            validarSeguridadConfirmModal();
        };

        window.mostrarSeguridadPassChecklist = function() {
            const card = document.getElementById('seguridadPassChecklist');
            const val = document.getElementById('seguridad_pass_nueva').value;
            const hasMin = val.length >= 8;
            const hasUpper = /[A-Z]/.test(val);
            const hasNum = /[0-9]/.test(val);
            const hasSpecial = /[^A-Za-z0-9]/.test(val);
            const todoCompleto = hasMin && hasUpper && hasNum && hasSpecial;

            if (card && !todoCompleto) {
                card.style.display = 'block';
            }
        };

        window.ocultarSeguridadPassChecklist = function() {
            setTimeout(() => {
                const card = document.getElementById('seguridadPassChecklist');
                if (card) {
                    card.style.display = 'none';
                }
            }, 200);
        };

        window.validarSeguridadConfirmModal = function() {
            const pass = document.getElementById('seguridad_pass_nueva').value;
            const confirmInput = document.getElementById('seguridad_pass_confirm');
            const confirmVal = confirmInput ? confirmInput.value : '';
            const feedback = document.getElementById('seguridadConfirmFeedback');

            if (!feedback) return;

            if (confirmVal.length === 0) {
                feedback.style.display = 'none';
                feedback.className = 'auth-confirm-msg';
                return;
            }

            feedback.style.display = 'flex';
            if (confirmVal === pass) {
                feedback.className = 'auth-confirm-msg match';
                feedback.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> <span>Las contraseñas coinciden</span>`;
            } else {
                feedback.className = 'auth-confirm-msg mismatch';
                feedback.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg> <span>Las contraseñas no coinciden</span>`;
            }
        };
    </script>
    <script src="Scripts/cuenta.js?v=<?= time(); ?>"></script>

</body>

</html>