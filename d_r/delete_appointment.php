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
$patient_id = $user['id'];


$appointment_id = $_GET['id'];


if ($role == 'secretary' || $role == 'doctor') {
    $sql = "UPDATE appointments SET status = 'Ακυρωμένο' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $appointment_id);
} else {
    $sql = "UPDATE appointments SET status = 'Ακυρωμένο' WHERE id = ? AND patient_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $appointment_id, $patient_id);
}

$stmt->execute();

if ($role == 'secretary' || $role == 'doctor') {
    header('Location: manage_appointments.php');
} else {
    header('Location: appointments.php');
}
exit();

$stmt->close();
$conn->close();
?>

