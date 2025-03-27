<?php
session_start();
require 'config.php'; // Kết nối database
require 'vendor/autoload.php'; // Thư viện PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

$uploaded_students = [];

// Xóa danh sách sinh viên đã tải lên
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_students'])) {
    // Xóa dữ liệu trong student_courses trước
    $conn->query("DELETE FROM student_courses");

    // Xóa dữ liệu trong students
    $conn->query("DELETE FROM students");

    // Xóa tài khoản sinh viên trong bảng users
    $conn->query("DELETE FROM users WHERE role = 'student'");

    $message = "Danh sách sinh viên đã được xóa!";
}

// Xử lý tải lên file Excel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file']['tmp_name'];

    // Đọc file Excel
    $spreadsheet = IOFactory::load($file);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // Chuẩn bị câu lệnh SQL
    $checkUserStmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmtUser = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'student')");
    $stmtStudent = $conn->prepare("INSERT INTO students (user_id, student_code, first_name, last_name, phone, email, class_code) VALUES (?, ?, ?, ?, ?, ?, ?)");

    $count = 0;
    $duplicate = 0;

    foreach ($rows as $index => $row) {
        if ($index === 0) continue; // Bỏ qua dòng tiêu đề

        list($student_code, $last_name, $first_name, $class_code, $email, $phone) = $row;

        if (!empty($student_code) && !empty($first_name) && !empty($last_name) && !empty($email)) {
            // Kiểm tra username đã tồn tại chưa
            $checkUserStmt->bind_param("s", $student_code);
            $checkUserStmt->execute();
            $result = $checkUserStmt->get_result();

            if ($result->num_rows === 0) { // Nếu chưa có trong DB, thêm mới
                $password = password_hash($student_code, PASSWORD_DEFAULT); // Mật khẩu = MaSV
                $stmtUser->bind_param("ss", $student_code, $password);

                if ($stmtUser->execute()) {
                    $user_id = $conn->insert_id; // Lấy ID user vừa tạo

                    $stmtStudent->bind_param("issssss", $user_id, $student_code, $first_name, $last_name, $phone, $email, $class_code);
                    if ($stmtStudent->execute()) {
                        $uploaded_students[] = [
                            'student_code' => $student_code,
                            'full_name' => $last_name . " " . $first_name,
                            'class_code' => $class_code,
                            'email' => $email,
                            'phone' => $phone
                        ];
                        $count++;
                    }
                }
            } else {
                $duplicate++;
            }
        }
    }

    $checkUserStmt->close();
    $stmtUser->close();
    $stmtStudent->close();

    $message = "Đã nhập thành công $count sinh viên! ($duplicate sinh viên đã tồn tại)";
}

// Truy vấn danh sách toàn bộ sinh viên đã nhập
$sql = "SELECT student_code, last_name, first_name, class_code, email, phone FROM students";
$result = $conn->query($sql);
$students_list = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhập danh sách sinh viên</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin: auto;
        }
        h2, h3 {
            color: #333;
        }
        .message {
            padding: 10px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        form {
            margin-bottom: 20px;
        }
        input[type="file"] {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            background-color: #007bff;
            color: white;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background-color: #0056b3;
        }
        .delete-btn {
            background-color: red;
        }
        .delete-btn:hover {
            background-color: darkred;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Nhập danh sách sinh viên từ Excel</h2>

        <?php if (isset($message)) echo "<p class='message'>$message</p>"; ?>

        <!-- Form tải lên file Excel -->
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="excel_file" required>
            <button type="submit">📥 Tải lên</button>
        </form>

        <!-- Hiển thị danh sách sinh viên -->
        <h3>📋 Danh sách sinh viên</h3>
        <table>
            <tr>
                <th>Mã SV</th>
                <th>Họ và Tên</th>
                <th>Lớp</th>
                <th>Email</th>
                <th>Điện thoại</th>
            </tr>
            <?php foreach ($students_list as $student): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['student_code']); ?></td>
                    <td><?php echo htmlspecialchars($student['last_name'] . " " . $student['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($student['class_code']); ?></td>
                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                    <td><?php echo htmlspecialchars($student['phone']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <!-- Nút xóa danh sách sinh viên -->
        <form method="POST">
            <button type="submit" name="delete_students" class="delete-btn">🗑 Xóa toàn bộ danh sách</button>
        </form>
        <!-- Nút quay về Trang chủ -->
        <a href="index.php">
            <button class="back-btn">🏠 Quay về Trang chủ</button>
        </a>
    </div>
</body>
</html>
