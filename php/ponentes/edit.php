<?php
// ponentes/edit.php - Editar información de un ponente
require_once '../config/database.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$speaker_id = $_GET['id'];
$errors = [];
$successMessage = '';

try {
    // Conexión a la base de datos usando la clase Database
    $database = new Database();
    $conn = $database->getConnection();
    
    // Si se ha enviado el formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validaciones
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $specialization = trim($_POST['specialization'] ?? '');
        $biography = trim($_POST['biography'] ?? '');
        
        if (empty($first_name)) {
            $errors[] = "El nombre es obligatorio";
        }
        
        if (empty($last_name)) {
            $errors[] = "El apellido es obligatorio";
        }
        
        if (empty($email)) {
            $errors[] = "El email es obligatorio";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Formato de email inválido";
        } else {
            // Verificar si el email ya existe para otro ponente
            $checkStmt = $conn->prepare("SELECT COUNT(*) FROM SPEAKERS WHERE email = :email AND speaker_id != :speaker_id");
            $checkStmt->bindParam(':email', $email);
            $checkStmt->bindParam(':speaker_id', $speaker_id);
            $checkStmt->execute();
            if ($checkStmt->fetchColumn() > 0) {
                $errors[] = "Este email ya está registrado por otro ponente";
            }
        }
        
        // Si no hay errores, actualizar el ponente
        if (empty($errors)) {
            $stmt = $conn->prepare("
                UPDATE SPEAKERS 
                SET first_name = :first_name, 
                    last_name = :last_name, 
                    email = :email, 
                    phone = :phone, 
                    specialization = :specialization, 
                    biography = :biography
                WHERE speaker_id = :speaker_id
            ");
            
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':specialization', $specialization);
            $stmt->bindParam(':biography', $biography);
            $stmt->bindParam(':speaker_id', $speaker_id);
            $stmt->execute();
            
            $successMessage = "Ponente actualizado correctamente";
        }
    }
    
    // Obtener datos actuales del ponente para mostrar en el formulario
    $stmt = $conn->prepare("SELECT * FROM SPEAKERS WHERE speaker_id = :speaker_id");
    $stmt->bindParam(':speaker_id', $speaker_id);
    $stmt->execute();
    $ponente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ponente) {
        $errors[] = "Ponente no encontrado";
    }
    
} catch(PDOException $e) {
    $errors[] = "Error: " . $e->getMessage();
}
?>

<?php include_once '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Editar Ponente</h1>
        <div>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> Ver todos los ponentes
            </a>
            <a href="view.php?id=<?php echo $speaker_id; ?>" class="btn btn-info">
                <i class="fas fa-eye"></i> Ver detalles
            </a>
        </div>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if ($successMessage): ?>
        <div class="alert alert-success">
            <?php echo $successMessage; ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($ponente) && $ponente): ?>
        <div class="form-section">
            <form method="POST" action="edit.php?id=<?php echo $speaker_id; ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="first_name" class="form-label">Nombre *</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($ponente['first_name']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="last_name" class="form-label">Apellido *</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($ponente['last_name']); ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($ponente['email']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($ponente['phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="specialization" class="form-label">Especialización</label>
                    <input type="text" class="form-control" id="specialization" name="specialization" 
                           value="<?php echo htmlspecialchars($ponente['specialization'] ?? ''); ?>">
                </div>
                
                <div class="mb-3">
                    <label for="biography" class="form-label">Biografía</label>
                    <textarea class="form-control" id="biography" name="biography" rows="5"><?php echo htmlspecialchars($ponente['biography'] ?? ''); ?></textarea>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary">Actualizar Ponente</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>