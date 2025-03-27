<?php
session_start();
require 'vendor/autoload.php';

$conn = new mysqli('localhost', 'root', '', 'internship_management');

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Lỗi kết nối: " . $conn->connect_error);
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];

// Lấy thông tin từ database
if ($role === 'lecturer') {
    $stmt = $conn->prepare("SELECT * FROM lecturers WHERE user_id = ?");
} else {
    $stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

// Lấy danh sách sinh viên từ database
$students_list = [];
$sql = "SELECT student_code, first_name, last_name, email, class_code FROM students";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students_list[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #333;
        }
        .options {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
        }
        .option-box {
            flex: 1;
            margin: 0 10px;
            padding: 20px;
            background: #007bff;
            color: white;
            border-radius: 10px;
            cursor: pointer;
            text-align: center;
            transition: background 0.3s;
        }
        .option-box:hover {
            background: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        a {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background: red;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        a:hover {
            background: darkred;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9em;
            color: #777;
        }
    </style>
</head>
<body>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h2 class="text-primary">Chào mừng, <?php echo htmlspecialchars($username); ?>!</h2>

        <?php if ($role === 'lecturer'): ?>
            <div class="card p-3 mb-4">
                <h3>Thông tin giảng viên</h3>
                <p><strong>Họ tên:</strong> <?php echo htmlspecialchars($user_data['first_name'] . " " . $user_data['last_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
                <p><strong>Bộ môn:</strong> <?php echo htmlspecialchars($user_data['department'] ?? 'Chưa cập nhật'); ?></p>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <a href="course_manage.php" class="btn btn-primary w-100">📚 Quản lý khóa học</a>
                </div>
                <div class="col-md-4">
                    <a href="lecture_profile.php" class="btn btn-warning w-100">👨‍🏫 Quản lý thông tin</a>
                </div>
                <div class="col-md-4">
                    <a href="import_students.php" class="btn btn-success w-100">📥 Nhập danh sách SV</a>
                </div>
            </div>
        <?php else: ?>
            <div class="card p-3 mb-4">
                <h3>Thông tin sinh viên</h3>
                <p><strong>Mã sinh viên:</strong> <?php echo htmlspecialchars($user_data['student_code']); ?></p>
                <p><strong>Họ tên:</strong> <?php echo htmlspecialchars($user_data['first_name'] . " " . $user_data['last_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
                <p><strong>Ngành học:</strong> <?php echo htmlspecialchars($user_data['major'] ?? 'Chưa cập nhật'); ?></p>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <a href="view_courses.php" class="btn btn-primary w-100">📖 Xem khóa học</a>
                </div>
                <div class="col-md-4">
                    <a href="internship_submission.php" class="btn btn-info w-100">📝 Nộp thực tập</a>
                </div>
                <div class="col-md-4">
                <a href="view_internship.php" class="btn btn-info w-100">🔍 Xem thông tin</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($students_list)): ?>
            <h3 class="mt-4">Danh sách sinh viên</h3>
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Mã SV</th>
                        <th>Họ và Tên</th>
                        <th>Lớp</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students_list as $index => $student): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($student['student_code']); ?></td>
                            <td><?php echo htmlspecialchars($student['last_name'] . " " . $student['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['class_code']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-danger">Không có sinh viên nào trong danh sách!</p>
        <?php endif; ?>

        <div class="mt-3">
            <a href="logout.php" class="btn btn-danger">🚪 Đăng xuất</a>
        </div>
    </div>

    <footer class="text-center mt-5 py-3 bg-light">
        <p>&copy; 2025 Hệ thống quản lý thực tập</p>
    </footer>
</body>
</html>