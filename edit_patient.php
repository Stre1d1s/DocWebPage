<?php
// Ξεκινάει η συνεδρία
session_start();

// Έλεγχος αν ο χρήστης δεν είναι συνδεδεμένος ή δεν έχει το ρόλο γιατρού ή γραμματέα
if (!isset($_SESSION['email']) || !in_array($_SESSION['role'], ['doctor', 'secretary'])) {
    // Ανακατεύθυνση στη σελίδα σύνδεσης αν δεν είναι συνδεδεμένος ή δεν έχει κατάλληλο ρόλο
    header('Location: login.php');
    exit();
}

// Συμπερίληψη του αρχείου για τη σύνδεση με τη βάση δεδομένων
include 'db.php';

// Λήψη του patient_id από το GET αίτημα
$patient_id = $_GET['id'];

// Προετοιμασία και εκτέλεση SQL ερωτήματος για να βρεθεί ο ασθενής με το συγκεκριμένο id και ρόλο
$sql = "SELECT * FROM users WHERE id = ? AND role = 'patient'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();

// Έλεγχος αν ο ασθενής δεν βρέθηκε
if (!$patient) {
    echo "Ο ασθενής δεν βρέθηκε.";
    exit();
}

// Έλεγχος αν το αίτημα είναι POST για ενημέρωση των στοιχείων του ασθενή
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Λήψη των νέων στοιχείων από το POST αίτημα
    $full_name = $_POST['full_name'];
    $contact_information = $_POST['contact_information'];
    $identity_number = $_POST['identity_number'];
    $amka = $_POST['amka'];
    $email = $_POST['email'];
    // Έλεγχος αν έχει δοθεί νέος κωδικός, αλλιώς χρησιμοποιείται ο παλιός κωδικός
    $password = !empty($_POST['password']) ? $_POST['password'] : $patient['password'];

    // Προετοιμασία και εκτέλεση SQL ερωτήματος για την ενημέρωση των στοιχείων του ασθενή
    $sql = "UPDATE users SET full_name = ?, email = ?, password = ?, identity_number = ?, amka = ?, contact_information = ? WHERE id = ? AND role = 'patient'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $full_name, $email, $password, $identity_number, $amka, $contact_information, $patient_id);

    // Έλεγχος αν η ενημέρωση των στοιχείων ήταν επιτυχής
    if ($stmt->execute()) {
        echo "Τα στοιχεία ενημερώθηκαν επιτυχώς.";
    } else {
        echo "Σφάλμα κατά την ενημέρωση: " . $stmt->error;
    }
}

// Κλείσιμο της προετοιμασμένης δήλωσης και της σύνδεσης με τη βάση δεδομένων
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <!-- Ορισμός του συνόλου χαρακτήρων σε UTF-8 για την υποστήριξη ελληνικών χαρακτήρων -->
    <meta charset="UTF-8">
    <!-- Ρύθμιση της προβολής ώστε να είναι κατάλληλη για κινητές συσκευές -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Τίτλος της σελίδας -->
    <title>Επεξεργασία Ασθενούς</title>
    <!-- Σύνδεση με το εξωτερικό αρχείο στυλ -->
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Επικεφαλίδα της σελίδας -->
    <header>
        <!-- Κεντρικός τίτλος της σελίδας -->
        <h1>Επεξεργασία Ασθενούς</h1>
        <!-- Πλοήγηση της σελίδας -->
        <nav>
            <ul>
                <!-- Σύνδεσμοι προς άλλες σελίδες του ιστότοπου ανάλογα με τον ρόλο του χρήστη -->
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
    <!-- Κύριο περιεχόμενο της σελίδας -->
    <main>
        <!-- Τίτλος της ενότητας για την επεξεργασία του ασθενή -->
        <h2>Επεξεργασία Ασθενούς</h2>
        <!-- Φόρμα για την επεξεργασία των στοιχείων του ασθενή -->
        <form action="edit_patient.php?id=<?php echo $patient_id; ?>" method="post">
            <!-- Ετικέτα και πεδίο εισαγωγής για το ονοματεπώνυμο -->
            <label for="full_name">Ονοματεπώνυμο:</label>
            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($patient['full_name']); ?>" required>

            <!-- Ετικέτα και πεδίο εισαγωγής για το email -->
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($patient['email']); ?>" required>

            <!-- Ετικέτα και πεδίο εισαγωγής για τον κωδικό (αφήνεται κενό αν δεν θέλει να αλλάξει τον κωδικό) -->
            <label for="password">Κωδικός (αφήστε το κενό αν δεν θέλετε να αλλάξετε τον κωδικό):</label>
            <input type="password" id="password" name="password">

            <!-- Ετικέτα και πεδίο εισαγωγής για τον αριθμό ταυτότητας -->
            <label for="identity_number">Αριθμός Ταυτότητας:</label>
            <input type="text" id="identity_number" name="identity_number" value="<?php echo htmlspecialchars($patient['identity_number']); ?>" required>

            <!-- Ετικέτα και πεδίο εισαγωγής για τον ΑΜΚΑ -->
            <label for="amka">ΑΜΚΑ:</label>
            <input type="text" id="amka" name="amka" value="<?php echo htmlspecialchars($patient['amka']); ?>" required>

            <!-- Ετικέτα και πεδίο εισαγωγής για τα στοιχεία επικοινωνίας -->
            <label for="contact_information">Στοιχεία Επικοινωνίας:</label>
            <input type="text" id="contact_information" name="contact_information" value="<?php echo htmlspecialchars($patient['contact_information']); ?>" required>

            <!-- Κουμπί υποβολής της φόρμας -->
            <button type="submit">Αποθήκευση</button>
        </form>
    </main>
    <!-- Υποσέλιδο της σελίδας -->
    <footer>
        <p>&copy; 2024 Ιατρείο. Όλα τα δικαιώματα κατοχυρωμένα.</p>
    </footer>
</body>
</html>