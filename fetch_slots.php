<?php
// Συμπερίληψη του αρχείου για τη σύνδεση με τη βάση δεδομένων
include 'db.php';

// Έλεγχος αν έχει σταλεί το doctor_id μέσω POST
if (isset($_POST['doctor_id'])) {
    // Λήψη του doctor_id από το POST αίτημα
    $doctor_id = $_POST['doctor_id'];

    // Προετοιμασία του SQL ερωτήματος για να βρεθεί η διαθεσιμότητα του γιατρού
    $sql = "SELECT id, slot_start, slot_end FROM doctor_availability WHERE doctor_id = ? AND slot_start > NOW()";
    $stmt = $conn->prepare($sql);
    // Δέσμευση του doctor_id στην προετοιμασμένη δήλωση
    $stmt->bind_param("i", $doctor_id);
    // Εκτέλεση της προετοιμασμένης δήλωσης
    $stmt->execute();
    // Λήψη των αποτελεσμάτων
    $result = $stmt->get_result();

    // Δημιουργία ενός πίνακα για την αποθήκευση των διαθέσιμων ωρών
    $slots = [];
    // Επανάληψη των αποτελεσμάτων και προσθήκη των δεδομένων στον πίνακα slots
    while ($row = $result->fetch_assoc()) {
        $slots[] = [
            'id' => $row['id'],
            'start' => date('Y-m-d H:i', strtotime($row['slot_start'])),
            'end' => date('Y-m-d H:i', strtotime($row['slot_end']))
        ];
    }
    // Επιστροφή των διαθέσιμων ωρών σε μορφή JSON
    echo json_encode($slots);

    // Κλείσιμο της προετοιμασμένης δήλωσης
    $stmt->close();
}

// Κλείσιμο της σύνδεσης με τη βάση δεδομένων
$conn->close();
?>