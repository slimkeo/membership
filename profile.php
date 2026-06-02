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
    $stmt = $conn->prepare("UPDATE members SET surname=?, name=?, cellnumber=?, institution=?, url=? WHERE id=?");
    $stmt->bind_param("sssssi", $surname, $name, $cellnumber, $institution, $profile_url, $member_id);
    $stmt->execute();

    /* UPSERT SIZES */
    $stmt = $conn->prepare("
        INSERT INTO member_profile_sizes (member_id, tshirt_size, hoodie_size, jacket_size, waist_size)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        tshirt_size=VALUES(tshirt_size),
        hoodie_size=VALUES(hoodie_size),
        jacket_size=VALUES(jacket_size),
        waist_size=VALUES(waist_size)
    ");

    $stmt->bind_param("issss", $member_id, $tshirt, $hoodie, $jacket, $waist);
    $stmt->execute();

    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Edit Profile</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background: #f4f6f9;
            min-height: 100vh;
            padding: 0;
            margin: 0;
        }

        .container {
            max-width: 100%;
            padding: 0;
        }

        .main-card {
            background: #fff;
            min-height: 100vh;
            border-radius: 0;
            box-shadow: none;
            padding: 20px 15px;
        }

        @media (min-width: 768px) {
            .main-card {
                max-width: 600px;
                margin: 20px auto;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                min-height: auto;
            }
        }

        .form-control, .form-select {
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 16px; /* Prevents zoom on iOS */
        }

        .btn {
            padding: 14px;
            font-size: 17px;
            border-radius: 8px;
        }

        .profile-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body>

<div class="container">
    <div class="main-card">

        <h3 class="mb-4 text-center">
            <i class="fas fa-user-edit"></i> Edit Profile
        </h3>

        <form method="POST" enctype="multipart/form-data">

            <!-- Profile Picture -->
            <div class="text-center mb-4">
                <?php if (!empty($data['url'])): ?>
                    <img src="<?= htmlspecialchars($data['url']) ?>" class="profile-preview" alt="Profile Picture">
                <?php else: ?>
                    <div class="profile-preview bg-light d-flex align-items-center justify-content-center mx-auto">
                        <i class="fas fa-user fa-3x text-muted"></i>
                    </div>
                <?php endif; ?>
                <div class="mt-3">
                    <input type="file" name="profile_pic" class="form-control" accept="image/*">
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-bold">Surname</label>
                    <input type="text" name="surname" class="form-control" 
                           value="<?= htmlspecialchars($data['surname'] ?? '') ?>" required>
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold">Name</label>
                    <input type="text" name="name" class="form-control" 
                           value="<?= htmlspecialchars($data['name'] ?? '') ?>" required>
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold">Cell Number</label>
                    <input type="tel" name="cellnumber" class="form-control" 
                           value="<?= htmlspecialchars($data['cellnumber'] ?? '') ?>">
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold">Institution</label>
                    <input type="text" name="institution" class="form-control" 
                           value="<?= htmlspecialchars($data['institution'] ?? '') ?>">
                </div>
            </div>

            <hr class="my-4">

            <h5 class="mb-3"><i class="fas fa-tshirt"></i> Merchandise Sizes</h5>

            <?php
            $sizes = ['XS','S','M','L','XL','XXL','XXXL','XXXXL'];
            $waists = ['26','28','30','32','34','36','38','40','42','44','46','48','50','52','54','56','58','60'];
            ?>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">T-Shirt Size</label>
                    <select name="tshirt_size" class="form-select">
                        <?php foreach($sizes as $s): ?>
                            <option value="<?= $s ?>" <?= ($data['tshirt_size'] == $s ? 'selected' : '') ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Hoodie Size</label>
                    <select name="hoodie_size" class="form-select">
                        <?php foreach($sizes as $s): ?>
                            <option value="<?= $s ?>" <?= ($data['hoodie_size'] == $s ? 'selected' : '') ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Jacket Size</label>
                    <select name="jacket_size" class="form-select">
                        <?php foreach($sizes as $s): ?>
                            <option value="<?= $s ?>" <?= ($data['jacket_size'] == $s ? 'selected' : '') ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Waist Size</label>
                    <select name="waist_size" class="form-select">
                        <?php foreach($waists as $w): ?>
                            <option value="<?= $w ?>" <?= ($data['waist_size'] == $w ? 'selected' : '') ?>><?= $w ?>"</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <br><br>

            <button type="submit" class="btn btn-success w-100">
                <i class="fas fa-save"></i> Save Changes
            </button>

        </form>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>