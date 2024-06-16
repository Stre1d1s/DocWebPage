<?php
// Ξεκινάμε τη συνεδρία
session_start();

// Ελέγχουμε αν ο χρήστης είναι συνδεδεμένος και αν ο ρόλος του είναι είτε 'γιατρός' είτε 'γραμματέας'
if (!isset($_SESSION['email']) || !in_array($_SESSION['role'], ['doctor', 'secretary'])) {
    // Αν ο χρήστης δεν είναι συνδεδεμένος ή δεν έχει τον κατάλληλο ρόλο, ανακατευθύνουμε στη σελίδα σύνδεσης
    header('Location: login.php');
    exit();
}

// Συμπεριλαμβάνουμε το αρχείο σύνδεσης με τη βάση δεδομένων
include 'db.php';

// Ελέγχουμε αν υπάρχει αίτημα διαγραφής ασθενούς
if (isset($_GET['delete'])) {
    // Παίρνουμε το ID του ασθενούς που θέλουμε να διαγράψουμε
    $patient_id = $_GET['delete'];
    // Ετοιμάζουμε το SQL ερώτημα για να διαγράψουμε τον ασθενή από τη βάση δεδομένων
    $sql = "DELETE FROM users WHERE id = ? AND role = 'patient'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
}

// Ετοιμάζουμε το SQL ερώτημα για να πάρουμε τη λίστα των ασθενών
$sql = "SELECT * FROM users WHERE role = 'patient'";
$result = $conn->query($sql);

// Κλείνουμε τη σύνδεση με τη βάση δεδομένων
$conn->close();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Διαχείριση Ασθενών</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Διαχείριση Ασθενών</h1>
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
        <h2>Λίστα Ασθενών</h2>
        <a href="add_patient.php">Προσθήκη Ασθενούς</a>
        <table>
            <thead>
                <tr>
                    <th>Ονοματεπώνυμο</th>
                    <th>E-mail</th>
                    <th>Α.M.K.A</th>
                    <th>Στοιχεία Επικοινωνίας</th>
                    <th>Ενέργειες</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['amka']); ?></td>
                        <td><?php echo htmlspecialchars($row['contact_information']); ?></td>
                        <td>
                            <a href="edit_patient.php?id=<?php echo $row['id']; ?>">Επεξεργασία</a>
                            <a href="manage_patients.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτόν τον ασθενή;');">Διαγραφή</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
    <footer>
        <p>&copy; 2024 Ιατρείο. Όλα τα δικαιώματα κατοχυρωμένα.</p>
    </footer>
</body>
</html>