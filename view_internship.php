<?php
session_start();
require_once "config.php"; // Kết nối CSDL

// Kiểm tra đăng nhập qua user_id
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    die("Lỗi: Bạn chưa đăng nhập.");
}

// Truy vấn lấy student_id từ bảng students dựa trên user_id
$stmt = $conn->prepare("SELECT student_id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($student_id);
if (!$stmt->fetch()) {
    die("Lỗi: Không tìm thấy thông tin sinh viên cho user_id này.");
}
$stmt->close();

// Truy vấn lấy thông tin thực tập của sinh viên dựa trên student_id
$sql = "SELECT id, course_id, company_name, company_address, industry, supervisor_name, supervisor_phone, supervisor_email, start_date, end_date, job_position, job_description, status, feedback 
        FROM internship_details 
        WHERE student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin thực tập</title>
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
    </style>
</head>

<body>

    <h2>Thông tin thực tập của bạn</h2>

    <?php if ($result->num_rows > 0): ?>
        <table>
            <tr>
                <th>Khóa học</th>
                <th>Công ty</th>
                <th>Địa chỉ</th>
                <th>Ngành</th>
                <th>Người hướng dẫn</th>
                <th>Liên hệ</th>
                <th>Thời gian</th>
                <th>Vị trí</th>
                <th>Mô tả công việc</th>
                <th>Trạng thái</th>
                <th>Phản hồi</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                // Xác định class cho trạng thái
                $statusClass = '';
                if ($row['status'] === 'pending') {
                    $statusClass = 'pending';
                } elseif ($row['status'] === 'approved') {
                    $statusClass = 'approved';
                } elseif ($row['status'] === 'rejected') {
                    $statusClass = 'rejected';
                }
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['course_id']) ?></td>
                    <td><?= htmlspecialchars($row['company_name']) ?></td>
                    <td><?= htmlspecialchars($row['company_address']) ?></td>
                    <td><?= htmlspecialchars($row['industry']) ?></td>
                    <td><?= htmlspecialchars($row['supervisor_name']) ?></td>
                    <td>
                        <?= htmlspecialchars($row['supervisor_phone']) ?><br>
                        <?= htmlspecialchars($row['supervisor_email']) ?>
                    </td>
                    <td><?= htmlspecialchars($row['start_date']) ?> - <?= htmlspecialchars($row['end_date']) ?></td>
                    <td><?= htmlspecialchars($row['job_position']) ?></td>
                    <td><?= htmlspecialchars($row['job_description']) ?></td>
                    <td>
                        <span class="badge <?= $statusClass; ?>">
                            <?= htmlspecialchars($row['status']); ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($row['feedback'] ?? 'Chưa có phản hồi') ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>Bạn chưa nộp thông tin thực tập.</p>
    <?php endif; ?>

</body>

</html>

<?php
$stmt->close();
$conn->close();
?>