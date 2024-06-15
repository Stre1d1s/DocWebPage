<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

include 'db.php';

$email = $_SESSION['email'];

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$patient_id = $user['id'];


$stmt = $conn->prepare("SELECT * FROM medical_history WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$history = $stmt->get_result();

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ιατρικό Ιστορικό</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Ιατρικό Ιστορικό</h1>
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
        <h2>Ιατρικό Ιστορικό</h2>
        <ul>
            <?php while ($record = $history->fetch_assoc()): ?>
                <li>
                    <strong>Ημερομηνία: <?php echo htmlspecialchars($record['record_date']); ?></strong><br>
                    <strong>Προβλήματα Υγείας: </strong><?php echo htmlspecialchars($record['health_issues']); ?><br>
                    <strong>Θεραπεία: </strong><?php echo htmlspecialchars($record['treatment']); ?>
                </li>
            <?php endwhile; ?>
        </ul>
    </main>
    <footer>
        <p>&copy; 2024 Ιατρείο. Όλα τα δικαιώματα κατοχυρωμένα.</p>
    </footer>
</body>
</html>


