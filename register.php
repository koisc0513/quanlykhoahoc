<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'internship_management');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $department = trim($_POST['department']);

    // Kiểm tra tài khoản đã tồn tại chưa
    $checkUser = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $checkUser->bind_param("s", $username);
    $checkUser->execute();
    if ($checkUser->get_result()->num_rows > 0) {
        $error = "Tài khoản đã tồn tại!";
    } else {
        // Thêm vào bảng users
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'lecturer')");
        $stmt->bind_param("ss", $username, $password);
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;

            // Thêm vào bảng lecturers
            $stmt2 = $conn->prepare("INSERT INTO lecturers (user_id, first_name, last_name, email, department) VALUES (?, ?, ?, ?, ?)");
            $stmt2->bind_param("issss", $user_id, $first_name, $last_name, $email, $department);
            $stmt2->execute();

            header("Location: login.php");
            exit();
        } else {
            $error = "Lỗi khi đăng ký!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký Giảng viên</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            text-align: center;
        }
        h2 {
            color: #333;
            margin-bottom: 15px;
        }
        .error {
            padding: 10px;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .form-group {
            width: 100%;
            margin-bottom: 15px;
            text-align: left;
        }
        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 5px;
            background-color: #28a745;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background-color: #218838;
        }
        .login-link {
            margin-top: 10px;
            display: block;
            text-decoration: none;
            color: #007bff;
        }
        .login-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>📝 Đăng ký Giảng viên</h2>

    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="post">
        <div class="form-group">
            <label>Tên đăng nhập:</label>
            <input type="text" name="username" required>
        </div>

        <div class="form-group">
            <label>Mật khẩu:</label>
            <input type="password" name="password" required>
        </div>

        <div class="form-group">
            <label>Họ:</label>
            <input type="text" name="first_name" required>
        </div>

        <div class="form-group">
            <label>Tên:</label>
            <input type="text" name="last_name" required>
        </div>

        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label>Khoa/Bộ môn:</label>
            <input type="text" name="department">
        </div>

        <button type="submit">📌 Đăng ký</button>
    </form>

    <a href="login.php" class="login-link">🔑 Đã có tài khoản? Đăng nhập ngay</a>
</div>

</body>
</html>
