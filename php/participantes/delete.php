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
$participant_id = '';
$error_message = '';
$redirect = true;

// Verificar si se ha proporcionado un ID de participante
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $participant_id = $_GET['id'];
    
    // Verificar si existe el participante
    $check_stmt = $conn->prepare("SELECT participant_id FROM PARTICIPANTS WHERE participant_id = ?");
    $check_stmt->bind_param("s", $participant_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows == 0) {
        $error_message = "El participante no existe";
    } else {
        // Verificar si el participante está registrado en algún evento (no debería eliminarse si tiene registros)
        $reg_check_stmt = $conn->prepare("SELECT id FROM REGISTRATIONS WHERE participant_id = ?");
        $reg_check_stmt->bind_param("s", $participant_id);
        $reg_check_stmt->execute();
        $reg_check_result = $reg_check_stmt->get_result();
        
        if ($reg_check_result->num_rows > 0) {
            // Si hay registros asociados, mostrar página de confirmación
            $redirect = false;
            
            // Si se ha confirmado la eliminación (por POST)
            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_delete']) && $_POST['confirm_delete'] == 'yes') {
                // Eliminar primero los registros asociados
                $delete_reg_stmt = $conn->prepare("DELETE FROM REGISTRATIONS WHERE participant_id = ?");
                $delete_reg_stmt->bind_param("s", $participant_id);
                $delete_reg_result = $delete_reg_stmt->execute();
                $delete_reg_stmt->close();
                
                if ($delete_reg_result) {
                    // Ahora eliminamos el participante
                    $delete_stmt = $conn->prepare("DELETE FROM PARTICIPANTS WHERE participant_id = ?");
                    $delete_stmt->bind_param("s", $participant_id);
                    
                    if ($delete_stmt->execute()) {
                        // Redirigir con mensaje de éxito
                        header("Location: index.php?message=deleted");
                        exit();
                    } else {
                        $error_message = "Error al eliminar el participante: " . $conn->error;
                        $redirect = false;
                    }
                    
                    $delete_stmt->close();
                } else {
                    $error_message = "Error al eliminar los registros asociados: " . $conn->error;
                    $redirect = false;
                }
            }
            
            $reg_check_stmt->close();
        } else {
            // Si no hay registros asociados, eliminar directamente
            $delete_stmt = $conn->prepare("DELETE FROM PARTICIPANTS WHERE participant_id = ?");
            $delete_stmt->bind_param("s", $participant_id);
            
            if ($delete_stmt->execute()) {
                // Redirigir con mensaje de éxito
                header("Location: index.php?message=deleted");
                exit();
            } else {
                $error_message = "Error al eliminar el participante: " . $conn->error;
                $redirect = false;
            }
            
            $delete_stmt->close();
        }
    }
    
    $check_stmt->close();
} else {
    $error_message = "ID de participante no proporcionado";
}

// Si hay un error o no se debe redirigir, mostrar la página
if (!empty($error_message) || !$redirect) {
    // Incluir cabecera
    require_once '../includes/header.php';
    
    // Obtener datos del participante si existe el ID
    $participant_name = "";
    if (!empty($participant_id)) {
        $name_stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM PARTICIPANTS WHERE participant_id = ?");
        $name_stmt->bind_param("s", $participant_id);
        $name_stmt->execute();
        $name_result = $name_stmt->get_result();
        
        if ($name_result->num_rows > 0) {
            $row = $name_result->fetch_assoc();
            $participant_name = $row['full_name'];
        }
        
        $name_stmt->close();
    }
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Eliminar Participante</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Participantes</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Eliminar Participante</li>
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
                    <a href="index.php" class="btn btn-primary">Volver a la lista de participantes</a>
                </div>
                <?php elseif (isset($reg_check_result) && $reg_check_result->num_rows > 0): ?>
                <div class="alert alert-warning" role="alert">
                    <strong>Advertencia:</strong> El participante <strong><?php echo htmlspecialchars($participant_name); ?></strong> tiene registros de participación en eventos. Si continúa, se eliminarán todos los registros asociados.
                </div>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $participant_id); ?>">
                    <div class="mb-3">
                        <p>¿Está seguro de que desea eliminar este participante y todos sus registros asociados?</p>
                        <input type="hidden" name="confirm_delete" value="yes">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col">
                            <button type="submit" class="btn btn-danger w-100">Sí, eliminar participante y registros</button>
                        </div>
                        <div class="col">
                            <a href="index.php" class="btn btn-secondary w-100">Cancelar</a>
                        </div>
                    </div>
                </form>
                <?php else: ?>
                <div class="alert alert-info" role="alert">
                    ¿Está seguro de que desea eliminar al participante <strong><?php echo htmlspecialchars($participant_name); ?></strong>?
                </div>
                
                <div class="row mb-3">
                    <div class="col">
                        <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $participant_id . "&confirm=yes"); ?>" class="btn btn-danger w-100">Eliminar</a>
                    </div>
                    <div class="col">
                        <a href="index.php" class="btn btn-secondary w-100">Cancelar</a>
                    </div>
                </div>
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