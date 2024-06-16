<?php
// Ξεκινάμε τη συνεδρία
session_start();

// Ελέγχουμε αν ο χρήστης είναι συνδεδεμένος
if (!isset($_SESSION['email'])) {
    // Αν δεν είναι, ανακατευθύνουμε στη σελίδα σύνδεσης
    header('Location: login.php');
    exit();
}

// Συμπεριλαμβάνουμε το αρχείο σύνδεσης με τη βάση δεδομένων
include 'db.php';

// Παίρνουμε το email και το ρόλο του χρήστη από τη συνεδρία
$email = $_SESSION['email'];
$role = $_SESSION['role'];

// Ετοιμάζουμε το SQL ερώτημα για να πάρουμε τα στοιχεία του χρήστη από τη βάση δεδομένων
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$patient_id = $user['id'];

// Παίρνουμε το ID του ραντεβού που θέλουμε να ακυρώσουμε
$appointment_id = $_GET['id'];

// Ελέγχουμε αν ο χρήστης είναι γραμματέας ή γιατρός
if ($role == 'secretary' || $role == 'doctor') {
    // Αν είναι, ετοιμάζουμε το SQL ερώτημα για να ακυρώσουμε το ραντεβού χωρίς περιορισμό
    $sql = "UPDATE appointments SET status = 'Ακυρωμένο' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $appointment_id);
} else {
    // Αν είναι ασθενής, ετοιμάζουμε το SQL ερώτημα για να ακυρώσουμε το ραντεβού μόνο αν ανήκει στον ασθενή
    $sql = "UPDATE appointments SET status = 'Ακυρωμένο' WHERE id = ? AND patient_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $appointment_id, $patient_id);
}

// Εκτελούμε το SQL ερώτημα
$stmt->execute();

// Ανακατεύθυνση στη σελίδα διαχείρισης ραντεβού ανάλογα με το ρόλο του χρήστη
if ($role == 'secretary' || $role == 'doctor') {
    header('Location: manage_appointments.php');
} else {
    header('Location: appointments.php');
}
exit();

// Κλείνουμε το statement και τη σύνδεση με τη βάση δεδομένων
$stmt->close();
$conn->close();
?>