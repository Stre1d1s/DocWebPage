<?php
session_start();

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'doctor') {
    header('Location: login.php');
    exit();
}

include 'db.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_availability'])) {
    $doctor_id = $_SESSION['user_id']; 
    $slot_start = $_POST['slot_start'];
    $slot_end = date('Y-m-d H:i:s', strtotime($slot_start . ' +30 minutes'));

    $sql = "INSERT INTO doctor_availability (doctor_id, slot_start, slot_end) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $doctor_id, $slot_start, $slot_end);
    if ($stmt->execute()) {
        header('Location: manage_availability.php');
        exit();
    } else {
        echo "Σφάλμα κατά την προσθήκη της διαθεσιμότητας: " . $stmt->error;
    }
}


if (isset($_GET['delete'])) {
    $slot_id = $_GET['delete'];
    $sql = "DELETE FROM doctor_availability WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $slot_id);
    if ($stmt->execute()) {
        header('Location: manage_availability.php');
        exit();
    } else {
        echo "Σφάλμα κατά τη διαγραφή της διαθεσιμότητας: " . $stmt->error;
    }
}


$doctor_id = $_SESSION['user_id']; 
$sql = "SELECT * FROM doctor_availability WHERE doctor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$availability = $stmt->get_result();

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



