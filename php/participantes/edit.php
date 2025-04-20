<?php
// Incluir archivos de configuración y cabecera
require_once '../config/database.php';

try {
    // Establecer conexión a la base de datos
    $database = new Database();
    $conn = $database->getConnection();
} catch(PDOException $exception) {
    die("Error de conexión: " . $exception->getMessage());
}

require_once '../includes/header.php';

// Inicializar variables
$participant_id = '';
$first_name = '';
$last_name = '';
$email = '';
$phone = '';
$institution = '';
$error_message = '';
$success_message = '';

// Verificar si se ha proporcionado un ID de participante
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $participant_id = $_GET['id'];
    
    // Preparar consulta para obtener datos del participante
    $stmt = $conn->prepare("SELECT * FROM PARTICIPANTS WHERE participant_id = :participant_id");
    $stmt->bindParam(':participant_id', $participant_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $participant = $stmt->fetch(PDO::FETCH_ASSOC);
        $first_name = $participant['first_name'];
        $last_name = $participant['last_name'];
        $email = $participant['email'];
        $phone = $participant['phone'];
        $institution = $participant['institution'];
    } else {
        // Si no se encuentra el participante, redirigir a la lista
        header("Location: index.php");
        exit();
    }
} else {
    // Si no se proporciona ID, redirigir a la lista
    header("Location: index.php");
    exit();
}

// Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar entrada
    if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['email'])) {
        $error_message = "Los campos Nombre, Apellido y Email son obligatorios";
    } else {
        // Recoger datos del formulario
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $institution = $_POST['institution'];
        
        // Validar email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Por favor, introduce un email válido";
        } else {
            // Verificar si el email ya existe para otro participante
            $check_stmt = $conn->prepare("SELECT id FROM PARTICIPANTS WHERE email = :email AND participant_id != :participant_id");
            $check_stmt->bindParam(':email', $email);
            $check_stmt->bindParam(':participant_id', $participant_id);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $error_message = "El email ya está registrado por otro participante";
            } else {
                // Preparar y ejecutar la consulta de actualización
                $update_stmt = $conn->prepare("
                    UPDATE PARTICIPANTS 
                    SET first_name = :first_name, 
                        last_name = :last_name, 
                        email = :email, 
                        phone = :phone, 
                        institution = :institution 
                    WHERE participant_id = :participant_id");
                
                $update_stmt->bindParam(':first_name', $first_name);
                $update_stmt->bindParam(':last_name', $last_name);
                $update_stmt->bindParam(':email', $email);
                $update_stmt->bindParam(':phone', $phone);
                $update_stmt->bindParam(':institution', $institution);
                $update_stmt->bindParam(':participant_id', $participant_id);
                
                if ($update_stmt->execute()) {
                    $success_message = "Participante actualizado con éxito";
                } else {
                    $error_message = "Error al actualizar el participante";
                }
            }
        }
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Editar Participante</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Participantes</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Editar Participante</li>
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
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success_message; ?>
                    <script>
                        setTimeout(function() {
                            window.location.href = 'index.php';
                        }, 2000); // Redirigir después de 2 segundos
                    </script>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $participant_id); ?>">
                    <div class="mb-3">
                        <label for="first_name" class="form-label">Nombre *</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="last_name" class="form-label">Apellido *</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="institution" class="form-label">Institución</label>
                        <input type="text" class="form-control" id="institution" name="institution" value="<?php echo htmlspecialchars($institution); ?>">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col">
                            <button type="submit" class="btn btn-primary w-100">Actualizar Participante</button>
                        </div>
                        <div class="col">
                            <a href="index.php" class="btn btn-secondary w-100">Cancelar</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>