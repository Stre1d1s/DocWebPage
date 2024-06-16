<?php
// Ξεκινάμε τη συνεδρία
session_start();

// Ελέγχουμε αν ο χρήστης είναι συνδεδεμένος και αν έχει τον κατάλληλο ρόλο (γιατρός ή γραμματέας)
if (!isset($_SESSION['email']) || ($_SESSION['role'] != 'doctor' && $_SESSION['role'] != 'secretary')) {
    // Αν ο χρήστης δεν είναι συνδεδεμένος ή δεν έχει τον κατάλληλο ρόλο, ανακατευθύνουμε στη σελίδα σύνδεσης
    header('Location: login.php');
    exit();
}

// Συμπεριλαμβάνουμε το αρχείο σύνδεσης με τη βάση δεδομένων
include 'db.php';

// Ελέγχουμε αν υπάρχει αίτημα διαγραφής ραντεβού
if (isset($_GET['delete'])) {
    // Παίρνουμε το ID του ραντεβού που θέλουμε να διαγράψουμε
    $appointment_id = $_GET['delete'];
    // Ετοιμάζουμε το SQL ερώτημα για διαγραφή του ραντεβού από τη βάση δεδομένων
    $sql = "DELETE FROM appointments WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
}

// Παίρνουμε όλα τα ραντεβού από τη βάση δεδομένων μαζί με τα στοιχεία του ασθενή και του γιατρού
$sql = "SELECT a.*, p.full_name AS patient_name, d.full_name AS doctor_name, d.specialty AS doctor_specialty 
        FROM appointments a 
        JOIN users p ON a.patient_id = p.id 
        JOIN users d ON a.doctor_id = d.id";
$result = $conn->query($sql);

// Κλείνουμε τη σύνδεση με τη βάση δεδομένων
$conn->close();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Διαχείριση Ραντεβού</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Διαχείριση Ραντεβού</h1>
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
        <h2>Όλα τα Ραντεβού</h2>
        <a href="add_appointment.php">Προσθήκη Ραντεβού</a>
        <table>
            <thead>
                <tr>
                    <th>Ημερομηνία</th>
                    <th>Ώρα</th>
                    <th>Ασθενής</th>
                    <th>Γιατρός</th>
                    <th>Περιγραφή</th>
                    <th>Κατάσταση</th>
                    <th>Ενέργειες</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['appointment_time']); ?></td>
                        <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['doctor_name'] . ' (' . $row['doctor_specialty'] . ')'); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td>
                            <?php if ($row['status'] !== 'Ολοκληρωμένο' && $row['status'] !== 'Ακυρωμένο'): ?>
                                <a href="edit_appointment.php?id=<?php echo $row['id']; ?>">Επεξεργασία</a>
                                <a href="delete_appointment.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Είστε σίγουροι ότι θέλετε να ακυρώσετε το ραντεβού;')">Ακύρωση</a>
                            <?php endif; ?>
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