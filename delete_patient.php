<?php
session_start();

if (!isset($_SESSION['email']) || !in_array($_SESSION['role'], ['secretary'])) {
    header('Location: login.php');
    exit();
}

include 'db.php';

$patient_id = $_GET['id'];


$sql = "DELETE FROM users WHERE id = ? AND role = 'patient'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();

$stmt->close();
$conn->close();

header('Location: manage_patients.php');
exit();
?>
