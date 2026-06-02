<?php
session_start();
require_once 'xyz/config.php';

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$member_id = $_SESSION['id'];

/* GET MEMBER */
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
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // KYC
    $surname = $_POST['surname'];
    $name = $_POST['name'];
    $cellnumber = $_POST['cellnumber'];
    $institution = $_POST['institution'];

    // Sizes
    $tshirt = $_POST['tshirt_size'];
    $hoodie = $_POST['hoodie_size'];
    $jacket = $_POST['jacket_size'];
    $waist = $_POST['waist_size'];
    $profile_pic = $_POST['profile_picture'];

    /* UPDATE MEMBERS */
    $stmt = $conn->prepare("
        UPDATE members 
        SET surname=?, name=?, cellnumber=?, institution=?
        WHERE id=?
    ");
    $stmt->bind_param("ssssi", $surname, $name, $cellnumber, $institution, $member_id);
    $stmt->execute();

    /* UPSERT SIZES */
    $stmt = $conn->prepare("
        INSERT INTO member_profile_sizes
        (member_id, tshirt_size, hoodie_size, jacket_size, waist_size, profile_picture)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        tshirt_size=VALUES(tshirt_size),
        hoodie_size=VALUES(hoodie_size),
        jacket_size=VALUES(jacket_size),
        waist_size=VALUES(waist_size),
        profile_picture=VALUES(profile_picture)
    ");

    $stmt->bind_param(
        "isssss",
        $member_id,
        $tshirt,
        $hoodie,
        $jacket,
        $waist,
        $profile_pic
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
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <style>
        body { background:#f4f6f9; }
        .card { background:#fff; padding:25px; margin-top:20px; border-radius:8px; }
    </style>
</head>

<body>

<div class="container">

<div class="card">

<h3>Edit Profile</h3>

<form method="POST">

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

<h4>Sizes</h4>

<!-- TSHIRT -->
<select name="tshirt_size" class="form-control">
    <?php foreach(['XS','S','M','L','XL','XXL','XXXL','XXXXL'] as $s) { ?>
        <option <?= ($data['tshirt_size']==$s?'selected':'') ?>><?= $s ?></option>
    <?php } ?>
</select>
<br>

<!-- HOODIE -->
<select name="hoodie_size" class="form-control">
    <?php foreach(['XS','S','M','L','XL','XXL','XXXL','XXXXL'] as $s) { ?>
        <option <?= ($data['hoodie_size']==$s?'selected':'') ?>><?= $s ?></option>
    <?php } ?>
</select>
<br>

<!-- JACKET -->
<select name="jacket_size" class="form-control">
    <?php foreach(['XS','S','M','L','XL','XXL','XXXL','XXXXL'] as $s) { ?>
        <option <?= ($data['jacket_size']==$s?'selected':'') ?>><?= $s ?></option>
    <?php } ?>
</select>
<br>

<!-- WAIST -->
<select name="waist_size" class="form-control">
    <?php foreach(['26','28','30','32','34','36','38','40','42','44','46','48','50','52','54','56','58','60'] as $w) { ?>
        <option <?= ($data['waist_size']==$w?'selected':'') ?>><?= $w ?></option>
    <?php } ?>
</select>

<br>

<div class="form-group">
    <label>Profile Picture URL</label>
    <input type="text" name="profile_picture" class="form-control"
           value="<?= $data['profile_picture'] ?>">
</div>

<br>

<button class="btn btn-success btn-block">
    Save Changes
</button>

</form>

</div>

</div>

</body>
</html>