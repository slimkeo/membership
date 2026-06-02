<?php
session_start();
require_once 'xyz/config.php';

function normalizeSwaziNumber($number) {

    // remove spaces, plus sign, dashes
    $number = preg_replace('/[^0-9]/', '', $number);

    // if already full format 268XXXXXXXX
    if (strlen($number) === 11 && substr($number, 0, 3) === "268") {
        return $number;
    }

    // if user typed 8 digits (local format)
    if (strlen($number) === 8) {
        return "268" . $number;
    }

    // fallback return original cleaned number
    return $number;
}




$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $cellnumber_raw = trim($_POST['cellnumber'] ?? '');
    $cellnumber = normalizeSwaziNumber($cellnumber_raw);
    $last6 = trim($_POST['last6'] ?? '');

    if (strlen($cellnumber) != 11 || strlen($last6) != 6) {
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
    <title>SNAT Member Login</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #003087, #0055cc);
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-box {
            width: 100%;
            max-width: 420px;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.25);
        }

        .logo {
            text-align: center;
            margin-bottom: 15px;
        }

        .logo img {
            width: 90px;
        }

        .title {
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
            color: #003087;
        }

        .btn-primary {
            background: #003087;
            border: none;
        }

        .btn-primary:hover {
            background: #002266;
        }

        .full-width {
            width: 100%;
        }

        @media (max-width: 768px) {
            body {
                padding: 15px;
            }

            .login-box {
                width: 100%;
                border-radius: 8px;
            }
        }
    </style>
</head>

<body>

<div class="login-box">

    <div class="logo">
        <img src="assets/SNATLOGO.png" alt="SNAT">
    </div>

    <h4 class="title">
        <i class="fa fa-user-circle"></i> Member Login
    </h4>

    <?php if ($message): ?>
        <div class="alert alert-danger text-center">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="form-group">
            <label>Cell Number</label>
            <input type="text"
                   name="cellnumber"
                   class="form-control input-lg"
                   placeholder="268XXXXXXXX"
                   required>
        </div>

        <div class="form-group">
            <label>Last 6 Digits of ID Number</label>
            <input type="text"
                   name="last6"
                   class="form-control input-lg"
                   maxlength="6"
                   placeholder="******"
                   required>
        </div>

        <button class="btn btn-primary btn-lg full-width">
            <i class="fa fa-sign-in"></i> Login
        </button>

    </form>

</div>

</body>
</html>