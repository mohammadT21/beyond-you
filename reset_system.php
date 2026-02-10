<?php
// reset_system.php - تنظيف البيانات للشهر الجديد
require_once 'config.php';

// تحقق من جلسة المدير فقط
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reset'])) {
    $conn = db_connect();
    
    // 1. تنظيف بيانات السجلات - حذف جميع السجلات (تنظيف عام)
    $delete_records = "DELETE FROM records";
    if (mysqli_query($conn, $delete_records)) {
        $deleted_records = mysqli_affected_rows($conn);
    } else {
        $error = 'حدث خطأ في حذف سجلات البيانات';
    }
    
    $current_month = date('n');
    $current_year = date('Y');
    
    // 2. تنظيف التقارير القديمة (اختياري - حذف جميع التقارير)
    if (isset($_POST['delete_reports']) && $_POST['delete_reports'] == 'all') {
        $delete_all_reports = "DELETE FROM reports";
        if (mysqli_query($conn, $delete_all_reports)) {
            $deleted_all_reports = mysqli_affected_rows($conn);
        } else {
            $error = 'حدث خطأ في حذف جميع التقارير';
        }
    }
    
    // 3. تنظيف الملفات المرفوعة (اختياري)
    if (isset($_POST['delete_files'])) {
        $files = glob(UPLOAD_DIR . '/*');
        $deleted_files = 0;
        foreach($files as $file) {
            if(is_file($file)) {
                unlink($file);
                $deleted_files++;
            }
        }
    }
    
    if (!$error) {
        $message = "<i class='fa-solid fa-circle-check'></i> تم تنظيف النظام بنجاح!";
        if (isset($deleted_records)) {
            $message .= "<br><i class='fa-solid fa-trash'></i> تم حذف $deleted_records سجل بيانات (جميع السجلات)";
        }
        if (isset($deleted_all_reports) && $deleted_all_reports > 0) {
            $message .= "<br><i class='fa-solid fa-clipboard-list'></i> تم حذف $deleted_all_reports تقرير (جميع التقارير)";
        }
        if (isset($deleted_files)) {
            $message .= "<br><i class='fa-solid fa-paperclip'></i> تم حذف $deleted_files ملف";
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
    <title>تنظيف النظام - نظام Beyond You</title>
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
            margin-top: -150px;
            margin-left: -150px;
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
                opacity: 0.2;
            }
            50% {
                transform: translate(-45%, -55%) scale(1.15);
                opacity: 0.4;
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

        .header {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            color: white;
            padding: 20px 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
            position: relative;
            z-index: 1;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .logo h1 {
            font-size: 1.8rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo h1 i {
            font-size: 1.8rem;
        }

        .logo p {
            opacity: 0.9;
            font-size: 0.95rem;
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
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Tajawal', sans-serif;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .nav-btn i {
            font-size: 1rem;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 0 20px;
            position: relative;
            z-index: 1;
        }

        .warning-card {
            background: rgba(254, 243, 199, 0.1);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(245, 158, 11, 0.3);
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .warning-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #fbbf24;
        }

        .warning-icon i {
            font-size: 4rem;
        }

        .warning-title {
            color: #fbbf24;
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 15px;
        }

        .warning-description {
            color: #fcd34d;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .form-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(148, 163, 184, 0.1);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: rgba(15, 23, 42, 0.4);
            border-radius: 10px;
            border: 2px solid rgba(148, 163, 184, 0.2);
            transition: all 0.3s ease;
        }

        .checkbox-group:hover {
            border-color: #00d9a5;
            background: rgba(0, 217, 165, 0.1);
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: #009879;
        }

        .checkbox-label {
            font-weight: 600;
            color: #e2e8f0;
            flex: 1;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-label i {
            font-size: 1.1rem;
        }

        .checkbox-description {
            color: #94a3b8;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .danger-btn {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            font-family: 'Tajawal', sans-serif;
            margin-top: 10px;
        }

        .danger-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(220, 38, 38, 0.3);
        }

        .danger-btn:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .message {
            background: rgba(16, 185, 129, 0.15);
            backdrop-filter: blur(10px);
            color: #6ee7b7;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: right;
            font-weight: 600;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .message i {
            margin-left: 8px;
        }

        .error {
            background: rgba(239, 68, 68, 0.15);
            backdrop-filter: blur(10px);
            color: #fca5a5;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
            border: 1px solid rgba(239, 68, 68, 0.3);
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
            gap: 15px;
            margin-top: 25px;
        }

        .action-btn {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            text-decoration: none;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .action-btn i {
            font-size: 1rem;
        }

        .action-primary {
            background: #009879;
            color: white;
        }

        .action-secondary {
            background: rgba(30, 41, 59, 0.6);
            color: #e2e8f0;
            border: 2px solid rgba(148, 163, 184, 0.2);
        }

        .action-btn:hover {
            transform: translateY(-2px);
        }

        .confirmation-text {
            background: rgba(239, 68, 68, 0.1);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(239, 68, 68, 0.3);
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            text-align: center;
            font-weight: 600;
            color: #fca5a5;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .confirmation-text i {
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
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

            .actions {
                flex-direction: column;
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

    <!-- الهيدر -->
    <header class="header">
        <div class="header-top">
            <div class="logo">
                <h1><i class="fa-solid fa-seedling"></i> Beyond You</h1>
                <p>تنظيف النظام</p>
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
        <!-- تحذير -->
        <div class="warning-card">
            <div class="warning-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="warning-title">تنبيه مهم!</div>
            <div class="warning-description">
                هذه العملية ستحذف جميع بيانات مؤشرات الاستدامة (جميع الأشهر والسنوات).<br>
                <strong>لا يمكن التراجع عن هذه العملية بعد التنفيذ!</strong>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="message">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- نموذج التنظيف -->
        <div class="form-card">
            <form method="post" action="" id="resetForm">
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="delete_data" name="delete_data" checked disabled>
                        <div>
                            <label for="delete_data" class="checkbox-label"><i class="fa-solid fa-trash"></i> حذف بيانات مؤشرات الاستدامة</label>
                            <div class="checkbox-description">
                                حذف جميع سجلات البيانات (جميع الأشهر والسنوات) - تنظيف عام (إجباري)
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="delete_reports" name="delete_reports" value="all">
                        <div>
                            <label for="delete_reports" class="checkbox-label"><i class="fa-solid fa-clipboard-list"></i> حذف جميع التقارير (قديمة وجديدة)</label>
                            <div class="checkbox-description">
                                حذف جميع التقارير المرسلة من العمداء (سيتم حذف تقارير الشهر الحالي تلقائياً)
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="delete_files" name="delete_files">
                        <div>
                            <label for="delete_files" class="checkbox-label"><i class="fa-solid fa-paperclip"></i> حذف الملفات المرفوعة</label>
                            <div class="checkbox-description">
                                حذف جميع الملفات المرفوعة مع التقارير
                            </div>
                        </div>
                    </div>
                </div>

                <div class="confirmation-text" id="confirmationText" style="display: none;">
                    <i class="fa-solid fa-triangle-exclamation"></i> يرجى كتابة "نعم" للتأكيد: 
                    <input type="text" id="confirmText" style="padding: 8px; margin: 0 10px; border: 1px solid #dc2626; border-radius: 5px; text-align: center;" placeholder="اكتب 'نعم' هنا">
                </div>

                <button type="submit" class="danger-btn" id="resetBtn" name="confirm_reset" disabled>
                    <i class="fa-solid fa-bomb"></i> بدء عملية التنظيف
                </button>
            </form>

            <div class="actions">
                <a href="dashboard_admin.php" class="action-btn action-secondary"><i class="fa-solid fa-arrow-right"></i> العودة للرئيسية</a>
                <a href="sustainability_indicators.php" class="action-btn action-primary"><i class="fa-solid fa-chart-bar"></i> عرض المؤشرات</a>
            </div>
        </div>
    </div>

    <!-- الفوتر -->
    <div class="footer">
        نظام Beyond You - جامعة اليرموك © 2026
    </div>

    <script>
        // تفعيل/تعطيل زر التنظيف بناءً على التأكيد
        document.getElementById('delete_reports').addEventListener('change', showConfirmation);
        document.getElementById('delete_files').addEventListener('change', showConfirmation);
        document.getElementById('confirmText').addEventListener('input', checkConfirmation);

        function showConfirmation() {
            const deleteReports = document.getElementById('delete_reports').checked;
            const deleteFiles = document.getElementById('delete_files').checked;
            const confirmationText = document.getElementById('confirmationText');
            const resetBtn = document.getElementById('resetBtn');
            
            if (deleteReports || deleteFiles) {
                confirmationText.style.display = 'block';
                resetBtn.disabled = true;
            } else {
                confirmationText.style.display = 'none';
                // تفعيل الزر مباشرة لأن حذف البيانات إجباري
                resetBtn.disabled = false;
            }
        }

        function checkConfirmation() {
            const confirmText = document.getElementById('confirmText').value;
            const resetBtn = document.getElementById('resetBtn');
            
            if (confirmText === 'نعم') {
                resetBtn.disabled = false;
                resetBtn.innerHTML = '<i class="fa-solid fa-bomb"></i> تأكيد عملية التنظيف';
            } else {
                resetBtn.disabled = true;
                resetBtn.innerHTML = '<i class="fa-solid fa-bomb"></i> بدء عملية التنظيف';
            }
        }
        
        // تفعيل الزر عند تحميل الصفحة (لأن حذف البيانات إجباري)
        document.addEventListener('DOMContentLoaded', function() {
            const resetBtn = document.getElementById('resetBtn');
            resetBtn.disabled = false;
        });

        // تأكيد قبل التنفيذ
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const deleteReports = document.getElementById('delete_reports').checked;
            const deleteFiles = document.getElementById('delete_files').checked;
            
            let message = 'هل أنت متأكد من رغبتك في تنظيف النظام بالكامل؟\n• سيتم حذف جميع سجلات البيانات (جميع الأشهر والسنوات)';
            
            if (deleteReports) {
                message += '\n• سيتم حذف جميع التقارير';
            }
            if (deleteFiles) {
                message += '\n• سيتم حذف جميع الملفات المرفوعة';
            }
            
            if (!confirm(message + '\n\nهذه العملية لا يمكن التراجع عنها!')) {
                e.preventDefault();
            }
        });

        // إخفاء رسائل النجاح بعد 10 ثواني
        setTimeout(() => {
            const message = document.querySelector('.message');
            if (message) {
                message.style.display = 'none';
            }
        }, 10000);
    </script>
</body>
</html>