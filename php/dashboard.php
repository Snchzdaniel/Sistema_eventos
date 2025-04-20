<?php
// php/dashboard.php (continuación)

// Incluir las estadísticas
require_once 'includes/stats.php';

// Incluir el encabezado
require_once 'includes/header.php';
?>

<h1 class="mb-4">Dashboard</h1>

<!-- Estadísticas rápidas -->
<div class="row">
    <div class="col-md-3">
        <div class="card bg-primary text-white mb-4">
            <div class="card-body stat-card">
                <i class="fas fa-calendar-alt"></i>
                <div class="stat-number"><?php echo $stats['eventos']; ?></div>
                <div class="stat-label">Eventos</div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="eventos/index.php">Ver detalles</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white mb-4">
            <div class="card-body stat-card">
                <i class="fas fa-user-tie"></i>
                <div class="stat-number"><?php echo $stats['ponentes']; ?></div>
                <div class="stat-label">Ponentes</div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="ponentes/index.php">Ver detalles</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white mb-4">
            <div class="card-body stat-card">
                <i class="fas fa-users"></i>
                <div class="stat-number"><?php echo $stats['participantes']; ?></div>
                <div class="stat-label">Participantes</div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="participantes/index.php">Ver detalles</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white mb-4">
            <div class="card-body stat-card">
                <i class="fas fa-clipboard-list"></i>
                <div class="stat-number"><?php echo $stats['registros']; ?></div>
                <div class="stat-label">Registros</div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="registros/index.php">Ver detalles</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Próximos eventos y últimos registros -->
<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-calendar-alt me-1"></i>
                Próximos Eventos
            </div>
            <div class="card-body">
                <?php if (empty($proximosEventos)): ?>
                    <div class="alert alert-info">No hay próximos eventos programados.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Evento</th>
                                    <th>Fecha</th>
                                    <th>Lugar</th>
                                    <th>Registros</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($proximosEventos as $evento): ?>
                                    <tr class="evento-item">
                                        <td>
                                            <a href="eventos/view.php?id=<?php echo $evento['id']; ?>">
                                                <?php echo htmlspecialchars($evento['title']); ?>
                                            </a>
                                            <span class="badge bg-primary evento-badge"><?php echo htmlspecialchars($evento['category_name']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($evento['event_date']); ?></td>
                                        <td><?php echo htmlspecialchars($evento['location']); ?></td>
                                        <td><?php echo isset($evento['registration_count']) ? $evento['registration_count'] : '0'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer small text-muted">
                <a href="eventos/index.php" class="btn btn-sm btn-primary">Ver todos los eventos</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-clipboard-list me-1"></i>
                Últimos Registros
            </div>
            <div class="card-body">
                <?php if (empty($ultimosRegistros)): ?>
                    <div class="alert alert-info">No hay registros recientes.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Participante</th>
                                    <th>Evento</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimosRegistros as $registro): ?>
                                    <tr>
                                        <td>
                                            <a href="participantes/view.php?id=<?php echo $registro['participant_id']; ?>">
                                                <?php echo htmlspecialchars($registro['participant_name']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="eventos/view.php?id=<?php echo $registro['event_id']; ?>">
                                                <?php echo htmlspecialchars($registro['event_title']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($registro['registration_date']); ?></td>
                                        <td>
                                            <?php if ($registro['payment_status'] == 'completed'): ?>
                                                <span class="badge bg-success">Pagado</span>
                                            <?php elseif ($registro['payment_status'] == 'pending'): ?>
                                                <span class="badge bg-warning">Pendiente</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Cancelado</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer small text-muted">
                <a href="registros/index.php" class="btn btn-sm btn-primary">Ver todos los registros</a>
            </div>
        </div>
    </div>
</div>

<!-- Gráfico estadístico simple -->
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-chart-bar me-1"></i>
                Estadísticas de Eventos por Categoría
            </div>
            <div class="card-body">
                <canvas id="eventsByCategoryChart" width="100%" height="40"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
<script>
// Obtener datos de la API para el gráfico
fetch('http://localhost:3000/api/eventos/stats/by-category')
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const categories = data.data.map(item => item.category_name);
            const counts = data.data.map(item => item.count);
            
            const ctx = document.getElementById('eventsByCategoryChart');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: categories,
                    datasets: [{
                        label: 'Número de Eventos',
                        data: counts,
                        backgroundColor: [
                            'rgba(0, 123, 255, 0.7)',
                            'rgba(40, 167, 69, 0.7)',
                            'rgba(23, 162, 184, 0.7)',
                            'rgba(255, 193, 7, 0.7)',
                            'rgba(220, 53, 69, 0.7)',
                            'rgba(111, 66, 193, 0.7)'
                        ],
                        borderColor: [
                            'rgba(0, 123, 255, 1)',
                            'rgba(40, 167, 69, 1)',
                            'rgba(23, 162, 184, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(220, 53, 69, 1)',
                            'rgba(111, 66, 193, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    })
    .catch(error => console.error('Error al cargar los datos del gráfico:', error));
</script>

<?php
// Incluir el pie de página
require_once 'includes/footer.php';
?>