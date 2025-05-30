<?php
session_start();
require '../dbconnect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $errors = [];

    if (empty($username)) {
        $errors['username'] = "Paydalanıwshı atı kerek!";
    }

    if (strlen($password) < 8) {
        $errors['password'] = "Parol 8 belgiden kem bolmawi kerek!";
    }

    if (count($errors) === 0) {

        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: /todolist/dashboard.php");
                exit();
            } else {
                $errors['login'] = "Parol qate!";
            }
        } else {
            $errors['login'] = "Paydalanıwshı atı yamasa parol naduris!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="uz">

<head>
    <meta charset="UTF-8">
    <title>Sistemaǵa kiriw</title>
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

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 10px;
            outline: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
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

        .signup {
            text-align: center;
            align-items: center;
        }

        .signup a {
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="form-wrapper">
            <h2>Sistemaǵa kiriw</h2>

           
            <form id="loginForm" action="login.php" method="POST" novalidate>
                <div class="form-group">
                    <label for="username">Paydalanıwshı atı:</label>
                    <input type="text" id="username" name="username" placeholder="Paydalanıwshı atıńızdı kirgiziń"
                        value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>"
                        class="<?php echo (isset($errors['username']) || isset($errors['login'])) ? 'invalid' : ''; ?>">
                    <span class="error <?php echo isset($errors['username']) ? 'show' : ''; ?>">
                        <?php echo $errors['username'] ?? ''; ?>
                    </span>
                    <span class="error" id="usernameError">Paydalanıwshı atı kerek!</span>
                </div>

                <div class="form-group">
                    <label for="password">Parol:</label>
                    <input type="password" id="password" name="password" placeholder="Paroldi kirgiziń"
                        class="<?php echo (isset($errors['password']) || isset($errors['login'])) ? 'invalid' : ''; ?>">
                    <span class="error <?php echo isset($errors['password']) ? 'show' : ''; ?>">
                        <?php echo $errors['password'] ?? ''; ?>
                    </span>
                    <!-- <span class="error" id="passwordError">Parol kerek!</span> -->
                </div>

                <?php if (isset($errors['login'])): ?>
                    <div class="login-error" style="color:red; text-align:center; margin-bottom:15px; font-weight:bold;">
                        <?php echo $errors['login']; ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <input type="submit" value="Kiriw">
                </div>

                <div class="signup">
                    <p>Sizde akkaunt joqpa? <a href="../register/register.php">Dizimnen ótiw</a></p>
                </div>
            </form>
        </div>
    </div>

    <!-- <div class="container">
        <div class="form-wrapper">
            <h2>Sistemaǵa kiriw</h2>
            
            <form id="loginForm" action="login.php" method="POST">
                <div class="form-group">
                    <label for="username">Login:</label>
                    <input type="text" id="username" name="username" placeholder="Paydalanıwshı atıńızdı kirgiziń">
                    <span class="error" id="usernameError">Logindi kiritin!</span>
                </div>

                <div class="form-group">
                    <label for="password">Parol:</label>
                    <input type="password" id="password" name="password" placeholder="Paroldi kirgiziń">
                    <span class="error" id="passwordError">Parol kirgizin!</span>
                </div>

                <div class="form-group">
                    <input type="submit" value="Kiriw">
                </div>

                <div class="signup">
                    <p>Sizde akkaunt joqpa? <a href="../register/register.php">Dizimnen ótiw</a></p>
                </div>
            </form>
        </div>
    </div> -->

    <script>
        document.getElementById("loginForm").addEventListener("submit", function(event) {
            let valid = true;

            // const email = document.getElementById("email");
            const username = document.getElementById("username");
            const password = document.getElementById("password");

            // const emailError = document.getElementById("emailError");
            const usernameError = document.getElementById("usernameError");
            const passwordError = document.getElementById("passwordError");

            // email.classList.remove("invalid");
            username.classList.remove("invalid");
            password.classList.remove("invalid");
            // emailError.classList.remove("show");
            usernameError.classList.remove("show");
            passwordError.classList.remove("show");

            // const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
            // if (!emailPattern.test(email.value)) {
            //     email.classList.add("invalid");
            //     emailError.classList.add("show");
            //     valid = false;
            // }


            if (username.value.trim() === "") {
                username.classList.add("invalid");
                usernameError.classList.add("show");
                valid = false;
            }



            if (password.value.trim() === "") {
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