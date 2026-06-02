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
s.waist_size,
s.profile_picture
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
        .card { background:#fff; padding:20px; border-radius:8px; margin-top:20px; box-shadow:0 2px 10px rgba(0,0,0,0.1); }
        .profile-icon { font-size:120px; color:#003087; }
        .label-title { font-weight:bold; }
    </style>
</head>

<body>

<div class="container">

    <div class="card text-center">

        <?php if (!empty($data['profile_picture'])) { ?>
            <img src="<?= $data['profile_picture'] ?>" class="img-circle" style="width:140px;height:140px;">
        <?php } else { ?>
            <i class="fa fa-user-circle profile-icon"></i>
        <?php } ?>

        <h3>SNAT Union No: 058-<?= $data['id'] ?></h3>

        <hr>

        <div class="row text-left">

            <div class="col-md-6">
                <p><span class="label-title">Name:</span> <?= strtoupper($data['name'].' '.$data['surname']) ?></p>
                <p><span class="label-title">ID Number:</span> <?= $data['idnumber'] ?></p>
                <p><span class="label-title">Cell:</span> <?= $data['cellnumber'] ?></p>
                <p><span class="label-title">Gender:</span> <?= $data['gender'] ?></p>
            </div>

            <div class="col-md-6">
                <p><span class="label-title">Branch:</span> <?= $data['branch_name'] ?></p>
                <p><span class="label-title">Institution:</span> <?= $data['institution'] ?></p>
                <p><span class="label-title">Employment:</span> <?= $data['employment_type'] ?></p>
            </div>

        </div>

        <hr>

        <h4>Merchandise Sizes</h4>

        <p><b>T-Shirt:</b> <?= $data['tshirt_size'] ?? '-' ?></p>
        <p><b>Hoodie:</b> <?= $data['hoodie_size'] ?? '-' ?></p>
        <p><b>Jacket:</b> <?= $data['jacket_size'] ?? '-' ?></p>
        <p><b>Waist:</b> <?= $data['waist_size'] ?? '-' ?></p>

        <br>

        <a href="profile.php" class="btn btn-primary">
            <i class="fa fa-edit"></i> Edit Profile
        </a>

    </div>

</div>

</body>
</html>