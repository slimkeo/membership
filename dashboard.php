<?php
session_start();
require_once 'xyz/config.php';

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$member_id = $_SESSION['id'];

$sql = "
SELECT 
m.*,
b.name AS branch_name,
e.description AS employment_type,
s.tshirt_size,
s.hoodie_size,
s.jacket_size,
s.waist_size
FROM members m
LEFT JOIN branches b ON b.id = m.branch
LEFT JOIN employement_status e ON e.id = m.employment_status
LEFT JOIN member_profile_sizes s ON s.member_id = m.id
WHERE m.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>SNAT Dashboard</title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <style>
        body { background:#f4f6f9; }
        .card {
            background:#fff;
            padding:25px;
            margin-top:20px;
            border-radius:8px;
            box-shadow:0 2px 10px rgba(0,0,0,0.1);
        }
        .profile-img {
            width:140px;
            height:140px;
            border-radius:50%;
        }
        .top-bar {
            margin-top:15px;
        }
    </style>
</head>

<body>

<div class="container">

    <!-- TOP BAR -->
    <div class="top-bar text-right">
        <a href="profile.php" class="btn btn-primary btn-sm">
            <i class="fa fa-edit"></i> Edit Profile
        </a>

        <a href="logout.php" class="btn btn-danger btn-sm">
            <i class="fa fa-sign-out"></i> Logout
        </a>
    </div>

    <div class="card text-center">

        <!-- PROFILE IMAGE -->
        <?php if (!empty($data['url'])) { ?>
            <img src="<?= $data['url'] ?>" class="profile-img">
        <?php } else { ?>
            <i class="fa fa-user-circle" style="font-size:140px;color:#003087;"></i>
        <?php } ?>

        <h3>SNAT Union No: 058-<?= $data['id'] ?></h3>

        <hr>

        <div class="row text-left">

            <div class="col-md-6">
                <p><b>Name:</b> <?= strtoupper($data['name'].' '.$data['surname']) ?></p>
                <p><b>ID Number:</b> <?= $data['idnumber'] ?></p>
                <p><b>Cell Number:</b> <?= $data['cellnumber'] ?></p>
                <p><b>Gender:</b> <?= $data['gender'] ?></p>
                <p><b>DOB:</b> <?= $data['dob'] ?></p>
            </div>

            <div class="col-md-6">
                <p><b>Branch:</b> <?= $data['branch_name'] ?></p>
                <p><b>Institution:</b> <?= $data['institution'] ?></p>
                <p><b>Employment:</b> <?= $data['employment_type'] ?></p>
                <p><b>Employee No:</b> <?= $data['employeeno'] ?></p>
                <p><b>TSC No:</b> <?= $data['tscno'] ?></p>
            </div>

        </div>

        <hr>

        <h4><i class="fa fa-shopping-bag"></i> Merchandise Sizes</h4>

        <div class="row text-left">
            <div class="col-md-3"><b>T-Shirt:</b> <?= $data['tshirt_size'] ?? '-' ?></div>
            <div class="col-md-3"><b>Hoodie:</b> <?= $data['hoodie_size'] ?? '-' ?></div>
            <div class="col-md-3"><b>Jacket:</b> <?= $data['jacket_size'] ?? '-' ?></div>
            <div class="col-md-3"><b>Waist:</b> <?= $data['waist_size'] ?? '-' ?></div>
        </div>

    </div>

</div>

</body>
</html>