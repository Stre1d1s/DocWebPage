<?php
// Ξεκινάμε τη συνεδρία
session_start();

// Ελέγχουμε αν ο χρήστης είναι συνδεδεμένος
if (!isset($_SESSION['email'])) {
    // Αν ο χρήστης δεν είναι συνδεδεμένος, ανακατευθύνουμε στη σελίδα σύνδεσης
    header('Location: login.php');
    exit();
}

// Συμπεριλαμβάνουμε το αρχείο σύνδεσης με τη βάση δεδομένων
include 'db.php';

// Παίρνουμε το email του χρήστη από τη συνεδρία
$email = $_SESSION['email'];

// Ετοιμάζουμε το SQL ερώτημα για να πάρουμε τα στοιχεία του χρήστη από τη βάση δεδομένων
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$patient_id = $user['id'];

// Ελέγχουμε αν η φόρμα έχει υποβληθεί
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Παίρνουμε τα δεδομένα από τη φόρμα
    $full_name = $_POST['full_name'];
    $contact_information = $_POST['contact_information'];
    $identity_number = $_POST['identity_number'];
    $amka = $_POST['amka'];

    // Ελέγχουμε αν όλα τα πεδία είναι συμπληρωμένα
    if (empty($full_name) || empty($contact_information) || empty($identity_number) || empty($amka)) {
        echo "Παρακαλώ συμπληρώστε όλα τα πεδία.";
    } else {
        // Ετοιμάζουμε το SQL ερώτημα για να ελέγξουμε αν ο Αριθμός Ταυτότητας ή το ΑΜΚΑ χρησιμοποιούνται ήδη από άλλο χρήστη
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE (identity_number = ? OR amka = ?) AND id != ?");
        $stmt->bind_param("ssi", $identity_number, $amka, $patient_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        // Αν βρεθεί χρήστης με τον ίδιο Αριθμό Ταυτότητας ή ΑΜΚΑ, εμφανίζουμε μήνυμα λάθους
        if ($row['count'] > 0) {
            echo "Το identity number ή το AMKA χρησιμοποιούνται ήδη.";
        } else {
            // Ετοιμάζουμε το SQL ερώτημα για να ενημερώσουμε τα στοιχεία του χρήστη στη βάση δεδομένων
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, contact_information = ?, identity_number = ?, amka = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $full_name, $contact_information, $identity_number, $amka, $patient_id);
            $stmt->execute();

            // Ανακατεύθυνση στη σελίδα προφίλ μετά την επιτυχή ενημέρωση
            header('Location: profile.php');
            exit();
        }
    }
}

// Κλείνουμε το statement και τη σύνδεση με τη βάση δεδομένων
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Προφίλ</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Προφίλ</h1>
        <nav>
            <ul>
                <li><a href="appointments.php">Τα Ραντεβού Μου</a></li>
                <li><a href="profile.php">Προφίλ</a></li>
                <li><a href="history.php">Ιατρικό Ιστορικό</a></li>
                <li><a href="logout.php">Αποσύνδεση</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h2>Προβολή και Τροποποίηση Προφίλ</h2>
        <form action="profile.php" method="post">
            <label for="full_name">Ονοματεπώνυμο:</label>
            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
            <label for="contact_information">Στοιχεία Επικοινωνίας:</label>
            <input type="text" id="contact_information" name="contact_information" value="<?php echo htmlspecialchars($user['contact_information']); ?>" required>
            <label for="identity_number">Αριθμός Ταυτότητας:</label>
            <input type="text" id="identity_number" name="identity_number" value="<?php echo htmlspecialchars($user['identity_number']); ?>" required>
            <label for="amka">ΑΜΚΑ:</label>
            <input type="text" id="amka" name="amka" value="<?php echo htmlspecialchars($user['amka']); ?>" required>
            <button type="submit">Ενημέρωση</button>
        </form>
    </main>
    <footer>
        <p>&copy; 2024 Ιατρείο. Όλα τα δικαιώματα κατοχυρωμένα.</p>
    </footer>
</body>
</html>