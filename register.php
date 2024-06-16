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
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Εγγραφή Χρήστη</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Εγγραφή στο Ιατρείο</h1>
        <nav>
            <ul>
                <li><a href="index.php">Αρχική</a></li>
                <li><a href="register.php">Εγγραφή</a></li>
                <li><a href="login.php">Σύνδεση</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <section>
            <h2>Φόρμα Εγγραφής</h2>
            <form action="register.php" method="post">
                <label for="full_name">Ονοματεπώνυμο:</label>
                <input type="text" id="full_name" name="full_name" required>
                
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
                
                <label for="password">Κωδικός:</label>
                <input type="password" id="password" name="password" required>
                
                <label for="identity_number">Αριθμός Ταυτότητας:</label>
                <input type="text" id="identity_number" name="identity_number" required>
                
                <label for="amka">ΑΜΚΑ:</label>
                <input type="text" id="amka" name="amka" required>
                
                <label for="contact_information">Στοιχεία Επικοινωνίας:</label>
                <input type="text" id="contact_information" name="contact_information" required>
                
                <button type="submit">Εγγραφή</button>
            </form>
        </section>
    </main>
    <footer>
        <p>&copy; 2024 Ιατρείο. Όλα τα δικαιώματα κατοχυρωμένα.</p>
    </footer>
</body>
</html>