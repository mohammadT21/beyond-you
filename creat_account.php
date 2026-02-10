<?php
session_start();
require_once 'CONFIG.php'; // تأكد من الاسم/المسار

// السماح فقط للأدمن
check_admin_login();

$conn = db_connect();

$errors = [];
$message = '';

$username   = '';
$password   = '';
$role       = 'admin'; // افتراضي
$faculty_id = 0;

// جلب الكليات لاستخدامها مع عميد الكلية
$faculties = [];
$sql = "SELECT id, name FROM faculties ORDER BY name ASC";
$res = mysqli_query($conn, $sql);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $faculties[] = $row;
    }
}

// معالجة الفورم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';
    $role       = $_POST['role'] ?? 'admin';
    $faculty_id = intval($_POST['faculty_id'] ?? 0);

    // أقل شروط ممكنة
    if ($username === '')  $errors[] = "الرجاء إدخال اسم المستخدم.";
    if ($password === '')  $errors[] = "الرجاء إدخال كلمة المرور.";
    if ($role === 'dean' && $faculty_id === 0) {
        $errors[] = "الرجاء اختيار الكلية للعميد.";
    }

    // فحص بسيط لتكرار اسم المستخدم (احذفه إن ما بدك أي شرط)
    if (empty($errors)) {
        $check_sql = "SELECT id FROM users WHERE username = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "اسم المستخدم مستخدم مسبقًا.";
        }
        mysqli_stmt_close($stmt);
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        if ($role === 'dean') {
            $insert_sql = "INSERT INTO users (username, password, role, faculty_id)
                           VALUES (?, ?, 'dean', ?)";
            $stmt2 = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($stmt2, "ssi", $username, $hash, $faculty_id);
        } else { // admin
            $insert_sql = "INSERT INTO users (username, password, role)
                           VALUES (?, ?, 'admin')";
            $stmt2 = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($stmt2, "ss", $username, $hash);
        }

        if (mysqli_stmt_execute($stmt2)) {
            $message    = "تم إنشاء الحساب بنجاح للمستخدم: " . htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
            $username   = '';
            $password   = '';
            $role       = 'admin';
            $faculty_id = 0;
        } else {
            $errors[] = "حدث خطأ أثناء حفظ الحساب.";
        }

        mysqli_stmt_close($stmt2);
    }
}

db_close($conn);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إضافة حساب جديد - Beyond You</title>

    <!-- نفس ستايل create_dean.php اختصاراً: -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:"Tajawal",sans-serif;background:#f8fafc;color:#334155}
        .header{background:linear-gradient(135deg,#009879 0,#007a62 100%);color:#fff;padding:20px 30px;box-shadow:0 4px 20px rgba(0,152,121,.15)}
        .header-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:15px}
        .logo h1{font-size:1.8rem;font-weight:800}
        .nav-buttons{display:flex;gap:10px}
        .nav-btn{background:rgba(255,255,255,.2);color:#fff;border:none;padding:10px 20px;border-radius:8px;cursor:pointer;text-decoration:none;font-weight:600;transition:.3s}
        .nav-btn:hover{background:rgba(255,255,255,.3);transform:translateY(-2px)}
        .container{max-width:600px;margin:40px auto;padding:0 20px}
        .form-container{background:#fff;padding:40px;border-radius:20px;box-shadow:0 10px 30px rgba(0,0,0,.08)}
        .page-title{color:#009879;font-size:2rem;text-align:center;margin-bottom:10px}
        .page-description{color:#64748b;text-align:center;margin-bottom:30px;font-size:1.1rem}
        .form-group{margin-bottom:25px}
        .form-label{display:block;margin-bottom:8px;color:#334155;font-weight:600}
        .form-input,.form-select{width:100%;padding:15px 20px;border:2px solid #e2e8f0;border-radius:12px;font-size:1rem;transition:.3s;background:#fff}
        .form-input:focus,.form-select:focus{outline:none;border-color:#009879;box-shadow:0 0 0 3px rgba(0,152,121,.1)}
        .submit-btn{background:linear-gradient(135deg,#009879 0,#007a62 100%);color:#fff;border:none;padding:16px;border-radius:12px;font-size:1.1rem;font-weight:700;cursor:pointer;width:100%;margin-top:10px;transition:.3s}
        .submit-btn:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(0,152,121,.3)}
        .message{background:#d1fae5;color:#065f46;padding:15px;border-radius:10px;margin-bottom:20px;text-align:center;font-weight:600;border:1px solid #a7f3d0}
        .error{background:#fee2e2;color:#991b1b;padding:15px;border-radius:10px;margin-bottom:20px;text-align:center;font-weight:600;border:1px solid #fecaca}
        .actions{display:flex;gap:15px;margin-top:25px}
        .action-btn{flex:1;padding:12px;border-radius:10px;text-decoration:none;text-align:center;font-weight:600;transition:.3s}
        .action-secondary{background:#f1f5f9;color:#334155;border:2px solid #e2e8f0}
        .action-btn:hover{transform:translateY(-2px)}
        @media (max-width:768px){
            .header-top{flex-direction:column;gap:10px;text-align:center}
            .form-container{padding:30px 20px}
        }
    </style>
</head>
<body>

<header class="header">
    <div class="header-top">
        <div class="logo">
            <h1>Beyond You</h1>
            <p>إدارة حسابات النظام</p>
        </div>
        <div class="nav-buttons">
            <a href="dashboard_admin.php" class="nav-btn">لوحة تحكم الأدمن</a>
            <a href="logout.php" class="nav-btn">تسجيل الخروج</a>
        </div>
    </div>
</header>

<div class="container">
    <div class="form-container">
        <h1 class="page-title">إضافة حساب (أدمن / عميد)</h1>
        <p class="page-description">إنشاء حساب جديد بدون الحاجة لكلمة سر سابقة.</p>

        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $e): ?>
                    <div><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="username" class="form-label">اسم المستخدم</label>
                <input type="text" id="username" name="username"
                       class="form-input"
                       value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">كلمة المرور</label>
                <input type="password" id="password" name="password"
                       class="form-input" required>
            </div>

            <div class="form-group">
                <label for="role" class="form-label">نوع الحساب</label>
                <select name="role" id="role" class="form-select" onchange="toggleFaculty()">
                    <option value="admin" <?php echo ($role === 'admin') ? 'selected' : ''; ?>>أدمن</option>
                    <option value="dean"  <?php echo ($role === 'dean')  ? 'selected' : ''; ?>>عميد كلية</option>
                </select>
            </div>

            <div class="form-group" id="faculty-group"
                 style="<?php echo ($role === 'dean') ? '' : 'display:none;'; ?>">
                <label for="faculty_id" class="form-label">الكلية</label>
                <select id="faculty_id" name="faculty_id" class="form-select">
                    <option value="0">-- اختر الكلية --</option>
                    <?php foreach ($faculties as $f): ?>
                        <option value="<?php echo $f['id']; ?>"
                            <?php echo ($faculty_id == $f['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="submit-btn">إنشاء الحساب</button>
        </form>

        <div class="actions">
            <a href="dashboard_admin.php" class="action-btn action-secondary">الرجوع للوحة التحكم</a>
        </div>
    </div>
</div>

<script>
function toggleFaculty() {
    const role   = document.getElementById('role').value;
    const facDiv = document.getElementById('faculty-group');
    facDiv.style.display = (role === 'dean') ? 'block' : 'none';
}
</script>

</body>
</html>
