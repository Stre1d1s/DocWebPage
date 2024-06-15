<?php
session_start();

if (!isset($_SESSION['email']) || !in_array($_SESSION['role'], ['doctor', 'secretary'])) {
    header('Location: login.php');
    exit();
}

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['username'];
    $password =$_POST['password'];
    $full_name = $_POST['full_name'];
    $contact_information = $_POST['contact_information'];
    $identity_number = $_POST['identity_number'];
    $amka = $_POST['amka'];


    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $error = "Το όνομα χρήστη υπάρχει ήδη.";
    } else {
        $sql = "INSERT INTO users (email, password, full_name, contact_information, identity_number, amka, role) VALUES (?, ?, ?, ?, ?, ?, 'patient')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $email, $password, $full_name, $contact_information, $identity_number, $amka);
        $stmt->execute();
        header('Location: manage_patients.php');
        exit();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Προσθήκη Ασθενούς</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Προσθήκη Ασθενούς</h1>
        <nav>
            <ul>
                <?php if ($_SESSION['role'] == 'doctor'): ?>
                    <li><a href="doctor_dashboard.php">Πίνακας Ελέγχου</a></li>
                    <li><a href="manage_patients.php">Διαχείριση Ασθενών</a></li>
                    <li><a href="manage_appointments.php">Διαχείριση Ραντεβού</a></li>
                    <li><a href="manage_history.php">Διαχείριση Ιστορικού</a></li>
                    <li><a href="manage_availability.php">Διαχείριση Διαθεσιμότητας</a></li>
                <?php elseif ($_SESSION['role'] == 'secretary'): ?>
                    <li><a href="secretary_dashboard.php">Πίνακας Ελέγχου</a></li>
                    <li><a href="manage_patients.php">Διαχείριση Ασθενών</a></li>
                    <li><a href="manage_appointments.php">Διαχείριση Ραντεβού</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Αποσύνδεση</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h2>Φόρμα Προσθήκης Ασθενούς</h2>
        <?php if (isset($error)): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>
        <form action="add_patient.php" method="post">
            <label for="username">Όνομα Χρήστη:</label>
            <input type="text" id="username" name="username" required>
            
            <label for="password">Κωδικός:</label>
            <input type="password" id="password" name="password" required>
            
            <label for="full_name">Ονοματεπώνυμο:</label>
            <input type="text" id="full_name" name="full_name" required>
            
            <label for="contact_information">Στοιχεία Επικοινωνίας:</label>
            <input type="text" id="contact_information" name="contact_information" required>

            <label for="identity_number">Αριθμός Ταυτότητας:</label>
            <input type="text" id="identity_number" name="identity_number" required>

            <label for="amka">ΑΜΚΑ:</label>
            <input type="text" id="amka" name="amka" required>
            
            <button type="submit">Προσθήκη</button>
        </form>
    </main>
    <footer>
        <p>&copy; 2024 Ιατρείο. Όλα τα δικαιώματα κατοχυρωμένα.</p>
    </footer>
</body>
</html>
