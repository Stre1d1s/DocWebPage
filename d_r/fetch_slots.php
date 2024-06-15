<?php
include 'db.php';

if (isset($_POST['doctor_id'])) {
    $doctor_id = $_POST['doctor_id'];

    $sql = "SELECT id, slot_start, slot_end FROM doctor_availability WHERE doctor_id = ? AND slot_start > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $slots = [];
    while ($row = $result->fetch_assoc()) {
        $slots[] = [
            'id' => $row['id'],
            'start' => date('Y-m-d H:i', strtotime($row['slot_start'])),
            'end' => date('Y-m-d H:i', strtotime($row['slot_end']))
        ];
    }
    echo json_encode($slots);

    $stmt->close();
}

$conn->close();
?>