<?php
// Ξεκινάει η συνεδρία
session_start();

// Έλεγχος αν ο χρήστης δεν είναι συνδεδεμένος
if (!isset($_SESSION['email'])) {
    // Αν ο χρήστης δεν είναι συνδεδεμένος, ανακατευθύνεται στη σελίδα σύνδεσης
    header('Location: login.php');
    exit();
}

// Συμπερίληψη του αρχείου για τη σύνδεση με τη βάση δεδομένων
include 'db.php';

// Λήψη του email του χρήστη από τη συνεδρία
$email = $_SESSION['email'];

// Προετοιμασία και εκτέλεση SQL ερωτήματος για να βρεθεί ο χρήστης με το συγκεκριμένο email
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$patient_id = $user['id'];

// Προετοιμασία και εκτέλεση SQL ερωτήματος για να βρεθεί το ιατρικό ιστορικό του χρήστη
$stmt = $conn->prepare("SELECT * FROM medical_history WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$history = $stmt->get_result();

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
    <title>Ιατρικό Ιστορικό</title>
    <!-- Σύνδεση με το εξωτερικό αρχείο στυλ -->
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Επικεφαλίδα της σελίδας -->
    <header>
        <!-- Κεντρικός τίτλος της σελίδας -->
        <h1>Ιατρικό Ιστορικό</h1>
        <!-- Πλοήγηση της σελίδας -->
        <nav>
            <ul>
                <!-- Σύνδεσμοι προς άλλες σελίδες του ιστότοπου -->
                <li><a href="appointments.php">Τα Ραντεβού Μου</a></li>
                <li><a href="profile.php">Προφίλ</a></li>
                <li><a href="history.php">Ιατρικό Ιστορικό</a></li>
                <li><a href="logout.php">Αποσύνδεση</a></li>
            </ul>
        </nav>
    </header>
    <!-- Κύριο περιεχόμενο της σελίδας -->
    <main>
        <!-- Τίτλος της ενότητας με το ιατρικό ιστορικό -->
        <h2>Ιατρικό Ιστορικό</h2>
        <!-- Λίστα με τα αρχεία του ιατρικού ιστορικού -->
        <ul>
            <?php while ($record = $history->fetch_assoc()): ?>
                <li>
                    <!-- Εμφάνιση της ημερομηνίας του αρχείου -->
                    <strong>Ημερομηνία: <?php echo htmlspecialchars($record['record_date']); ?></strong><br>
                    <!-- Εμφάνιση των προβλημάτων υγείας -->
                    <strong>Προβλήματα Υγείας: </strong><?php echo htmlspecialchars($record['health_issues']); ?><br>
                    <!-- Εμφάνιση της θεραπείας -->
                    <strong>Θεραπεία: </strong><?php echo htmlspecialchars($record['treatment']); ?>
                </li>
            <?php endwhile; ?>
        </ul>
    </main>
    <!-- Υποσέλιδο της σελίδας -->
    <footer>
        <p>&copy; 2024 Ιατρείο. Όλα τα δικαιώματα κατοχυρωμένα.</p>
    </footer>
</body>
</html>