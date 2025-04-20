<?php
// ponentes/index.php - Lista de todos los ponentes
require_once '../config/database.php';


try {
    // Conexión a la base de datos
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Consulta para obtener todos los ponentes
    $stmt = $conn->prepare("SELECT * FROM SPEAKERS ORDER BY last_name, first_name");
    $stmt->execute();
    $ponentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar los eventos asociados a cada ponente
    foreach ($ponentes as $key => $ponente) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM EVENT_SPEAKERS WHERE speaker_id = ?");
        $stmt->execute([$ponente['speaker_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $ponentes[$key]['total_events'] = $result['total'];
    }
    
} catch(PDOException $e) {
    $error = "Error de conexión: " . $e->getMessage();
}
?>

<?php include_once '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gestión de Ponentes</h1>
        <a href="create.php" class="btn btn-success">
            <i class="fas fa-plus"></i> Nuevo Ponente
        </a>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col">
                    <h5>Lista de Ponentes</h5>
                </div>
                <div class="col-md-4">
                    <input type="text" id="searchPonentes" class="form-control" placeholder="Buscar ponentes...">
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Especialización</th>
                            <th>Email</th>
                            <th>Eventos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="ponentesTable">
                        <?php if (isset($ponentes) && count($ponentes) > 0): ?>
                            <?php foreach ($ponentes as $ponente): ?>
                                <tr class="evento-item">
                                    <td>
                                        <a href="view.php?id=<?php echo $ponente['speaker_id']; ?>">
                                            <?php echo htmlspecialchars($ponente['first_name'] . ' ' . $ponente['last_name']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($ponente['specialization'] ?? 'No especificado'); ?></td>
                                    <td><?php echo htmlspecialchars($ponente['email']); ?></td>
                                    <td>
                                        <span class="evento-badge bg-primary text-white">
                                            <?php echo $ponente['total_events']; ?> eventos
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view.php?id=<?php echo $ponente['speaker_id']; ?>" class="btn btn-sm btn-info" title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $ponente['speaker_id']; ?>" class="btn btn-sm btn-primary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $ponente['speaker_id']; ?>" class="btn btn-sm btn-danger" title="Eliminar" 
                                           onclick="return confirm('¿Está seguro de que desea eliminar este ponente?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No hay ponentes registrados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchPonentes');
    const tableRows = document.querySelectorAll('#ponentesTable tr');
    
    searchInput.addEventListener('keyup', function() {
        const searchTerm = searchInput.value.toLowerCase();
        
        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
});
</script>

<?php include_once '../includes/footer.php'; ?>