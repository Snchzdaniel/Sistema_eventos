<?php
// php/eventos/index.php
require_once '../config/database.php';
require_once '../includes/header.php';

// Establecer conexión a la base de datos
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Parámetros de paginación y filtrado
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Construir la consulta SQL
$sql = "SELECT e.*, c.name as category_name 
        FROM events e 
        LEFT JOIN event_categories c ON e.category_id = c.category_id 
        WHERE 1=1";

if (!empty($status)) {
    $sql .= " AND e.status = '" . $conn->real_escape_string($status) . "'";
}
if (!empty($category)) {
    $sql .= " AND e.category_id = '" . $conn->real_escape_string($category) . "'";
}
if (!empty($search)) {
    $sql .= " AND (e.title LIKE '%" . $conn->real_escape_string($search) . "%' 
                  OR e.description LIKE '%" . $conn->real_escape_string($search) . "%')";
}

// Obtener el total de registros
$countResult = $conn->query($sql);
$total = $countResult->num_rows;
$totalPages = ceil($total / $limit);

// Añadir paginación
$sql .= " LIMIT $offset, $limit";

// Ejecutar la consulta
$result = $conn->query($sql);
$eventos = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $eventos[] = $row;
    }
}

// Obtener categorías para el filtro
$categoriesResult = $conn->query("SELECT * FROM event_categories ORDER BY name");
$categories = [];
if ($categoriesResult) {
    while ($row = $categoriesResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Incluir el encabezado
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Gestión de Eventos</h1>
    <a href="create.php" class="btn btn-success">
        <i class="fas fa-plus-circle"></i> Nuevo Evento
    </a>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="index.php" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Buscar</label>
                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <label for="category" class="form-label">Categoría</label>
                <select class="form-select" id="category" name="category">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>" <?php echo $category == $cat['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Estado</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos los estados</option>
                    <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Activo</option>
                    <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Completado</option>
                    <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de eventos -->
<div class="card">
    <div class="card-body">
        <?php if (empty($eventos)): ?>
            <div class="alert alert-info">No se encontraron eventos.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Categoría</th>
                            <th>Fecha</th>
                            <th>Lugar</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eventos as $evento): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($evento['title']); ?></td>
                                <td><?php echo isset($evento['category_name']) ? htmlspecialchars($evento['category_name']) : ''; ?></td>
                                <td><?php echo htmlspecialchars($evento['event_date']); ?></td>
                                <td><?php echo htmlspecialchars($evento['location']); ?></td>
                                <td>
                                    <?php if ($evento['status'] == 'active'): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php elseif ($evento['status'] == 'completed'): ?>
                                        <span class="badge bg-info">Completado</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Cancelado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="view.php?id=<?php echo $evento['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $evento['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $evento['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Está seguro de que desea eliminar este evento?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Navegación de páginas">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>&search=<?php echo urlencode($search); ?>">Anterior</a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>&search=<?php echo urlencode($search); ?>">Siguiente</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
// Incluir el pie de página
require_once '../includes/footer.php';
?>