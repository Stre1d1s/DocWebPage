<?php
// Ξεκινάμε τη συνεδρία
session_start();

// Συμπεριλαμβάνουμε το αρχείο σύνδεσης με τη βάση δεδομένων
include 'db.php';

// Ελέγχουμε αν η φόρμα υποβλήθηκε
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Παίρνουμε τα δεδομένα της φόρμας
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $identity_number = $_POST['identity_number'];
    $amka = $_POST['amka'];
    $contact_information = $_POST['contact_information'];

    // Ελέγχουμε αν το email, ο αριθμός ταυτότητας ή το ΑΜΚΑ υπάρχουν ήδη στη βάση δεδομένων
    $sql = "SELECT * FROM users WHERE email = ? OR identity_number = ? OR amka = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $email, $identity_number, $amka);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Αν υπάρχει ήδη χρήστης με το ίδιο email, αριθμό ταυτότητας ή ΑΜΚΑ, εμφανίζουμε μήνυμα λάθους
        echo "Το email, ο αριθμός ταυτότητας ή το ΑΜΚΑ υπάρχει ήδη. Παρακαλώ ελέγξτε τα στοιχεία σας.";
    } else {
        // Αν δεν υπάρχει χρήστης με τα ίδια στοιχεία, εισάγουμε το νέο χρήστη στη βάση δεδομένων
        $sql = "INSERT INTO users (full_name, email, password, identity_number, amka, contact_information, role, registration_date) VALUES (?, ?, ?, ?, ?, ?, 'patient', NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $full_name, $email, $password, $identity_number, $amka, $contact_information);

        if ($stmt->execute()) {
            // Αν η εισαγωγή είναι επιτυχής, ανακατευθύνουμε στη σελίδα σύνδεσης
            echo "Η εγγραφή ολοκληρώθηκε με επιτυχία.";
            header('Location: login.php');
            exit();
        } else {
            // Αν υπάρξει λάθος κατά την εισαγωγή, εμφανίζουμε μήνυμα λάθους
            echo "Σφάλμα κατά την εγγραφή του χρήστη.";
        }
    }

    // Κλείνουμε το statement
    $stmt->close();
}

// Κλείνουμε τη σύνδεση με τη βάση δεδομένων
$conn->close();
?>

<!DOCTYPE html>
<html lang="en" >
<head>
  <meta charset="UTF-8">
  <title>Register - Health Clinic</title>
  <link rel="shortcut icon" href="images/favicon.png" type="">
  <link rel="stylesheet" href="./css/styles.css">

</head>
<body>
	<div class="main">  	
		<div class="signup">
			<form action="register.php" method="post">
				<label>Sign up</label>
				<input type="text" name="full_name" placeholder="Full Name" required>
				<input type="email" name="email" placeholder="Email" required>
				<input type="password" name="password" placeholder="Password" required>
				<input type="text" name="identity_number" placeholder="ID number" required>
				<input type="text" name="amka" placeholder="AMKA" required>
				<input type="text" name="contact_information" placeholder="Contact Details" required>
				<button type="submit">Sign up</button>
			</form>
		</div>
	</div>
	<footer>
		&copy; <span id="displayYear"></span> All Rights Reserved 2024
	</footer>
</body>
</html>