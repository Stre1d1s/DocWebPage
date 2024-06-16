<?php
session_start();

// Έλεγχος αν ο χρήστης έχει συνδεθεί, αν όχι ανακατεύθυνση στη σελίδα εισόδου
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

include 'db.php';

$email = $_SESSION['email'] ?? '';
$role = $_SESSION['role'] ?? '';

// Ανάκτηση στοιχείων χρήστη
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$patient_id = $user['id'];

if (isset($_GET['id'])) {
    $appointment_id = $_GET['id'];

    // Έλεγχος αν ο χρήστης είναι γραμματέας ή γιατρός για διαφορετικά ερωτήματα SQL
    if ($role == 'secretary' || $role == 'doctor') {
        $sql = "SELECT * FROM appointments WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $appointment_id);
    } else {
        $sql = "SELECT * FROM appointments WHERE id = ? AND patient_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $appointment_id, $patient_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();

    // Έλεγχος αν το ραντεβού βρέθηκε
    if (!$appointment) {
        echo "Το ραντεβού δεν βρέθηκε.";
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $patient_id = $_POST['patient_id'];
        $time_slot_id = $_POST['time_slot_id'];
        $description = $_POST['description'];
        $status = $_POST['status'];

        // Έλεγχος αν όλα τα πεδία είναι συμπληρωμένα
        if (!empty($patient_id) && !empty($time_slot_id) && !empty($description) && !empty($status)) {
            $sql = "SELECT slot_start, slot_end, doctor_id FROM doctor_availability WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $time_slot_id);
            $stmt->execute();
            $stmt->bind_result($appointment_date_time, $slot_end, $doctor_id);
            $stmt->fetch();
            $stmt->close();

            $appointment_date = date('Y-m-d', strtotime($appointment_date_time));
            $appointment_time = date('H:i:s', strtotime($appointment_date_time));

            // Έλεγχος αν υπάρχει ήδη ραντεβού για τον γιατρό την επιλεγμένη ώρα
            $sql = "SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'Ακυρωμένο' AND id != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issi", $doctor_id, $appointment_date, $appointment_time, $appointment_id);
            $stmt->execute();
            $stmt->bind_result($existing_appointments_count);
            $stmt->fetch();
            $stmt->close();

            if ($existing_appointments_count == 0) {
                $sql = "UPDATE appointments SET patient_id = ?, appointment_date = ?, appointment_time = ?, description = ?, status = ?, doctor_id = ?, time_slot_id = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issssiii", $patient_id, $appointment_date, $appointment_time, $description, $status, $doctor_id, $time_slot_id, $appointment_id);
                if ($stmt->execute()) {
                    header('Location: appointments.php');
                    exit();
                } else {
                    echo "Σφάλμα κατά την επεξεργασία του ραντεβού: " . $stmt->error;
                }
            } else {
                echo "Υπάρχει ήδη ραντεβού για τον συγκεκριμένο γιατρό την επιλεγμένη ώρα.";
            }
        } else {
            echo "Παρακαλώ συμπληρώστε όλα τα πεδία.";
        }
    }
} else {
    echo "Μη έγκυρο αίτημα.";
    exit();
}

// Ανάκτηση λίστας ασθενών
$sql_patients = "SELECT id, full_name FROM users WHERE role = 'patient'";
$patients_result = $conn->query($sql_patients);

// Ανάκτηση λίστας γιατρών
$sql_doctors = "SELECT id, full_name, specialty FROM users WHERE role = 'doctor'";
$doctors_result = $conn->query($sql_doctors);

$conn->close();
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Επεξεργασία Ραντεβού</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            var initialDoctorId = $('#doctor_id').val();
            if (initialDoctorId) {
                fetchSlots(initialDoctorId);
            }

            $('#doctor_id').change(function() {
                var doctorId = $(this).val();
                if (doctorId) {
                    fetchSlots(doctorId);
                } else {
                    $('#time_slot_id').html('<option value="">Επιλέξτε ώρα</option>');
                }
            });

            function fetchSlots(doctorId) {
                $.ajax({
                    type: 'POST',
                    url: 'fetch_slots.php',
                    data: { doctor_id: doctorId },
                    dataType: 'json',
                    success: function(response) {
                        var timeSlotSelect = $('#time_slot_id');
                        timeSlotSelect.empty();
                        timeSlotSelect.append('<option value="">Επιλέξτε ώρα</option>');
                        if (response.error) {
                            alert(response.error);
                        } else {
                            response.forEach(function(slot) {
                                var selected = slot.id == '<?php echo $appointment['time_slot_id']; ?>' ? ' selected' : '';
                                timeSlotSelect.append('<option value="' + slot.id + '"' + selected + '>' + slot.start + ' - ' + slot.end + '</option>');
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            }
        });
    </script>
</head>
<body>
    <header>
        <h1>Επεξεργασία Ραντεβού</h1>
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
        <h2>Επεξεργασία Ραντεβού</h2>
        <form action="edit_appointment.php?id=<?php echo $appointment_id; ?>" method="post">
            <label for="patient_id">Ασθενής:</label>
            <select id="patient_id" name="patient_id" required>
                <option value="">Επιλέξτε Ασθενή</option>
                <?php while ($row = $patients_result->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>" <?php if ($appointment['patient_id'] == $row['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($row['full_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            
            <label for="doctor_id">Γιατρός:</label>
            <select id="doctor_id" name="doctor_id" required>
                <option value="">Επιλέξτε Γιατρό</option>
                <?php while ($row = $doctors_result->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>" <?php if ($appointment['doctor_id'] == $row['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($row['full_name'] . ' (' . $row['specialty'] . ')'); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="time_slot_id">Ώρα Ραντεβού:</label>
            <select id="time_slot_id" name="time_slot_id" required>
                <option value="">Επιλέξτε ώρα</option>
            </select>

            <label for="description">Λεπτομέρειες:</label>
            <textarea id="description" name="description" required><?php echo htmlspecialchars($appointment['description']); ?></textarea>

            <label for="status">Κατάσταση:</label>
            <select id="status" name="status" required>
                <option value="Δημιουργημένο" <?php if ($appointment['status'] == 'Δημιουργημένο') echo 'selected'; ?>>Δημιουργημένο</option>
                <option value="Ολοκληρωμένο" <?php if ($appointment['status'] == 'Ολοκληρωμένο') echo 'selected'; ?>>Ολοκληρωμένο</option>
                <option value="Ακυρωμένο" <?php if ($appointment['status'] == 'Ακυρωμένο') echo 'selected'; ?>>Ακυρωμένο</option>
            </select>

            <button type="submit">Ενημέρωση Ραντεβού</button>
        </form>
    </main>
    <footer>
        <p>&copy; 2024 Ιατρείο. Όλα τα δικαιώματα κατοχυρωμένα.</p>
    </footer>
</body>
</html>