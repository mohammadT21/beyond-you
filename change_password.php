<?php
// change_password.php - صفحة تغيير كلمة المرور
require_once 'config.php';

// التحقق من الجلسة
if (!isset($_SESSION['user_role'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = db_connect();
    
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // التحقق من كلمة المرور الحالية
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT password FROM users WHERE id = $user_id";
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_assoc($result);
    
    if (!password_verify($current_password, $user['password'])) {
        $error = 'كلمة المرور الحالية غير صحيحة';
    } elseif ($new_password !== $confirm_password) {
        $error = 'كلمة المرور الجديدة غير متطابقة';
    } elseif (strlen($new_password) < 6) {
        $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    } else {
        // تحديث كلمة المرور
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE users SET password = '$new_password_hash' WHERE id = $user_id";
        
        if (mysqli_query($conn, $update_sql)) {
            $message = '<i class="fa-solid fa-circle-check"></i> تم تغيير كلمة المرور بنجاح';
            // تفريغ الحقول
            $_POST = [];
        } else {
            $error = '<i class="fa-solid fa-circle-exclamation"></i> حدث خطأ أثناء حفظ كلمة المرور الجديدة';
        }
    }
    
    db_close($conn);
}

$page_title = $_SESSION['user_role'] === 'admin' ? 'تغيير كلمة المرور - المدير' : 'تغيير كلمة المرور - العميد';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
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
            max-width: 500px;
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

        .user-badge {
            background: rgba(0, 217, 165, 0.15);
            backdrop-filter: blur(10px);
            color: #00d9a5;
            padding: 14px 24px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 30px;
            font-weight: 600;
            border: 1px solid rgba(0, 217, 165, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .user-badge i {
            font-size: 1.1rem;
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

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 14px 50px 14px 18px;
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

        .form-input:focus {
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

        .form-input.error-input {
            border-color: #ef4444;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.15);
        }

        .form-input.error-input:focus {
            border-color: #ef4444;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.2);
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

        .password-strength {
            margin-top: 8px;
            font-size: 0.9rem;
        }

        .strength-weak { color: #dc2626; }
        .strength-medium { color: #d97706; }
        .strength-strong { color: #059669; }

        .form-actions {
            margin-top: 35px;
            padding-top: 25px;
            border-top: 2px solid #f1f5f9;
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

        .submit-btn:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0, 217, 165, 0.4);
        }

        .submit-btn:hover:not(:disabled)::before {
            left: 100%;
        }

        .submit-btn:active:not(:disabled) {
            transform: translateY(-1px);
        }

        .submit-btn:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
            opacity: 0.7;
        }

        .submit-btn i {
            font-size: 1rem;
        }

        .submit-btn.loading {
            pointer-events: none;
        }

        .submit-btn.loading i:first-child {
            display: none;
        }

        .submit-btn.loading .fa-spinner {
            display: inline-block;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
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

        .error i {
            font-size: 1rem;
        }

        .field-error {
            display: none;
            color: #dc2626;
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

        .password-requirements {
            background: rgba(59, 130, 246, 0.15);
            backdrop-filter: blur(10px);
            padding: 22px;
            border-radius: 12px;
            margin-top: 30px;
            font-size: 0.9rem;
            color: #93c5fd;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .password-requirements h4 {
            margin-bottom: 15px;
            color: #bfdbfe;
            font-weight: 700;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .password-requirements h4 i {
            color: #60a5fa;
        }

        .requirements-list {
            list-style: none;
            padding-right: 0;
        }

        .requirements-list li {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .requirement-met {
            color: #059669;
        }

        .requirement-unmet {
            color: #dc2626;
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
                <p>تغيير كلمة المرور</p>
            </div>
            
            <div class="user-info">
                <div class="user-welcome">
                    <div class="welcome">مرحباً بعودتك</div>
                    <div class="username"><?= htmlspecialchars($_SESSION['username']) ?></div>
                </div>
                
                <div class="nav-buttons">
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <a href="dashboard_admin.php" class="nav-btn"><i class="fa-solid fa-house"></i> الرئيسية</a>
                    <?php else: ?>
                        <a href="dashboard_dean.php" class="nav-btn"><i class="fa-solid fa-house"></i> الرئيسية</a>
                    <?php endif; ?>
                    <a href="logout.php" class="nav-btn"><i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج</a>
                </div>
            </div>
        </div>
    </header>

    <!-- المحتوى الرئيسي -->
    <div class="container">
        <div class="form-container">
            <h1 class="page-title">
                <i class="fa-solid fa-lock"></i>
                <span>تغيير كلمة المرور</span>
            </h1>
            <p class="page-description">قم بتحديث كلمة المرور الخاصة بحسابك للحفاظ على الأمان</p>

            <div class="user-badge">
                <i class="fa-solid fa-user"></i>
                <span><?= htmlspecialchars($_SESSION['username']) ?> - <?= $_SESSION['user_role'] === 'admin' ? 'المدير العام' : 'عميد الكلية' ?></span>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="message">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <form method="post" action="" id="passwordForm">
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fa-solid fa-key"></i>
                        <span>معلومات كلمة المرور</span>
                    </div>

                    <div class="form-group">
                        <label for="current_password" class="form-label required">
                            <i class="fa-solid fa-lock"></i>
                            <span>كلمة المرور الحالية</span>
                        </label>
                        <div class="input-wrapper">
                            <input type="password" id="current_password" name="current_password" class="form-input" 
                                   placeholder="أدخل كلمة المرور الحالية">
                            <button type="button" class="password-toggle" id="currentPasswordToggle" aria-label="إظهار/إخفاء كلمة المرور">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <div class="field-error" id="currentPasswordError">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>الرجاء إدخال كلمة المرور الحالية</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_password" class="form-label required">
                            <i class="fa-solid fa-lock"></i>
                            <span>كلمة المرور الجديدة</span>
                        </label>
                        <div class="input-wrapper">
                            <input type="password" id="new_password" name="new_password" class="form-input" 
                                   placeholder="أدخل كلمة المرور الجديدة" minlength="6">
                            <button type="button" class="password-toggle" id="newPasswordToggle" aria-label="إظهار/إخفاء كلمة المرور">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength"></div>
                        <div class="field-error" id="newPasswordError">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>كلمة المرور يجب أن تكون 6 أحرف على الأقل</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label required">
                            <i class="fa-solid fa-check-double"></i>
                            <span>تأكيد كلمة المرور الجديدة</span>
                        </label>
                        <div class="input-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                                   placeholder="أعد إدخال كلمة المرور الجديدة">
                            <button type="button" class="password-toggle" id="confirmPasswordToggle" aria-label="إظهار/إخفاء كلمة المرور">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordMatch"></div>
                        <div class="field-error" id="confirmPasswordError">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>كلمة المرور غير متطابقة</span>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="submit-btn" id="submitBtn">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>حفظ كلمة المرور الجديدة</span>
                        <i class="fa-solid fa-spinner" style="display: none;"></i>
                    </button>
                </div>
            </form>

            <div class="password-requirements">
                <h4>
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>متطلبات كلمة المرور:</span>
                </h4>
                <ul class="requirements-list">
                    <li id="reqLength">
                        <i class="fa-solid fa-circle-check requirement-unmet" id="lengthIcon"></i>
                        <span>6 أحرف على الأقل</span>
                    </li>
                    <li id="reqMatch">
                        <i class="fa-solid fa-circle-check requirement-unmet" id="matchIcon"></i>
                        <span>كلمة المرور متطابقة</span>
                    </li>
                </ul>
            </div>

            <div class="actions">
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="dashboard_admin.php" class="action-btn action-secondary">
                        <i class="fa-solid fa-arrow-right"></i>
                        <span>العودة للرئيسية</span>
                    </a>
                <?php else: ?>
                    <a href="dashboard_dean.php" class="action-btn action-secondary">
                        <i class="fa-solid fa-arrow-right"></i>
                        <span>العودة للرئيسية</span>
                    </a>
                <?php endif; ?>
                <a href="logout.php" class="action-btn action-primary">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>تسجيل الخروج</span>
                </a>
            </div>
        </div>
    </div>

    <!-- الفوتر -->
    <div class="footer">
        نظام Beyond You - جامعة اليرموك © 2026
    </div>

    <script>
        // Password visibility toggles
        function setupPasswordToggle(inputId, toggleId) {
            const toggle = document.getElementById(toggleId);
            const input = document.getElementById(inputId);
            
            if (toggle && input) {
                toggle.addEventListener('click', function() {
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    
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
        }

        setupPasswordToggle('current_password', 'currentPasswordToggle');
        setupPasswordToggle('new_password', 'newPasswordToggle');
        setupPasswordToggle('confirm_password', 'confirmPasswordToggle');

        // التحقق من قوة كلمة المرور
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthText = document.getElementById('passwordStrength');
            const lengthIcon = document.getElementById('lengthIcon');
            const newPasswordError = document.getElementById('newPasswordError');
            
            // التحقق من الطول
            if (password.length >= 6) {
                lengthIcon.className = 'fa-solid fa-circle-check requirement-met';
                this.classList.remove('error-input');
                newPasswordError.classList.remove('show');
            } else if (password.length > 0) {
                lengthIcon.className = 'fa-solid fa-circle-xmark requirement-unmet';
                this.classList.add('error-input');
                if (password.length < 6) {
                    newPasswordError.classList.add('show');
                }
            } else {
                lengthIcon.className = 'fa-solid fa-circle-check requirement-unmet';
                this.classList.remove('error-input');
                newPasswordError.classList.remove('show');
            }
            
            // تحديد قوة كلمة المرور
            if (password.length === 0) {
                strengthText.textContent = '';
                strengthText.className = 'password-strength';
            } else if (password.length < 6) {
                strengthText.textContent = 'ضعيفة';
                strengthText.className = 'password-strength strength-weak';
            } else if (password.length < 8) {
                strengthText.textContent = 'متوسطة';
                strengthText.className = 'password-strength strength-medium';
            } else {
                strengthText.textContent = 'قوية';
                strengthText.className = 'password-strength strength-strong';
            }
            
            checkFormValidity();
        });

        // التحقق من تطابق كلمة المرور
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            const matchText = document.getElementById('passwordMatch');
            const matchIcon = document.getElementById('matchIcon');
            const confirmPasswordError = document.getElementById('confirmPasswordError');
            
            if (confirmPassword.length === 0) {
                matchText.textContent = '';
                matchText.className = 'password-strength';
                matchIcon.className = 'fa-solid fa-circle-check requirement-unmet';
                this.classList.remove('error-input');
                confirmPasswordError.classList.remove('show');
            } else if (newPassword === confirmPassword) {
                matchText.textContent = 'كلمة المرور متطابقة';
                matchText.className = 'password-strength strength-strong';
                matchIcon.className = 'fa-solid fa-circle-check requirement-met';
                this.classList.remove('error-input');
                confirmPasswordError.classList.remove('show');
            } else {
                matchText.textContent = 'كلمة المرور غير متطابقة';
                matchText.className = 'password-strength strength-weak';
                matchIcon.className = 'fa-solid fa-circle-xmark requirement-unmet';
                this.classList.add('error-input');
                confirmPasswordError.classList.add('show');
            }
            
            checkFormValidity();
        });

        // التحقق من صحة النموذج
        function checkFormValidity() {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const submitBtn = document.getElementById('submitBtn');
            
            const isCurrentPasswordValid = currentPassword.length > 0;
            const isLengthValid = newPassword.length >= 6;
            const isMatchValid = newPassword === confirmPassword && confirmPassword.length > 0;
            
            submitBtn.disabled = !(isCurrentPasswordValid && isLengthValid && isMatchValid);
        }

        // Form validation on submit
        const form = document.getElementById('passwordForm');
        form.addEventListener('submit', function(e) {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Clear previous errors
            document.querySelectorAll('.form-input').forEach(input => {
                input.classList.remove('error-input');
            });
            document.querySelectorAll('.field-error').forEach(error => {
                error.classList.remove('show');
            });

            let isValid = true;

            // Validate current password
            if (!currentPassword.trim()) {
                document.getElementById('current_password').classList.add('error-input');
                document.getElementById('currentPasswordError').classList.add('show');
                isValid = false;
            }

            // Validate new password
            if (!newPassword.trim() || newPassword.length < 6) {
                document.getElementById('new_password').classList.add('error-input');
                document.getElementById('newPasswordError').classList.add('show');
                isValid = false;
            }

            // Validate confirm password
            if (!confirmPassword.trim() || newPassword !== confirmPassword) {
                document.getElementById('confirm_password').classList.add('error-input');
                document.getElementById('confirmPasswordError').classList.add('show');
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
            const submitBtn = document.getElementById('submitBtn');
            const submitBtnSpan = submitBtn.querySelector('span');
            const submitBtnIcon = submitBtn.querySelector('.fa-floppy-disk');
            const submitBtnSpinner = submitBtn.querySelector('.fa-spinner');
            
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
            submitBtnIcon.style.display = 'none';
            submitBtnSpinner.style.display = 'inline-block';
            submitBtnSpan.textContent = 'جاري الحفظ...';
        });

        // Clear errors when user types
        document.getElementById('current_password').addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('error-input');
                document.getElementById('currentPasswordError').classList.remove('show');
            }
        });
    </script>
</body>
</html>