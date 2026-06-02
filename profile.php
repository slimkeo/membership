<?php
session_start();
require_once 'xyz/config.php';

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$member_id = $_SESSION['id'];

/* GET DATA */
$sql = "
SELECT m.*, s.*
FROM members m
LEFT JOIN member_profile_sizes s ON s.member_id = m.id
WHERE m.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

/* UPDATE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $surname = $_POST['surname'];
    $name = $_POST['name'];
    $cellnumber = $_POST['cellnumber'];
    $institution = $_POST['institution'];

    $tshirt = $_POST['tshirt_size'];
    $hoodie = $_POST['hoodie_size'];
    $jacket = $_POST['jacket_size'];
    $waist = $_POST['waist_size'];

    $profile_url = $data['url'] ?? null;

    if (!empty($_FILES['profile_pic']['name'])) {

        $folder = "uploads/";
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $file = time() . "_" . basename($_FILES['profile_pic']['name']);
        $target = $folder . $file;

        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target)) {
            $profile_url = $target;
        }
    }

    $stmt = $conn->prepare("
        UPDATE members 
        SET surname=?, name=?, cellnumber=?, institution=?, url=?
        WHERE id=?
    ");

    $stmt->bind_param(
        "sssssi",
        $surname,
        $name,
        $cellnumber,
        $institution,
        $profile_url,
        $member_id
    );
    $stmt->execute();

    $stmt = $conn->prepare("
        INSERT INTO member_profile_sizes
        (member_id, tshirt_size, hoodie_size, jacket_size, waist_size)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        tshirt_size=VALUES(tshirt_size),
        hoodie_size=VALUES(hoodie_size),
        jacket_size=VALUES(jacket_size),
        waist_size=VALUES(waist_size)
    ");

    $stmt->bind_param("issss",
        $member_id,
        $tshirt,
        $hoodie,
        $jacket,
        $waist
    );

    $stmt->execute();

    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <style>
        body {
            background: #eef2f7;
            margin: 0;
            padding: 0;
        }

        .top-bar {
            background: #003087;
            color: #fff;
            padding: 12px 15px;
            font-size: 16px;
        }

        .container-fluid {
            padding: 0;
        }

        .form-area {
            padding: 15px;
        }

        .section-title {
            margin-top: 20px;
            font-weight: bold;
            color: #003087;
        }

        .btn-save {
            background: #28a745;
            color: #fff;
            border: none;
            width: 100%;
            padding: 14px;
            font-size: 16px;
            border-radius: 0;
            position: fixed;
            bottom: 0;
            left: 0;
        }

        .form-control {
            height: 45px;
            font-size: 15px;
        }

        label {
            font-size: 14px;
        }

        .spacer {
            height: 70px;
        }
    </style>
</head>

<body>

<!-- TOP BAR -->
<div class="top-bar">
    <i class="fa fa-edit"></i> Edit Profile
</div>

<div class="container-fluid form-area">

<form method="POST" enctype="multipart/form-data">

    <!-- KYC -->
    <div class="form-group">
        <label>Surname</label>
        <input type="text" name="surname" class="form-control"
               value="<?= $data['surname'] ?>">
    </div>

    <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" class="form-control"
               value="<?= $data['name'] ?>">
    </div>

    <div class="form-group">
        <label>Cell Number</label>
        <input type="text" name="cellnumber" class="form-control"
               value="<?= $data['cellnumber'] ?>">
    </div>

    <div class="form-group">
        <label>Institution</label>
        <input type="text" name="institution" class="form-control"
               value="<?= $data['institution'] ?>">
    </div>

    <hr>

    <h4 class="section-title"><i class="fa fa-shopping-bag"></i> Sizes</h4>

    <?php
    $sizes = ['XS','S','M','L','XL','XXL','XXXL','XXXXL'];
    $waists = ['26','28','30','32','34','36','38','40','42','44','46','48','50','52','54','56','58','60'];
    ?>

    <div class="form-group">
        <label>T-Shirt</label>
        <select name="tshirt_size" class="form-control">
            <?php foreach($sizes as $s) { ?>
                <option value="<?= $s ?>" <?= ($data['tshirt_size']==$s?'selected':'') ?>>
                    <?= $s ?>
                </option>
            <?php } ?>
        </select>
    </div>

    <div class="form-group">
        <label>Hoodie</label>
        <select name="hoodie_size" class="form-control">
            <?php foreach($sizes as $s) { ?>
                <option value="<?= $s ?>" <?= ($data['hoodie_size']==$s?'selected':'') ?>>
                    <?= $s ?>
                </option>
            <?php } ?>
        </select>
    </div>

    <div class="form-group">
        <label>Jacket</label>
        <select name="jacket_size" class="form-control">
            <?php foreach($sizes as $s) { ?>
                <option value="<?= $s ?>" <?= ($data['jacket_size']==$s?'selected':'') ?>>
                    <?= $s ?>
                </option>
            <?php } ?>
        </select>
    </div>

    <div class="form-group">
        <label>Waist</label>
        <select name="waist_size" class="form-control">
            <?php foreach($waists as $w) { ?>
                <option value="<?= $w ?>" <?= ($data['waist_size']==$w?'selected':'') ?>>
                    <?= $w ?>
                </option>
            <?php } ?>
        <?php } ?>
    </select>
    </div>

    <hr>

    <div class="form-group">
        <label>Profile Picture</label>
        <input type="file" name="profile_pic" class="form-control">
    </div>

    <div class="spacer"></div>

    <button type="submit" class="btn-save">
        <i class="fa fa-save"></i> Save Changes
    </button>

</form>

</div>

</body>
</html>