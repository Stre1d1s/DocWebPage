<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

include 'db.php';

$email = $_SESSION['email']; 
$role = $_SESSION['role'];

$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Πίνακας Ελέγχου</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Πίνακας Ελέγχου</h1>
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
        <h2>Καλώς ήρθες, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
    </main>
    <footer>
        <p>&copy; 2024 Ιατρείο. Όλα τα δικαιώματα κατοχυρωμένα.</p>
    </footer>
</body>
</html>

