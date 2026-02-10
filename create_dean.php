<?php
// create_dean.php - صفحة إنشاء حسابات العمداء
require_once 'config.php';

// تحقق من أن المستخدم هو admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$conn = db_connect();
$errors = [];
$message = '';

// جلب faculty_id من URL إذا كان موجوداً
$selected_faculty_id = isset($_GET['faculty_id']) ? intval($_GET['faculty_id']) : 0;

// جلب قائمة الكليات لعرضها في القائمة المنسدلة
$faculties = [];
$res = mysqli_query($conn, "SELECT id, name FROM faculties ORDER BY name ASC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $faculties[] = $row;
    }
}

// عند الإرسال: إدراج العميد
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $faculty_id = intval($_POST['faculty_id'] ?? 0);

    if ($username === '') $errors[] = 'الرجاء إدخال اسم المستخدم.';
    if ($password === '') $errors[] = 'الرجاء إدخال كلمة المرور.';
    if ($faculty_id <= 0) $errors[] = 'الرجاء اختيار كلية مرتبطة بالعميد.';

    // منع تكرار اسم المستخدم
    if (empty($errors)) {
        $check_sql = "SELECT id FROM users WHERE username = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = 'اسم المستخدم مستخدم مسبقًا. اختر اسمًا آخر.';
        }
        mysqli_stmt_close($stmt);
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $insert_sql = "INSERT INTO users (username, password, role, faculty_id) VALUES (?, ?, 'dean', ?)";
        $stmt2 = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt2, 'ssi', $username, $hash, $faculty_id);
        if (mysqli_stmt_execute($stmt2)) {
            $message = '<i class="fa-solid fa-circle-check"></i> تم إنشاء حساب العميد بنجاح (' . htmlspecialchars($username) . ').';
            // تفريغ الحقول بعد الإدخال
            $username = '';
            $faculty_id = 0;
        } else {
            $errors[] = 'حدث خطأ أثناء حفظ الحساب. حاول لاحقًا.';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب عميد - نظام Beyond You</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background: #0a0e27;
            background-image: 
                radial-gradient(ellipse at top right, rgba(0, 152, 121, 0.15) 0%, transparent 60%),
                radial-gradient(ellipse at bottom left, rgba(99, 102, 241, 0.15) 0%, transparent 60%),
                radial-gradient(ellipse at center, rgba(0, 152, 121, 0.06) 0%, transparent 70%);
            color: #e2e8f0;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* Subtle animated grid pattern */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="100" height="100" patternUnits="userSpaceOnUse"><path d="M 100 0 L 0 0 0 100" fill="none" stroke="rgba(148,163,184,0.05)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.5;
            pointer-events: none;
            z-index: 0;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 30%, rgba(0, 217, 165, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(99, 102, 241, 0.08) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
            animation: gradientBreath 12s ease-in-out infinite;
        }

        @keyframes gradientBreath {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.8;
                transform: scale(1.05);
            }
        }

        .header {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            color: white;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
            z-index: 1;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .logo h1 {
            font-size: 1.8rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo h1 i {
            font-size: 1.6rem;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }

        .logo p {
            opacity: 0.9;
            font-size: 0.95rem;
            margin-top: 5px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-welcome {
            text-align: left;
        }

        .user-welcome .welcome {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .user-welcome .username {
            font-weight: 700;
            font-size: 1.1rem;
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
        }

        .nav-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Tajawal', sans-serif;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(10px);
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .nav-btn i {
            font-size: 1rem;
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 0 20px;
            position: relative;
            z-index: 1;
        }

        .form-container {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            padding: 45px;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(148, 163, 184, 0.1);
            animation: fadeInDown 0.8s ease-out;
            position: relative;
            overflow: hidden;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #00d9a5, #6366f1, #00d9a5);
            background-size: 200% 100%;
            animation: shimmer 3s linear infinite;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }
            100% {
                background-position: 200% 0;
            }
        }

        .page-title {
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.2rem;
            text-align: center;
            margin-bottom: 12px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .page-title i {
            font-size: 2rem;
        }

        .page-description {
            color: #94a3b8;
            text-align: center;
            margin-bottom: 35px;
            font-size: 1.15rem;
            line-height: 1.8;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #e2e8f0;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(148, 163, 184, 0.2);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section-title i {
            color: #00d9a5;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            color: #e2e8f0;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: -0.01em;
        }

        .form-label i {
            color: #00d9a5;
            font-size: 1rem;
        }

        .form-label.required::after {
            content: ' *';
            color: #dc2626;
            font-weight: 700;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid rgba(148, 163, 184, 0.2);
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: 'Tajawal', sans-serif;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(10px);
            color: #e2e8f0;
            line-height: 1.5;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #00d9a5;
            box-shadow: 0 0 0 4px rgba(0, 217, 165, 0.15);
            background: rgba(15, 23, 42, 0.6);
            transform: translateY(-2px);
        }

        .form-input::placeholder {
            color: #64748b;
            opacity: 1;
        }

        .form-input.error-input, .form-select.error-input {
            border-color: #ef4444;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.15);
        }

        .form-input.error-input:focus, .form-select.error-input:focus {
            border-color: #ef4444;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.2);
        }

        .input-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 1.1rem;
            padding: 5px;
            transition: color 0.3s ease;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover {
            color: #00d9a5;
        }

        .form-input[type="password"] {
            padding-right: 50px;
        }

        .form-actions {
            margin-top: 35px;
            padding-top: 25px;
            border-top: 2px solid rgba(148, 163, 184, 0.2);
        }

        .submit-btn {
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            color: white;
            border: none;
            padding: 16px 24px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            width: 100%;
            font-family: 'Tajawal', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 217, 165, 0.3);
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0, 217, 165, 0.4);
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .submit-btn i {
            font-size: 1rem;
        }

        .message {
            background: rgba(16, 185, 129, 0.15);
            backdrop-filter: blur(10px);
            color: #6ee7b7;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
            border: 1px solid rgba(16, 185, 129, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 2px 15px rgba(16, 185, 129, 0.2);
            animation: fadeInDown 0.5s ease-out;
        }

        .message i {
            font-size: 1.1rem;
        }

        .error {
            background: rgba(239, 68, 68, 0.15);
            backdrop-filter: blur(10px);
            color: #fca5a5;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
            border: 1px solid rgba(239, 68, 68, 0.3);
            box-shadow: 0 2px 15px rgba(239, 68, 68, 0.2);
            animation: shake 0.5s ease-out;
        }

        @keyframes shake {
            0%, 100% {
                transform: translateX(0);
            }
            10%, 30%, 50%, 70%, 90% {
                transform: translateX(-5px);
            }
            20%, 40%, 60%, 80% {
                transform: translateX(5px);
            }
        }

        .error-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .error-list li {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .error-list li:last-child {
            margin-bottom: 0;
        }

        .error-list li i {
            font-size: 0.9rem;
        }

        .field-error {
            display: none;
            color: #fca5a5;
            font-size: 0.85rem;
            margin-top: 8px;
            font-weight: 500;
            align-items: center;
            gap: 6px;
            padding-right: 4px;
            animation: slideDown 0.2s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .field-error.show {
            display: flex;
        }

        .field-error i {
            font-size: 0.8rem;
            flex-shrink: 0;
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid rgba(148, 163, 184, 0.2);
        }

        .action-btn {
            flex: 1;
            padding: 14px 20px;
            border-radius: 12px;
            text-decoration: none;
            text-align: center;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 2px solid transparent;
        }

        .action-primary {
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            color: white;
            box-shadow: 0 4px 20px rgba(0, 217, 165, 0.3);
        }

        .action-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 217, 165, 0.4);
        }

        .action-secondary {
            background: rgba(30, 41, 59, 0.6);
            color: #e2e8f0;
            border: 2px solid rgba(148, 163, 184, 0.2);
        }

        .action-secondary:hover {
            background: rgba(30, 41, 59, 0.8);
            border-color: #00d9a5;
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 217, 165, 0.2);
            color: #00d9a5;
        }

        .action-btn i {
            font-size: 0.9rem;
        }

        .footer {
            text-align: center;
            padding: 30px;
            color: #94a3b8;
            border-top: 1px solid rgba(148, 163, 184, 0.1);
            margin-top: 50px;
            position: relative;
            z-index: 1;
        }

        .password-note {
            background: rgba(59, 130, 246, 0.15);
            backdrop-filter: blur(10px);
            padding: 18px;
            border-radius: 12px;
            margin-top: 25px;
            font-size: 0.9rem;
            color: #93c5fd;
            border: 1px solid rgba(59, 130, 246, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .password-note i {
            font-size: 1.1rem;
            color: #0369a1;
        }

        .password-note strong {
            color: #0c4a6e;
        }

        @media (max-width: 768px) {
            .header {
                padding: 25px 20px;
            }

            .header-top {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .user-info {
                flex-direction: column;
            }

            .nav-buttons {
                justify-content: center;
            }

            .container {
                padding: 0 15px;
            }

            .form-container {
                padding: 30px 25px;
            }

            .page-title {
                font-size: 1.8rem;
                flex-direction: column;
                gap: 10px;
            }

            .form-section-title {
                font-size: 1.1rem;
            }

            .form-actions {
                margin-top: 30px;
                padding-top: 20px;
            }

            .submit-btn {
                padding: 14px 20px;
                font-size: 0.95rem;
            }

            .actions {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- الهيدر -->
    <header class="header">
        <div class="header-top">
            <div class="logo">
                <h1><i class="fa-solid fa-seedling"></i> Beyond You</h1>
                <p>إنشاء حساب عميد</p>
            </div>
            
            <div class="user-info">
                <div class="user-welcome">
                    <div class="welcome">مرحباً بعودتك</div>
                    <div class="username"><?= htmlspecialchars($_SESSION['username']) ?></div>
                </div>
                
                <div class="nav-buttons">
                    <a href="dashboard_admin.php" class="nav-btn"><i class="fa-solid fa-house"></i> الرئيسية</a>
                    <a href="logout.php" class="nav-btn"><i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج</a>
                </div>
            </div>
        </div>
    </header>

    <!-- المحتوى الرئيسي -->
    <div class="container">
        <div class="form-container">
            <h1 class="page-title">
                <i class="fa-solid fa-user-plus"></i>
                <span>إنشاء حساب عميد</span>
            </h1>
            <p class="page-description">إضافة حسابات جديدة لعمداء الكليات وإدارة الصلاحيات</p>

            <?php if (!empty($errors)): ?>
                <div class="error">
                    <ul class="error-list">
                        <?php foreach ($errors as $error): ?>
                            <li>
                                <i class="fa-solid fa-circle-exclamation"></i>
                                <span><?= htmlspecialchars($error) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="message">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <form method="post" action="" id="createDeanForm">
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fa-solid fa-user-circle"></i>
                        <span>معلومات الحساب</span>
                    </div>

                    <div class="form-group">
                        <label for="username" class="form-label required">
                            <i class="fa-solid fa-user"></i>
                            <span>اسم المستخدم</span>
                        </label>
                        <input type="text" id="username" name="username" class="form-input" 
                               value="<?= isset($username) ? htmlspecialchars($username) : '' ?>" 
                               placeholder="أدخل اسم المستخدم للعميد">
                        <div class="field-error" id="usernameError">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>الرجاء إدخال اسم المستخدم</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label required">
                            <i class="fa-solid fa-lock"></i>
                            <span>كلمة المرور</span>
                        </label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" class="form-input" 
                                   placeholder="أدخل كلمة المرور">
                            <button type="button" class="password-toggle" id="passwordToggle" aria-label="إظهار/إخفاء كلمة المرور">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <div class="field-error" id="passwordError">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>الرجاء إدخال كلمة المرور</span>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fa-solid fa-building"></i>
                        <span>الكلية المرتبطة</span>
                    </div>

                    <div class="form-group">
                        <label for="faculty_id" class="form-label required">
                            <i class="fa-solid fa-building-columns"></i>
                            <span>اختر الكلية</span>
                        </label>
                        <select id="faculty_id" name="faculty_id" class="form-select">
                            <option value="0">-- اختر الكلية المرتبطة بالعميد --</option>
                            <?php foreach ($faculties as $faculty): ?>
                                <option value="<?= $faculty['id'] ?>" 
                                        <?= ((isset($faculty_id) && $faculty_id == $faculty['id']) || ($selected_faculty_id > 0 && $selected_faculty_id == $faculty['id'])) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($faculty['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="field-error" id="facultyError">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>الرجاء اختيار كلية مرتبطة بالعميد</span>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="submit-btn">
                        <i class="fa-solid fa-user-plus"></i>
                        <span>إنشاء حساب العميد</span>
                    </button>
                </div>
            </form>

            <div class="password-note">
                <i class="fa-solid fa-shield-halved"></i>
                <span><strong>ملاحظة أمان:</strong> كلمة المرور تُخزن مشفرة تلقائياً في قاعدة البيانات</span>
            </div>

            <div class="actions">
                <a href="dashboard_admin.php" class="action-btn action-secondary">
                    <i class="fa-solid fa-arrow-right"></i>
                    <span>العودة للرئيسية</span>
                </a>
                <a href="faculties.php" class="action-btn action-primary">
                    <i class="fa-solid fa-building"></i>
                    <span>عرض الكليات</span>
                </a>
            </div>
        </div>
    </div>

    <!-- الفوتر -->
    <div class="footer">
        نظام Beyond You - جامعة اليرموك © 2026
    </div>

    <script>
        // Password visibility toggle
        const passwordToggle = document.getElementById('passwordToggle');
        const passwordInput = document.getElementById('password');
        
        if (passwordToggle && passwordInput) {
            passwordToggle.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                const icon = this.querySelector('i');
                if (type === 'password') {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                } else {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            });
        }

        // Form validation
        const form = document.getElementById('createDeanForm');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                // Clear previous errors
                document.querySelectorAll('.form-input, .form-select').forEach(input => {
                    input.classList.remove('error-input');
                });
                document.querySelectorAll('.field-error').forEach(error => {
                    error.classList.remove('show');
                });

                let isValid = true;

                // Validate username
                const usernameInput = document.getElementById('username');
                if (!usernameInput.value.trim()) {
                    usernameInput.classList.add('error-input');
                    document.getElementById('usernameError').classList.add('show');
                    isValid = false;
                }

                // Validate password
                const passwordInput = document.getElementById('password');
                if (!passwordInput.value.trim()) {
                    passwordInput.classList.add('error-input');
                    document.getElementById('passwordError').classList.add('show');
                    isValid = false;
                }

                // Validate faculty
                const facultySelect = document.getElementById('faculty_id');
                if (!facultySelect.value || facultySelect.value == '0') {
                    facultySelect.classList.add('error-input');
                    document.getElementById('facultyError').classList.add('show');
                    isValid = false;
                }

                if (!isValid) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    
                    // Scroll to first error
                    const firstError = document.querySelector('.error-input');
                    if (firstError) {
                        firstError.focus();
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    return false;
                }

                // Show loading state
                const submitBtn = document.querySelector('.submit-btn');
                const submitBtnSpan = submitBtn.querySelector('span');
                const submitBtnIcon = submitBtn.querySelector('i');
                
                submitBtn.disabled = true;
                submitBtnIcon.className = 'fa-solid fa-spinner fa-spin';
                submitBtnSpan.textContent = 'جاري الإنشاء...';
            });

            // Clear errors when user types
            document.getElementById('username').addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('error-input');
                    document.getElementById('usernameError').classList.remove('show');
                }
            });

            document.getElementById('password').addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('error-input');
                    document.getElementById('passwordError').classList.remove('show');
                }
            });

            document.getElementById('faculty_id').addEventListener('change', function() {
                if (this.value && this.value != '0') {
                    this.classList.remove('error-input');
                    document.getElementById('facultyError').classList.remove('show');
                }
            });
        }
    </script>
</body>
</html>