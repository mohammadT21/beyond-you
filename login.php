<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'CONFIG.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = db_connect();

    // قراءة البيانات مع تنظيفها
    $username = clean_input($_POST['username'] ?? '', $conn);
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['usertype'] ?? 'admin'; // admin أو dean

    if ($username === '' || $password === '') {
        $error = 'يرجى إدخال اسم المستخدم وكلمة المرور.';
    } else {
        // استعلام لجلب المستخدم حسب الاسم والدور
$sql = "SELECT * FROM users WHERE username = ? AND role = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ss', $username, $user_type);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) === 1) {
                $user = mysqli_fetch_assoc($result);

                // تحقق كلمة المرور (توقّع تخزينها باستخدام password_hash)
                if (password_verify($password, $user['password'])) {
                    // حفظ بيانات الجلسة
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['username']  = $user['username'];
                    $_SESSION['user_role'] = $user['role'];

                    if ($user['role'] === 'dean' && isset($user['faculty_id'])) {
                        $_SESSION['faculty_id'] = $user['faculty_id'];
                    }

                    // توجيه حسب الدور
                    if ($user['role'] === 'admin') {
                        header('Location: dashboard_admin.php');
                    } else {
                        header('Location: dashboard_dean.php');
                    }
                    exit;
                } else {
                    $error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
                }
            } else {
                $error = 'اسم المستخدم أو الدور غير صحيح.';
            }

            mysqli_stmt_close($stmt);
        } else {
            $error = 'حدث خطأ في النظام أثناء معالجة الطلب.';
            error_log('DB prepare error (login): ' . mysqli_error($conn));
        }
    }

    db_close($conn);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - Beyond You</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Subtle animated grid pattern */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="100" height="100" patternUnits="userSpaceOnUse"><path d="M 100 0 L 0 0 0 100" fill="none" stroke="rgba(148,163,184,0.03)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.5;
            pointer-events: none;
            z-index: 0;
            animation: gridFloat 25s ease-in-out infinite;
        }

        @keyframes gridFloat {
            0%, 100% {
                transform: translate(0, 0);
            }
            50% {
                transform: translate(30px, 30px);
            }
        }

        /* Animated gradient overlay */
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

        /* Floating animated orbs - clean and minimal */
        .bg-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(100px);
            pointer-events: none;
            z-index: 0;
            opacity: 0.4;
        }

        .bg-orb-1 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(0, 217, 165, 0.15), transparent 70%);
            top: -150px;
            right: -150px;
            animation: orbFloat1 20s ease-in-out infinite;
        }

        .bg-orb-2 {
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15), transparent 70%);
            bottom: -100px;
            left: -100px;
            animation: orbFloat2 25s ease-in-out infinite;
            animation-delay: 5s;
        }

        .bg-orb-3 {
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(0, 152, 121, 0.1), transparent 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation: orbFloat3 30s ease-in-out infinite;
            animation-delay: 10s;
        }

        @keyframes orbFloat1 {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            33% {
                transform: translate(-50px, 80px) scale(1.1);
            }
            66% {
                transform: translate(50px, -50px) scale(0.9);
            }
        }

        @keyframes orbFloat2 {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            33% {
                transform: translate(60px, -80px) scale(1.15);
            }
            66% {
                transform: translate(-40px, 60px) scale(0.95);
            }
        }

        @keyframes orbFloat3 {
            0%, 100% {
                transform: translate(-50%, -50%) scale(1);
                opacity: 0.3;
            }
            50% {
                transform: translate(-45%, -55%) scale(1.2);
                opacity: 0.5;
            }
        }

        /* Subtle geometric wave shapes */
        .bg-wave {
            position: fixed;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            opacity: 0.1;
        }

        .bg-wave-1 {
            background: 
                linear-gradient(135deg, transparent 0%, rgba(0, 217, 165, 0.1) 50%, transparent 100%);
            clip-path: polygon(0 20%, 100% 10%, 100% 30%, 0 40%);
            animation: waveMove1 15s ease-in-out infinite;
        }

        .bg-wave-2 {
            background: 
                linear-gradient(45deg, transparent 0%, rgba(99, 102, 241, 0.1) 50%, transparent 100%);
            clip-path: polygon(0 70%, 100% 60%, 100% 80%, 0 90%);
            animation: waveMove2 18s ease-in-out infinite;
            animation-delay: 3s;
        }

        @keyframes waveMove1 {
            0%, 100% {
                transform: translateX(0);
            }
            50% {
                transform: translateX(20px);
            }
        }

        @keyframes waveMove2 {
            0%, 100% {
                transform: translateX(0);
            }
            50% {
                transform: translateX(-25px);
            }
        }
        .login-container {
            display: flex;
            width: 100%;
            max-width: 1000px;
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(148, 163, 184, 0.1);
            overflow: hidden;
            position: relative;
            z-index: 1;
            animation: slideUp 0.8s ease-out;
        }

        .login-container::before {
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

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }
            100% {
                background-position: 200% 0;
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, rgba(0, 152, 121, 0.2) 0%, rgba(99, 102, 241, 0.2) 100%);
            backdrop-filter: blur(10px);
            color: #fff;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
            border-left: 1px solid rgba(148, 163, 184, 0.1);
        }
        .login-left::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        .login-left::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite reverse;
        }
        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            50% {
                transform: translate(30px, -30px) scale(1.1);
            }
        }
        .logo {
            text-align: center;
            margin-bottom: 40px;
        }
        .logo {
            position: relative;
            z-index: 2;
        }
        .logo h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            animation: fadeInDown 1s ease-out;
        }
        .logo h1 i {
            font-size: 2.2rem;
            animation: pulse 2s ease-in-out infinite;
        }
        .logo p {
            font-size: 1.1rem;
            opacity: 0.9;
            animation: fadeInDown 1s ease-out 0.2s both;
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
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }
        .stats {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 15px;
            position: relative;
            z-index: 2;
            animation: fadeInUp 1s ease-out 0.4s both;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .stat-item {
            text-align: center;
            flex: 1;
            transition: transform 0.3s ease;
        }
        .stat-item:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 5px;
            background: linear-gradient(135deg, #fff 0%, rgba(255, 255, 255, 0.8) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .stat-label {
            font-size: 0.8rem;
            opacity: 0.9;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .features {
            list-style: none;
            margin-top: 20px;
            position: relative;
            z-index: 2;
            animation: fadeInUp 1s ease-out 0.6s both;
        }
        .features li {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            font-size: 1rem;
            transition: transform 0.3s ease, padding-right 0.3s ease;
            padding-right: 5px;
        }
        .features li:hover {
            transform: translateX(-5px);
            padding-right: 10px;
        }
        .features li i {
            background: rgba(255, 255, 255, 0.25);
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 12px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .features li:hover i {
            background: rgba(255, 255, 255, 0.35);
            transform: scale(1.1);
        }
        .university-badge {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 18px;
            border-radius: 12px;
            margin-top: 30px;
            position: relative;
            z-index: 2;
            animation: fadeInUp 1s ease-out 0.8s both;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: transform 0.3s ease;
        }
        .university-badge:hover {
            transform: translateY(-3px);
            background: rgba(255, 255, 255, 0.25);
        }
        .uni-logo {
            font-size: 2.5rem;
            margin-left: 15px;
            animation: bounce 2s ease-in-out infinite;
        }
        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }
        .uni-info {
            display: flex;
            flex-direction: column;
        }
        .uni-info strong {
            font-size: 1.1rem;
        }
        .uni-info span {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .login-right {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            animation: fadeInRight 0.8s ease-out 0.3s both;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(10px);
        }
        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .login-header h2 {
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .login-header p {
            color: #94a3b8;
            font-size: 1rem;
        }
        .user-type-selector {
            display: flex;
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 5px;
            margin-bottom: 30px;
            position: relative;
            border: 2px solid rgba(148, 163, 184, 0.2);
            transition: border-color 0.3s ease;
        }
        .user-type-selector:focus-within {
            border-color: #00d9a5;
        }
        .user-type {
            flex: 1;
            text-align: center;
            padding: 14px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            position: relative;
        }
        .user-type i {
            font-size: 1.1rem;
            transition: transform 0.3s ease;
        }
        .user-type:hover {
            background: rgba(0, 217, 165, 0.1);
            color: #00d9a5;
        }
        .user-type.active {
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            color: #fff;
            box-shadow: 0 4px 20px rgba(0, 217, 165, 0.3);
            transform: translateY(-2px);
        }
        .user-type.active i {
            transform: scale(1.2);
        }
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            color: #e2e8f0;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .form-group label i {
            color: #00d9a5;
            font-size: 1rem;
        }
        .input-wrapper {
            position: relative;
        }
        .form-group input {
            width: 100%;
            padding: 14px 18px 14px 50px;
            border: 2px solid rgba(148, 163, 184, 0.2);
            border-radius: 12px;
            font-size: 1rem;
            font-family: 'Tajawal', sans-serif;
            transition: all 0.3s ease;
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(10px);
            color: #e2e8f0;
        }
        .form-group input::placeholder {
            color: #64748b;
        }
        .form-group input:focus {
            outline: none;
            border-color: #00d9a5;
            box-shadow: 0 0 0 4px rgba(0, 217, 165, 0.1);
            background: rgba(30, 41, 59, 0.8);
        }
        .input-icon {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            pointer-events: none;
            font-size: 1rem;
        }
        .input-wrapper:focus-within .input-icon {
            color: #00d9a5;
        }
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
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
        .form-group input[type="password"] {
            padding-right: 50px;
        }
        .form-group input[type="text"]:not([name="password"]) {
            padding-right: 50px;
        }
        .form-group input.error {
            border-color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
        }
        .form-group input.error:focus {
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.2);
        }
        .login-btn {
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            color: #fff;
            border: none;
            padding: 16px 24px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Tajawal', sans-serif;
            margin-top: 10px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 217, 165, 0.3);
        }
        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0, 217, 165, 0.4);
        }
        .login-btn:hover::before {
            left: 100%;
        }
        .login-btn:active {
            transform: translateY(-1px);
        }
        .login-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        .login-btn i {
            font-size: 1rem;
        }
        .login-btn.loading i {
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
        .error-message {
            background: rgba(239, 68, 68, 0.15);
            backdrop-filter: blur(10px);
            color: #fca5a5;
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
            border: 1px solid rgba(239, 68, 68, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            animation: shake 0.5s ease-out;
            box-shadow: 0 2px 15px rgba(239, 68, 68, 0.2);
        }
        .error-message i {
            font-size: 1.1rem;
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
        .demo-accounts {
            background: rgba(0, 217, 165, 0.1);
            backdrop-filter: blur(10px);
            padding: 18px;
            border-radius: 12px;
            margin-top: 25px;
            font-size: 0.9rem;
            border: 1px solid rgba(0, 217, 165, 0.2);
            animation: fadeInUp 1s ease-out 1s both;
        }
        .demo-accounts h4 {
            color: #00d9a5;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
        }
        .demo-accounts h4 i {
            font-size: 1.1rem;
        }
        .demo-accounts div {
            margin-bottom: 6px;
            color: #94a3b8;
            font-weight: 500;
        }
        .demo-accounts strong {
            color: #00d9a5;
        }
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            .login-container {
                flex-direction: column;
                border-radius: 20px;
            }
            .login-left,
            .login-right {
                padding: 35px 25px;
            }
            .logo h1 {
                font-size: 2rem;
                gap: 10px;
            }
            .logo h1 i {
                font-size: 1.8rem;
            }
            .stats {
                flex-direction: row;
                gap: 10px;
                padding: 20px 15px;
            }
            .stat-number {
                font-size: 1.5rem;
            }
            .stat-label {
                font-size: 0.75rem;
            }
            .features li {
                font-size: 0.9rem;
                margin-bottom: 12px;
            }
            .login-header h2 {
                font-size: 1.6rem;
            }
            .user-type {
                padding: 12px 10px;
                font-size: 0.9rem;
            }
            .user-type i {
                font-size: 1rem;
            }
            .form-group input {
                padding: 14px 18px;
            }
            .login-btn {
                padding: 14px 20px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Background Animation Elements -->
    <div class="bg-orb bg-orb-1"></div>
    <div class="bg-orb bg-orb-2"></div>
    <div class="bg-orb bg-orb-3"></div>
    <div class="bg-wave bg-wave-1"></div>
    <div class="bg-wave bg-wave-2"></div>

<div class="login-container">
    <!-- الجزء الأيسر التعريفي -->
    <div class="login-left">
        <div class="logo">
            <h1><i class="fa-solid fa-seedling"></i> Beyond You</h1>
            <p>الريادة في التحول البيئي الذكي</p>
        </div>
        <div class="stats">
            <div class="stat-item">
                <div class="stat-number">16</div>
                <div class="stat-label">كلية جامعية</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">10</div>
                <div class="stat-label">مؤشر استدامة</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">100%</div>
                <div class="stat-label">تحول رقمي</div>
            </div>
        </div>
        <ul class="features">
            <li><i class="fa-solid fa-check"></i> لوحات تحكم تفاعلية حية</li>
            <li><i class="fa-solid fa-check"></i> تحليل أداء بيئي دقيق</li>
            <li><i class="fa-solid fa-check"></i> تقارير ذكية تلقائية</li>
            <li><i class="fa-solid fa-check"></i> تحفيز المنافسة الخضراء بين الكليات</li>
        </ul>
        <div class="university-badge">
            <div class="uni-logo"><i class="fa-solid fa-graduation-cap"></i></div>
            <div class="uni-info">
                <strong>جامعة اليرموك</strong>
                <span>نحو حرم جامعي مستدام</span>
            </div>
        </div>
    </div>

    <!-- الجزء الأيمن لتسجيل الدخول -->
    <div class="login-right">
        <div class="login-header">
            <h2 id="welcomeText">مرحباً بعودتك</h2>
            <p id="loginDescription">سجل الدخول لإدارة نظام الاستدامة</p>
        </div>

        <div class="user-type-selector">
            <div class="user-type active" data-type="admin">
                <i class="fa-solid fa-user-tie"></i>
                <span>المدير العام</span>
            </div>
            <div class="user-type" data-type="dean">
                <i class="fa-solid fa-graduation-cap"></i>
                <span>عميد الكلية</span>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message" id="errorMessage">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <input type="hidden" name="usertype" id="userType" value="admin">

            <div class="form-group">
                <label for="username"><i class="fa-solid fa-user"></i> اسم المستخدم</label>
                <div class="input-wrapper">
                    <input type="text" id="username" name="username" required
                           placeholder="ادخل اسم المستخدم">
                </div>
            </div>

            <div class="form-group">
                <label for="password"><i class="fa-solid fa-lock"></i> كلمة المرور</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" required
                           placeholder="ادخل كلمة المرور">
                    <button type="button" class="password-toggle" id="passwordToggle" aria-label="إظهار/إخفاء كلمة المرور">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="login-btn" id="loginButton">
                <i class="fa-solid fa-right-to-bracket"></i>
                <span>تسجيل الدخول</span>
            </button>
        </form>
       <!--
        <div class="demo-accounts">
            <h4><i class="fa-solid fa-key"></i> حسابات تجريبية:</h4>
            <div><strong>المدير العام:</strong> admin / Admin1234</div>
            <div><strong>عميد الهندسة:</strong> dean_engineering / Dean123@</div>
            <div><strong>عميد الطب:</strong> dean_medicine / Dean123@</div>
        </div>
		-->
    </div>
</div>

<script>
    // تبديل نوع المستخدم
    document.querySelectorAll('.user-type').forEach(function (el) {
        el.addEventListener('click', function () {
            document.querySelectorAll('.user-type').forEach(function (item) {
                item.classList.remove('active');
            });
            this.classList.add('active');

            const userType = this.getAttribute('data-type');
            document.getElementById('userType').value = userType;

            const welcomeText = document.getElementById('welcomeText');
            const loginDesc = document.getElementById('loginDescription');
            const loginBtn = document.getElementById('loginButton');
            const loginBtnSpan = loginBtn.querySelector('span');

            if (userType === 'admin') {
                welcomeText.textContent = 'مرحباً بعودتك';
                loginDesc.textContent = 'سجل الدخول لإدارة نظام الاستدامة على مستوى الجامعة.';
                loginBtnSpan.textContent = 'تسجيل الدخول كمدير عام';
            } else {
                welcomeText.textContent = 'مرحباً بعودتك يا عميد';
                loginDesc.textContent = 'سجل الدخول لمتابعة مؤشرات الاستدامة الخاصة بكليتك.';
                loginBtnSpan.textContent = 'تسجيل الدخول كعميد كلية';
            }
        });
    });

    // تبديل إظهار/إخفاء كلمة المرور
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

    // Form validation and loading state
    const loginForm = document.querySelector('form');
    const loginBtn = document.getElementById('loginButton');
    
    if (loginForm && loginBtn) {
        loginForm.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            // Remove previous errors
            document.getElementById('username').classList.remove('error');
            document.getElementById('password').classList.remove('error');
            
            let hasError = false;
            
            if (!username) {
                document.getElementById('username').classList.add('error');
                hasError = true;
            }
            
            if (!password) {
                document.getElementById('password').classList.add('error');
                hasError = true;
            }
            
            if (hasError) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            loginBtn.disabled = true;
            loginBtn.classList.add('loading');
            const loginBtnSpan = loginBtn.querySelector('span');
            const originalText = loginBtnSpan.textContent;
            loginBtnSpan.textContent = 'جاري تسجيل الدخول...';
            
            // If form submission fails, re-enable button after 2 seconds
            setTimeout(function() {
                loginBtn.disabled = false;
                loginBtn.classList.remove('loading');
                loginBtnSpan.textContent = originalText;
            }, 2000);
        });
    }

    // إخفاء رسالة الخطأ بعد 5 ثواني
    setTimeout(function () {
        const errorMsg = document.getElementById('errorMessage');
        if (errorMsg) {
            errorMsg.style.opacity = '0';
            errorMsg.style.transform = 'translateY(-10px)';
            errorMsg.style.transition = 'all 0.3s ease';
            setTimeout(function() {
                errorMsg.style.display = 'none';
            }, 300);
        }
    }, 5000);

    // Remove error class on input
    document.getElementById('username').addEventListener('input', function() {
        this.classList.remove('error');
    });
    
    document.getElementById('password').addEventListener('input', function() {
        this.classList.remove('error');
    });
</script>
</body>
</html>
