/**
 * main.js - Funcionalidades JavaScript para el Sistema de Gestión de Eventos Académicos
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Inicializar popovers de Bootstrap
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Manejar el cierre automático de alertas
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert-success, .alert-info');
        alerts.forEach(function(alert) {
            // Crear un nuevo objeto de evento bootstrap para cerrar la alerta
            var bsAlert = new bootstrap.Alert(alert);
            // Cerrar la alerta después de 3 segundos
            setTimeout(function() {
                bsAlert.close();
            }, 3000);
        });
    }, 500);

    // Validación de formularios con Bootstrap
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Confirmación para eliminación de elementos
    var deleteButtons = document.querySelectorAll('.confirm-delete');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('¿Está seguro de que desea eliminar este elemento? Esta acción no se puede deshacer.')) {
                e.preventDefault();
            }
        });
    });

    // Búsqueda en tiempo real para tablas
    var searchInputs = document.querySelectorAll('.table-search');
    searchInputs.forEach(function(input) {
        input.addEventListener('keyup', function() {
            var searchText = this.value.toLowerCase();
            var targetTableId = this.getAttribute('data-target');
            var tableRows = document.querySelectorAll('#' + targetTableId + ' tbody tr');
            
            tableRows.forEach(function(row) {
                var text = row.textContent.toLowerCase();
                if (text.indexOf(searchText) === -1) {
                    row.style.display = 'none';
                } else {
                    row.style.display = '';
                }
            });
        });
    });

    // Funcionalidad para el filtrado avanzado
    setupAdvancedFilters();

    // Inicializar selectores con búsqueda
    initializeSelectSearch();

    // Funcionalidad para actualizar estados de asistencia
    setupAttendanceButtons();

    // Funcionalidad para actualizar estados de pago
    setupPaymentStatusButtons();

    // Inicializar gráficos del dashboard si existe el contenedor
    if (document.getElementById('eventsByCategory')) {
        initializeDashboardCharts();
    }
});

/**
 * Configura los filtros avanzados para las tablas de datos
 */
function setupAdvancedFilters() {
    const filterForm = document.getElementById('advancedFilterForm');
    if (!filterForm) return;

    // Restablece los filtros al hacer clic en el botón de restablecer
    const resetButton = filterForm.querySelector('.reset-filters');
    if (resetButton) {
        resetButton.addEventListener('click', function(e) {
            e.preventDefault();
            const inputs = filterForm.querySelectorAll('input, select');
            inputs.forEach(input => {
                if (input.type === 'checkbox' || input.type === 'radio') {
                    input.checked = false;
                } else {
                    input.value = '';
                }
            });
            filterForm.submit();
        });
    }

    // Gestionar cambios en selectores de filtros con autoenvío
    const autoSubmitFilters = filterForm.querySelectorAll('.auto-submit');
    autoSubmitFilters.forEach(filter => {
        filter.addEventListener('change', function() {
            filterForm.submit();
        });
    });
}

/**
 * Inicializa selectores con capacidad de búsqueda
 */
function initializeSelectSearch() {
    // Esta función puede ser expandida para implementar selectores con búsqueda
    // utilizando librerías como Select2 o similares
    console.log('Selectores con búsqueda inicializados');
}

/**
 * Configura los botones de asistencia para actualización rápida
 */
function setupAttendanceButtons() {
    const attendanceButtons = document.querySelectorAll('.attendance-toggle');
    
    attendanceButtons.forEach(button => {
        button.addEventListener('click', function() {
            const registrationId = this.getAttribute('data-registration-id');
            const currentStatus = this.getAttribute('data-status') === '1';
            const newStatus = !currentStatus;
            
            // Aquí se implementaría una llamada AJAX a la API para actualizar el estado
            updateAttendanceStatus(registrationId, newStatus, this);
        });
    });
}

/**
 * Actualiza el estado de asistencia mediante AJAX
 */
