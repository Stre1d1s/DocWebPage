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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $doctor_id = $_POST['doctor_id'];
    $slot_id = $_POST['slot_id'];
    $description = $_POST['description'];

    $sql_slot = "SELECT * FROM doctor_availability WHERE id = ? AND slot_start > NOW()";
    $stmt_slot = $conn->prepare($sql_slot);
    $stmt_slot->bind_param("i", $slot_id);
    $stmt_slot->execute();
    $result_slot = $stmt_slot->get_result();
    $slot = $result_slot->fetch_assoc();

    if ($slot) {
        $appointment_date = date('Y-m-d', strtotime($slot['slot_start']));
        $appointment_time = date('H:i', strtotime($slot['slot_start']));


        $sql_check = "SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'Ακυρωμένο'";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
        $stmt_check->execute();
        $stmt_check->bind_result($existing_appointments_count);
        $stmt_check->fetch();
        $stmt_check->close();

        if ($existing_appointments_count == 0) {
            $status = 'Δημιουργημένο';
            $sql = "INSERT INTO appointments (patient_id, appointment_date, appointment_time, description, status, doctor_id) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issssi", $patient_id, $appointment_date, $appointment_time, $description, $status, $doctor_id);
            if ($stmt->execute()) {
                header('Location: appointments.php');
                exit();
            } else {
                echo "Σφάλμα κατά την προσθήκη του ραντεβού: " . $stmt->error;
            }
        } else {
            echo "Υπάρχει ήδη ραντεβού για τον συγκεκριμένο γιατρό την επιλεγμένη ώρα.";
        }
    } else {
        echo "Το επιλεγμένο slot δεν είναι διαθέσιμο.";
    }

    $stmt_slot->close();
}


$sql = "SELECT a.*, d.full_name AS doctor_name FROM appointments a JOIN users d ON a.doctor_id = d.id WHERE a.patient_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$appointments = $stmt->get_result();


$sql_doctors = "SELECT id, full_name, specialty FROM users WHERE role = 'doctor'";
$doctors_result = $conn->query($sql_doctors);

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Appointments</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/feather/feather.css">
    <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="vendors/select2/select2.min.css">
    <link rel="stylesheet" href="vendors/select2-bootstrap-theme/select2-bootstrap.min.css">
    <link rel="stylesheet" href="vendors/datatables.net-bs4/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" type="text/css" href="dashJs/select.dataTables.min.css">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <link rel="stylesheet" href="dashCss/vertical-layout-light/style.css">
    <!-- endinject -->
    <link rel="shortcut icon" href="images/favicon.jpg" />
    <script>
        function loadAvailableSlots(doctorId) {
            fetch('fetch_slots.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'doctor_id=' + doctorId
                })
                .then(response => response.json())
                .then(data => {
                    const slotSelect = document.getElementById('slot_id');
                    slotSelect.innerHTML = '<option value="">Επιλέξτε διαθέσιμο slot</option>';
                    data.forEach(slot => {
                        const option = document.createElement('option');
                        option.value = slot.id;
                        option.text = `${slot.start} - ${slot.end}`;
                        slotSelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Error fetching slots:', error));
        }
    </script>
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
                    <li class="nav-item">
                        <a class="nav-link" href="patient_dashboard.php">
                            <i class="icon-grid menu-icon"></i>
                            <span class="menu-title">Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="icon-head menu-icon"></i>
                            <span class="menu-title">Edit Profile</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="appointments.php">
                            <i class="icon-layout menu-icon"></i>
                            <span class="menu-title">Appointments</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="history.php">
                            <i class="icon-columns menu-icon"></i>
                            <span class="menu-title">History</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <!-- partial -->
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-md-12 grid-margin">
                            <div class="row">
                                <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                                    <h3 class="font-weight-bold">Appointments</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Add Appointment</h4>
                                    <form action="appointments.php" method="post">
                                        <div class="form-group">
                                            <label for="doctor_id">Avaible Doctor</label>
                                            <select class="js-example-basic-single w-100" id="doctor_id" name="doctor_id" required onchange="loadAvailableSlots(this.value)">
                                                <option value="">Select Doctor</option>
                                                <?php while ($row = $doctors_result->fetch_assoc()) : ?>
                                                    <option value="<?php echo $row['id']; ?>">
                                                        <?php echo htmlspecialchars($row['full_name'] . ' (' . $row['specialty'] . ')'); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="slot_id">Available Date</label>
                                            <select class="js-example-basic-single w-100" id="slot_id" name="slot_id" required>
                                                <option value="">Select Date</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <textarea class="form-control" id="description" name="description" required rows="4"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary mr-2">Update</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">My Appointments</h4>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Doctor</th>
                                                    <th>Status</th>
                                                    <th>Descritpion</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($appointment = $appointments->fetch_assoc()) : ?>
                                                    <tr>
                                                    <td><?php echo  htmlspecialchars($row['appointment_date']); ?> <?php echo  htmlspecialchars($row['appointment_time']); ?></td>
                                                        <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                                        <?php if ($appointment['status'] != 'Ακυρωμένο' && $appointment['status'] != 'Ολοκληρωμένο') : ?>
                                                            <td><label class="badge badge-success"><?php echo htmlspecialchars($appointment['status']); ?></label></td>
                                                        <?php elseif ($appointment['status'] == 'Ολοκληρωμένο') : ?>
                                                            <td><label class="badge badge-warning"><?php echo htmlspecialchars($appointment['status']); ?></label></td>
                                                        <?php elseif ($appointment['status'] == 'Ακυρωμένο') : ?>
                                                            <td><label class="badge badge-danger"><?php echo htmlspecialchars($appointment['status']); ?></label></td>
                                                        <?php endif; ?>
                                                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                                                        <?php if ($appointment['status'] != 'Ακυρωμένο' && $appointment['status'] != 'Ολοκληρωμένο') : ?>
                                                            <td><a href="edit_appointment.php?id=<?php echo $appointment['id']; ?>">Επεξεργασία</a></td>
                                                            <td><a href="delete_appointment.php?id=<?php echo $appointment['id']; ?>" onclick="return confirm('Are you sure ?')">Ακύρωση</a></td>
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
    <script src="vendors/typeahead.js/typeahead.bundle.min.js"></script>
    <script src="vendors/select2/select2.min.js"></script>
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
    <script src="dashJs/file-upload.js"></script>
    <script src="dashJs/typeahead.js"></script>
    <script src="dashJs/select2.js"></script>
    <!-- End custom js for this page-->
</body>

</html>