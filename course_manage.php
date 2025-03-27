<?php
session_start();
require 'config.php'; // Kết nối database
require 'vendor/autoload.php'; // Thư viện PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

// Kiểm tra quyền giảng viên
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lecturer') {
    header("Location: login.php");
    exit();
}

$lecturer_id = $_SESSION['lecturer_id'];
$message = "";

// **Thêm khóa học**
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_course'])) {
    $course_code = trim($_POST['course_code']);
    $course_name = trim($_POST['course_name']);
    $description = trim($_POST['description']);

    $stmt = $conn->prepare("INSERT INTO internship_courses (course_code, course_name, description, lecturer_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $course_code, $course_name, $description, $lecturer_id);
    
    $message = $stmt->execute() ? "<div class='alert alert-success'>Thêm khóa học thành công!</div>" : "<div class='alert alert-danger'>Lỗi: " . $stmt->error . "</div>";
    $stmt->close();
}

// **Cập nhật khóa học**
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_course'])) {
    $course_id = intval($_POST['course_id']);
    $course_code = trim($_POST['course_code']);
    $course_name = trim($_POST['course_name']);
    //$description = trim($_POST['description']);

    $stmt = $conn->prepare("UPDATE internship_courses SET course_code = ?, course_name = ?, description = ? WHERE course_id = ? AND lecturer_id = ?");
    $stmt->bind_param("sssii", $course_code, $course_name, $description, $course_id, $lecturer_id);

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>Cập nhật khóa học thành công!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Lỗi khi cập nhật!</div>";
    }
    $stmt->close();
}


// **Xóa khóa học**
if (isset($_GET['delete_id'])) {
    $course_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM internship_courses WHERE course_id = ? AND lecturer_id = ?");
    $stmt->bind_param("ii", $course_id, $lecturer_id);
    
    $message = $stmt->execute() ? "<div class='alert alert-success'>Đã xóa khóa học.</div>" : "<div class='alert alert-danger'>Lỗi khi xóa khóa học!</div>";
    $stmt->close();
}

// **Lấy danh sách khóa học của giảng viên**
$stmt = $conn->prepare("SELECT * FROM internship_courses WHERE lecturer_id = ?");
$stmt->bind_param("i", $lecturer_id);
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// **Lấy danh sách sinh viên chưa đăng ký vào khóa học**
$students = [];
if (!empty($courses) && isset($_POST['course_id'])) {
    $course_id = intval($_POST['course_id']);
    $stmt = $conn->prepare("SELECT s.student_id, s.first_name FROM students s WHERE NOT EXISTS (SELECT 1 FROM student_courses sc WHERE sc.student_id = s.student_id AND sc.course_id = ?)");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
 
// **Thêm sinh viên vào khóa học**
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student_course'])) {
    $course_id = intval($_POST['course_id']);
    $student_id = trim($_POST['student_id']);
    
    $stmt = $conn->prepare("INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)");
    $stmt->bind_param("si", $student_id, $course_id);
    
    $message = $stmt->execute() ? "<div class='alert alert-success'>Đã thêm sinh viên vào khóa học.</div>" : "<div class='alert alert-danger'>Lỗi khi thêm sinh viên!</div>";
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý Khóa học</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script>
        function showEditForm(courseId, courseCode, courseName) {
            document.getElementById('editForm').style.display = 'block';
            document.getElementById('edit_course_id').value = courseId;
            document.getElementById('edit_course_code').value = courseCode;
            document.getElementById('edit_course_name').value = courseName;
        }
    </script>
</head>
<body class="container py-4">
    <h2 class="mb-4">Quản lý Khóa học</h2>
    
    <?= $message; ?>

    <div class="card p-4 mb-4">
        <h3>Thêm/Sửa Khóa học</h3>
        <form method="POST">
            <input type="hidden" name="course_id" value="<?= isset($_GET['edit_id']) ? $_GET['edit_id'] : ''; ?>">
            <div class="mb-3">
                <label class="form-label">Mã Khóa học:</label>
                <input type="text" name="course_code" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Tên Khóa học:</label>
                <input type="text" name="course_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Mô tả:</label>
                <textarea name="description" class="form-control"></textarea>
            </div>
            <button type="submit" name="add_course" class="btn btn-primary">Thêm Khóa học</button>
        </form>
    </div>

    <h3>Danh sách Khóa học</h3>
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Mã Khóa học</th>
                <th>Tên Khóa học</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($courses as $course): ?>
                <tr>
                    <td><?= htmlspecialchars($course['course_code']); ?></td>
                    <td><?= htmlspecialchars($course['course_name']); ?></td>
                    <td>
                    <button class="btn btn-warning btn-sm" onclick="showEditForm('<?= $course['course_id']; ?>', '<?= htmlspecialchars($course['course_code']); ?>', '<?= htmlspecialchars($course['course_name']); ?>')">✏ Sửa</button>
                    <a href="course_manage.php?delete_id=<?= $course['course_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xác nhận xóa?');">❌ Xóa</a>
                    <button class="btn btn-info btn-sm" onclick="location.href='internship_review.php?course_id=<?= $course['course_id']; ?>'">👁 Xem chi tiết</button>


                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div id="editForm" class="card p-4 mt-4" style="display: none;">
        <h3>Sửa Khóa học</h3>
        <form method="POST">
            <input type="hidden" name="course_id" id="edit_course_id">
            <div class="mb-3">
                <label class="form-label">Mã Khóa học:</label>
                <input type="text" name="course_code" id="edit_course_code" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Tên Khóa học:</label>
                <input type="text" name="course_name" id="edit_course_name" class="form-control" required>
            </div>
            <button type="submit" name="update_course" class="btn btn-primary">Cập nhật</button>
        </form>
    </div>

    <div class="card p-4 mt-4">
        <h3>Thêm Sinh viên vào Khóa học</h3>
        <form method="POST">
            <select name="course_id" class="form-control mb-2" required onchange="this.form.submit()">
                <option value="">-- Chọn khóa học --</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= $course['course_id']; ?>" <?= isset($_POST['course_id']) && $_POST['course_id'] == $course['course_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($course['course_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="student_id" class="form-control mb-2" required>
                <option value="">-- Chọn sinh viên --</option>
                <?php foreach ($students as $student): ?>
                    <option value="<?= htmlspecialchars($student['student_id']); ?>">
                        <?= htmlspecialchars($student['student_id'] . ' - ' . $student['first_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="add_student_course" class="btn btn-primary">Thêm Sinh viên</button>
        </form>
    </div>
    <a href="index.php">
            <button class="back-btn">🏠 Quay về Trang chủ</button>
        </a>
</body>
</html>
