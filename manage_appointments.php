<?php
// Ξεκινάμε τη συνεδρία
session_start();

// Ελέγχουμε αν ο χρήστης είναι συνδεδεμένος και αν έχει τον κατάλληλο ρόλο (γιατρός ή γραμματέας)
if (!isset($_SESSION['email']) || ($_SESSION['role'] != 'doctor' && $_SESSION['role'] != 'secretary')) {
    // Αν ο χρήστης δεν είναι συνδεδεμένος ή δεν έχει τον κατάλληλο ρόλο, ανακατευθύνουμε στη σελίδα σύνδεσης
    header('Location: login.php');
    exit();
}

// Συμπεριλαμβάνουμε το αρχείο σύνδεσης με τη βάση δεδομένων
include 'db.php';

// Ελέγχουμε αν υπάρχει αίτημα διαγραφής ραντεβού
if (isset($_GET['delete'])) {
    // Παίρνουμε το ID του ραντεβού που θέλουμε να διαγράψουμε
    $appointment_id = $_GET['delete'];
    // Ετοιμάζουμε το SQL ερώτημα για διαγραφή του ραντεβού από τη βάση δεδομένων
    $sql = "DELETE FROM appointments WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
}

// Παίρνουμε όλα τα ραντεβού από τη βάση δεδομένων μαζί με τα στοιχεία του ασθενή και του γιατρού
$sql = "SELECT a.*, p.full_name AS patient_name, d.full_name AS doctor_name, d.specialty AS doctor_specialty 
        FROM appointments a 
        JOIN users p ON a.patient_id = p.id 
        JOIN users d ON a.doctor_id = d.id";
$result = $conn->query($sql);

// Κλείνουμε τη σύνδεση με τη βάση δεδομένων
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Manage Appointments</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/feather/feather.css">
    <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="vendors/datatables.net-bs4/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" type="text/css" href="dashJs/select.dataTables.min.css">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <link rel="stylesheet" href="dashCss/vertical-layout-light/style.css">
    <!-- endinject -->
    <link rel="shortcut icon" href="images/favicon.jpg" />
</head>