function updateAttendanceStatus(registrationId, status, buttonElement) {
    // Simulación de una llamada AJAX a la API
    // En una implementación real, esta sería una petición fetch o XMLHttpRequest
    console.log(`Actualizando asistencia para ID ${registrationId} a ${status ? 'Asistió' : 'No asistió'}`);
    
    // Simular respuesta exitosa
    setTimeout(() => {
        // Actualizar botón y su estado
        buttonElement.setAttribute('data-status', status ? '1' : '0');
        
        if (status) {
            buttonElement.classList.remove('btn-outline-secondary');
            buttonElement.classList.add('btn-success');
            buttonElement.innerHTML = '<i class="bi bi-check-circle"></i> Asistió';
        } else {
            buttonElement.classList.remove('btn-success');
            buttonElement.classList.add('btn-outline-secondary');
            buttonElement.innerHTML = '<i class="bi bi-circle"></i> No registrado';
        }
        
        // Mostrar notificación
        showNotification('Estado de asistencia actualizado con éxito', 'success');
    }, 500);
}

/**
 * Configura los botones de estado de pago para actualización rápida
 */
function setupPaymentStatusButtons() {
    const paymentStatusSelect = document.querySelectorAll('.payment-status-select');
    
    paymentStatusSelect.forEach(select => {
        select.addEventListener('change', function() {
            const registrationId = this.getAttribute('data-registration-id');
            const newStatus = this.value;
            
            // Actualizar estado de pago
            updatePaymentStatus(registrationId, newStatus);
        });
    });
}

/**
 * Actualiza el estado de pago mediante AJAX
 */
function updatePaymentStatus(registrationId, status) {
    // Simulación de una llamada AJAX a la API
    console.log(`Actualizando estado de pago para ID ${registrationId} a ${status}`);
    
    // Simular respuesta exitosa
    setTimeout(() => {
        // Mostrar notificación
        showNotification('Estado de pago actualizado con éxito', 'success');
    }, 500);
}

/**
 * Muestra una notificación temporal
 */
function showNotification(message, type = 'info') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} notification-toast`;
    notification.innerHTML = message;
    
    // Añadir al DOM
    document.body.appendChild(notification);
    
    // Mostrar con animación
    setTimeout(() => {
        notification.classList.add('show');
        
        // Eliminar después de 3 segundos
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }, 100);
}

/**
 * Inicializa gráficos para el dashboard
 */
function initializeDashboardCharts() {
    // Esta función inicializaría gráficos utilizando librerías como Chart.js
    // para mostrar estadísticas en el dashboard
    
    console.log('Inicializando gráficos del dashboard');
    
    // Ejemplo de estructura para inicializar un gráfico de categorías de eventos
    // (esto requeriría incluir la librería Chart.js en el proyecto)
    /*
    const ctx = document.getElementById('eventsByCategory').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Conferencia', 'Taller', 'Seminario', 'Webinar'],
            datasets: [{
                data: [12, 19, 3, 5],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
    */
}

/**
 * Funcionalidad para vista previa de imagen al cargar archivo
 * @param {HTMLInputElement} input - El elemento input de archivo
 * @param {string} previewId - ID del elemento donde se mostrará la vista previa
 */
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

/**
 * Función para exportar tablas a CSV
 * @param {string} tableId - ID de la tabla a exportar
 * @param {string} filename - Nombre del archivo a descargar
 */
function exportTableToCSV(tableId, filename = 'datos.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    // Obtener todas las filas
    const rows = table.querySelectorAll('tr');
    
    // Preparar los datos CSV
    const csvContent = [];
    
    rows.forEach(row => {
        const rowData = [];
        const cols = row.querySelectorAll('td, th');
        
        cols.forEach(col => {
            // Escapar comillas y procesar el texto
            let text = col.innerText;
            text = text.replace(/"/g, '""');
            rowData.push(`"${text}"`);
        });
        
        csvContent.push(rowData.join(','));
    });
    
    // Crear el blob y descargar
    const csv = csvContent.join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * Función para imprimir una sección específica
 * @param {string} elementId - ID del elemento a imprimir
 */
function printElement(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const originalContent = document.body.innerHTML;
    const printContent = element.innerHTML;
    
    document.body.innerHTML = `
        <div class="print-section">
            ${printContent}
        </div>
    `;
    
    window.print();
    document.body.innerHTML = originalContent;
    
    // Reinicializar los scripts después de restaurar el contenido
    document.dispatchEvent(new Event('DOMContentLoaded'));
}