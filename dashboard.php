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
        body {
            background: #eef2f7;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .top-bar { margin-top: 15px; }

        .member-card {
            position: relative;
            max-width: 920px;
            margin: 20px auto 30px;
            padding: 24px 24px 28px;
            border-radius: 18px;
            background: linear-gradient(135deg, #0a3a74 0%, #1c5ca8 45%, #2f79cc 100%);
            color: #fff;
            box-shadow: 0 12px 30px rgba(0,0,0,.25);
            overflow: hidden;
        }

        .member-card::before {
            content: "";
            position: absolute;
            top: -80px;
            right: -80px;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: rgba(255,255,255,.08);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .card-title {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: .5px;
        }

        .union-no {
            margin: 6px 0 0;
            font-size: 15px;
            opacity: .95;
        }

        .card-body {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 22px;
            align-items: start;
        }

        .photo-wrap { text-align: center; }

        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 16px;
            object-fit: cover;
            border: 4px solid rgba(255,255,255,.85);
            box-shadow: 0 6px 14px rgba(0,0,0,.28);
            background: #fff;
        }

        .fallback-icon {
            font-size: 150px;
            color: rgba(255,255,255,.9);
            line-height: 1;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 20px;
        }

        .field {
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.18);
            padding: 8px 10px;
            border-radius: 10px;
            font-size: 14px;
        }

        .field b { color: #dfefff; }

        .sizes-row {
            margin-top: 14px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .size-box {
            background: rgba(255,255,255,.14);
            border-radius: 10px;
            padding: 8px 10px;
            text-align: center;
            font-size: 13px;
        }

        .card-logo {
            position: absolute;
            right: 18px;
            bottom: 12px;
            opacity: .9;
            max-height: 36px;
            max-width: 110px;
        }

        .brand-text {
            position: absolute;
            left: 24px;
            bottom: 10px;
            font-size: 12px;
            opacity: .8;
            letter-spacing: .4px;
        }

        @media (max-width: 768px) {
            .card-body { grid-template-columns: 1fr; }
            .info-grid { grid-template-columns: 1fr; }
            .sizes-row { grid-template-columns: repeat(2, 1fr); }
            .card-logo { max-height: 30px; }
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

    <div class="member-card">

            <div class="card-header">
                <div>
                    <h3 class="card-title">SNAT MEMBERSHIP CARD</h3>
                    <p class="union-no">Union No: 058-<?= $data['id'] ?></p>
                </div>
            </div>

            <div class="card-body">
                <div class="photo-wrap">
                    <?php if (!empty($data['url'])) { ?>
                        <img src="<?= $data['url'] ?>" class="profile-img" alt="Profile Photo">
                    <?php } else { ?>
                        <i class="fa fa-user-circle fallback-icon"></i>
                    <?php } ?>
                </div>

                <div>
                    <div class="info-grid">
                        <div class="field"><b>Name:</b> <?= strtoupper($data['name'].' '.$data['surname']) ?></div>
                        <div class="field"><b>ID Number:</b> <?= $data['idnumber'] ?></div>
                        <div class="field"><b>Cell Number:</b> <?= $data['cellnumber'] ?></div>
                        <div class="field"><b>Gender:</b> <?= $data['gender'] ?></div>
                        <div class="field"><b>DOB:</b> <?= $data['dob'] ?></div>
                        <div class="field"><b>Branch:</b> <?= $data['branch_name'] ?></div>
                        <div class="field"><b>Institution:</b> <?= $data['institution'] ?></div>
                        <div class="field"><b>Employment:</b> <?= $data['employment_type'] ?></div>
                        <div class="field"><b>Employee No:</b> <?= $data['employeeno'] ?></div>
                        <div class="field"><b>TSC No:</b> <?= $data['tscno'] ?></div>
                    </div>

                    <div class="sizes-row">
                        <div class="size-box"><b>T-Shirt:</b> <?= $data['tshirt_size'] ?? '-' ?></div>
                        <div class="size-box"><b>Hoodie:</b> <?= $data['hoodie_size'] ?? '-' ?></div>
                        <div class="size-box"><b>Jacket:</b> <?= $data['jacket_size'] ?? '-' ?></div>
                        <div class="size-box"><b>Waist:</b> <?= $data['waist_size'] ?? '-' ?></div>
                    </div>
                </div>
            </div>

            <span class="brand-text">SNAT Union</span>

            <!-- Replace with your real logo path -->
            <img src="assets/logo.png" class="card-logo" alt="SNAT Logo">
            </div>

</div>

</body>
</html>