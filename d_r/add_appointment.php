<?php
session_start();

if (!isset($_SESSION['email']) || !in_array($_SESSION['role'], ['doctor', 'secretary'])) {
    header('Location: login.php');
    exit();
}

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = $_POST['patient_id'];
    $time_slot_id = $_POST['time_slot_id'];
    $description = $_POST['description'];
    $status = 'Δημιουργημένο';


    if (!empty($patient_id) && !empty($time_slot_id) && !empty($description)) {
       
        $sql = "SELECT slot_start, slot_end, doctor_id FROM doctor_availability WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $time_slot_id);
        $stmt->execute();
        $stmt->bind_result($appointment_date_time, $slot_end, $doctor_id);
        $stmt->fetch();
        $stmt->close();

        $appointment_date = date('Y-m-d', strtotime($appointment_date_time));
        $appointment_time = date('H:i:s', strtotime($appointment_date_time));

        $sql = "SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'Ακυρωμένο'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
        $stmt->execute();
        $stmt->bind_result($existing_appointments_count);
        $stmt->fetch();
        $stmt->close();

        if ($existing_appointments_count == 0) {
            $sql = "INSERT INTO appointments (patient_id, appointment_date, appointment_time, description, status, doctor_id, time_slot_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issssii", $patient_id, $appointment_date, $appointment_time, $description, $status, $doctor_id, $time_slot_id);
            if ($stmt->execute()) {
                header('Location: manage_appointments.php');
                exit();
            } else {
                echo "Σφάλμα κατά την προσθήκη του ραντεβού: " . $stmt->error;
            }
        } else {
            echo "Υπάρχει ήδη ραντεβού για τον συγκεκριμένο γιατρό την επιλεγμένη ώρα.";
        }
    } else {
        echo "Παρακαλώ συμπληρώστε όλα τα πεδία.";
    }
}


$sql_patients = "SELECT id, full_name FROM users WHERE role = 'patient'";
$patients_result = $conn->query($sql_patients);


$sql_doctors = "SELECT id, full_name, specialty FROM users WHERE role = 'doctor'";
$doctors_result = $conn->query($sql_doctors);

$conn->close();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Προσθήκη Ραντεβού</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
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