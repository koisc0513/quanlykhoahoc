<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'internship_management');

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Kiểm tra nếu người dùng đã đăng nhập và có role giảng viên
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lecturer') {
    header("Location: login.php");
    exit();
}

$lecturer_id = $_SESSION['lecturer_id'];
$message = "";

// Lấy thông tin giảng viên
$stmt = $conn->prepare("SELECT first_name, last_name, email, department FROM lecturers WHERE lecturer_id = ?");
$stmt->bind_param("i", $lecturer_id);
$stmt->execute();
$result = $stmt->get_result();
$lecturer = $result->fetch_assoc();
$stmt->close();

// Cập nhật thông tin giảng viên
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $department = trim($_POST['department']);

    if (!empty($first_name) && !empty($last_name) && !empty($email)) {
        $stmt = $conn->prepare("UPDATE lecturers SET first_name = ?, last_name = ?, email = ?, department = ? WHERE lecturer_id = ?");
        $stmt->bind_param("ssssi", $first_name, $last_name, $email, $department, $lecturer_id);

        if ($stmt->execute()) {
            $message = "Cập nhật thông tin thành công!";
        } else {
            $message = "Có lỗi xảy ra, vui lòng thử lại.";
        }

        $stmt->close();
    } else {
        $message = "Vui lòng điền đầy đủ thông tin!";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Hồ sơ Giảng viên</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 25px;
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
        .message {
            padding: 10px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }
        form {
            display: flex;
            flex-direction: column;
            align-items: center;
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
            padding: 8px;
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
        .back-btn {
            display: block;
            width: 100%;
            margin-top: 10px;
            padding: 10px;
            text-decoration: none;
            text-align: center;
            background-color: #28a745;
            color: white;
            border-radius: 5px;
            transition: 0.3s;
        }
        .back-btn:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>🧑‍🏫 Hồ sơ Giảng viên</h2>

    <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>

    <form method="post">
        <div class="form-group">
            <label>Họ:</label>
            <input type="text" name="first_name" value="<?= htmlspecialchars($lecturer['first_name']) ?>" required>
        </div>

        <div class="form-group">
            <label>Tên:</label>
            <input type="text" name="last_name" value="<?= htmlspecialchars($lecturer['last_name']) ?>" required>
        </div>

        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($lecturer['email']) ?>" required>
        </div>

        <div class="form-group">
            <label>Bộ môn:</label>
            <input type="text" name="department" value="<?= htmlspecialchars($lecturer['department']) ?>">
        </div>

        <button type="submit">💾 Cập nhật</button>
    </form>

    <a href="index.php" class="back-btn">🏠 Quay về Trang chủ</a>
</div>

</body>
</html>

