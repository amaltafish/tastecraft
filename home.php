<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome to TasteCraft</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            direction: ltr;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
            background-image: url('home3.jpg'); /* רקע רק על main */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .content {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            flex: 1;
        }

        .right-box {
            width: 40%;
            margin-right: 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: rgba(0, 0, 0, 0.4);
            padding: 40px;
            border-radius: 20px;
            color: white;
        }

        h1 {
            font-size: 42px;
            margin-bottom: 10px;
            text-align: center;
            letter-spacing: 2px;
        }

        p {
            font-size: 18px;
            margin-bottom: 30px;
            text-align: center;
        }

        .btn {
            background-color: transparent;
            border: 2px solid white;
            color: white;
            padding: 14px 40px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
            width: 200px;
        }

        .btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: scale(1.05);
        }

        a {
            text-decoration: none;
        }

        .socials {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 10px;
        }

        .socials img {
            width: 30px;
            height: 30px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .socials img:hover {
            transform: scale(1.2);
        }

        footer {
            text-align: center;
            background-color: rgba(0, 0, 0, 0.85);
            color: white;
            padding: 10px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>

<main>
    <div class="content">
        <div class="right-box">
            <h1>UNLEASH YOUR INNER CHEF</h1>
            <p>through the lens of flavor</p>
            <a href="login.php"><button class="btn">LOGIN</button></a>
            <a href="signup.php"><button class="btn">SIGNUP</button></a>
        </div>
    </div>

    <div class="socials">
        <a href="https://www.instagram.com" target="_blank">
            <img src="InstagramLogo.jpg" alt="Instagram">
        </a>
        <a href="https://www.facebook.com" target="_blank">
            <img src="FacebookLogo2.jpg" alt="Facebook">
        </a>
        <a href="https://www.twitter.com" target="_blank">
            <img src="TwitterLogo.jpg" alt="Twitter">
        </a>
    </div>
</main>

<footer>  
    &copy; 2025 TasteCraft. All rights reserved.
</footer>

</body>
</html>

