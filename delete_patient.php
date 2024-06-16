<?php
session_start(); // Έναρξη της συνεδρίας

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και αν είναι γραμματέας
if (!isset($_SESSION['email']) || !in_array($_SESSION['role'], ['secretary'])) {
    header('Location: login.php'); // Ανακατεύθυνση στη σελίδα σύνδεσης αν δεν πληρούνται οι προϋποθέσεις
    exit();
}

include 'db.php'; // Συμπερίληψη του αρχείου για τη σύνδεση με τη βάση δεδομένων

$patient_id = $_GET['id']; // Λήψη του ID του ασθενή από τη γραμμή URL

// Προετοιμασία και εκτέλεση SQL εντολής για διαγραφή του ασθενή
$sql = "DELETE FROM users WHERE id = ? AND role = 'patient'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();

$stmt->close(); // Κλείσιμο του statement
$conn->close(); // Κλείσιμο της σύνδεσης με τη βάση δεδομένων

header('Location: manage_patients.php'); // Ανακατεύθυνση στη σελίδα διαχείρισης ασθενών
exit();
?>