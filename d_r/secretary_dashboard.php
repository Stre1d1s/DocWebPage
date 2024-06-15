<?php
session_start();

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'secretary') {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Πίνακας Ελέγχου Γραμματέα</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Πίνακας Ελέγχου Γραμματέα</h1>
        <nav>
            <ul>
                <li><a href="secretary_dashboard.php">Πίνακας Ελέγχου</a></li>
                <li><a href="manage_patients.php">Διαχείριση Ασθενών</a></li>
                <li><a href="manage_appointments.php">Διαχείριση Ραντεβού</a></li>
                <li><a href="logout.php">Αποσύνδεση</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <section>
            <h2>Καλώς ήρθατε στον Πίνακα Ελέγχου</h2>
            <p>Επιλέξτε μία από τις παρακάτω ενέργειες:</p>
            <ul>
                <li><a href="manage_patients.php">Διαχείριση Ασθενών</a></li>
                <li><a href="manage_appointments.php">Διαχείριση Ραντεβού</a></li>
            </ul>
        </section>
    </main>
    <footer>
        <p>&copy; 2024 Ιατρείο. Όλα τα δικαιώματα κατοχυρωμένα.</p>
    </footer>
</body>
</html>
