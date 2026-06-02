<?php
session_start();
require_once 'xyz/config.php';

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

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

    /* PROFILE IMAGE UPLOAD */
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

    /* UPDATE MEMBERS */
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

    /* UPSERT SIZES */
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

    $stmt->bind_param(
        "issss",
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
    </style>
</head>

<body>

<div class="container">

<div class="card">

<h3><i class="fa fa-edit"></i> Edit Profile</h3>

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

<h4><i class="fa fa-shopping-bag"></i> Merchandise Sizes</h4>

<?php
$sizes = ['XS','S','M','L','XL','XXL','XXXL','XXXXL'];
$waists = ['26','28','30','32','34','36','38','40','42','44','46','48','50','52','54','56','58','60'];
?>

<div class="form-group">
    <label>T-Shirt Size</label>
    <select name="tshirt_size" class="form-control">
        <?php foreach($sizes as $s) { ?>
            <option value="<?= $s ?>" <?= ($data['tshirt_size']==$s?'selected':'') ?>>
                <?= $s ?>
            </option>
        <?php } ?>
    </select>
</div>

<div class="form-group">
    <label>Hoodie Size</label>
    <select name="hoodie_size" class="form-control">
        <?php foreach($sizes as $s) { ?>
            <option value="<?= $s ?>" <?= ($data['hoodie_size']==$s?'selected':'') ?>>
                <?= $s ?>
            </option>
        <?php } ?>
    </select>
</div>

<div class="form-group">
    <label>Jacket Size</label>
    <select name="jacket_size" class="form-control">
        <?php foreach($sizes as $s) { ?>
            <option value="<?= $s ?>" <?= ($data['jacket_size']==$s?'selected':'') ?>>
                <?= $s ?>
            </option>
        <?php } ?>
    </select>
</div>

<div class="form-group">
    <label>Waist Size</label>
    <select name="waist_size" class="form-control">
        <?php foreach($waists as $w) { ?>
            <option value="<?= $w ?>" <?= ($data['waist_size']==$w?'selected':'') ?>>
                <?= $w ?>
            </option>
        <?php } ?>
    </select>
</div>

<hr>

<div class="form-group">
    <label>Profile Picture</label>
    <input type="file" name="profile_pic" class="form-control">
</div>

<br>

<button class="btn btn-success btn-block">
    <i class="fa fa-save"></i> Save Changes
</button>

</form>

</div>

</div>

</body>
</html>