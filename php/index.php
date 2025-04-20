<?php
// php/index.php
require_once 'includes/api_client.php';
$api = new ApiClient();

// Obtener los próximos eventos
$response = $api->get('/eventos?limit=4&status=active');
$eventos = [];
if ($response['status_code'] == 200 && isset($response['data']['data'])) {
    $eventos = $response['data']['data'];
}

// Incluir el encabezado
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="jumbotron bg-light p-5 rounded">
            <h1 class="display-4">Sistema de Gestión de Eventos Académicos</h1>
            <p class="lead">Gestiona conferencias, talleres, seminarios y webinars de manera eficiente.</p>
            <hr class="my-4">
            <p>Accede a las diferentes secciones para administrar eventos, ponentes, participantes y registros.</p>
            <a class="btn btn-primary btn-lg" href="dashboard.php" role="button">Ver Dashboard</a>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <h2>Próximos Eventos</h2>
    </div>
</div>

<div class="row">
    <?php if (empty($eventos)): ?>
        <div class="col-12">
            <div class="alert alert-info">
                No hay eventos próximos disponibles.
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($eventos as $evento): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card mb-4">
                    <div class="card-header">
                        <?php echo htmlspecialchars($evento['title']); ?>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">
                            <span class="badge bg-primary"><?php echo htmlspecialchars($evento['category_name']); ?></span>
                        </h5>
                        <p class="card-text"><?php echo htmlspecialchars(substr($evento['description'], 0, 100)); ?>...</p>
                        <p>
                            <i class="fas fa-calendar"></i> <?php echo htmlspecialchars($evento['event_date']); ?><br>
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($evento['location']); ?>
                        </p>
                        <a href="eventos/view.php?id=<?php echo $evento['id']; ?>" class="btn btn-sm btn-primary">Ver detalles</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <i class="fas fa-calendar-alt fa-3x text-primary mb-3"></i>
                <h5 class="card-title">Gestión de Eventos</h5>
                <p class="card-text">Crea, actualiza y administra tus eventos académicos.</p>
                <a href="eventos/index.php" class="btn btn-primary">Ir a Eventos</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <i class="fas fa-user-tie fa-3x text-success mb-3"></i>
                <h5 class="card-title">Gestión de Ponentes</h5>
                <p class="card-text">Administra los perfiles de tus ponentes y conferencistas.</p>
                <a href="ponentes/index.php" class="btn btn-success">Ir a Ponentes</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <i class="fas fa-users fa-3x text-info mb-3"></i>
                <h5 class="card-title">Gestión de Participantes</h5>
                <p class="card-text">Registra y administra los participantes de tus eventos.</p>
                <a href="participantes/index.php" class="btn btn-info">Ir a Participantes</a>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir el pie de página
require_once 'includes/footer.php';
?>