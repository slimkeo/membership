<?php
session_start();
require_once 'xyz/config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $cellnumber = trim($_POST['cellnumber'] ?? '');
    $last6 = trim($_POST['last6'] ?? '');

    if (strlen($cellnumber) < 10 || strlen($last6) != 6) {
        $message = "Invalid login details.";
    } else {

        $sql = "SELECT id, idnumber, cellnumber, name, surname
                FROM members
                WHERE cellnumber = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $cellnumber);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {

            $idnumber = $user['idnumber'];
            $id_last6 = substr($idnumber, -6);

            if ($id_last6 === $last6) {

                $_SESSION['id'] = $user['id'];
                $_SESSION['user'] = $user['name'];

                header("Location: dashboard.php");
                exit;

            } else {
                $message = "Incorrect ID number digits.";
            }

        } else {
            $message = "Member not found.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Member Login - SNAT</title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <style>
        body {
            background: #f4f6f9;
        }

        .login-box {
            margin-top: 80px;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .title {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

<div class="container">
    <div class="col-md-4 col-md-offset-4 login-box">

        <h3 class="title">
            <i class="fa fa-user"></i> Member Login
        </h3>

        <?php if ($message): ?>
            <div class="alert alert-danger"><?= $message ?></div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label>Cell Number</label>
                <input type="text" name="cellnumber"
                       class="form-control"
                       placeholder="268XXXXXXXX"
                       required>
            </div>

            <div class="form-group">
                <label>Last 6 Digits of ID Number</label>
                <input type="text" name="last6"
                       class="form-control"
                       maxlength="6"
                       placeholder="******"
                       required>
            </div>

            <button class="btn btn-primary btn-block">
                <i class="fa fa-sign-in"></i> Login
            </button>

        </form>

    </div>
</div>

</body>
</html>