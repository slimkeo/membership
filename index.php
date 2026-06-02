<?php
// index.php - SNAT Union Membership Registration Portal
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// ==================== DATABASE CONNECTION ====================
require_once 'xyz/config.php';   // Make sure this file creates $conn correctly

// Check if connection is successful
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn->connect_error ?? 'Connection variable not found'));
}

// Fetch dropdown data
$branches_result = $conn->query("SELECT id, name FROM branches ORDER BY name ASC");
$branches = $branches_result ? $branches_result->fetch_all(MYSQLI_ASSOC) : [];

$emp_result = $conn->query("SELECT id, description FROM employement_status ORDER BY description ASC");
$employment_status = $emp_result ? $emp_result->fetch_all(MYSQLI_ASSOC) : [];

// ==================== SMS FUNCTION ====================
function sendMemberSMS($phone, $member_id, $surname, $name) {
    $api_key = "c25hdGZpbmFuY2VAb3V0bG9vay5jb20tcmVhbHNtcw==";

    $message = "VM {$name} {$surname},\n\n";
    $message .= "Your SNAT Union membership has been updated successfully.\n";
    $message .= "Union No: 058-{$member_id}\n\n";
    $message .= "Thank you!\nSNAT Union";
    $message .= "Tell other VMs to update their KYC for Union Numbers here https://membership.snatunion.com/";

    $url = "https://www.realsms.co.sz/urlSend?_apiKey={$api_key}&dest={$phone}&message=" . urlencode($message);

    $response = @file_get_contents($url);
    return ($response !== FALSE);
}

// ==================== FORM PROCESSING ====================
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $idnumber      = trim($_POST['idnumber'] ?? '');
    $employeeno    = trim($_POST['employeeno'] ?? '');
    $tscno         = trim($_POST['tscno'] ?? '');
    $surname       = strtoupper(trim($_POST['surname'] ?? ''));
    $name          = ucwords(strtolower(trim($_POST['name'] ?? '')));
    $cellnumber    = trim($_POST['cellnumber'] ?? '');
    $dob           = trim($_POST['dob'] ?? '');
    $gender        = $_POST['gender'] ?? '';
    $schoolcode    = trim($_POST['schoolcode'] ?? '');
    $institution   = trim($_POST['institution'] ?? '');
    $employment_status_id = !empty($_POST['employment_status']) ? intval($_POST['employment_status']) : null;
    $branch_id     = !empty($_POST['branch']) ? intval($_POST['branch']) : null;
    $nominee       = trim($_POST['nominee_fullname'] ?? '');

    // Validation
    if (strlen($idnumber) !== 13) {
        $message = "ID Number must be exactly 13 digits.";
    } elseif (empty($surname) || empty($name)) {
        $message = "Surname and Name are required.";
    } elseif (strlen($cellnumber) !== 11 || strpos($cellnumber, '268') !== 0) {
        $message = "Cell number must start with 268 and be 11 digits.";
    } else {
        // Check duplicate
        $check = $conn->prepare("SELECT id FROM members WHERE idnumber = ? OR cellnumber = ?");
        if ($check) {
            $check->bind_param("ss", $idnumber, $cellnumber);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $message = "A member with this ID Number or Cell Number already exists.";
            } else {
                // ==================== FIXED INSERT ====================
                $sql = "INSERT INTO members 
                        (idnumber, employeeno, tscno, surname, name, cellnumber, dob, gender,
                         schoolcode, institution, employment_status, branch)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);

                if ($stmt === false) {
                    $message = "Prepare Error: " . $conn->error;
                } else {
                    $stmt->bind_param("ssssssssssis",
                        $idnumber, $employeeno, $tscno, $surname, $name, $cellnumber,
                        $dob, $gender, $schoolcode, $institution, $employment_status_id,
                        $branch_id);

                    if ($stmt->execute()) {
                        $new_member_id = $conn->insert_id;
                        $success = true;
                        $message = "Member registered successfully!<br><strong>Member ID: {$new_member_id}</strong>";

                        $sms_sent = sendMemberSMS($cellnumber, $new_member_id, $surname, $name);
                        if ($sms_sent) {
                            $message .= "<br><small>SMS notification has been sent.</small>";
                        } else {
                            $message .= "<br><small style='color:orange;'>SMS could not be sent at this time.</small>";
                        }
                    } else {
                        $message = "Execute Error: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
            $check->close();
        } else {
            $message = "Check Query Error: " . $conn->error;
        }
    }
}

