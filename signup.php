<?php
session_start();

$con = new mysqli("localhost", "root", "", "tastecraft");

if (!$con) {
    die("Database connection failed: " . mysqli_error($con));
}

if (isset($_POST['bt']) && $_POST['id'] != null && $_POST['Fname'] != null && $_POST['Lname'] != null && $_POST['Email'] != null && $_POST['password'] != null) {
    $id = $_POST['id'];
    $Fname = $_POST['Fname'];
    $Lname = $_POST['Lname'];
    $Email = $_POST['Email'];
    
    // ✅ חזרה לסיסמה לא מוצפנת
    $pass = $_POST['password'];
    
    $code = $_POST['code'];

    // 🔒 תיקון: prepared statement
    $sql = "SELECT * FROM users WHERE id = ? OR Email = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("is", $id, $Email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<script>alert('User with the given ID or Email already exists.');</script>";
    } else {
        if ($code == '12345') {
            $flag = 1;
        } else {
            $flag = 0;
        }

        // 🔒 תיקון: prepared statement להכנסה
        $sql = "INSERT INTO users (id, Fname, Lname, Email, Password, flag) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("issssi", $id, $Fname, $Lname, $Email, $pass, $flag);
        
        if ($stmt->execute()) {
            header('Location: login.php');
            exit();
        } else {
            echo $con->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign-up - TasteCraft</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url('home3.jpg');
            background-size: cover;
            background-position: center;
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
            background-color: rgba(0, 0, 0, 0.7);
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
            max-width: 100%;
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
            <div class="title">Sign-up</div><br><br>
            <label for="id">ID</label><br>
            <input type="number" name="id" id="id" required placeholder="Enter your ID" /><br>
            <label for="Fname">First Name</label><br>
            <input type="text" name="Fname" id="Fname" required placeholder="Enter your first name" /><br>
            <label for="Lname">Last Name</label><br>
            <input type="text" name="Lname" id="Lname" required placeholder="Enter your last name" /><br>
            <label for="Email">Email</label><br>
            <input type="email" name="Email" id="Email" required placeholder="Enter your email" /><br>
            <label for="password">Password</label><br>
            <input type="password" name="password" id="password" required placeholder="Enter your password" /><br>
            <label for="code">Admin Code (optional)</label><br>
            <input type="password" name="code" id="code" placeholder="Enter admin code (if applicable)" /><br><br>
            <button class="btn" type="submit" name="bt" id="signupBtn" disabled>Sign Up</button><br><br>
            <div class="ques">Already have an account?</div>
            <button class="btn" type="button" onclick="window.location.href='login.php'">Login</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const idField = document.getElementById('id');
            const FnameField = document.getElementById('Fname');
            const LnameField = document.getElementById('Lname');
            const emailField = document.getElementById('Email');
            const passwordField = document.getElementById('password');
            const signupBtn = document.getElementById('signupBtn');

            function checkFields() {
                if (idField.value.trim() !== '' && FnameField.value.trim() !== '' && LnameField.value.trim() !== '' && emailField.value.trim() !== '' && passwordField.value.trim() !== '') {
                    signupBtn.disabled = false;
                } else {
                    signupBtn.disabled = true;
                }
            }

            idField.addEventListener('input', checkFields);
            FnameField.addEventListener('input', checkFields);
            LnameField.addEventListener('input', checkFields);
            emailField.addEventListener('input', checkFields);
            passwordField.addEventListener('input', checkFields);
        });
    </script>
</body>
</html>