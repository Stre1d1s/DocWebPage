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

// Ελέγχουμε αν η φόρμα έχει υποβληθεί
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Παίρνουμε τα δεδομένα από τη φόρμα
    $doctor_id = $_POST['doctor_id'];
    $slot_id = $_POST['slot_id'];
    $description = $_POST['description'];

    // Ετοιμάζουμε το SQL ερώτημα για να ελέγξουμε αν το επιλεγμένο slot είναι ακόμα διαθέσιμο
    $sql_slot = "SELECT * FROM doctor_availability WHERE id = ? AND slot_start > NOW()";
    $stmt_slot = $conn->prepare($sql_slot);
    $stmt_slot->bind_param("i", $slot_id);
    $stmt_slot->execute();
    $result_slot = $stmt_slot->get_result();
    $slot = $result_slot->fetch_assoc();

    // Ελέγχουμε αν το slot είναι διαθέσιμο
    if ($slot) {
        // Παίρνουμε την ημερομηνία και την ώρα του ραντεβού από το slot
        $appointment_date = date('Y-m-d', strtotime($slot['slot_start']));
        $appointment_time = date('H:i', strtotime($slot['slot_start']));

        // Ετοιμάζουμε το SQL ερώτημα για να ελέγξουμε αν υπάρχει ήδη ραντεβού για τον συγκεκριμένο γιατρό την επιλεγμένη ώρα
        $sql_check = "SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'Ακυρωμένο'";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
        $stmt_check->execute();
        $stmt_check->bind_result($existing_appointments_count);
        $stmt_check->fetch();
        $stmt_check->close();

        // Ελέγχουμε αν δεν υπάρχει ήδη ραντεβού την επιλεγμένη ώρα
        if ($existing_appointments_count == 0) {
            // Ορίζουμε την κατάσταση του ραντεβού ως 'Δημιουργημένο'
            $status = 'Δημιουργημένο';
            // Ετοιμάζουμε το SQL ερώτημα για να προσθέσουμε το νέο ραντεβού στη βάση δεδομένων
            $sql = "INSERT INTO appointments (patient_id, appointment_date, appointment_time, description, status, doctor_id) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issssi", $patient_id, $appointment_date, $appointment_time, $description, $status, $doctor_id);
            if ($stmt->execute()) {
                // Ανακατεύθυνση στη σελίδα των ραντεβού μετά την επιτυχή προσθήκη
                header('Location: appointments.php');
                exit();
            } else {
                echo "Σφάλμα κατά την προσθήκη του ραντεβού: " . $stmt->error;
            }
        } else {
            echo "Υπάρχει ήδη ραντεβού για τον συγκεκριμένο γιατρό την επιλεγμένη ώρα.";
        }
    } else {
        echo "Το επιλεγμένο slot δεν είναι διαθέσιμο.";
    }

    $stmt_slot->close();
}

// Ετοιμάζουμε το SQL ερώτημα για να πάρουμε τα ραντεβού του ασθενή από τη βάση δεδομένων
$sql = "SELECT a.*, d.full_name AS doctor_name FROM appointments a JOIN users d ON a.doctor_id = d.id WHERE a.patient_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$appointments = $stmt->get_result();

// Ετοιμάζουμε το SQL ερώτημα για να πάρουμε όλους τους γιατρούς από τη βάση δεδομένων
$sql_doctors = "SELECT id, full_name, specialty FROM users WHERE role = 'doctor'";
$doctors_result = $conn->query($sql_doctors);

// Κλείνουμε το statement και τη σύνδεση με τη βάση δεδομένων
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Τα Ραντεβού Μου</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        // Συνάρτηση για φόρτωση διαθέσιμων slots για τον επιλεγμένο γιατρό
        function loadAvailableSlots(doctorId) {
            fetch('fetch_slots.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'doctor_id=' + doctorId
            })
            .then(response => response.json())
            .then(data => {
                const slotSelect = document.getElementById('slot_id');
                slotSelect.innerHTML = '<option value="">Επιλέξτε διαθέσιμο slot</option>';
                data.forEach(slot => {
                    const option = document.createElement('option');
                    option.value = slot.id;
                    option.text = `${slot.start} - ${slot.end}`;
                    slotSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error fetching slots:', error));
        }
    </script>
</head>
<body>
    <header>
        <h1>Τα Ραντεβού Μου</h1>
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
        <h2>Προσθήκη Ραντεβού</h2>
        <form action="appointments.php" method="post">
            <label for="doctor_id">Διαθέσιμος Γιατρός:</label>
            <select id="doctor_id" name="doctor_id" required onchange="loadAvailableSlots(this.value)">
                <option value="">Επιλέξτε Γιατρό</option>
                <?php while ($row = $doctors_result->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>">
                        <?php echo htmlspecialchars($row['full_name'] . ' (' . $row['specialty'] . ')'); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <label for="slot_id">Διαθέσιμο Slot:</label>
            <select id="slot_id" name="slot_id" required>
                <option value="">Επιλέξτε διαθέσιμο slot</option>
            </select>
            <label for="description">Περιγραφή:</label>
            <textarea id="description" name="description" required></textarea>
            <button type="submit">Προσθήκη Ραντεβού</button>
        </form>
        <h2>Τα Ραντεβού Μου</h2>
        <ul>
            <?php while ($appointment = $appointments->fetch_assoc()): ?>
                <li>
                    Ημερομηνία: <?php echo $appointment['appointment_date']; ?> - Ώρα: <?php echo $appointment['appointment_time']; ?><br>
                    Γιατρός: <?php echo htmlspecialchars($appointment['doctor_name']); ?><br>
                    Κατάσταση: <?php echo htmlspecialchars($appointment['status']); ?><br>
                    Περιγραφή: <?php echo $appointment['description']; ?><br>
                    <?php if ($appointment['status'] != 'Ακυρωμένο' && $appointment['status'] != 'Ολοκληρωμένο'): ?>
                        <a href="edit_appointment.php?id=<?php echo $appointment['id']; ?>">Επεξεργασία</a>
                        <a href="delete_appointment.php?id=<?php echo $appointment['id']; ?>" onclick="return confirm('Είστε σίγουροι ότι θέλετε να ακυρώσετε το ραντεβού;')">Ακύρωση</a>
                    <?php endif; ?>
                </li>
            <?php endwhile; ?>
        </ul>
    </main>
    <footer>
        <p>&copy; 2024 Ιατρείο. Όλα τα δικαιώματα κατοχυρωμένα.</p>
    </footer>
</body>
</html>