// Close connection at the end (optional but good practice)
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SNAT Union - Membership KYC</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .header { background: linear-gradient(135deg, #ffffff, #f9f9f9); color: white; padding: 20px 0; }
        .logo-img { height: 150px; }
        .card { box-shadow: 0 4px 20px rgba(0,0,0,0.1); border-radius: 8px; }
        .btn-primary { background-color: #003087; border-color: #003087; }
        .btn-primary:hover { background-color: #002266; }
    </style>
</head>
<body>

<div class="header">
    <div class="container">
        <div class="row">
            <div class="col-md-12 text-center">
                <img src="assets/SNATLOGO.png" alt="SNAT Union Logo" class="logo-img">
                <h2 style="margin-top: 10px; margin-bottom: 5px; color: black;">SNAT UNION (058) KYC FORM</h2>
            </div>
        </div>
    </div>
    <div class="text-right" style="margin-top:10px;">
    <a href="login.php" class="btn btn-success">
        <i class="fa fa-sign-in"></i> Member Login
    </a>
</div>
</div>

<div class="container">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4><i class="fa fa-user-plus"></i> Register New Member</h4>
                </div>
                
                <div class="card-body p-4">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $success ? 'success' : 'danger' ?>">
                            <?= $message ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="form-horizontal">
                        <!-- All your form fields are already here - no change needed -->
                        <!-- ID NUMBER -->
                        <div class="form-group">
                            <label class="col-md-3 control-label">ID Number <span class="text-danger">*</span></label>
                            <div class="col-md-7">
                                <input type="text" minlength="13" maxlength="13" class="form-control"
                                       id="idnumber_input" name="idnumber" required
                                       placeholder="Enter 13-digit ID number" oninput="extractDOBFromID()">
                            </div>
                        </div>

                        <!-- SURNAME -->
                        <div class="form-group">
                            <label class="col-md-3 control-label">Surname <span class="text-danger">*</span></label>
                            <div class="col-md-7">
                                <input type="text" class="form-control" name="surname" required
                                       placeholder="Surname" style="text-transform: uppercase;">
                            </div>
                        </div>

                        <!-- NAME -->
                        <div class="form-group">
                            <label class="col-md-3 control-label">Name <span class="text-danger">*</span></label>
                            <div class="col-md-7">
                                <input type="text" class="form-control" name="name" required
                                       placeholder="First Name(s)" style="text-transform: uppercase;">
                            </div>
                        </div>

                        <!-- CELL NUMBER -->
                        <div class="form-group">
                            <label class="col-md-3 control-label">MTN Cell Number <span class="text-danger">*</span></label>
                            <div class="col-md-7">
                                <input type="text" class="form-control" name="cellnumber"
                                       maxlength="11" minlength="11" pattern="268[0-9]{8}"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                                       placeholder="268XXXXXXXX" required>
                            </div>
                        </div>

                        <!-- EMPLOYEE NO -->
                        <div class="form-group">
                            <label class="col-md-3 control-label">Employee No</label>
                            <div class="col-md-7">
                                <input type="text" class="form-control" name="employeeno" placeholder="Employee number (optional)">
                            </div>
                        </div>

                        <!-- TSC NO -->
                        <div class="form-group">
                            <label class="col-md-3 control-label">TSC No</label>
                            <div class="col-md-7">
                                <input type="text" class="form-control" name="tscno" placeholder="TSC number (optional)">
                            </div>
                        </div>

                        <!-- DOB -->
                        <div class="form-group">
                            <label class="col-md-3 control-label">Date of Birth <span class="text-danger">*</span></label>
                            <div class="col-md-7">
                                <div class="input-group date" data-provide="datepicker" data-date-format="yyyy-mm-dd">
                                    <input type="text" class="form-control" name="dob" id="dob_input" required>
                                    <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
                                </div>
                            </div>
                        </div>
                       <!-- GENDER -->
                        <div class="form-group">
                            <label class="col-md-3 control-label">Gender <span class="text-danger">*</span></label>
                            <div class="col-md-7">
                                <select name="gender" class="form-control" required>
                                    <option value="">Select Gender</option>
                                    <option value="M">Male</option>
                                    <option value="F">Female</option>
                                </select>
                            </div>
                        </div>

                        <!-- EMPLOYMENT STATUS -->
                        <div class="form-group">
                            <label class="col-md-3 control-label">Employment Type <span class="text-danger">*</span></label>
                            <div class="col-md-7">
                                <select name="employment_status" class="form-control" required>
                                    <option value="">Select Employment Type</option>
                                    <?php foreach ($employment_status as $row): ?>
                                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['description']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- BRANCH -->
                        <div class="form-group">
                            <label class="col-md-3 control-label">Branch <span class="text-danger">*</span></label>
                            <div class="col-md-7">
                                <select name="branch" class="form-control" required>
                                    <option value="">Select Branch</option>
                                    <?php foreach ($branches as $row): ?>
                                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <!-- SCHOOL / Instititution -->
                        <div class="form-group">
                            <label class="col-md-3 control-label">School / Institution</label>
                            <div class="col-md-7">
                                <input type="text" class="form-control" name="institution" placeholder="School / Institution">
                            </div>
                        </div>
                        <!-- SCHOOL CODE -->
                        <div class="form-group">
                            <label class="col-md-3 control-label">School Code</label>
                            <div class="col-md-7">
                                <input type="number" class="form-control" name="schoolcode" placeholder="School code (if applicable)">
                            </div>
                        </div>
                        
                        <!-- CONSENT SECTION -->
                        <div class="form-group">
                            <div class="col-md-10 col-md-offset-1">
                                <div class="checkbox">
                                    <label style="font-size: 14px; line-height: 1.5;">
                                        <input type="checkbox" name="consent" id="consent" required> 
                                        I confirm that I am a subscribing member of the union, the information provided is accurate and I <strong>consent</strong> to SNAT Union 
                                        collecting, processing, and using my personal data for the purposes of membership registration, 
                                        administration, and communication. I understand that my data will be treated confidentially 
                                        in accordance with relevant laws.
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-md-7 col-md-offset-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fa fa-user-plus"></i> Register Member
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript (unchanged) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

<script>
function extractDOBFromID() {
    const id = document.getElementById('idnumber_input').value;
    if (id.length >= 6) {
        const yearPart = id.substring(0, 2);
        const month = id.substring(2, 4);
        const day = id.substring(4, 6);
        const fullYear = parseInt(yearPart) > 30 ? '19' + yearPart : '20' + yearPart;
        document.getElementById('dob_input').value = `${fullYear}-${month}-${day}`;
    }
}
$(document).ready(function(){
    $('.date').datepicker({
        autoclose: true,
        todayHighlight: true,
        format: 'yyyy-mm-dd'
    });
});
</script>
</body>
</html>