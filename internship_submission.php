
<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'internship_management');

if ($conn->connect_error) {
    die("Lỗi kết nối: " . $conn->connect_error);
}

// Kiểm tra đăng nhập & quyền sinh viên
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

// Lấy user_id từ session (chính là cột user_id trong bảng users)
$user_id = $_SESSION['user_id'];

// Truy vấn để lấy student_id thực tế từ bảng students
$checkStudentQuery = "SELECT student_id FROM students WHERE user_id = ?";
$checkStudentStmt = $conn->prepare($checkStudentQuery);
$checkStudentStmt->bind_param("i", $user_id);
$checkStudentStmt->execute();
$checkStudentStmt->store_result();

// Nếu không tìm thấy student_id, nghĩa là tài khoản chưa được liên kết với bảng students
if ($checkStudentStmt->num_rows === 0) {
    die("Lỗi: Tài khoản của bạn chưa được liên kết với thông tin sinh viên. Vui lòng liên hệ giảng viên để thêm tài khoản.");
}

// Lấy ra giá trị student_id
$checkStudentStmt->bind_result($real_student_id);
$checkStudentStmt->fetch();
$checkStudentStmt->close();

// Lấy danh sách khóa học
$courseQuery = "SELECT course_id, course_name FROM internship_courses";
$courseResult = $conn->query($courseQuery);

$message = ""; // Biến lưu thông báo kết quả

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_id = $_POST['course_id'];
    $company_name = $_POST['company_name'];
    $company_address = $_POST['company_address'];
    $industry = $_POST['industry'];
    $supervisor_name = $_POST['supervisor_name'];
    $supervisor_phone = $_POST['supervisor_phone'];
    $supervisor_email = $_POST['supervisor_email'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $job_position = $_POST['job_position'];
    $job_description = $_POST['job_description'];

    // Kiểm tra xem sinh viên đã nộp thông tin thực tập cho khóa học này chưa
    $checkStmt = $conn->prepare("
        SELECT id 
        FROM internship_details 
        WHERE student_id = ? AND course_id = ?
    ");
    $checkStmt->bind_param("ii", $real_student_id, $course_id);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        // Đã tồn tại bản ghi cho student_id & course_id này
        $message = "Bạn đã nộp thông tin thực tập cho khóa học này!";
    } else {
        // Thêm bản ghi mới vào internship_details
        $stmt = $conn->prepare("
            INSERT INTO internship_details 
            (student_id, course_id, company_name, company_address, industry, 
             supervisor_name, supervisor_phone, supervisor_email, start_date, 
             end_date, job_position, job_description, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");

        // Truyền đúng student_id lấy từ bảng students
        $stmt->bind_param(
            "iissssssssss",
            $real_student_id,
            $course_id,
            $company_name,
            $company_address,
            $industry,
            $supervisor_name,
            $supervisor_phone,
            $supervisor_email,
            $start_date,
            $end_date,
            $job_position,
            $job_description
        );

        if ($stmt->execute()) {
            $message = "Nộp thông tin thực tập thành công!";
        } else {
            $message = "Lỗi: " . $stmt->error;
        }
        $stmt->close();
    }
    $checkStmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nộp Thông Tin Thực Tập</title>
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
            max-width: 600px;
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
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

        .message {
            text-align: center;
            font-weight: bold;
            margin: 15px 0;
            color: green;
        }

        .error {
            text-align: center;
            font-weight: bold;
            margin: 15px 0;
            color: red;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #007bff;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>📝 Nộp Thông Tin Thực Tập</h2>

        <?php
        // Hiển thị thông báo thành công hoặc lỗi
        if (!empty($message)) {
            $cssClass = (strpos($message, 'thành công') !== false) ? "message" : "error";
            echo "<p class='$cssClass'>$message</p>";
        }
        ?>

        <form method="POST">
            <div class="form-group">
                <label>Chọn khóa học:</label>
                <select name="course_id" required>
                    <option value="">-- Chọn khóa học --</option>
                    <?php while ($row = $courseResult->fetch_assoc()): ?>
                        <option value="<?php echo $row['course_id']; ?>">
                            <?php echo htmlspecialchars($row['course_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Tên công ty:</label>
                <input type="text" name="company_name" required>
            </div>

            <div class="form-group">
                <label>Địa chỉ công ty:</label>
                <textarea name="company_address" required></textarea>
            </div>

            <div class="form-group">
                <label>Ngành nghề:</label>
                <input type="text" name="industry" required>
            </div>

            <div class="form-group">
                <label>Người hướng dẫn:</label>
                <input type="text" name="supervisor_name" required>
            </div>

            <div class="form-group">
                <label>Số điện thoại người hướng dẫn:</label>
                <input type="text" name="supervisor_phone" required>
            </div>

            <div class="form-group">
                <label>Email người hướng dẫn:</label>
                <input type="email" name="supervisor_email" required>
            </div>

            <div class="form-group">
                <label>Ngày bắt đầu:</label>
                <input type="date" name="start_date" required>
            </div>

            <div class="form-group">
                <label>Ngày kết thúc:</label>
                <input type="date" name="end_date" required>
            </div>

            <div class="form-group">
                <label>Vị trí thực tập:</label>
                <input type="text" name="job_position" required>
            </div>

            <div class="form-group">
                <label>Mô tả công việc:</label>
                <textarea name="job_description" required></textarea>
            </div>

            <button type="submit">📌 Nộp</button>
        </form>

        <a href="index.php" class="back-link">⬅️ Quay lại</a>
    </div>
</body>

</html>