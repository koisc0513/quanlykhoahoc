<?php
session_start();
require 'config.php';

// Kiểm tra giảng viên đăng nhập
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lecturer') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['course_id'])) {
    echo "Thiếu course_id!";
    exit();
}

$course_id = intval($_GET['course_id']);
$lecturer_id = $_SESSION['lecturer_id'];
$message = "";

// Cập nhật trạng thái và phản hồi thực tập
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $internship_id = intval($_POST['internship_id']);
    $status = $_POST['status'];
    $feedback = trim($_POST['feedback'] ?? '');

    // Cập nhật cả trạng thái và phản hồi
    $stmt = $conn->prepare("UPDATE internship_details SET status = ?, feedback = ? WHERE id = ?");
    $stmt->bind_param("ssi", $status, $feedback, $internship_id);

    if ($stmt->execute()) {
        $message = "Cập nhật trạng thái và phản hồi thành công!";
    } else {
        $message = "Lỗi khi cập nhật!";
    }
    $stmt->close();
}

// Lấy danh sách sinh viên thực tập
$stmt = $conn->prepare("
    SELECT i.id AS internship_id, s.student_code, s.first_name, s.last_name, i.company_name, i.job_position, i.status, i.feedback 
    FROM internship_details i 
    JOIN students s ON i.student_id = s.student_id 
    WHERE i.course_id = ?
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
// Chuyển kết quả truy vấn thành mảng
$internships = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý Thực tập</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        /* Style cho trạng thái */
        .badge {
            padding: 5px 10px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
        }

        .pending {
            background-color: #f0ad4e;
        }

        .approved {
            background-color: #5cb85c;
        }

        .rejected {
            background-color: #d9534f;
        }

        /* Style cho form phản hồi */
        textarea {
            width: 200px;
            height: 60px;
            vertical-align: top;
        }
    </style>
</head>

<body>
    <h2>Quản lý Thực tập</h2>
    <?php if (!empty($message)) echo "<p style='color: green;'>$message</p>"; ?>

    <table>
        <tr>
            <th>MSSV</th>
            <th>Họ Tên</th>
            <th>Công Ty</th>
            <th>Vị Trí</th>
            <th>Trạng Thái</th>
            <th>Phản Hồi</th>
            <th>Hành động</th>
        </tr>
        <?php foreach ($internships as $internship): ?>
            <tr>
                <td><?= htmlspecialchars($internship['student_code']); ?></td>
                <td><?= htmlspecialchars($internship['first_name'] . ' ' . $internship['last_name']); ?></td>
                <td><?= htmlspecialchars($internship['company_name']); ?></td>
                <td><?= htmlspecialchars($internship['job_position']); ?></td>
                <td>
                    <span class="badge 
                        <?php
                        if ($internship['status'] === 'pending') echo 'pending';
                        elseif ($internship['status'] === 'approved') echo 'approved';
                        elseif ($internship['status'] === 'rejected') echo 'rejected';
                        ?>">
                        <?= htmlspecialchars($internship['status']); ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($internship['feedback'] ?? 'Chưa có phản hồi'); ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="internship_id" value="<?= $internship['internship_id'] ?>">
                        <select name="status">
                            <option value="pending" <?= $internship['status'] == 'pending' ? 'selected' : '' ?>>Chờ duyệt</option>
                            <option value="approved" <?= $internship['status'] == 'approved' ? 'selected' : '' ?>>Chấp nhận</option>
                            <option value="rejected" <?= $internship['status'] == 'rejected' ? 'selected' : '' ?>>Từ chối</option>
                        </select>
                        <br>
                        <textarea name="feedback" placeholder="Nhập phản hồi"><?= htmlspecialchars($internship['feedback'] ?? '') ?></textarea>
                        <br>
                        <button type="submit" name="update_status">Cập nhật</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>

</html>