<body>
    <div class="container-scroller">
        <!-- partial:partials/_navbar.html -->
        <nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
            <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
                <a class="navbar-brand brand-logo mr-5" href="patient_dashboard.php"><img src="dashImages/logo.svg" class="mr-2" alt="logo" /></a>
                <a class="navbar-brand brand-logo-mini" href="patient_dashboard.php"><img src="dashImages/logo-mini.svg" alt="logo" /></a>
            </div>
            <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
                <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
                    <span class="icon-menu"></span>
                </button>
                <ul class="navbar-nav navbar-nav-right">
                    <li class="nav-item nav-profile dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" id="profileDropdown">
                            <img src="dashImages/user-icon.jpg" alt="profile" />
                        </a>
                        <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="profileDropdown">
                            <a class="dropdown-item" href="logout.php">
                                <i class="ti-power-off text-primary"></i>
                                Logout
                            </a>
                        </div>
                    </li>
                </ul>
                <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
                    <span class="icon-menu"></span>
                </button>
            </div>
        </nav>
        <!-- partial -->
        <div class="container-fluid page-body-wrapper">
            <!-- partial:partials/_settings-panel.html -->
            <div class="theme-setting-wrapper">
                <div id="settings-trigger"><i class="ti-settings"></i></div>
                <div id="theme-settings" class="settings-panel">
                    <i class="settings-close ti-close"></i>
                    <p class="settings-heading">SIDEBAR SKINS</p>
                    <div class="sidebar-bg-options selected" id="sidebar-light-theme">
                        <div class="img-ss rounded-circle bg-light border mr-3"></div>Light
                    </div>
                    <div class="sidebar-bg-options" id="sidebar-dark-theme">
                        <div class="img-ss rounded-circle bg-dark border mr-3"></div>Dark
                    </div>
                    <p class="settings-heading mt-2">HEADER SKINS</p>
                    <div class="color-tiles mx-0 px-4">
                        <div class="tiles success"></div>
                        <div class="tiles warning"></div>
                        <div class="tiles danger"></div>
                        <div class="tiles info"></div>
                        <div class="tiles dark"></div>
                        <div class="tiles default"></div>
                    </div>
                </div>
            </div>
            <!-- partial -->
            <!-- partial:partials/_sidebar.html -->
            <nav class="sidebar sidebar-offcanvas" id="sidebar">
                <ul class="nav">
                    <?php if ($_SESSION['role'] == 'doctor') : ?>
                        <li class="nav-item">
                            <a class="nav-link" href="secretary_dashboard.php">
                                <i class="icon-grid menu-icon"></i>
                                <span class="menu-title">Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_appointments.php">
                                <i class="icon-layout menu-icon"></i>
                                <span class="menu-title">Manage Appointments</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_patients.php">
                                <i class="icon-columns menu-icon"></i>
                                <span class="menu-title">Manage patients</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_history.php">
                                <i class="icon-columns menu-icon"></i>
                                <span class="menu-title">Manage History</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_availability.php">
                                <i class="icon-columns menu-icon"></i>
                                <span class="menu-title">Manage Availability</span>
                            </a>
                        </li>
                    <?php elseif ($_SESSION['role'] == 'secretary') : ?>
                        <li class="nav-item">
                            <a class="nav-link" href="secretary_dashboard.php">
                                <i class="icon-grid menu-icon"></i>
                                <span class="menu-title">Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_appointments.php">
                                <i class="icon-layout menu-icon"></i>
                                <span class="menu-title">Manage Appointments</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_patients.php">
                                <i class="icon-columns menu-icon"></i>
                                <span class="menu-title">Manage patients</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <!-- partial -->
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-md-12 grid-margin">
                            <div class="row">
                                <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                                    <h3 class="font-weight-bold">Manage Appointments</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">All Appointments</h4>
                                    <a class="btn btn-primary mr-2" href="add_appointment.php">Add Appointment</a>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date/Time</th>
                                                    <th>Patient</th>
                                                    <th>Doctor</th>
                                                    <th>Status</th>
                                                    <th>Descritpion</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($row = $result->fetch_assoc()) : ?>
                                                    <tr>
                                                        <td><?php echo  htmlspecialchars($row['appointment_date']); ?> <?php echo  htmlspecialchars($row['appointment_time']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['doctor_name'] . ' (' . $row['doctor_specialty'] . ')'); ?></td>
                                                        <?php if ($row['status'] != 'Ακυρωμένο' && $row['status'] != 'Ολοκληρωμένο') : ?>
                                                            <td><label class="badge badge-success"><?php echo htmlspecialchars($row['status']); ?></label></td>
                                                        <?php elseif ($row['status'] == 'Ολοκληρωμένο') : ?>
                                                            <td><label class="badge badge-warning"><?php echo htmlspecialchars($row['status']); ?></label></td>
                                                        <?php elseif ($row['status'] == 'Ακυρωμένο') : ?>
                                                            <td><label class="badge badge-danger"><?php echo htmlspecialchars($row['status']); ?></label></td>
                                                        <?php endif; ?>
                                                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                                                        <?php if ($row['status'] != 'Ακυρωμένο' && $row['status'] != 'Ολοκληρωμένο') : ?>
                                                            <td><a href="edit_appointment.php?id=<?php echo $row['id']; ?>">Επεξεργασία</a></td>
                                                            <td><a href="delete_appointment.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure ?')">Ακύρωση</a></td>
                                                        <?php endif; ?>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- content-wrapper ends -->
                <!-- partial:partials/_footer.html -->
                <footer class="footer">
                    <div class="d-sm-flex justify-content-center justify-content-sm-between">
                        <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">Copyright © 2024. All rights reserved.</span>
                        <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">Hand-crafted & made with <i class="ti-heart text-danger ml-1"></i></span>
                    </div>
                </footer>
                <!-- partial -->
            </div>
            <!-- main-panel ends -->
        </div>
        <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->

    <!-- plugins:js -->
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <script src="vendors/chart.js/Chart.min.js"></script>
    <script src="vendors/datatables.net/jquery.dataTables.js"></script>
    <script src="vendors/datatables.net-bs4/dataTables.bootstrap4.js"></script>
    <script src="dashJs/dataTables.select.min.js"></script>

    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="dashJs/off-canvas.js"></script>
    <script src="dashJs/hoverable-collapse.js"></script>
    <script src="dashJs/template.js"></script>
    <script src="dashJs/settings.js"></script>
    <script src="dashJs/todolist.js"></script>
    <!-- endinject -->
    <!-- Custom js for this page-->
    <script src="dashJs/dashboard.js"></script>
    <script src="dashJs/Chart.roundedBarCharts.js"></script>
    <!-- End custom js for this page-->
</body>

</html>