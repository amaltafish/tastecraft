<?php
session_start();

$con = new mysqli("localhost", "root", "", "tastecraft");

if (!$con) {
    die("Database connection failed: " . mysqli_error($con));
}

if (isset($_POST['bt']) && $_POST['Email'] != null && $_POST['password'] != null) {
    $Email = $_POST['Email'];
    $pass = $_POST['password'];
    
    // 🔒 תיקון: prepared statement
    $sql = "SELECT id, Fname, Email, Password, flag FROM users WHERE Email = ? AND Password = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ss", $Email, $pass);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['id'] = $row['id'];
        $_SESSION['Fname'] = $row['Fname'];
        $_SESSION['flag'] = $row['flag'];

        if ($row['flag'] == 1) {
            header('Location: admin.php');
            exit();
        } else {
            header('Location: home2.php');
            exit();
        }
    } else {
        echo "<script>alert('Invalid email or password');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - TasteCraft</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url('login.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: fixed;
            margin: 0;
            padding: 0;
        }

        .content {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        form {
            background-color: rgba(0, 0, 0, 0.5);
            padding: 2em;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            font-size: 18px;
            color: white;
            width: 320px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        input {
            margin-bottom: 1em;
            padding: 0.5em;
            width: 90%;
            box-sizing: border-box;
            border-radius: 10px;
            border: none;
            font-size: 16px;
        }

        input:focus {
            background-color: #ebe8e8;
            outline: none;
            border: solid #f5aa5b 2px;
        }

        .btn {
            padding: 12px 25px;
            margin: 10px 0;
            width: 100%;
            border-radius: 12px;
            font-size: 16px;
            font-weight: bold;
            border: 2px solid white;
            background-color: transparent;
            color: white;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: scale(1.05);
        }

        .title {
            font-size: 26px;
            margin-bottom: 20px;
            background-color: black;
            padding: 8px 20px;
            border-radius: 10px;
            display: inline-block;
        }

        .ques {
            margin-top: 10px;
            font-size: 14px;
            color: #f5aa5b;
        }

        button:disabled {
            background-color: grey;
            cursor: not-allowed;
            border-color: grey;
        }
    </style>
</head>
<body>
    <div class="content">
        <form method="post">
            <div class="title">Login</div><br><br>
            <label for="Email">Email</label><br>
            <input type="email" name="Email" id="Email" required placeholder="Enter your email" /><br>
            <label for="password">Password</label><br>
            <input type="password" name="password" id="password" required placeholder="Enter your password" /><br>
            <button class="btn" type="submit" name="bt" id="loginBtn" disabled>Login</button><br>
            <div class="ques">Don't have an account?</div>
            <button class="btn" type="button" onclick="window.location.href='signup.php'">Signup</button><br>
            <button class="btn" type="button" onclick="window.location.href='home.php'">Back to Home</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const emailField = document.getElementById('Email');
            const passwordField = document.getElementById('password');
            const loginBtn = document.getElementById('loginBtn');

            function checkFields() {
                if (emailField.value.trim() !== '' && passwordField.value.trim() !== '') {
                    loginBtn.disabled = false;
                } else {
                    loginBtn.disabled = true;
                }
            }

            emailField.addEventListener('input', checkFields);
            passwordField.addEventListener('input', checkFields);
        });
    </script>
</body>
</html>