<?php
// Ξεκινάει η συνεδρία
session_start();

// Συμπερίληψη του αρχείου για τη σύνδεση με τη βάση δεδομένων
include 'db.php';

// Ελέγχει αν το αίτημα είναι POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Λήψη των στοιχείων σύνδεσης από τη φόρμα
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Έλεγχος αν τα πεδία email και κωδικός δεν είναι κενά
    if (!empty($email) && !empty($password)) {
        // Προετοιμασία του SQL ερωτήματος για να βρεθεί ο χρήστης με το δοθέν email και κωδικό
        $sql = "SELECT * FROM users WHERE email = ? AND password = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        // Έλεγχος αν βρέθηκε κάποιος χρήστης
        if ($result->num_rows > 0) {
            // Λήψη των στοιχείων του χρήστη
            $row = $result->fetch_assoc();
            // Αποθήκευση στοιχείων στη συνεδρία
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $row['role'];
            $_SESSION['user_id'] = $row['id'];
            
            // Ανακατεύθυνση ανάλογα με το ρόλο του χρήστη
            if ($row['role'] == 'secretary') {
                header('Location: secretary_dashboard.php');
            } elseif ($row['role'] == 'doctor') {
                header('Location: doctor_dashboard.php');
            } else {
                header('Location: patient_dashboard.php');
            }
            exit();
        } else {
            // Μήνυμα λάθους αν δεν βρέθηκε χρήστης με τα δοθέντα στοιχεία
            $error_message = "Λάθος email ή κωδικός.";
        }
    } else {
        // Μήνυμα λάθους αν τα πεδία email ή κωδικός είναι κενά
        $error_message = "Παρακαλώ εισάγετε email και κωδικό.";
    }
    // Κλείσιμο της προετοιμασμένης δήλωσης και της σύνδεσης με τη βάση δεδομένων
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en" >
<head>
    <!-- Ορισμός του συνόλου χαρακτήρων σε UTF-8 για την υποστήριξη ελληνικών χαρακτήρων -->
    <meta charset="UTF-8">
    <!-- Ρύθμιση της προβολής ώστε να είναι κατάλληλη για κινητές συσκευές -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Τίτλος της σελίδας -->
    <title>Login - Health Clinic</title>
    <link rel="shortcut icon" href="images/favicon.png" type="">
    <!-- Σύνδεση με το εξωτερικό αρχείο στυλ -->
    <link rel="stylesheet" href="./css/styles.css">

</head>
<body>
	<div class="main">  	
		<div class="login">
            <!-- Εμφάνιση μηνύματος λάθους αν υπάρχει -->
            <?php if (!empty($error_message)): ?>
                <p style="color: red;"><?php echo $error_message; ?></p>
            <?php endif; ?>
            <!-- Φόρμα σύνδεσης -->
			<form action="login.php" method="post">
				<label>Sign in</label>
                <!-- Πεδίο για το email -->
				<input type="email" name="email" placeholder="Email" required>
                <!-- Πεδίο για τον κωδικό -->
				<input type="password" name="password" placeholder="Password" required>
                <!-- Κουμπί υποβολής της φόρμας -->
				<button type="submit">Sign in</button>
			</form>
		</div>
	</div>
    <!-- Υποσέλιδο της σελίδας -->
	<footer>
		&copy; <span id="displayYear"></span> All Rights Reserved 2024
	</footer>
</body>
</html>