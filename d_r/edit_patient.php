<?php
session_start();

if (!isset($_SESSION['email']) || !in_array($_SESSION['role'], ['doctor', 'secretary'])) {
    header('Location: login.php');
    exit();
}

include 'db.php';

$patient_id = $_GET['id'];

$sql = "SELECT * FROM users WHERE id = ? AND role = 'patient'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();

if (!$patient) {
    echo "Ο ασθενής δεν βρέθηκε.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $contact_information = $_POST['contact_information'];
    $identity_number = $_POST['identity_number'];
    $amka = $_POST['amka'];
    $email = $_POST['email'];
    $password = !empty($_POST['password']) ? $_POST['password'] : $patient['password'];

    $sql = "UPDATE users SET full_name = ?, email = ?, password = ?, identity_number = ?, amka = ?, contact_information = ? WHERE id = ? AND role = 'patient'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $full_name, $email, $password, $identity_number, $amka, $contact_information, $patient_id);

    if ($stmt->execute()) {
        echo "Τα στοιχεία ενημερώθηκαν επιτυχώς.";
    } else {
        echo "Σφάλμα κατά την ενημέρωση: " . $stmt->error;
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Επεξεργασία Ασθενούς</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Επεξεργασία Ασθενούς</h1>
        <nav>
            <ul>
                <?php if ($_SESSION['role'] == 'doctor'): ?>
                    <li><a href="doctor_dashboard.php">Πίνακας Ελέγχου</a></li>
                    <li><a href="manage_patients.php">Διαχείριση Ασθενών</a></li>
                    <li><a href="manage_appointments.php">Διαχείριση Ραντεβού</a></li>
                    <li><a href="manage_history.php">Διαχείριση Ιστορικού</a></li>
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
        <h2>Επεξεργασία Ασθενούς</h2>
        <form action="edit_patient.php?id=<?php echo $patient_id; ?>" method="post">
            <label for="full_name">Ονοματεπώνυμο:</label>
            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($patient['full_name']); ?>" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($patient['email']); ?>" required>

            <label for="password">Κωδικός (αφήστε το κενό αν δεν θέλετε να αλλάξετε τον κωδικό):</label>
            <input type="password" id="password" name="password">

            <label for="identity_number">Αριθμός Ταυτότητας:</label>
            <input type="text" id="identity_number" name="identity_number" value="<?php echo htmlspecialchars($patient['identity_number']); ?>" required>

            <label for="amka">ΑΜΚΑ:</label>
            <input type="text" id="amka" name="amka" value="<?php echo htmlspecialchars($patient['amka']); ?>" required>

            <label for="contact_information">Στοιχεία Επικοινωνίας:</label>
            <input type="text" id="contact_information" name="contact_information" value="<?php echo htmlspecialchars($patient['contact_information']); ?>" required>

            <button type="submit">Αποθήκευση</button>
        </form>
    </main>
    <footer>
        <p>&copy; 2024 Ιατρείο. Όλα τα δικαιώματα κατοχυρωμένα.</p>
    </footer>
</body>
</html>

