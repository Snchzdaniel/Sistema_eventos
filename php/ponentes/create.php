<?php
// ponentes/create.php - Formulario para crear un nuevo ponente
require_once '../config/database.php';

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Conexión a la base de datos
        $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
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
            // Verificar si el email ya existe
            $checkStmt = $conn->prepare("SELECT COUNT(*) FROM SPEAKERS WHERE email = ?");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetchColumn() > 0) {
                $errors[] = "Este email ya está registrado";
            }
        }
        
        // Si no hay errores, insertar el ponente
        if (empty($errors)) {
            // Generar un ID único para el ponente
            $speaker_id = 'SPK' . strtoupper(substr(md5(uniqid()), 0, 8));
            
            // Preparar consulta de inserción
            $stmt = $conn->prepare("
                INSERT INTO SPEAKERS (speaker_id, first_name, last_name, email, phone, specialization, biography) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            // Ejecutar consulta con los parámetros
            $stmt->execute([$speaker_id, $first_name, $last_name, $email, $phone, $specialization, $biography]);
            
            $successMessage = "Ponente creado correctamente";
            // Limpiar datos del formulario después de la inserción exitosa
            unset($first_name, $last_name, $email, $phone, $specialization, $biography);
        }
        
    } catch(PDOException $e) {
        $errors[] = "Error: " . $e->getMessage();
    }
}
?>

<?php include_once '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Crear Nuevo Ponente</h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a la lista
        </a>
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
    
    <div class="form-section">
        <form method="POST" action="create.php">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="first_name" class="form-label">Nombre *</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" 
                           value="<?php echo htmlspecialchars($first_name ?? ''); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="last_name" class="form-label">Apellido *</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" 
                           value="<?php echo htmlspecialchars($last_name ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label">Teléfono</label>
                    <input type="text" class="form-control" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="specialization" class="form-label">Especialización</label>
                <input type="text" class="form-control" id="specialization" name="specialization" 
                       value="<?php echo htmlspecialchars($specialization ?? ''); ?>">
            </div>
            
            <div class="mb-3">
                <label for="biography" class="form-label">Biografía</label>
                <textarea class="form-control" id="biography" name="biography" rows="5"><?php echo htmlspecialchars($biography ?? ''); ?></textarea>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary">Guardar Ponente</button>
            </div>
        </form>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>