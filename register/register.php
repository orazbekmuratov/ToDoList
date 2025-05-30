<?php
require 'dbconnect.php'; // $conn bu yerda

// Dáslepki mánisler hám qáteler ushın ózgeriwshiler
$username = $email = $password = "";
$errors = [];
$emailExistsError = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Formanı validlew
    if (empty($username)) {
        $errors['username'] = "Paydalanıwshı atı kerek!";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Durıs email kiritiń!";
    }
    if (strlen($password) < 8) {
        $errors['password'] = "Parol 8 belgiden kem bolmawi kerek!";
    }

    // Elektron pochta bazasında barma tekseriw
    if (!isset($errors['email'])) { // tek ǵana elektron pochta formatı durıs bolsa tekseriw
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $emailExistsError = "Bul elektron pochta álleqashan dizimnen ótken!";
        }
        $stmt->close();
    }

    // Eger qáteler joq bolsa hám elektron pochta tákirarlanbasa
    if (empty($errors) && $emailExistsError === "") {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $username, $email, $hashedPassword);

        if ($stmt->execute()) {
            header("Location: ../login/login.php");
            exit();
        } else {
            echo "<p style='color:red;'>Xatolik yuz berdi: " . $stmt->error . "</p>";
        }
    }
}

  ?>





<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dizimnen ótiw</title>
   <style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Arial', sans-serif;
}

body {
    background: linear-gradient(45deg, #6a11cb, #2575fc);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
    background-image: url('4891584.jpg');
   backdrop-filter: blur(4px);
   background-repeat: repeat;
   background-attachment: scroll;
   background-position: center;
}

.container {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100%;
}

.form-wrapper {
    background: rgba(255, 255, 255, 0.9);
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    width: 350px;
    transform: scale(1);
    transition: transform 0.4s ease-in-out;
}

.form-wrapper:hover {
    transform: scale(1.05);
}

h2 {
    text-align: center;
    color: #333;
    margin-bottom: 20px;
    font-size: 24px;
}

.form-group {
    margin-bottom: 20px;
    position: relative;
}

label {
    display: block;
    font-size: 14px;
    color: #333;
    margin-bottom: 5px;
    transition: color 0.3s ease;
}

input[type="text"], input[type="email"], input[type="password"] {
    width: 100%;
    padding: 12px;
    font-size: 16px;
    border: 1px solid #ccc;
    border-radius: 10px;
    outline: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus {
    border-color: #2575fc;
    box-shadow: 0 0 10px rgba(37, 117, 252, 0.5);
    background-color: #f3f3f3;
}

input[type="submit"] {
    width: 100%;
    padding: 12px;
    font-size: 18px;
    background-color: #2575fc;
    border: none;
    color: white;
    border-radius: 10px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

input[type="submit"]:hover {
    background-color: #6a11cb;
}

.error {
    position: absolute;
    bottom: -20px;
    left: 0;
    font-size: 12px;
    color: red;
    display: none;
    font-weight: bold;
}

.error.show {
    display: block;
}

input.invalid {
    border-color: red;
    background-color: #f8d7da;
}

@media (max-width: 768px) {
    .form-wrapper {
        width: 90%;
    }
}
.signup{
  text-align: center;
  align-items: center;
}
.signup  a {
  text-decoration: none;
}


   </style>
</head>
<body>

<div class="container">
    <div class="form-wrapper">
        <h2>Dizimnen ótiw</h2>
        <form id="registerForm" action="register.php" method="POST">
            <div class="form-group">
                <label for="username">Paydalanıwshı atı:</label>
                <input type="text" id="username" name="username" placeholder="Paydalanıwshı atın kirgiziń" value="<?= htmlspecialchars($username) ?>  " >
                <span class="error" id="usernameError">Paydalanıwshı atı kerek!</span>
                   <?php if (isset($errors['username'])): ?>
        <span style="color:red;"><?= $errors['username'] ?></span><br>
    <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" placeholder="Email mánzilin kirgiziń"  value="">
                <span class="error" id="emailError">Iltimas, duris elektron pochta kirgiziń!</span>
                <?php if (isset($errors['email'])): ?>
        <span style="color:red;"><?= $errors['email'] ?></span><br>
    <?php endif; ?>
    <?php if ($emailExistsError): ?>
        <span style="color:red;"><?= $emailExistsError ?></span><br>
    <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Parol:</label>
                <input type="password" id="password" name="password" placeholder="Paroldi kirgiziń" >
                <span class="error" id="passwordError">Parol 8 belgiden kem bolmawi kerek!</span>
            </div>

            <div class="form-group">
                <input type="submit" value="Ro'yxatdan O'tish">
            </div>

            <div class="signup">
              <p> Siz dizimnen otkensibe? <a href="../login/login.php">Login</a></p>
            </div>
        </form>
    </div>
</div>

<script>
  document.getElementById("registerForm").addEventListener("submit", function(event) {
    let valid = true;

    // Inputlar
    const username = document.getElementById("username");
    const email = document.getElementById("email");
    const password = document.getElementById("password");

    // Qáteler ushın elementler
    const usernameError = document.getElementById("usernameError");
    const emailError = document.getElementById("emailError");
    const passwordError = document.getElementById("passwordError");

    // Qátelerdi tazalaw
    username.classList.remove("invalid");
    email.classList.remove("invalid");
    password.classList.remove("invalid");
    usernameError.classList.remove("show");
    emailError.classList.remove("show");
    passwordError.classList.remove("show");

    // Paydalanıwshı atı tekseriwi
    if (username.value.trim() === "") {
        username.classList.add("invalid");
        usernameError.classList.add("show");
        valid = false;
    }

    // Elektron pochta tekseriwi
    const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    if (!emailPattern.test(email.value)) {
        email.classList.add("invalid");
        emailError.classList.add("show");
        valid = false;
    }

    // Parol tekseriw
    if (password.value.length < 8) {
        password.classList.add("invalid");
        passwordError.classList.add("show");
        valid = false;
    }

    if (!valid) {
        event.preventDefault();
    }
});

</script>

</body>
</html>
