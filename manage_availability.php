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

// Ελέγχουμε αν η φόρμα έχει υποβληθεί και αν ζητείται προσθήκη διαθεσιμότητας
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_availability'])) {
    // Παίρνουμε τα δεδομένα από τη φόρμα
    $doctor_id = $_SESSION['user_id']; 
    $slot_start = $_POST['slot_start'];
    // Υπολογίζουμε την ώρα λήξης του slot προσθέτοντας 30 λεπτά
    $slot_end = date('Y-m-d H:i:s', strtotime($slot_start . ' +30 minutes'));

    // Ετοιμάζουμε το SQL ερώτημα για προσθήκη νέου slot διαθεσιμότητας στη βάση δεδομένων
    $sql = "INSERT INTO doctor_availability (doctor_id, slot_start, slot_end) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $doctor_id, $slot_start, $slot_end);
    if ($stmt->execute()) {
        // Ανακατεύθυνση στη σελίδα διαχείρισης διαθεσιμότητας μετά την επιτυχή προσθήκη
        header('Location: manage_availability.php');
        exit();
    } else {
        echo "Σφάλμα κατά την προσθήκη της διαθεσιμότητας: " . $stmt->error;
    }
}

// Ελέγχουμε αν υπάρχει αίτημα διαγραφής διαθεσιμότητας
if (isset($_GET['delete'])) {
    // Παίρνουμε το ID του slot που θέλουμε να διαγράψουμε
    $slot_id = $_GET['delete'];
    // Ετοιμάζουμε το SQL ερώτημα για διαγραφή του slot από τη βάση δεδομένων
    $sql = "DELETE FROM doctor_availability WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $slot_id);
    if ($stmt->execute()) {
        // Ανακατεύθυνση στη σελίδα διαχείρισης διαθεσιμότητας μετά την επιτυχή διαγραφή
        header('Location: manage_availability.php');
        exit();
    } else {
        echo "Σφάλμα κατά τη διαγραφή της διαθεσιμότητας: " . $stmt->error;
    }
}

// Παίρνουμε όλα τα slots διαθεσιμότητας του γιατρού από τη βάση δεδομένων
$doctor_id = $_SESSION['user_id']; 
$sql = "SELECT * FROM doctor_availability WHERE doctor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$availability = $stmt->get_result();

// Κλείνουμε το statement και τη σύνδεση με τη βάση δεδομένων
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Διαχείριση Διαθεσιμότητας</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js"></script>
</head>
<body>
    <header>
        <h1>Διαχείριση Διαθεσιμότητας</h1>
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
        <h2>Τα Slots Διαθεσιμότητάς Μου</h2>
        <div id="calendar"></div>
        <form action="manage_availability.php" method="post">
            <label for="slot_start">Προσθήκη Διαθεσιμότητας:</label>
            <input type="datetime-local" id="slot_start" name="slot_start" required>
            <button type="submit" name="add_availability">Προσθήκη</button>
        </form>
    </main>

    <script>
        $(document).ready(function () {
            $('#calendar').fullCalendar({
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'month,agendaWeek,agendaDay'
                },
                events: [
                    <?php
                    // Παίρνουμε τα δεδομένα για τα slots διαθεσιμότητας και τα προσθέτουμε στο ημερολόγιο
                    while ($slot = $availability->fetch_assoc()) {
                        echo '{
                            title: "Διαθέσιμο",
                            start: "' . $slot['slot_start'] . '",
                            end: "' . $slot['slot_end'] . '",
                            url: "manage_availability.php?delete=' . $slot['id'] . '"
                        },';
                    }
                    ?>
                ]
            });
        });
    </script>
</body>
</html>



