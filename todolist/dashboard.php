<?php
session_start();

// Autentifikaciyanı tekseriw
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: ../login/login.php");
    exit();
}

//Paydalanıwshı maǵlıwmatların alıw.
$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);

// Maǵlıwmatlar bazasına jalǵanıw

require "../dbconnect.php";

// Kategoriyalardı qabıl etiw
$stmt = $conn->prepare("SELECT id, name, color FROM categories WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Tapsırmalardı alıw
$stmt = $conn->prepare("SELECT t.id, t.name, t.time, t.is_completed, c.name AS category_name 
                       FROM tasks t 
                       JOIN categories c ON t.category_id = c.id 
                       WHERE t.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// POST sorawların qayta islew
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $errors = [];

        // Validatsiya
        if (empty($username)) {
            $errors['username'] = 'Paydalanıwshı atı kerek!';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Durıs email kiritiń!';
        }
        if (!empty($password) && strlen($password) < 8) {
            $errors['password'] = 'Parol 8 belgiden kem bolmawi kerek!';
        }

        // Email boshqa foydalanuvchi tomonidan ishlatilganligini tekshirish
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors['email'] = 'Bul email álleqashan dizimnen ótken!';
        }
        $stmt->close();

        if (empty($errors)) {
            if (!empty($password)) {
                // Yangi parolni hash qilish
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                $stmt->bind_param("sssi", $username, $email, $hashedPassword, $user_id);
            } else {
                // Parol kiritilmagan bo‘lsa, faqat username va email yangilanadi
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $stmt->bind_param("ssi", $username, $email, $user_id);
            }
            $success = $stmt->execute();
            if ($success) {
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
            }
            $stmt->close();
            echo json_encode(['success' => $success, 'errors' => []]);
        } else {
            echo json_encode(['success' => false, 'errors' => $errors]);
        }
        exit;
    }

    // Jańa kategoriya qosıw
    if ($action === 'add_category') {
        $name = trim($_POST['name'] ?? '');
        if (!empty($name)) {
            $colors = ["#4CAF50", "#2196F3", "#FF9800", "#9C27B0", "#E91E63", "#C71AC9", "#eb1374"];
            $color = $colors[array_rand($colors)];
            $stmt = $conn->prepare("INSERT INTO categories (user_id, name, color) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $name, $color);
            $success = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $success]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Kategoriya nomi bo‘sh bo‘lmasligi kerak']);
        }
        exit;
    }

    // Kategoriyanı redaktorlaw
    if ($action === 'edit_category') {
        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        if (!empty($id) && !empty($name)) {
            $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sii", $name, $id, $user_id);
            $success = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $success]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Ma’lumotlar to‘liq emas']);
        }
        exit;
    }

    // Kategoriyanı óshiriw
    if ($action === 'delete_category') {
        $id = $_POST['id'] ?? '';
        if (!empty($id)) {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $id, $user_id);
            $success = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $success]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Kategoriya ID topilmadi']);
        }
        exit;
    }

    // Jańa wazıypa qosıw
    if ($action === 'add_task') {
        $name = trim($_POST['name'] ?? '');
        $category_id = $_POST['category_id'] ?? '';
        $time = $_POST['time'] ?? '';
        if (!empty($name) && !empty($category_id) && !empty($time)) {
            $stmt = $conn->prepare("INSERT INTO tasks (user_id, category_id, name, time) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $user_id, $category_id, $name, $time);
            $success = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $success]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Barcha maydonlar to‘ldirilishi kerak']);
        }
        exit;
    }

    // Tapsırmanı redaktorlaw
    if ($action === 'edit_task') {
        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $time = $_POST['time'] ?? '';
        if (!empty($id) && !empty($name) && !empty($time)) {
            $stmt = $conn->prepare("UPDATE tasks SET name = ?, time = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ssii", $name, $time, $id, $user_id);
            $success = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $success]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Ma’lumotlar to‘liq emas']);
        }
        exit;
    }

    // Tapsırmanı óshiriw
    if ($action === 'delete_task') {
        $id = $_POST['id'] ?? '';
        if (!empty($id)) {
            $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $id, $user_id);
            $success = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $success]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Vazifa ID topilmadi']);
        }
        exit;
    }

    // Tapsırma jaǵdayın ózgertiw
    if ($action === 'toggle_task') {
        $id = $_POST['id'] ?? '';
        $is_completed = filter_var($_POST['isCompleted'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        if (!empty($id)) {
            $stmt = $conn->prepare("UPDATE tasks SET is_completed = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("iii", $is_completed, $id, $user_id);
            $success = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $success]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Vazifa ID topilmadi']);
        }
        exit;
    }

    // shıǵıw
    if ($action === 'logout') {
        session_destroy();
        echo json_encode(['success' => true]);
        exit;
    }
    // Tapsırmalardı filtrlew hám izlew
    if ($action === 'filter_tasks') {
        $category_id = $_POST['category_id'] ?? '';
        $status = $_POST['status'] ?? '';
        $search = "%" . trim($_POST['search'] ?? '') . "%";
        $query = "SELECT t.id, t.name, t.time, t.is_completed, c.name AS category_name 
                  FROM tasks t 
                  JOIN categories c ON t.category_id = c.id 
                  WHERE t.user_id = ? AND t.name LIKE ?";
        $params = [$user_id, $search];
        $types = "is";

        if (!empty($category_id)) {
            $query .= " AND t.category_id = ?";
            $params[] = $category_id;
            $types .= "i";
        }
        if ($status !== '') {
            $query .= " AND t.is_completed = ?";
            $params[] = $status === 'completed' ? 1 : 0;
            $types .= "i";
        }

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode(['success' => true, 'tasks' => $tasks]);
        exit;
    }
    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $stmt->execute(); // Qayta so‘rov
        $user = $stmt->get_result()->fetch_assoc();
        $_SESSION['email'] = $user['email']; // Email qo‘shish
        header("Location: /todolist/dashboard.php");
        exit();
    }
}


