<?php
session_start(); // Έναρξη της συνεδρίας

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και αν είναι γιατρός ή γραμματέας
if (!isset($_SESSION['email']) || !in_array($_SESSION['role'], ['doctor', 'secretary'])) {
    header('Location: login.php'); // Ανακατεύθυνση στη σελίδα σύνδεσης αν δεν πληρούνται οι προϋποθέσεις
    exit();
}

include 'db.php'; // Συμπερίληψη του αρχείου για τη σύνδεση με τη βάση δεδομένων

// Έλεγχος αν η μέθοδος αιτήματος είναι POST (δηλαδή, αν η φόρμα έχει υποβληθεί)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = $_POST['patient_id']; // Λήψη του ID του ασθενή από τη φόρμα
    $time_slot_id = $_POST['time_slot_id']; // Λήψη του ID του χρονικού διαστήματος από τη φόρμα
    $description = $_POST['description']; // Λήψη της περιγραφής από τη φόρμα
    $status = 'Δημιουργημένο'; // Καθορισμός της αρχικής κατάστασης του ραντεβού

    // Έλεγχος αν όλα τα πεδία της φόρμας έχουν συμπληρωθεί
    if (!empty($patient_id) && !empty($time_slot_id) && !empty($description)) {
        // Προετοιμασία και εκτέλεση SQL εντολής για λήψη πληροφοριών για το χρονικό διάστημα
        $sql = "SELECT slot_start, slot_end, doctor_id FROM doctor_availability WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $time_slot_id);
        $stmt->execute();
        $stmt->bind_result($appointment_date_time, $slot_end, $doctor_id);
        $stmt->fetch();
        $stmt->close();

        $appointment_date = date('Y-m-d', strtotime($appointment_date_time)); // Λήψη της ημερομηνίας του ραντεβού
        $appointment_time = date('H:i:s', strtotime($appointment_date_time)); // Λήψη της ώρας του ραντεβού

        // Προετοιμασία και εκτέλεση SQL εντολής για έλεγχο ύπαρξης άλλου ραντεβού για τον ίδιο γιατρό και την ίδια ώρα
        $sql = "SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'Ακυρωμένο'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
        $stmt->execute();
        $stmt->bind_result($existing_appointments_count);
        $stmt->fetch();
        $stmt->close();

        // Έλεγχος αν δεν υπάρχει ήδη ραντεβού την ίδια ώρα για τον ίδιο γιατρό
        if ($existing_appointments_count == 0) {
            // Προετοιμασία και εκτέλεση SQL εντολής για προσθήκη του νέου ραντεβού
            $sql = "INSERT INTO appointments (patient_id, appointment_date, appointment_time, description, status, doctor_id, time_slot_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issssii", $patient_id, $appointment_date, $appointment_time, $description, $status, $doctor_id, $time_slot_id);
            if ($stmt->execute()) {
                header('Location: manage_appointments.php'); // Ανακατεύθυνση στη σελίδα διαχείρισης ραντεβού
                exit();
            } else {
                echo "Σφάλμα κατά την προσθήκη του ραντεβού: " . $stmt->error; // Εμφάνιση μηνύματος σφάλματος αν αποτύχει η εκτέλεση
            }
        } else {
            echo "Υπάρχει ήδη ραντεβού για τον συγκεκριμένο γιατρό την επιλεγμένη ώρα."; // Εμφάνιση μηνύματος αν υπάρχει ήδη ραντεβού
        }
    } else {
        echo "Παρακαλώ συμπληρώστε όλα τα πεδία."; // Εμφάνιση μηνύματος αν δεν έχουν συμπληρωθεί όλα τα πεδία
    }
}

// Προετοιμασία και εκτέλεση SQL εντολής για λήψη των ασθενών
$sql_patients = "SELECT id, full_name FROM users WHERE role = 'patient'";
$patients_result = $conn->query($sql_patients);

// Προετοιμασία και εκτέλεση SQL εντολής για λήψη των γιατρών
$sql_doctors = "SELECT id, full_name, specialty FROM users WHERE role = 'doctor'";
$doctors_result = $conn->query($sql_doctors);

$conn->close(); // Κλείσιμο της σύνδεσης με τη βάση δεδομένων
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Προσθήκη Ραντεβού</title>
    <link rel="stylesheet" href="styles.css"> <!-- Συμπερίληψη του αρχείου CSS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script> <!-- Συμπερίληψη της βιβλιοθήκης jQuery -->
    <script>
        // jQuery script για φόρτωση διαθέσιμων χρονικών διαστημάτων ανάλογα με τον γιατρό που επιλέχθηκε
        $(document).ready(function() {
            $('#doctor_id').change(function() {
                var doctorId = $(this).val();
                if (doctorId) {
                    $.ajax({
                        type: 'POST',
                        url: 'fetch_slots.php',
                        data: { doctor_id: doctorId },
                        dataType: 'json',
                        success: function(response) {
                            var timeSlotSelect = $('#time_slot_id');
                            timeSlotSelect.empty();
                            timeSlotSelect.append('<option value="">Επιλέξτε ώρα</option>');
                            response.forEach(function(slot) {
                                timeSlotSelect.append('<option value="' + slot.id + '">' + slot.start + ' - ' + slot.end + '</option>');
                            });
                        },
                        error: function(xhr, status, error) {
                            console.error(xhr.responseText);
                        }
                    });
                } else {
                    $('#time_slot_id').html('<option value="">Επιλέξτε ώρα</option>');
                }
            });
        });
    </script>
</head>
<body>
    <header>
        <h1>Προσθήκη Ραντεβού</h1>
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
        <h2>Προσθήκη Νέου Ραντεβού</h2>
        <form action="add_appointment.php" method="post">
            <label for="patient_id">Ασθενής:</label>
            <select id="patient_id" name="patient_id" required>
                <option value="">Επιλέξτε Ασθενή</option>
                <?php while ($row = $patients_result->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['full_name']); ?></option>
                <?php endwhile; ?>
            </select>
            
            <label for="doctor_id">Γιατρός:</label>
            <select id="doctor_id" name="doctor_id" required>
                <option value="">Επιλέξτε Γιατρό</option>
                <?php while ($row = $doctors_result->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>">
                        <?php echo htmlspecialchars($row['full_name'] . ' (' . $row['specialty'] . ')'); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="time_slot_id">Ώρα Ραντεβού:</label>
            <select id="time_slot_id" name="time_slot_id" required>
                <option value="">Επιλέξτε ώρα</option>
            </select>

            <label for="description">Λεπτομέρειες:</label>
            <textarea id="description" name="description" required></textarea>

            <button type="submit">Προσθήκη Ραντεβού</button>
        </form>
    </main>
    <footer>
        <p>&copy; 2024 Ιατρείο. Όλα τα δικαιώματα κατοχυρωμένα.</p>
    </footer>
</body>
</html>