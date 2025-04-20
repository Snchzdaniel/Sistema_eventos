<?php
// Incluir archivo de configuración de la base de datos
require_once '../config/database.php';

// Establecer conexión a la base de datos
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Inicializar variables
$registration_id = '';
$error_message = '';
$redirect = true;

// Verificar si se ha proporcionado un ID de registro
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $registration_id = $_GET['id'];
    
    // Verificar si existe el registro
    $check_stmt = $conn->prepare("SELECT registration_id FROM REGISTRATIONS WHERE registration_id = ?");
    $check_stmt->bind_param("s", $registration_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows == 0) {
        $error_message = "El registro no existe";
    } else {
        // Si se ha confirmado la eliminación
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_delete']) && $_POST['confirm_delete'] == 'yes') {
            // Preparar y ejecutar la consulta de eliminación
            $delete_stmt = $conn->prepare("DELETE FROM REGISTRATIONS WHERE registration_id = ?");
            $delete_stmt->bind_param("s", $registration_id);
            
            if ($delete_stmt->execute()) {
                // Redirigir con mensaje de éxito
                header("Location: index.php?message=deleted");
                exit();
            } else {
                $error_message = "Error al eliminar el registro: " . $conn->error;
                $redirect = false;
            }
            
            $delete_stmt->close();
        } else {
            // Mostrar página de confirmación
            $redirect = false;
        }
    }
    
    $check_stmt->close();
} else {
    $error_message = "ID de registro no proporcionado";
}

// Si hay un error o no se debe redirigir, mostrar la página
if (!empty($error_message) || !$redirect) {
    // Incluir cabecera
    require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Eliminar Registro</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Registros</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Eliminar Registro</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="form-section">
                <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
                <div class="text-center mt-3">
                    <a href="index.php" class="btn btn-primary">Volver a la lista de registros</a>
                </div>
                <?php else: ?>
                <div class="alert alert-warning" role="alert">
                    ¿Está seguro de que desea eliminar este registro?
                </div>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $registration_id); ?>">
                    <div class="mb-3">
                        <p>Esta acción no se puede deshacer.</p>
                        <input type="hidden" name="confirm_delete" value="yes">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col">
                            <button type="submit" class="btn btn-danger w-100">Sí, eliminar registro</button>
                        </div>
                        <div class="col">
                            <a href="index.php" class="btn btn-secondary w-100">Cancelar</a>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
    // Incluir pie de página
    require_once '../includes/footer.php';
}

// Si se debe redirigir y no hay errores, hacerlo
if ($redirect && empty($error_message)) {
    header("Location: index.php");
    exit();
}

// Cerrar la conexión a la base de datos
$conn->close();
?>