?>

<!DOCTYPE html>
<html lang="uz">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tapsırmalar basqarıwshısı</title>
    
    <style>
        /* Ulıwma stiller */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            display: flex;
            height: 100vh;
            background: #f4f7f6;
            background-image: url('./Majestic\ Mountain\ Range\ at\ Sunrise_Sunset.jpeg');
            background-size: cover;
            background-position: center;
            backdrop-filter: blur(4px);
        }

        /*Sidebar stilleri */
        .sidebar {
            width: 280px;
            background-color: rgba(255, 255, 255, 0.5);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 50px 20px;
            border-right: 2px solid #3388ff;
            border-radius: 15px;
            backdrop-filter: blur(4px);
            justify-content: space-between;
            height: 100%;
            overflow: auto;
        }

        .logout {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: auto;
            padding: 10px;
            width: 100%;
        }

        .logout-btn {
            background-color: #dc3545;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }

        .sidebar input {
            border-radius: 15px;
            padding: 14px;
            font-size: 16px;
            background-color: #ffffff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            margin-bottom: 20px;
            border: none;
        }

        .sidebar input::placeholder {
            color: #888;
        }

        .sidebar input:focus {
            outline: none;
            border: 2px solid #3366ff;
        }

        .sidebar button {
            padding: 12px 20px;
            background-color: #3366ff;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
            text-transform: uppercase;
        }

        .sidebar button:hover {
            background-color: #2a4da1;
        }

        /*Tiykarǵı kontent stilleri*/
        .main {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
        }

        .main h2 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #d4fcdc;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .greeting {
            font-size: 18px;
            color: #ffffff;
            font-weight: normal;
        }

        /*Tapsırma forması stilleri */
        .task-form {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 10px;
            border-radius: 8px;
            display: flex;
            gap: 8px;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
            /* transition: transform 0.3s ease; */
        }

        /* .task-form :hover {
            transform: scale(1.05);
        } */

        .task-form input,
        .task-form select {
            padding: 6px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 13px;
            flex: 1;
            min-width: 80px;
        }

        .task-form input:focus,
        .task-form select:focus {
            outline: none;
            border: 1px solid #3366ff;
            box-shadow: 0 0 5px rgba(51, 102, 255, 0.3);
        }

        .task-form button {
            background-color: #3366ff;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: background-color 0.3s;
            transition: transform 0.3s ease;
        }

        .task-form button:hover {
            transform: scale(1.05);
            background-color: #2a4da1;
        }

        @media screen and (max-width: 768px) {
            .task-form {
                flex-direction: column;
                align-items: stretch;
            }

            .task-form input,
            .task-form select {
                min-width: 100%;
            }
        }

        .task-form {
            background-color: #d4fcdc;
            padding: 20px;
            border-radius: 15px;
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }

        .task-form input,
        .task-form select {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            border: none;
            font-size: 16px;
        }

        .task-form input:focus,
        .task-form select:focus {
            outline: none;
            border: 2px solid #3366ff;
        }

        .task-form button {
            background-color: #3b1ae0;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .task-form button:hover {
            background-color: #553c94;

        }

        /* Tapsırmalar dizimi stilleri */
        .task-list .task {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #ffffff;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .task-list .task:hover {
            transform: scale(1.05);
        }

        .task-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .task input[type="checkbox"] {
            width: 20px;
            height: 20px;
        }

        .task .task-name {
            font-weight: bold;
            font-size: 16px;
        }

        .task .category-badge {
            background-color: #43d47f;
            color: white;
            padding: 3px 8px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: bold;
        }

        .task.completed .task-name {
            text-decoration: line-through;
            opacity: 0.6;
            color: grey;
        }

        .task.completed .category-badge {
            background-color: #f2d544;
            color: black;
        }

        .task .task-time {
            background-color: #ff00cc;
            color: white;
            padding: 3px 8px;
            border-radius: 8px;
            font-size: 13px;
        }

        .task.completed .task-time {
            color: grey;
        }

        .task .actions button {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            margin-left: 8px;
        }

        .task .actions .edit {
            color: #e83e8c;
        }

        .task .actions .delete {
            color: #dc3545;
        }

        /* Kategoriya diziminiń stilleri */
        .category-list {
            margin-top: 10px;
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .category-item {
            margin: 10px 0;
            padding: 10px;
            border-radius: 9px;
            color: white;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.3s ease;
        }

        .category-item:hover {
            transform: scale(1.05);
            opacity: 0.8;
        }

        .edit-btn,
        .delete-btn {
            background-color: transparent;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 20px;
        }

        .edit-btn:hover,
        .delete-btn:hover {
            color: #13c60c;
        }

        /* Modal aynalar stilleri */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 16px;
            max-width: 400px;
            margin: auto;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .modal-content h3 {
            margin-top: 0;
            font-size: 24px;
        }

        .modal-content input,
        .modal-content select {
            width: 100%;
            margin-bottom: 12px;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 16px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .modal-content input:focus,
        .modal-content select:focus {
            outline: none;
            border: 2px solid #3366ff;
        }

        .modal-buttons {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: 10px;
        }

        .modal-buttons button {
            padding: 10px 16px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
        }

        .modal .close {
            position: absolute;
            top: 12px;
            right: 16px;
            font-size: 24px;
            cursor: pointer;
        }

        /* Túymeler stilleri */
        #saveCategoryBtn {
            background-color: #3388ff;
            color: white;
        }

        #cancelCategory {
            background-color: #e74c3c;
            color: white;
        }

        #saveTaskBtn {
            background-color: #0519ff;
            color: white;
        }

        #cancelTaskBtn {
            background-color: #dc3545;
            color: white;
        }

        #saveEditCategory {
            background-color: #3366ff;
            color: white;
        }

        #cancelEditCategory {
            background-color: #e74c3c;
            color: white;
        }

        #confirmYes {
            background-color: #3366ff;
            color: white;
        }

        #confirmNo {
            background-color: #c01212;
            color: white;
        }

        #saveEditedTask {
            background-color: #3366ff;
            color: white;
        }

        #cancelEditTask {
            background-color: #e74c3c;
            color: white;
        }

        #logoutConfirmBtn {
            background: #e74c3c;
            color: white;
        }

        #logoutCancelBtn {
            background: #ccc;
            color: white;
        }

        /* Qáte xabarları stilleri */
        .error-message {
            color: red;
            font-size: 12px;
            margin-top: 4px;
            display: none;
        }

        .input-error {
            border: 1px solid red;
        }

        /* Tapsırma animaciyaları */
        .task-list {
            opacity: 1;
            transform: translateY(0);
            transition: opacity 0.5s ease, transform 0.5s ease;
        }

        .task.fade-out {
            opacity: 0;
            transform: translateY(30px) rotate(15deg);
        }

        /* Beyimlesiwsheń dizayn*/
        @media screen and (max-width: 768px) {
            .sidebar {
                width: 250px;
                padding: 30px 15px;
            }

            .task-form {
                flex-direction: column;
            }

            .task-list .task {
                flex-direction: column;
                align-items: flex-start;
            }

            .task-list .task .task-time {
                margin-top: 5px;
            }
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            color: #333;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 16px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .form-group input:focus {
            outline: none;
            border: 2px solid #3366ff;
        }

        #saveProfileBtn {
            background-color: #3366ff;
            color: white;
        }

        #cancelProfileBtn {
            background-color: #e74c3c;
            color: white;
        }

        #profileBtn {
            background-color: #3366ff;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 10px;
            width: 100%;
            margin-bottom: 20px;
            font-family: 'Arial Rounded MT Bold', Arial, sans-serif;
            font-size: 16px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        #profileBtn:hover {
            background: linear-gradient(90deg, #D45C5C, #D45CBA);
            box-shadow: 0 0 15px rgba(51, 102, 255, 0.5);
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            #profileBtn {
                font-size: 14px;
                padding: 10px;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar bólimi-->
    <div class="sidebar">
        <button id="profileBtn" style="background-color: #3366ff; color: white; padding: 12px; border-radius: 10px; border: none; width: 100%; margin-bottom: 20px; font-family: 'Arial Rounded MT Bold', sans-serif; cursor: pointer; text-transform: uppercase;">Profil</button>
        <button id="addCategoryBtn">+ Jańa kategoriya</button>
        <div id="categoryList" class="category-list">
            <?php foreach ($categories as $cat): ?>
                <div class="category-item" style="background-color: <?= htmlspecialchars($cat['color']) ?>" data-id="<?= $cat['id'] ?>">
                    <span><?= htmlspecialchars($cat['name']) ?></span>
                    <div>
                        <button class="edit-btn" onclick="openEditCategoryModal('<?= $cat['id'] ?>')">✏️</button>
                        <button class="delete-btn" onclick="openDeleteCategoryModal('<?= $cat['id'] ?>')">🗑️</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="logout">
            <button class="logout-btn">Shıǵıw</button>
        </div>
    </div>

    <!--Tiykarǵı kontent bólimi -->
    <div class="main">
        <h2>Barlıq wazıypalar <span class="greeting">Salem, <?= $username ?>!</span></h2>
        <div class="task-form">
            <button id="addTaskBtn">+ Jańa wazıypa</button>
            <input type="text" id="searchInput" placeholder="Izlew..." />
            <select id="filterCategorySelect">
                <option value="">Kategoriya</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="filterStatusSelect">
                <option value="">Jaǵday</option>
                <option value="completed">Orınlanǵan</option>
                <option value="pending">Orınlanbaǵan</option>
            </select>
        </div>
        <div class="task-list" id="taskList">
            <?php foreach ($tasks as $task): ?>
                <div class="task <?= $task['is_completed'] ? 'completed' : '' ?>" data-id="<?= $task['id'] ?>">
                    <div class="task-left">
                        <input type="checkbox" <?= $task['is_completed'] ? 'checked' : '' ?> onchange="toggleTaskStatus(this)" />
                        <span class="task-name"><?= htmlspecialchars($task['name']) ?></span>
                        <span class="category-badge"><?= htmlspecialchars($task['category_name']) ?></span>
                    </div>
                    <div class="task-right">
                        <span class="task-time"><?= htmlspecialchars($task['time']) ?></span>
                        <span class="actions">
                            <button class="edit" onclick="editTask('<?= $task['id'] ?>')">✏️</button>
                            <button class="delete" onclick="deleteTask('<?= $task['id'] ?>')">🗑️</button>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Kategoriya qosıw modalı-->
    <div class="modal" id="categoryModal">
        <div class="modal-content">
            <h3>Jańa Kategoriya qosıw</h3>
            <input type="text" id="categoryName" placeholder="Kategoriya atı" />
            <small id="categoryError" class="error-message"></small>
            <div class="modal-buttons">
                <button id="cancelCategory">Bekar etiw</button>
                <button id="saveCategoryBtn">Saqlaw</button>
            </div>
        </div>
    </div>

    <!-- Tapsırma qosıw modalı-->
    <div id="taskModal" class="modal">
        <div class="modal-content">
            <h3>Jańa wazıypa qosıw</h3>
            <input type="text" id="modalTaskName" placeholder="Tapsırma atı" />
            <small class="error-message" id="errorTaskName"></small>
            <select id="modalTaskCategory">
                <option value="">Kategoriya tanlań</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <small class="error-message" id="errorTaskCategory"></small>
            <input type="datetime-local" id="modalTaskTime" />
            <small class="error-message" id="errorTaskTime"></small>
            <div class="modal-buttons">
                <button id="cancelTaskBtn">Bekar etiw</button>
                <button id="saveTaskBtn">Saqlaw</button>
            </div>
        </div>
    </div>

    <!-- Kategoriyanı redaktorlaw modalı -->
    <div id="editCategoryModal" class="modal">
        <div class="modal-content">
            <h3>Kategoriyanı redaktorlaw</h3>
            <input type="text" id="editCategoryName" placeholder="Jana kategoriya atamasın kirgiziń." />
            <small id="editCategoryError" class="error-message"></small>
            <div class="modal-buttons">
                <button id="cancelEditCategory">Bekar etiw</button>
                <button id="saveEditCategory">Saqlaw</button>
            </div>
        </div>
    </div>

    <!-- Kategoriyanı óshiriw modalı -->
    <div class="modal" id="deleteCategoriyModal">
        <div class="modal-content">
            <h3>Kategoriyanı óshiriwdi qáleysiz be?</h3>
            <div class="modal-buttons">
                <button id="confirmNo">Joq</button>
                <button id="confirmYes">Awa</button>
            </div>
        </div>
    </div>

    <!-- Tapsırma redaktorlaw modalı-->
    <div id="editTaskModal" class="modal">
        <div class="modal-content">
            <h3>Tapsırmanı redaktorlaw</h3>
            <input type="text" id="editTaskTitle" placeholder="Tapsırma atı" />
            <small id="editTaskError" class="error-message"></small>
            <input type="datetime-local" id="editTaskDateTime" />
            <div class="modal-buttons">
                <button id="cancelEditTask">Bekar etiw</button>
                <button id="saveEditedTask">Saqlaw</button>
            </div>
        </div>
    </div>

    <!-- Shıǵıw tastıyıqlaw modalı-->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <h3>Esap betinen shıqqıńız kele me?</h3>
            <div class="modal-buttons">
                <button id="logoutCancelBtn">Joq</button>
                <button id="logoutConfirmBtn">Awa</button>
            </div>
        </div>
    </div>

    <div id="profileModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('profileModal')">×</span>
            <h3>Profildi sazlaw</h3>
            <div class="form-group">
                <label for="profileUsername">Paydalanıwshı atı:</label>
                <input type="text" id="profileUsername" placeholder="Paydalanıwshı atı" value="<?php echo htmlspecialchars($username); ?>" style="font-family: 'Arial Rounded MT Bold', sans-serif;" />
                <small id="profileUsernameError" class="error-message"></small>
            </div>
            <div class="form-group">
                <label for="profileEmail">Email:</label>
                <input type="email" id="profileEmail" placeholder="Email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" style="font-family: 'Arial Rounded MT Bold', sans-serif;" />
                <small id="profileEmailError" class="error-message"></small>
            </div>
            <div class="form-group">
                <label for="profilePassword">Jańa parol (ıqtiyarıy):</label>
                <input type="password" id="profilePassword" placeholder="Jana paroldı kirgiziń" style="font-family: 'Arial Rounded MT Bold', sans-serif;" />
                <small id="profilePasswordError" class="error-message"></small>
            </div>
            <div class="modal-buttons">
                <button id="cancelProfileBtn">Bekar etiw</button>
                <button id="saveProfileBtn">Saqlaw</button>
            </div>
        </div>
    </div>

    <script>
        // Modal aynalardı basqarıw ushın járdemshi funkciyalar
        function showModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function hideModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Kategoriya qosıw modalı
        document.getElementById('addCategoryBtn').addEventListener('click', () => {
            showModal('categoryModal');
        });

        document.getElementById('cancelCategory').addEventListener('click', () => {
            hideModal('categoryModal');
            document.getElementById('categoryName').value = '';
            document.getElementById('categoryError').style.display = 'none';
        });

        document.getElementById('saveCategoryBtn').addEventListener('click', () => {
            const name = document.getElementById('categoryName').value.trim();
            const error = document.getElementById('categoryError');

            if (!name) {
                error.textContent = 'Kategoriya atın kirgiziń!';
                error.style.display = 'block';
                document.getElementById('categoryName').classList.add('input-error');
                return;
            }

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=add_category&name=${encodeURIComponent(name)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        error.textContent = data.error;
                        error.style.display = 'block';
                    }
                });
        });

        document.getElementById('categoryName').addEventListener('input', () => {
            const error = document.getElementById('categoryError');
            if (document.getElementById('categoryName').value.trim()) {
                error.style.display = 'none';
                document.getElementById('categoryName').classList.remove('input-error');
            }
        });

        // Kategoriyanı redaktorlaw modalı
        let currentEditCategoryId = null;

        function openEditCategoryModal(id) {
            currentEditCategoryId = id;
            const category = document.querySelector(`.category-item[data-id="${id}"] span`).textContent;
            document.getElementById('editCategoryName').value = category;
            showModal('editCategoryModal');
        }

        document.getElementById('cancelEditCategory').addEventListener('click', () => {
            hideModal('editCategoryModal');
        });

        document.getElementById('saveEditCategory').addEventListener('click', () => {
            const name = document.getElementById('editCategoryName').value.trim();
            const error = document.getElementById('editCategoryError');

            if (!name) {
                error.textContent = 'Kategoriya atın kirgiziń!';
                error.style.display = 'block';
                document.getElementById('editCategoryName').classList.add('input-error');
                return;
            }

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=edit_category&id=${encodeURIComponent(currentEditCategoryId)}&name=${encodeURIComponent(name)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        error.textContent = data.error;
                        error.style.display = 'block';
                    }
                });
        });

        // Kategoriyanı óshiriw modalı
        let currentDeleteCategoryId = null;

        function openDeleteCategoryModal(id) {
            currentDeleteCategoryId = id;
            showModal('deleteCategoriyModal');
        }

        document.getElementById('confirmNo').addEventListener('click', () => {
            hideModal('deleteCategoriyModal');
        });

        document.getElementById('confirmYes').addEventListener('click', () => {
            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=delete_category&id=${encodeURIComponent(currentDeleteCategoryId)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
        });

        // Tapsırma qosıw modalı
        document.getElementById('addTaskBtn').addEventListener('click', () => {
            showModal('taskModal');
        });

        document.getElementById('cancelTaskBtn').addEventListener('click', () => {
            hideModal('taskModal');
            document.getElementById('modalTaskName').value = '';
            document.getElementById('modalTaskCategory').value = '';
            document.getElementById('modalTaskTime').value = '';
            document.querySelectorAll('.error-message').forEach(e => e.style.display = 'none');
        });

        document.getElementById('saveTaskBtn').addEventListener('click', () => {
            const name = document.getElementById('modalTaskName').value.trim();
            const category_id = document.getElementById('modalTaskCategory').value;
            const time = document.getElementById('modalTaskTime').value;

            let isValid = true;
            document.querySelectorAll('.error-message').forEach(e => e.style.display = 'none');
            document.getElementById('modalTaskName').classList.remove('input-error');
            document.getElementById('modalTaskCategory').classList.remove('input-error');
            document.getElementById('modalTaskTime').classList.remove('input-error');

            if (!name) {
                document.getElementById('errorTaskName').textContent = 'Tapsırmanıń atın kirgiziń!';
                document.getElementById('errorTaskName').style.display = 'block';
                document.getElementById('modalTaskName').classList.add('input-error');
                isValid = false;
            }

            if (!category_id) {
                document.getElementById('errorTaskCategory').textContent = 'Kategoriya tańlań!';
                document.getElementById('errorTaskCategory').style.display = 'block';
                document.getElementById('modalTaskCategory').classList.add('input-error');
                isValid = false;
            }

            if (!time) {
                document.getElementById('errorTaskTime').textContent = 'Waqıttı tańlań!';
                document.getElementById('errorTaskTime').style.display = 'block';
                document.getElementById('modalTaskTime').classList.add('input-error');
                isValid = false;
            }

            if (!isValid) return;

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=add_task&name=${encodeURIComponent(name)}&category_id=${encodeURIComponent(category_id)}&time=${encodeURIComponent(time)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        document.getElementById('errorTaskName').textContent = data.error;
                        document.getElementById('errorTaskName').style.display = 'block';
                    }
                });
        });

        // Tapsırma qátelerin tazalaw
        document.getElementById('modalTaskName').addEventListener('input', () => {
            if (document.getElementById('modalTaskName').value.trim()) {
                document.getElementById('errorTaskName').style.display = 'none';
                document.getElementById('modalTaskName').classList.remove('input-error');
            }
        });

        document.getElementById('modalTaskCategory').addEventListener('change', () => {
            if (document.getElementById('modalTaskCategory').value) {
                document.getElementById('errorTaskCategory').style.display = 'none';
                document.getElementById('modalTaskCategory').classList.remove('input-error');
            }
        });

        document.getElementById('modalTaskTime').addEventListener('input', () => {
            if (document.getElementById('modalTaskTime').value) {
                document.getElementById('errorTaskTime').style.display = 'none';
                document.getElementById('modalTaskTime').classList.remove('input-error');
            }
        });

        // Tapsırmanı redaktorlaw
        function editTask(id) {
            const task = document.querySelector(`.task[data-id="${id}"]`);
            document.getElementById('editTaskTitle').value = task.querySelector('.task-name').textContent;
            document.getElementById('editTaskDateTime').value = task.querySelector('.task-time').textContent;
            showModal('editTaskModal');
            document.getElementById('saveEditedTask').onclick = () => {
                const name = document.getElementById('editTaskTitle').value.trim();
                const time = document.getElementById('editTaskDateTime').value;
                if (name && time) {
                    fetch('', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `action=edit_task&id=${encodeURIComponent(id)}&name=${encodeURIComponent(name)}&time=${encodeURIComponent(time)}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            }
                        });
                }
            };
        }

        document.getElementById('cancelEditTask').addEventListener('click', () => {
            hideModal('editTaskModal');
        });

        // Tapsırmanı óshiriw
        function deleteTask(id) {
            const task = document.querySelector(`.task[data-id="${id}"]`);
            task.classList.add('fade-out');
            setTimeout(() => {
                fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=delete_task&id=${encodeURIComponent(id)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        }
                    });
            }, 500);
        }

        // Tapsırma jaǵdayın ózgertiw
        function toggleTaskStatus(checkbox) {
            const task = checkbox.closest('.task');
            const id = task.dataset.id;
            task.classList.toggle('completed');
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=toggle_task&id=${encodeURIComponent(id)}&isCompleted=${checkbox.checked}`
            });
        }

        // Shıǵıw modalı
        document.querySelector('.logout-btn').addEventListener('click', () => {
            showModal('logoutModal');
        });

        document.getElementById('logoutCancelBtn').addEventListener('click', () => {
            hideModal('logoutModal');
        });

        document.getElementById('logoutConfirmBtn').addEventListener('click', () => {
            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=logout`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '../login/login.php';
                    }
                });
        });
        // Filtr funkciyası
        function applyFilter() {
            const search = document.getElementById('searchInput').value.trim();
            const category_id = document.getElementById('filterCategorySelect').value;
            const status = document.getElementById('filterStatusSelect').value;
            let body = `action=filter_tasks&category_id=${encodeURIComponent(category_id)}&status=${encodeURIComponent(status)}`;
            if (search) {
                body += `&search=${encodeURIComponent(search)}`;
            }
            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: body
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderTasks(data.tasks);
                    } else {
                        console.error('Filtr xatosi:', data);
                    }
                });
        }

        // Izlew funkciyası
        document.getElementById('searchInput').addEventListener('input', applyFilter);

        // Filtr select ózgerisleri
        document.getElementById('filterCategorySelect').addEventListener('change', applyFilter);
        document.getElementById('filterStatusSelect').addEventListener('change', applyFilter);

        // Tapsırmalardı renderlew funkciyası
        function renderTasks(tasks) {
            const taskList = document.getElementById('taskList');
            taskList.innerHTML = '';
            tasks.forEach(task => {
                const taskDiv = document.createElement('div');
                taskDiv.className = `task ${task.is_completed ? 'completed' : ''}`;
                taskDiv.dataset.id = task.id;
                taskDiv.innerHTML = `
            <div class="task-left">
                <input type="checkbox" ${task.is_completed ? 'checked' : ''} onchange="toggleTaskStatus(this)" />
                <span class="task-name">${task.name}</span>
                <span class="category-badge">${task.category_name}</span>
            </div>
            <div class="task-right">
                <span class="task-time">${task.time}</span>
                <span class="actions">
                    <button class="edit" onclick="editTask('${task.id}')">✏️</button>
                    <button class="delete" onclick="deleteTask('${task.id}')">🗑️</button>
                </span>
            </div>
        `;
                taskList.appendChild(taskDiv);
            });
        }
        document.getElementById('profileBtn').addEventListener('click', () => {
            showModal('profileModal');
            document.getElementById('profileUsernameError').style.display = 'none';
            document.getElementById('profileEmailError').style.display = 'none';
            document.getElementById('profilePasswordError').style.display = 'none';
            document.getElementById('profileUsername').classList.remove('input-error');
            document.getElementById('profileEmail').classList.remove('input-error');
            document.getElementById('profilePassword').classList.remove('input-error');
            document.getElementById('profilePassword').value = ''; // Parol maydonini tozalash
        });

        document.getElementById('cancelProfileBtn').addEventListener('click', () => {
            hideModal('profileModal');
            document.getElementById('profileUsername').value = '<?php echo htmlspecialchars($username); ?>';
            document.getElementById('profileEmail').value = '<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>';
            document.getElementById('profilePassword').value = '';
        });

        document.getElementById('saveProfileBtn').addEventListener('click', () => {
            const username = document.getElementById('profileUsername').value.trim();
            const email = document.getElementById('profileEmail').value.trim();
            const password = document.getElementById('profilePassword').value.trim();
            const usernameError = document.getElementById('profileUsernameError');
            const emailError = document.getElementById('profileEmailError');
            const passwordError = document.getElementById('profilePasswordError');
            let valid = true;

            // Frontend validatsiyasi
            if (!username) {
                usernameError.textContent = 'Paydalanıwshı atı kerek!';
                usernameError.style.display = 'block';
                document.getElementById('profileUsername').classList.add('input-error');
                valid = false;
            } else {
                usernameError.style.display = 'none';
                document.getElementById('profileUsername').classList.remove('input-error');
            }

            const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
            if (!emailPattern.test(email)) {
                emailError.textContent = 'Durıs email kiritiń!';
                emailError.style.display = 'block';
                document.getElementById('profileEmail').classList.add('input-error');
                valid = false;
            } else {
                emailError.style.display = 'none';
                document.getElementById('profileEmail').classList.remove('input-error');
            }

            if (password && password.length < 8) {
                passwordError.textContent = 'Parol 8 belgiden kem bolmawi kerek!';
                passwordError.style.display = 'block';
                document.getElementById('profilePassword').classList.add('input-error');
                valid = false;
            } else {
                passwordError.style.display = 'none';
                document.getElementById('profilePassword').classList.remove('input-error');
            }

            if (!valid) return;

            // Serverga so‘rov
            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=update_profile&username=${encodeURIComponent(username)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        if (data.errors.username) {
                            usernameError.textContent = data.errors.username;
                            usernameError.style.display = 'block';
                            document.getElementById('profileUsername').classList.add('input-error');
                        }
                        if (data.errors.email) {
                            emailError.textContent = data.errors.email;
                            emailError.style.display = 'block';
                            document.getElementById('profileEmail').classList.add('input-error');
                        }
                        if (data.errors.password) {
                            passwordError.textContent = data.errors.password;
                            passwordError.style.display = 'block';
                            document.getElementById('profilePassword').classList.add('input-error');
                        }
                    }
                });
        });
    </script>
</body>

</html>