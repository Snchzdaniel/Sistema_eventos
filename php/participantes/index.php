<?php
// php/participantes/index.php - Lista de todos los participantes
require_once '../config/database.php';
include_once '../includes/header.php';

// Inicializar conexión a la base de datos
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Configurar búsqueda
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
$params = [];
$types = '';

if (!empty($search)) {
    $search_condition = " WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR institution LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param, $search_param];
    $types = "ssss";
}

// Consulta para obtener participantes con búsqueda opcional
$sql = "SELECT * FROM PARTICIPANTS" . $search_condition . " ORDER BY last_name, first_name";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-md-8">
            <h2>Gestión de Participantes</h2>
            <p>Administre la información de los participantes en eventos académicos.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Participante
            </a>
        </div>
    </div>

    <?php
    // Mostrar mensaje de éxito si existe
    if (isset($_GET['success'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                ' . htmlspecialchars($_GET['success']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }

    // Mostrar mensaje de error si existe
    if (isset($_GET['error'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                ' . htmlspecialchars($_GET['error']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }
    ?>

    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <strong>Lista de Participantes</strong>
                </div>
                <div class="col-md-6">
                    <form class="d-flex" method="GET">
                        <input class="form-control me-2" type="search" placeholder="Buscar participante..." name="search" 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-outline-primary" type="submit">Buscar</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Institución</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo '<tr class="evento-item">';
                                echo '<td>' . htmlspecialchars($row['participant_id']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['phone'] ?? 'N/A') . '</td>';
                                echo '<td>' . htmlspecialchars($row['institution'] ?? 'N/A') . '</td>';
                                echo '<td>
                                    <a href="view.php?id=' . $row['participant_id'] . '" class="btn btn-sm btn-info" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=' . $row['participant_id'] . '" class="btn btn-sm btn-warning" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=' . $row['participant_id'] . '" class="btn btn-sm btn-danger" title="Eliminar" 
                                       onclick="return confirm(\'¿Está seguro que desea eliminar este participante?\')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="6" class="text-center">No se encontraron participantes</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <div class="row">
                <div class="col">
                    <p class="mb-0">Total de participantes: <strong><?php echo $result->num_rows; ?></strong></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$stmt->close();
$conn->close();
include_once '../includes/footer.php';
?>