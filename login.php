<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'internship_management');

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];

            // Nếu là giảng viên, lấy lecturer_id từ bảng lecturers
            if ($user['role'] === 'lecturer') {
                $stmt2 = $conn->prepare("SELECT lecturer_id FROM lecturers WHERE user_id = ?");
                $stmt2->bind_param("i", $user['user_id']);
                $stmt2->execute();
                $result2 = $stmt2->get_result();

                if ($result2->num_rows === 1) {
                    $lecturer = $result2->fetch_assoc();
                    $_SESSION['lecturer_id'] = $lecturer['lecturer_id'];
                }
                $stmt2->close();
            }

            // Chuyển hướng về index.php
            header("Location: index.php");
            exit();
        } else {
            $error = "Sai mật khẩu!";
        }
    } else {
        $error = "Tài khoản không tồn tại!";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập</title>
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
            max-width: 400px;
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
            background-color: #007bff;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background-color: #0056b3;
        }
        .register-link {
            margin-top: 10px;
            display: block;
            text-decoration: none;
            color: #007bff;
        }
        .register-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>🔑 Đăng nhập</h2>

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

        <button type="submit">🚀 Đăng nhập</button>
    </form>

    <a href="register.php" class="register-link">📌 Chưa có tài khoản? Đăng ký ngay</a>
</div>

</body>
</html>

