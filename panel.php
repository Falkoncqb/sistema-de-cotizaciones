<?php
session_start();
// Si el usuario no ha iniciado sesión, redirigirlo a la página de acceso
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Cotizaciones</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f2f5; margin: 0; }
        .main-container { display: flex; width: 100%; }
        .form-section { width: 100%; padding: 20px; overflow-y: auto; background-color: #fff; box-sizing: border-box; }
        h1, h2, h3 { color: #333; }
        .container { padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); background: #fff; }
        fieldset { border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; }
        legend { font-weight: bold; color: #333; padding: 0 10px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="tel"], input[type="date"], input[type="number"], textarea { width: 100%; padding: 10px; margin-bottom: 10px; border-radius: 4px; border: 1px solid #ccc; box-sizing: border-box; }
        .grid-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #e9ecef; }
        .button-container { display: flex; gap: 10px; margin-top: 10px; }
        .main-button, .secondary-button, .action-button, .client-button { padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; text-align: center; }
        .main-button { background-color: #28a745; color: white; flex-grow: 1; }
        .secondary-button { background-color: #007bff; color: white; }
        .danger-button { background-color: #ffc107; color: #212529; }
        .client-button { background-color: #6c757d; color: white; width: 100%; margin-bottom: 15px; }
        .client-list-item { background: #fff; border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; }
        .client-list-item h3 { margin-top: 0; }
        .client-actions { display: flex; gap: 8px; margin-top: 10px; }
        .action-button.edit { background-color: #ffc107; color: #212529; text-decoration: none; display: inline-block;}
        .action-button.delete { background-color: #dc3545; color: white; }
        .action-button.quote { background-color: #17a2b8; color: white; }
        .delete-item-btn { background-color: #dc3545; padding: 5px 10px; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .sidebar { height: 100vh; width: 500px; position: fixed; z-index: 1001; top: 0; right: -500px; background-color: #f8f9fa; overflow-x: hidden; transition: 0.3s; box-shadow: -3px 0 6px rgba(0,0,0,0.1); display: flex; flex-direction: column; }
        .sidebar.open { right: 0; }
        .sidebar-header { display: flex; border-bottom: 1px solid #dee2e6; }
        .sidebar-tab { flex: 1; padding: 15px; cursor: pointer; text-align: center; background-color: #e9ecef; font-weight: bold; }
        .sidebar-tab.active { background-color: #fff; border-bottom: 2px solid #007bff; }
        .sidebar-content-container { padding: 20px; overflow-y: auto; flex-grow: 1; }
        .sidebar-content { display: none; }
        .sidebar-content.active { display: block; }
        .top-button-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .top-button-group { display: flex; gap: 10px; }
        .top-button { font-size: 14px; cursor: pointer; background-color: #007bff; color: white; border: none; border-radius: 5px; padding: 10px 15px; text-decoration: none; text-align: center; }
        .logout-button { background-color: #dc3545; }
        #searchInput { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .modal { display: none; position: fixed; z-index: 1003; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 450px; border-radius: 8px; text-align: center; }
        .modal-content h2 { margin-top: 0; }
        .modal-content .button-container { justify-content: center; }
    </style>
</head>
<body>
<div class="main-container">
    <div class="form-section">
        <div class="top-button-bar">
            <div class="top-button-group">
                <button id="sidebar-toggle" class="top-button" onclick="toggleSidebar()">GESTION DE CLIENTES</button>
                <a href="cliente_form.php" target="_blank" class="top-button">CREAR NUEVO CLIENTE</a>
            </div>
            <a href="logout.php" class="top-button logout-button">Cerrar Sesión</a>
        </div>
        <div style="text-align: center; margin-bottom: 20px;">
            <img src="logomb.jpg" alt="Logo M&B Soluciones" style="max-width: 250px; height: auto;">
        </div>
        <h1>OFERTA ECONÓMICA</h1>
        <form id="cotizacionForm" action="generar_cotizacion.php" method="post" target="_blank">
            <input type="hidden" id="cotizacion_id_hidden" name="cotizacion_id">
            <input type="hidden" id="cliente_id" name="cliente_id">
            <div class="grid-container">
                <fieldset>
                    <legend>Datos del Emisor</legend>
                    <label for="emisor_empresa">Empresa:</label>
                    <input type="text" id="emisor_empresa" name="emisor_empresa" value="M&B Soluciones SpA" readonly>
                    <label for="emisor_rut">RUT:</label>
                    <input type="text" id="emisor_rut" name="emisor_rut" value="77.858.422-0" readonly>
                    <label for="emisor_cotizado_por">Cotizado por:</label>
                    <input type="text" id="emisor_cotizado_por" name="emisor_cotizado_por" placeholder="Nombre completo">
                </fieldset>
                <fieldset>
                    <legend>Datos de la Cotización</legend>
                    <label for="cotizacion_no">Nº Cotización:</label>
                    <input type="text" id="cotizacion_no" name="cotizacion_no" placeholder="Ej: COT-001/24">
                    <label for="fecha">Fecha:</label>
                    <input type="date" id="fecha" name="fecha">
                    <label for="referencia">Referencia:</label>
                    <input type="text" id="referencia" name="referencia" placeholder="Ej: Venta 30 Ruedas">
                </fieldset>
            </div>
            <fieldset id="fieldset-cliente">
                <legend>Datos del Cliente</legend>
                <div class="grid-container">
                    <div>
                        <label for="cliente_nombre">Nombre:</label>
                        <input type="text" id="cliente_nombre" name="cliente_nombre" required>
                        <label for="cliente_empresa">Empresa:</label>
                        <input type="text" id="cliente_empresa" name="cliente_empresa">
                        <label for="cliente_rut">RUT:</label>
                        <input type="text" id="cliente_rut" name="cliente_rut" required>
                    </div>
                    <div>
                        <label for="cliente_direccion">Dirección:</label>
                        <input type="text" id="cliente_direccion" name="cliente_direccion">
                        <label for="cliente_telefono">Teléfono:</label>
                        <input type="tel" id="cliente_telefono" name="cliente_telefono">
                        <label for="cliente_email">Email:</label>
                        <input type="email" id="cliente_email" name="cliente_email">
                    </div>
                </div>
            </fieldset>
            <fieldset id="items-fieldset">
                <legend>Ítems a Cotizar</legend>
                <div id="items-container"></div>
                <button type="button" class="secondary-button" onclick="agregarGrupo()">+ Agregar Ítem</button>
            </fieldset>
            <fieldset>
                <legend>Condiciones Comerciales</legend>
                <div class="grid-container">
                    <div>
                        <label for="aceptacion">Aceptación de Cotización:</label>
                        <textarea id="aceptacion" name="aceptacion" rows="2"></textarea>
                        <label for="garantia">Garantía:</label>
                        <textarea id="garantia" name="garantia" rows="2"></textarea>
                    </div>
                    <div>
                        <label for="forma_pago">Forma de Pago:</label>
                        <textarea id="forma_pago" name="forma_pago" rows="2"></textarea>
                        <label for="lugar_entrega">Lugar de Entrega:</label>
                        <textarea id="lugar_entrega" name="lugar_entrega" rows="2"></textarea>
                    </div>
                </div>
            </fieldset>
            <fieldset>
                <legend>Totales</legend>
                <div class="grid-container">
                    <div></div>
                    <div>
                        <label>Neto:</label>
                        <input type="text" id="neto" name="neto" readonly>
                        <label>IVA (19%):</label>
                        <input type="text" id="iva" name="iva" readonly>
                        <label>Total:</label>
                        <input type="text" id="total" name="total" readonly>
                    </div>
                </div>
            </fieldset>
            <div class="button-container">
                <button id="main-action-button" class="main-button" type="submit">Generar Cotización en PDF</button>
                <button class="danger-button" type="button" onclick="limpiarCotizacion()">Limpiar Cotización</button>
            </div>
        </form>
    </div>
</div>
<div id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-tab active" data-tab="clientes">Listado de Clientes</div>
        <div class="sidebar-tab" data-tab="cotizaciones">Historial</div>
    </div>
    <div class="sidebar-content-container">
        <input type="text" id="searchInput" placeholder="Buscar...">
        <div id="tab-clientes" class="sidebar-content active">
            <div id="client-list"></div>
        </div>
        <div id="tab-cotizaciones" class="sidebar-content">
            <div id="listaCotizaciones"></div>
        </div>
    </div>
</div>
<div id="custom-alert-modal" class="modal">
    <div class="modal-content">
        <h2 id="alert-title"></h2>
        <p id="alert-message"></p>
        <div class="button-container">
            <button id="alert-confirm-btn" class="main-button"></button>
            <button id="alert-cancel-btn" class="secondary-button">Cancelar</button>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    limpiarCotizacion(false); 
    document.getElementById('cotizacionForm').addEventListener('submit', handleFormSubmit);
    document.getElementById('alert-cancel-btn').addEventListener('click', cerrarAlerta);
    document.getElementById('searchInput').addEventListener('input', handleSearch);
    document.getElementById('cotizacionForm').addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            const target = event.target;
            if (target.tagName === 'TEXTAREA' || target.type === 'submit' || target.type === 'button') {
                return;
            }
            event.preventDefault();
            const formElements = Array.from(this.elements);
            const currentIndex = formElements.indexOf(target);
            let nextElement = null;
            for (let i = currentIndex + 1; i < formElements.length; i++) {
                const el = formElements[i];
                if (el.offsetParent !== null && !el.disabled && !el.readOnly && el.type !== 'hidden') {
                    nextElement = el;
                    break;
                }
            }
            if (nextElement) {
                nextElement.focus();
                if (nextElement.select) {
                    nextElement.select();
                }
            }
        }
    });
    document.getElementById('items-container').addEventListener('input', calcularTotales);
    document.querySelectorAll('.sidebar-tab').forEach(tab => {
        tab.addEventListener('click', () => mostrarTab(tab.dataset.tab));
    });
    document.querySelector('.form-section').addEventListener('click', function(event) {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        if (sidebar.classList.contains('open') && !sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
            toggleSidebar();
        }
    });
    mostrarTab('clientes');
});
function cerrarAlerta() {
    document.getElementById('custom-alert-modal').style.display = 'none';
}
function mostrarAlerta(titulo, mensaje, confirmText, onConfirm, onCancel = null) {
    document.getElementById('alert-title').textContent = titulo;
    document.getElementById('alert-message').textContent = mensaje;
    const confirmBtn = document.getElementById('alert-confirm-btn');
    confirmBtn.textContent = confirmText;
    confirmBtn.onclick = () => {
        cerrarAlerta();
        if (onConfirm) onConfirm();
    };
    const cancelBtn = document.getElementById('alert-cancel-btn');
    cancelBtn.style.display = onCancel ? 'inline-block' : 'none';
    if (onCancel) {
        cancelBtn.onclick = () => {
            cerrarAlerta();
            if (onCancel) onCancel();
        };
    }
    document.getElementById('custom-alert-modal').style.display = 'block';
}
function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); }
function mostrarTab(tabId) {
    document.querySelectorAll('.sidebar-tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.sidebar-content').forEach(content => content.classList.remove('active'));
    const activeTabElement = document.querySelector(`.sidebar-tab[data-tab='${tabId}']`);
    if (activeTabElement) activeTabElement.classList.add('active');
    const activeContentElement = document.getElementById(`tab-${tabId}`);
    if (activeContentElement) activeContentElement.classList.add('active');
    const searchInput = document.getElementById('searchInput');
    searchInput.value = '';
    searchInput.placeholder = tabId === 'clientes' ? 'Buscar por nombre o RUT...' : 'Buscar por Nº o cliente...';
    if (tabId === 'clientes') {
        cargarClientes();
    } else if (tabId === 'cotizaciones') {
        cargarHistorialCotizaciones();
    }
}
function handleSearch(event) {
    const term = event.target.value.trim().toLowerCase();
    const activeTab = document.querySelector('.sidebar-tab.active').dataset.tab;
    if (term === '') {
        if (activeTab === 'clientes') cargarClientes(); else cargarHistorialCotizaciones();
        return;
    }
    if (activeTab === 'clientes') {
        fetch(`api_clientes.php?accion=buscar&term=${encodeURIComponent(term)}`).then(res => res.json()).then(renderClientes);
    } else {
        fetch(`api_cotizaciones.php?accion=buscar&term=${encodeURIComponent(term)}`).then(res => res.json()).then(renderCotizaciones);
    }
}
function renderClientes(clientes) {
    const lista = document.getElementById('client-list');
    lista.innerHTML = '';
    if (clientes && clientes.length > 0) {
        clientes.forEach(cliente => {
            const item = document.createElement('div');
            item.className = 'client-list-item';
            const editLink = `cliente_form.php?id=${cliente.id}`;
            item.innerHTML = `<h3>${cliente.nombre || 'Sin nombre'}</h3><p>${cliente.empresa || ''}<br>${cliente.rut || ''}</p><div class="client-actions"><button class="action-button quote" onclick="seleccionarCliente(${cliente.id})">Cotizar</button><a href="${editLink}" target="_blank" class="action-button edit">Editar</a><button class="action-button delete" onclick="borrarCliente(${cliente.id})">Borrar</button></div>`;
            lista.appendChild(item);
        });
    } else { lista.innerHTML = '<p>No se encontraron clientes.</p>'; }
}
function renderCotizaciones(cotizaciones) {
    const lista = document.getElementById('listaCotizaciones');
    let html = `<table style="width:100%; font-size: 14px;"><thead><tr><th>#</th><th>Fecha</th><th>Cliente</th><th>Acciones</th></tr></thead><tbody>`;
    if (cotizaciones && cotizaciones.length > 0) {
        cotizaciones.forEach(cot => {
            let fechaFormateada = 'N/A';
            if (cot.fecha_cotizacion) {
                const fecha = new Date(cot.fecha_cotizacion + 'T00:00:00');
                fechaFormateada = fecha.toLocaleDateString('es-CL');
            }
            html += `<tr><td>${cot.cotizacion_no}</td><td>${fechaFormateada}</td><td>${cot.nombre_cliente || 'N/A'}</td><td style="display: flex; gap: 5px;"><button class="action-button edit" style="padding: 5px 8px;" onclick="modificarCotizacion(${cot.id})">Modificar</button><a href="ver_pdf.php?id=${cot.id}" target="_blank" class="action-button quote" style="padding: 5px 8px; text-decoration: none;">PDF</a><button class="action-button delete" style="padding: 5px 8px;" onclick="borrarCotizacion(${cot.id})">Borrar</button></td></tr>`;
        });
    } else { html += '<tr><td colspan="4">No se encontraron cotizaciones.</td></tr>'; }
    html += '</tbody></table>';
    lista.innerHTML = html;
}
function cargarClientes() {
    const lista = document.getElementById('client-list');
    lista.innerHTML = '<p>Cargando clientes...</p>';
    fetch('api_clientes.php?accion=get_all').then(response => response.json()).then(renderClientes).catch(error => { console.error('Error al cargar clientes:', error); lista.innerHTML = '<p style="color: red;">Error al cargar los clientes.</p>'; });
}
function cargarHistorialCotizaciones() {
    const lista = document.getElementById('listaCotizaciones');
    lista.innerHTML = '<p>Cargando historial...</p>';
    fetch('api_cotizaciones.php?accion=get_all').then(response => response.json()).then(renderCotizaciones).catch(error => { console.error('Error al cargar historial:', error); lista.innerHTML = '<p style="color: red;">Error al cargar el historial.</p>'; });
}
async function handleFormSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const cotizacionId = document.getElementById('cotizacion_id_hidden').value;
    const clienteId = document.getElementById('cliente_id').value;
    const esActualizacion = cotizacionId !== '';
    const esClienteNuevo = clienteId === '';
    const proceed = () => {
        if (esActualizacion) {
            actualizarCotizacionEnBD();
        } else {
            // Se abre en una nueva pestaña
        }
    };
    if (esActualizacion) {
        proceed();
    } else if (esClienteNuevo) {
        mostrarAlerta('Confirmar Acción', 'Se guardará este nuevo cliente y la cotización se abrirá en una nueva pestaña. ¿Continuar?', 'Continuar', () => form.submit(), () => {});
    } else {
        form.submit();
    }
}
function modificarCotizacion(id) {
    fetch(`api_cotizaciones.php?accion=get_by_id&id=${id}`).then(res => res.json()).then(cot => {
        if (!cot) { alert('Error: No se encontró la cotización.'); return; }
        limpiarCotizacion(false);
        document.getElementById('cotizacion_id_hidden').value = cot.id;
        seleccionarCliente(cot.cliente_id, false);
        document.getElementById('cotizacion_no').value = cot.cotizacion_no || '';
        document.getElementById('fecha').value = cot.fecha_cotizacion || '';
        document.getElementById('referencia').value = cot.referencia || '';
        document.getElementById('aceptacion').value = cot.aceptacion || '';
        document.getElementById('garantia').value = cot.garantia || '';
        document.getElementById('forma_pago').value = cot.forma_pago || '';
        document.getElementById('lugar_entrega').value = cot.lugar_entrega || '';
        const itemsContainer = document.getElementById('items-container');
        itemsContainer.innerHTML = '';
        if (cot.items && Array.isArray(cot.items)) {
            cot.items.forEach((groupData, groupIndex) => {
                agregarGrupo(groupData.title);
                if (groupData.subitems && Array.isArray(groupData.subitems)) {
                    groupData.subitems.forEach((subitemData, subitemIndex) => {
                        const grupoActual = itemsContainer.querySelectorAll('.item-group')[groupIndex];
                        if (subitemIndex > 0) {
                           agregarSubitem(grupoActual.querySelector('button[onclick*="agregarSubitem"]'));
                        }
                        const subitemRow = grupoActual.querySelectorAll('tbody tr')[subitemIndex];
                        subitemRow.querySelector('input[name*="[descripcion]"]').value = subitemData.descripcion || '';
                        subitemRow.querySelector('input[name*="[cantidad]"]').value = subitemData.cantidad || 1;
                        let precio = parseFloat(subitemData.precio_unitario || 0);
                        subitemRow.querySelector('input[name*="[precio_unitario]"]').value = precio.toLocaleString('es-CL');
                    });
                }
            });
        }
        calcularTotales();
        document.getElementById('main-action-button').textContent = 'Actualizar y Previsualizar';
        toggleSidebar();
    });
}
function actualizarCotizacionEnBD() {
    const itemsData = recolectarItems();
    let montoTotal = 0;
    itemsData.forEach(g => {
        if(g.subitems) {
            g.subitems.forEach(s => montoTotal += (parseFloat(s.cantidad) || 0) * (desformatearParaCalculo(s.precio_unitario) || 0) )
        }
    });
    const data = {
        accion: 'update',
        id: document.getElementById('cotizacion_id_hidden').value,
        cliente_id: document.getElementById('cliente_id').value,
        cotizacion_no: document.getElementById('cotizacion_no').value,
        fecha: document.getElementById('fecha').value,
        referencia: document.getElementById('referencia').value,
        items: itemsData,
        monto_total: montoTotal,
        aceptacion: document.getElementById('aceptacion').value,
        garantia: document.getElementById('garantia').value,
        forma_pago: document.getElementById('forma_pago').value,
        lugar_entrega: document.getElementById('lugar_entrega').value,
    };
    fetch('api_cotizaciones.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                mostrarAlerta('Éxito', response.success + " Previsualice el PDF.", 'Ver PDF', () => {
                    window.open(`ver_pdf.php?id=${data.id}`, '_blank');
                    limpiarCotizacion(false)
                });
            } else {
                mostrarAlerta('Error', response.error || 'Ocurrió un problema.', 'Cerrar');
            }
        });
}
function limpiarCotizacion(conAviso = true) {
    document.getElementById('cotizacionForm').reset();
    document.getElementById('cliente_id').value = '';
    document.getElementById('cotizacion_id_hidden').value = '';
    document.getElementById('items-container').innerHTML = '';
    agregarGrupo();
    document.getElementById('cotizacion_no').value = '';
    document.getElementById('fecha').valueAsDate = new Date();
    document.getElementById('emisor_cotizado_por').value = "";
    document.getElementById('main-action-button').textContent = 'Generar Cotización en PDF';
    document.getElementById('aceptacion').value = 'Contra Orden de Compra o aceptación de esta cotización.';
    document.getElementById('garantia').value = 'Sin garantía, el producto es nuevo.';
    document.getElementById('forma_pago').value = 'Transferencia bancaria, luego se emite y se envia la factura.';
    document.getElementById('lugar_entrega').value = 'Enviado por Varmontt por pagar donde el cliente indique.';
    if (conAviso) { mostrarAlerta('Información', 'Formulario limpiado.', 'Aceptar'); }
}
function agregarGrupo(titulo = '') {
    const container = document.getElementById('items-container');
    const groupIndex = container.querySelectorAll('.item-group').length;
    const groupDiv = document.createElement('div');
    groupDiv.className = 'item-group';
    groupDiv.style.marginBottom = '20px';
    groupDiv.innerHTML = `<fieldset><legend>Ítem ${groupIndex + 1}</legend><button type="button" onclick="this.closest('.item-group').remove(); recalcularIndices(); calcularTotales();" style="float: right; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">X</button><label>Título del Ítem:</label><input type="text" name="items[${groupIndex}][title]" value="${titulo}" placeholder="Ej: Venta de Productos o Servicio de Mantención"><table><thead><tr><th>#</th><th>Descripción</th><th>Cant.</th><th>P. Unitario</th><th>Total</th><th></th></tr></thead><tbody></tbody></table><button type="button" class="secondary-button" onclick="agregarSubitem(this)">Insertar Linea</button></fieldset>`;
    container.appendChild(groupDiv);
    agregarSubitem(groupDiv.querySelector('button[onclick*="agregarSubitem"]'));
}
function agregarSubitem(btn) {
    const tbody = btn.closest('fieldset').querySelector('tbody');
    const group = btn.closest('.item-group');
    const groupIndex = Array.from(document.querySelectorAll('.item-group')).indexOf(group);
    const subitemIndex = tbody.querySelectorAll('tr').length;
    const fila = document.createElement('tr');
    fila.innerHTML = `<td>${groupIndex + 1}.${subitemIndex + 1}</td><td><input type="text" name="items[${groupIndex}][subitems][${subitemIndex}][descripcion]" placeholder="Descripción del sub-ítem"></td><td><input type="number" name="items[${groupIndex}][subitems][${subitemIndex}][cantidad]" value="1" min="0" step="any"></td><td><input type="text" name="items[${groupIndex}][subitems][${subitemIndex}][precio_unitario]" value="0" oninput="formatearNumeroInput(this)"></td><td><input type="text" name="items[${groupIndex}][subitems][${subitemIndex}][total]" readonly></td><td><button type="button" class="delete-item-btn" onclick="eliminarFila(this)">X</button></td>`;
    tbody.appendChild(fila);
}
function eliminarFila(btn) {
    const fila = btn.closest('tr');
    const tbody = fila.parentNode;
    fila.remove();
    recalcularIndicesSubitems(tbody);
    calcularTotales();
}
function recalcularIndices() {
    document.querySelectorAll('.item-group').forEach((group, groupIndex) => {
        group.querySelector('legend').textContent = `Ítem ${groupIndex + 1}`;
        group.querySelector('input[name*="[title]"]').name = `items[${groupIndex}][title]`;
        group.querySelectorAll('tbody tr').forEach((row, subitemIndex) => {
            row.querySelector('td:first-child').textContent = `${groupIndex + 1}.${subitemIndex + 1}`;
            row.querySelector('input[name*="[descripcion]"]').name = `items[${groupIndex}][subitems][${subitemIndex}][descripcion]`;
            row.querySelector('input[name*="[cantidad]"]').name = `items[${groupIndex}][subitems][${subitemIndex}][cantidad]`;
            row.querySelector('input[name*="[precio_unitario]"]').name = `items[${groupIndex}][subitems][${subitemIndex}][precio_unitario]`;
            row.querySelector('input[name*="[total]"]').name = `items[${groupIndex}][subitems][${subitemIndex}][total]`;
        });
    });
}
function recalcularIndicesSubitems(tbody) {
    const group = tbody.closest('.item-group');
    const groupIndex = Array.from(document.querySelectorAll('.item-group')).indexOf(group);
    tbody.querySelectorAll('tr').forEach((row, subitemIndex) => {
        row.querySelector('td:first-child').textContent = `${groupIndex + 1}.${subitemIndex + 1}`;
    });
}
function recolectarItems() {
    const itemsData = [];
    document.querySelectorAll('.item-group').forEach((group, groupIndex) => {
        const subitems = [];
        group.querySelectorAll('tbody tr').forEach((row) => {
            subitems.push({
                descripcion: row.querySelector(`input[name*="[descripcion]"]`).value,
                cantidad: row.querySelector(`input[name*="[cantidad]"]`).value,
                precio_unitario: desformatearParaCalculo(row.querySelector(`input[name*="[precio_unitario]"]`).value),
            });
        });
        itemsData.push({
            title: group.querySelector(`input[name*="[title]"]`).value,
            subitems: subitems,
        });
    });
    return itemsData;
}
function calcularTotales() {
    let netoTotal = 0;
    document.querySelectorAll('.item-group').forEach(group => {
        group.querySelectorAll('tbody tr').forEach(row => {
            const cantidad = parseFloat(row.querySelector('input[name*="[cantidad]"]').value) || 0;
            const precioUnitario = desformatearParaCalculo(row.querySelector('input[name*="[precio_unitario]"]').value);
            const totalFila = cantidad * precioUnitario;
            row.querySelector('input[name*="[total]"]').value = formatearMoneda(totalFila);
            netoTotal += totalFila;
        });
    });
    const iva = netoTotal * 0.19;
    const total = netoTotal + iva;
    document.getElementById('neto').value = formatearMoneda(netoTotal);
    document.getElementById('iva').value = formatearMoneda(iva);
    document.getElementById('total').value = formatearMoneda(total);
}
function formatearMoneda(valor) { return new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(valor); }
function desformatearParaCalculo(valor) {
    if (typeof valor !== 'string') {
        valor = String(valor);
    }
    return parseFloat(valor.replace(/\./g, '').replace(/[^0-9,-]/g, '').replace(',', '.')) || 0;
}
function formatearNumeroInput(input) {
    const cursorPosition = input.selectionStart;
    const originalLength = input.value.length;
    let numericValue = input.value.replace(/\D/g, '');
    if (numericValue) {
        const formattedValue = Number(numericValue).toLocaleString('es-CL');
        input.value = formattedValue;
        const newLength = formattedValue.length;
        const lengthDifference = newLength - originalLength;
        input.setSelectionRange(cursorPosition + lengthDifference, cursorPosition + lengthDifference);
    } else {
        input.value = '';
    }
    calcularTotales();
}
function seleccionarCliente(id, generarNuevoNumero = true) {
    fetch(`api_clientes.php?accion=get_by_id&id=${id}`).then(res => res.json()).then(cliente => {
        if(cliente){
            document.getElementById('cliente_id').value = cliente.id || '';
            document.getElementById('cliente_nombre').value = cliente.nombre || '';
            document.getElementById('cliente_empresa').value = cliente.empresa || '';
            document.getElementById('cliente_rut').value = cliente.rut || '';
            document.getElementById('cliente_direccion').value = cliente.direccion || '';
            document.getElementById('cliente_telefono').value = cliente.telefono || '';
            document.getElementById('cliente_email').value = cliente.email || '';
            toggleSidebar();
        }
    });
}
function borrarCotizacion(id) {
    mostrarAlerta('Confirmar Eliminación', '¿Seguro que quieres eliminar esta cotización?', 'Sí, Eliminar', () => {
        fetch('api_cotizaciones.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ accion: 'borrar', id: id }) })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                mostrarAlerta('Éxito', response.success, 'Aceptar', cargarHistorialCotizaciones);
            } else {
                mostrarAlerta('Error', response.error || 'Ocurrió un problema.', 'Cerrar');
            }
        });
    }, () => {});
}
function borrarCliente(id) {
    mostrarAlerta('Confirmar Eliminación', '¿Seguro que quieres eliminar este cliente?', 'Sí, Eliminar', () => {
        fetch('api_clientes.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ accion: 'borrar', id: id }) })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                mostrarAlerta('Éxito', response.success, 'Aceptar', cargarClientes);
            } else {
                mostrarAlerta('Error', response.error || 'Ocurrió un problema.', 'Cerrar');
            }
        });
    }, () => {});
}
</script>
</body>
</html>

