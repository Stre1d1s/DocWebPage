<?php
session_start();

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'doctor') {
    header('Location: login.php');
    exit();
}

include 'db.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_history'])) {
    $patient_id = $_POST['patient_id'];
    $health_issues = $_POST['health_issues'];
    $treatment = $_POST['treatment'];
    
    $sql = "INSERT INTO medical_history (patient_id, health_issues, treatment) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $patient_id, $health_issues, $treatment);
    if ($stmt->execute()) {
        header('Location: manage_history.php');
        exit();
    } else {
        echo "Σφάλμα κατά την προσθήκη του ιστορικού: " . $stmt->error;
    }
}


$sql = "SELECT medical_history.*, users.full_name AS patient_name FROM medical_history JOIN users ON medical_history.patient_id = users.id";
$result = $conn->query($sql);

$patients_sql = "SELECT id, full_name FROM users WHERE role = 'patient'";
$patients_result = $conn->query($patients_sql);


if (isset($_GET['delete'])) {
    $history_id = $_GET['delete'];
    $sql = "DELETE FROM medical_history WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $history_id);
    if ($stmt->execute()) {
        header('Location: manage_history.php');
        exit();
    } else {
        echo "Σφάλμα κατά την διαγραφή του ιστορικού: " . $stmt->error;
    }
}

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
                                <a href="manage_history.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτό το ιστορικό;');">Διαγραφή</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>
        <section>
            <h2>Προσθήκη Νέου Ιστορικού</h2>
            <form action="manage_history.php" method="post">
                <input type="hidden" name="add_history" value="1">
                <label for="patient_id">Ασθενής:</label>
                <select id="patient_id" name="patient_id" required>
                    <?php while ($patient = $patients_result->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($patient['id']); ?>"><?php echo htmlspecialchars($patient['full_name']); ?></option>
                    <?php endwhile; ?>
                </select>
                <label for="health_issues">Προβλήματα Υγείας:</label>
                <textarea id="health_issues" name="health_issues" required></textarea>
                <label for="treatment">Θεραπεία:</label>
                <textarea id="treatment" name="treatment" required></textarea>
                <button type="submit">Προσθήκη</button>
            </form>
        </section>
    </main>
    <footer>
        <p>&copy; 2024 Ιατρείο. Όλα τα δικαιώματα κατοχυρωμένα.</p>
    </footer>
</body>
</html>






