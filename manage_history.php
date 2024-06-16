<?php
// Ξεκινάμε τη συνεδρία
session_start();

// Ελέγχουμε αν ο χρήστης είναι συνδεδεμένος και αν ο ρόλος του είναι 'γιατρός'
if (!isset($_SESSION['email']) || $_SESSION['role'] != 'doctor') {
    // Αν ο χρήστης δεν είναι συνδεδεμένος ή δεν έχει τον κατάλληλο ρόλο, ανακατευθύνουμε στη σελίδα σύνδεσης
    header('Location: login.php');
    exit();
}

// Συμπεριλαμβάνουμε το αρχείο σύνδεσης με τη βάση δεδομένων
include 'db.php';

// Ελέγχουμε αν η φόρμα έχει υποβληθεί
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_history'])) {
        // Παίρνουμε τα δεδομένα από τη φόρμα για προσθήκη νέου ιστορικού
        $patient_id = $_POST['patient_id'];
        $health_issues = $_POST['health_issues'];
        $treatment = $_POST['treatment'];
        
        // Ετοιμάζουμε το SQL ερώτημα για προσθήκη νέου ιστορικού στη βάση δεδομένων
        $sql = "INSERT INTO medical_history (patient_id, health_issues, treatment) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $patient_id, $health_issues, $treatment);
        if ($stmt->execute()) {
            // Ανακατεύθυνση στη σελίδα διαχείρισης ιστορικού μετά την επιτυχή προσθήκη
            header('Location: manage_history.php');
            exit();
        } else {
            echo "Σφάλμα κατά την προσθήκη του ιστορικού: " . $stmt->error;
        }
    } elseif (isset($_POST['edit_history'])) {
        // Παίρνουμε τα δεδομένα από τη φόρμα για επεξεργασία υπάρχοντος ιστορικού
        $history_id = $_POST['history_id'];
        $health_issues = $_POST['health_issues'];
        $treatment = $_POST['treatment'];

        // Ετοιμάζουμε το SQL ερώτημα για ενημέρωση του ιστορικού στη βάση δεδομένων
        $sql = "UPDATE medical_history SET health_issues = ?, treatment = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $health_issues, $treatment, $history_id);
        if ($stmt->execute()) {
            // Ανακατεύθυνση στη σελίδα διαχείρισης ιστορικού μετά την επιτυχή ενημέρωση
            header('Location: manage_history.php');
            exit();
        } else {
            echo "Σφάλμα κατά την ενημέρωση του ιστορικού: " . $stmt->error;
        }
    }
}

// Ελέγχουμε αν υπάρχει αίτημα διαγραφής ιστορικού
if (isset($_GET['delete'])) {
    // Παίρνουμε το ID του ιστορικού που θέλουμε να διαγράψουμε
    $history_id = $_GET['delete'];
    // Ετοιμάζουμε το SQL ερώτημα για διαγραφή του ιστορικού από τη βάση δεδομένων
    $sql = "DELETE FROM medical_history WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $history_id);
    if ($stmt->execute()) {
        // Ανακατεύθυνση στη σελίδα διαχείρισης ιστορικού μετά την επιτυχή διαγραφή
        header('Location: manage_history.php');
        exit();
    } else {
        echo "Σφάλμα κατά την διαγραφή του ιστορικού: " . $stmt->error;
    }
}

// Παίρνουμε όλα τα ιστορικά από τη βάση δεδομένων
$sql = "SELECT medical_history.*, users.full_name AS patient_name FROM medical_history JOIN users ON medical_history.patient_id = users.id";
$result = $conn->query($sql);

// Παίρνουμε όλους τους ασθενείς από τη βάση δεδομένων
$patients_sql = "SELECT id, full_name FROM users WHERE role = 'patient'";
$patients_result = $conn->query($patients_sql);

// Ελέγχουμε αν υπάρχει αίτημα επεξεργασίας ιστορικού
$history_to_edit = null;
if (isset($_GET['edit'])) {
    // Παίρνουμε το ID του ιστορικού που θέλουμε να επεξεργαστούμε
    $history_id = $_GET['edit'];
    // Ετοιμάζουμε το SQL ερώτημα για να πάρουμε τα στοιχεία του ιστορικού από τη βάση δεδομένων
    $sql = "SELECT * FROM medical_history WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $history_id);
    $stmt->execute();
    $history_to_edit = $stmt->get_result()->fetch_assoc();
}

// Κλείνουμε τη σύνδεση με τη βάση δεδομένων
$conn->close();
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Διαχείριση Ιστορικού Ασθενών</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Διαχείριση Ιστορικού Ασθενών</h1>
        <nav>
            <ul>
                <li><a href="doctor_dashboard.php">Πίνακας Ελέγχου</a></li>
                <li><a href="manage_patients.php">Διαχείριση Ασθενών</a></li>
                <li><a href="manage_appointments.php">Διαχείριση Ραντεβού</a></li>
                <li><a href="manage_history.php">Διαχείριση Ιστορικού</a></li>
                <li><a href="manage_availability.php">Διαχείριση Διαθεσιμότητας</a></li>
                <li><a href="logout.php">Αποσύνδεση</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <section>
            <h2>Όλα τα Ιστορικά</h2>
            <table>
                <thead>
                    <tr>
                        <th>Ασθενής</th>
                        <th>Ημερομηνία</th>
                        <th>Προβλήματα Υγείας</th>
                        <th>Θεραπεία</th>
                        <th>Ενέργειες</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['record_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['health_issues']); ?></td>
                            <td><?php echo htmlspecialchars($row['treatment']); ?></td>
                            <td>
                                <a href="manage_history.php?edit=<?php echo $row['id']; ?>">Επεξεργασία</a>
                                <a href="manage_history.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτό το ιστορικό;');">Διαγραφή</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>
        <section>
            <h2><?php echo $history_to_edit ? 'Επεξεργασία Ιστορικού' : 'Προσθήκη Νέου Ιστορικού'; ?></h2>
            <form action="manage_history.php" method="post">
                <?php if ($history_to_edit): ?>
                    <input type="hidden" name="history_id" value="<?php echo $history_to_edit['id']; ?>">
                    <input type="hidden" name="edit_history" value="1">
                <?php else: ?>
                    <input type="hidden" name="add_history" value="1">
                <?php endif; ?>
                <label for="patient_id">Ασθενής:</label>
                <select id="patient_id" name="patient_id" required <?php echo $history_to_edit ? 'disabled' : ''; ?>>
                    <?php while ($patient = $patients_result->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($patient['id']); ?>" <?php if ($history_to_edit && $history_to_edit['patient_id'] == $patient['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($patient['full_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <label for="health_issues">Προβλήματα Υγείας:</label>
                <textarea id="health_issues" name="health_issues" required><?php echo $history_to_edit ? htmlspecialchars($history_to_edit['health_issues']) : ''; ?></textarea>
                <label for="treatment">Θεραπεία:</label>
                <textarea id="treatment" name="treatment" required><?php echo $history_to_edit ? htmlspecialchars($history_to_edit['treatment']) : ''; ?></textarea>
                <button type="submit"><?php echo $history_to_edit ? 'Ενημέρωση' : 'Προσθήκη'; ?></button>
            </form>
        </section>
    </main>
    <footer>
        <p>&copy; 2024 Ιατρείο. Όλα τα δικαιώματα κατοχυρωμένα.</p>
    </footer>
</body>
</html>