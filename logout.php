<?php
// Ξεκινάει η συνεδρία
session_start();

// Καταστροφή της συνεδρίας, διαγράφοντας όλα τα δεδομένα συνεδρίας
session_destroy();

// Ανακατεύθυνση του χρήστη στην αρχική σελίδα
header('Location: index.html');

// Τερματισμός του script για να διασφαλιστεί ότι η ανακατεύθυνση γίνεται άμεσα
exit();
?>

