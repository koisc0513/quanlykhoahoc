<?php
session_start();
require_once "config.php"; // Kết nối CSDL

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo "Bạn không có quyền truy cập trang này.";
    exit;
}

$student_id = $_SESSION['user_id'];

// Truy vấn danh sách khóa học mà sinh viên đã được thêm vào
$sql = "
    SELECT ic.course_code, ic.course_name, ic.description, l.first_name, l.last_name 
    FROM student_courses sc
    JOIN internship_courses ic ON sc.course_id = ic.course_id
    JOIN lecturers l ON ic.lecturer_id = l.lecturer_id
    JOIN students s ON sc.student_id = s.student_id
    WHERE s.user_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Khóa Học Của Tôi</title>
</head>
<body>
    <h2>Danh sách khóa học của bạn</h2>
    <table border="1">
        <thead>
            <tr>
                <th>Mã khóa học</th>
                <th>Tên khóa học</th>
                <th>Mô tả</th>
                <th>Giảng viên</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['course_code']) ?></td>
                    <td><?= htmlspecialchars($row['course_name']) ?></td>
                    <td><?= htmlspecialchars($row['description']) ?></td>
                    <td><?= htmlspecialchars($row['first_name'] . " " . $row['last_name']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
