<?php
// php/participantes/create.php - Crear nuevo participante
require_once '../config/database.php';
include_once '../includes/header.php';

// Inicializar conexión a la base de datos
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Inicializar variables
$first_name = $last_name = $email = $phone = $institution = "";
$errors = [];

// Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar y sanitizar entradas
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $institution = trim($_POST['institution'] ?? '');

    // Validaciones
    if (empty($first_name)) {
        $errors[] = "El nombre es obligatorio";
    }
    
    if (empty($last_name)) {
        $errors[] = "El apellido es obligatorio";
    }
    
    if (empty($email)) {
        $errors[] = "El email es obligatorio";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El formato de email no es válido";
    }

    // Verificar si el email ya existe
    $check_email = $conn->prepare("SELECT id FROM PARTICIPANTS WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $result = $check_email->get_result();
    
    if ($result->num_rows > 0) {
        $errors[] = "Este email ya está registrado para otro participante";
    }
    $check_email->close();

    // Si no hay errores, proceder con la inserción
    if (empty($errors)) {
        // Generar participant_id único (formato: PAR + 3 números)
        $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(participant_id, 4) AS UNSIGNED)) as max_id FROM PARTICIPANTS WHERE participant_id LIKE 'PAR%'");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $next_id = ($row['max_id'] ?? 0) + 1;
        $participant_id = 'PAR' . str_pad($next_id, 3, '0', STR_PAD_LEFT);
        $stmt->close();

        // Insertar nuevo participante
        $stmt = $conn->prepare("INSERT INTO PARTICIPANTS (participant_id, first_name, last_name, email, phone, institution) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $participant_id, $first_name, $last_name, $email, $phone, $institution);

        if ($stmt->execute()) {
            // Redirigir a la lista con mensaje de éxito
            header("Location: index.php?success=Participante registrado correctamente");
            exit;
        } else {
            $errors[] = "Error al registrar el participante: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<div class="container mt-4">
    <div class="row mb-3">
        <div class="col">
            <h2>Registrar Nuevo Participante</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Participantes</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Crear</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="form-section">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="first_name" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="last_name" class="form-label">Apellido <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    <small class="form-text text-muted">Este será el identificador único para iniciar sesión.</small>
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label">Teléfono</label>
                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="institution" class="form-label">Institución</label>
                <input type="text" class="form-control" id="institution" name="institution" value="<?php echo htmlspecialchars($institution); ?>">
                <small class="form-text text-muted">Universidad, empresa o institución a la que pertenece.</small>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Registrar Participante</button>
            </div>
        </form>
    </div>
</div>

<?php
$conn->close();
include_once '../includes/footer.php';
?>