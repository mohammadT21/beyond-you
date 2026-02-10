<?php
// dashboard_dean.php - صفحة العميد (إدخال البيانات + رفع التقارير)
require_once 'config.php';

// التحقق من الجلسة (العميد فقط)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'dean') {
    header('Location: login.php');
    exit;
}

$conn = db_connect();
$faculty_id = intval($_SESSION['faculty_id']);

// جلب اسم الكلية المرتبطة بالعميد
$sql_faculty = "SELECT name FROM faculties WHERE id = $faculty_id";
$result_faculty = mysqli_query($conn, $sql_faculty);
$faculty = mysqli_fetch_assoc($result_faculty);
$faculty_name = $faculty ? $faculty['name'] : 'كلية غير معروفة';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $month  = intval($_POST['month']);
    $year   = intval($_POST['year']);
    $values = isset($_POST['indicator']) ? $_POST['indicator'] : [];

    // 1) التحقق أن جميع القيم مُدخَلة وليست فارغة
    $allFilled = true;
    foreach ($values as $indicator_id => $value) {
        if (trim($value) === '' || $value === null) {
            $allFilled = false;
            break;
        }
    }

    if (!$allFilled) {
        // رسالة خطأ ولا يتم الحفظ
        $message = '<i class="fa-solid fa-triangle-exclamation"></i> يجب إدخال جميع قيم المؤشرات قبل الحفظ.';
    } else {
        // 2) جميع الحقول مليانة، نبدأ الحفظ
        foreach ($values as $indicator_id => $value) {
            $numValue = floatval($value);

            // ممكن تضيفي تحقق أن القيمة ليست سالبة
            if ($numValue < 0) {
                $message = '<i class="fa-solid fa-triangle-exclamation"></i> لا يمكن إدخال قيم سالبة.';
                // ممكن هنا تعملي break لو حابة توقف الحفظ
                continue;
            }

            // حذف أي بيانات قديمة لنفس الشهر والمؤشر
            $delete_sql = "DELETE FROM records 
                           WHERE faculty_id = $faculty_id 
                             AND indicator_id = $indicator_id 
                             AND month = $month 
                             AND year = $year";
            mysqli_query($conn, $delete_sql);

            // إدخال البيانات الجديدة
            $insert_sql = "INSERT INTO records (faculty_id, indicator_id, value, month, year) 
                           VALUES ($faculty_id, $indicator_id, $numValue, $month, $year)";
            mysqli_query($conn, $insert_sql);
        }

        // رسالة نجاح بعد إتمام الحفظ
        if (!isset($message) || $message === '') {
            $message = '<i class="fa-solid fa-circle-check"></i> تم حفظ بيانات مؤشرات الاستدامة بنجاح لشهر ' . $month . '/' . $year;
            $data_saved = true; // علامة أن البيانات تم حفظها
        }
    }
}

$data_saved = isset($data_saved) ? $data_saved : false;
$saved_month = isset($_POST['month']) ? intval($_POST['month']) : null;
$saved_year = isset($_POST['year']) ? intval($_POST['year']) : null;

// جلب مؤشرات الاستدامة
$indicators_sql = "SELECT * FROM indicators";
$indicators_result = mysqli_query($conn, $indicators_sql);
$indicators = [];
while ($row = mysqli_fetch_assoc($indicators_result)) {
    $indicators[] = $row;
}

// جلب التوصيات المحفوظة للكلية إذا كانت موجودة
$faculty_recommendations = null;
if ($data_saved && $saved_month && $saved_year) {
    require_once 'gemini_ai.php';
    $faculty_recommendations = get_saved_recommendations($conn, $saved_month, $saved_year, $faculty_id);
}

db_close($conn);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدخال البيانات - نظام Beyond You</title>
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
                radial-gradient(at 0% 0%, rgba(0, 152, 121, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(99, 102, 241, 0.15) 0px, transparent 50%);
            color: #e2e8f0;
            min-height: 100vh;
            position: relative;
        }

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
            background: radial-gradient(circle, rgba(0, 217, 165, 0.1) 0%, transparent 70%);
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
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
            background: rgba(0, 217, 165, 0.2);
            color: #00d9a5;
            border: 1px solid rgba(0, 217, 165, 0.3);
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-family: 'Tajawal', sans-serif;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            backdrop-filter: blur(10px);
        }

        .nav-btn:hover {
            background: rgba(0, 217, 165, 0.3);
            border-color: rgba(0, 217, 165, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 217, 165, 0.3);
        }

        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
            position: relative;
            z-index: 1;
        }

        .welcome-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            padding: 50px 40px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            margin-bottom: 40px;
            text-align: center;
            border: 1px solid rgba(148, 163, 184, 0.1);
            animation: fadeInDown 0.8s ease-out;
            position: relative;
            overflow: hidden;
        }

        .welcome-card::before {
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

        .welcome-card::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0, 217, 165, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
            pointer-events: none;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
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

        .welcome-card h2 {
            background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.5rem;
            margin-bottom: 15px;
            font-weight: 800;
            position: relative;
            z-index: 1;
            letter-spacing: -0.02em;
        }

        .welcome-card p {
            color: #94a3b8;
            font-size: 1.2rem;
            line-height: 1.8;
            position: relative;
            z-index: 1;
        }

        .form-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            margin-bottom: 30px;
            border: 1px solid rgba(148, 163, 184, 0.1);
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
            letter-spacing: -0.02em;
        }

        .section-title i {
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.8rem;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            color: #cbd5e1;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: -0.01em;
        }

        .form-label i {
            margin-left: 8px;
            color: #00d9a5;
        }

        .section-title i, .message i, .nav-btn i, .submit-btn i {
            margin-left: 8px;
        }

        .action-icon i {
            margin: 0;
        }

        .form-input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid rgba(148, 163, 184, 0.2);
            border-radius: 12px;
            font-size: 0.95rem;
            font-family: 'Tajawal', sans-serif;
            transition: all 0.3s ease;
            background: rgba(15, 23, 42, 0.4);
            color: #e2e8f0;
            line-height: 1.5;
            backdrop-filter: blur(10px);
        }

        .form-input:focus {
            outline: none;
            border-color: #00d9a5;
            box-shadow: 0 0 0 4px rgba(0, 217, 165, 0.15);
            background: rgba(15, 23, 42, 0.6);
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

        .indicator-error {
            margin-top: 10px;
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

        .month-year-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 35px;
        }

        .indicator-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        @media (min-width: 1200px) {
            .indicator-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .indicator-item {
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 16px;
            border-right: 4px solid #00d9a5;
            transition: all 0.3s ease;
            border: 1px solid rgba(148, 163, 184, 0.2);
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        .indicator-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0, 217, 165, 0.05) 0%, rgba(99, 102, 241, 0.05) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .indicator-item:focus-within {
            border-color: #00d9a5;
            box-shadow: 0 0 0 4px rgba(0, 217, 165, 0.15), 0 10px 30px rgba(0, 217, 165, 0.2);
            background: rgba(15, 23, 42, 0.6);
            transform: translateY(-2px);
        }

        .indicator-item:focus-within::before {
            opacity: 1;
        }

        .indicator-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            gap: 12px;
        }

        .indicator-name {
            font-weight: 600;
            color: #e2e8f0;
            flex: 1;
            font-size: 0.95rem;
            line-height: 1.4;
            position: relative;
            z-index: 1;
        }

        .indicator-unit {
            color: #00d9a5;
            font-weight: 700;
            background: rgba(0, 217, 165, 0.15);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            border: 1px solid rgba(0, 217, 165, 0.3);
            white-space: nowrap;
            flex-shrink: 0;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
        }

        .form-actions {
            margin-top: 35px;
            padding-top: 25px;
            border-top: 1px solid rgba(148, 163, 184, 0.2);
        }

        .submit-btn {
            background: linear-gradient(135deg, #00d9a5 0%, #009879 100%);
            color: white;
            border: none;
            padding: 16px 24px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            font-family: 'Tajawal', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 20px rgba(0, 217, 165, 0.3);
            position: relative;
            overflow: hidden;
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
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 217, 165, 0.4);
            background: linear-gradient(135deg, #00f5d4 0%, #00b894 100%);
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(0, 152, 121, 0.3);
        }

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .submit-btn i {
            font-size: 1rem;
        }

        .actions-grid {
            width: 100%;
            display: flex;
            gap: 20px;
            margin-top: 30px;
            align-items: center;
            justify-content: center;
        }

        .action-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.06);
            text-align: center;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            border: 2px solid #e2e8f0;
            width: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 152, 121, 0.15);
            border-color: #009879;
            background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);
        }

        .action-icon {
            font-size: 3rem;
            margin-bottom: 18px;
            color: #009879;
            transition: transform 0.3s ease;
        }

        .action-card:hover .action-icon {
            transform: scale(1.1);
        }

        .action-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .action-desc {
            color: #64748b;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .message {
            background: rgba(0, 217, 165, 0.15);
            backdrop-filter: blur(10px);
            color: #00d9a5;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
            border: 1px solid rgba(0, 217, 165, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(0, 217, 165, 0.2);
            animation: fadeInDown 0.5s ease-out;
        }

        .message.error {
            background: rgba(239, 68, 68, 0.15);
            backdrop-filter: blur(10px);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.2);
        }

        .message i {
            font-size: 1.1rem;
        }

        .recommendations-display {
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            margin-top: 20px;
        }

        .recommendations-text {
            color: #e2e8f0;
            line-height: 2;
            font-size: 1.05rem;
            white-space: pre-wrap;
        }

        .recommendations-text h3 {
            color: #00d9a5;
            margin-top: 25px;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .recommendations-text h4 {
            color: #6366f1;
            margin-top: 20px;
            margin-bottom: 12px;
            font-size: 1.1rem;
        }

        .recommendations-text strong {
            color: #00d9a5;
        }

        .recommendations-text ul,
        .recommendations-text ol {
            margin-right: 25px;
            margin-top: 10px;
            margin-bottom: 15px;
        }

        .recommendations-text li {
            margin-bottom: 10px;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .chart-card {
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        .chart-card h4 {
            color: #00d9a5;
            margin-bottom: 20px;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-card canvas {
            max-height: 400px;
            height: 300px !important;
        }

        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        .footer {
            text-align: center;
            padding: 30px;
            color: #64748b;
            border-top: 1px solid rgba(148, 163, 184, 0.1);
            margin-top: 50px;
            position: relative;
            z-index: 1;
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

            .form-card {
                padding: 25px 20px;
            }

            .welcome-card {
                padding: 25px 20px;
            }

            .welcome-card h2 {
                font-size: 1.6rem;
            }

            .section-title {
                font-size: 1.3rem;
                margin-bottom: 24px;
            }

            .month-year-group {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .indicator-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .indicator-item {
                padding: 18px;
            }

            .indicator-name {
                font-size: 0.95rem;
            }

            .indicator-unit {
                font-size: 0.8rem;
                padding: 5px 10px;
            }

            .form-actions {
                margin-top: 30px;
                padding-top: 20px;
            }

            .submit-btn {
                padding: 14px 20px;
                font-size: 0.95rem;
            }

            .actions-grid {
                flex-direction: column;
                gap: 15px;
            }

            .action-card {
                width: 100%;
                padding: 25px;
            }

            .action-icon {
                font-size: 2.5rem;
                margin-bottom: 15px;
            }

            .action-title {
                font-size: 1.1rem;
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
            </div>
            
            <div class="user-info">
                <div class="user-welcome">
                    <div class="welcome">مرحباً بعودتك</div>
                    <div class="username"><?= htmlspecialchars($_SESSION['username']) ?></div>
                </div>
                
                <div class="nav-buttons">
                    <a href="dean_reports.php" class="nav-btn"><i class="fa-solid fa-file-arrow-up"></i> رفع التقارير</a>
                    <a href="logout.php" class="nav-btn"><i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج</a>
                </div>
            </div>
        </div>
    </header>

    <!-- المحتوى الرئيسي -->
    <div class="container">
        <!-- ترحيب -->
        <div class="welcome-card">
            <h2>مرحباً عميد <?= htmlspecialchars($faculty_name) ?></h2>
            <p>يمكنك إدخال بيانات مؤشرات الاستدامة ورفع التقارير للمدير العام</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?= strpos($message, 'fa-circle-check') !== false ? '' : 'error' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- نموذج إدخال البيانات -->
        <div class="form-card">
            <div class="section-title">
                <i class="fa-solid fa-chart-line"></i>
                <span>إدخال بيانات مؤشرات الاستدامة</span>
            </div>

            <form method="post" action="" id="dataForm">
                <div class="month-year-group">
                    <div class="form-group">
                        <label for="month" class="form-label"><i class="fa-solid fa-calendar"></i> الشهر</label>
                        <input type="number" id="month" name="month" class="form-input" 
                               min="1" max="12" value="<?= date('n') ?>" required>
                        <div class="field-error" id="monthError">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>الرجاء اختيار الشهر</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="year" class="form-label"><i class="fa-solid fa-calendar-days"></i> السنة</label>
                        <input type="number" id="year" name="year" class="form-input" 
                               min="2020" max="2030" value="<?= date('Y') ?>" required>
                        <div class="field-error" id="yearError">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>الرجاء اختيار السنة</span>
                        </div>
                    </div>
                </div>

                <div class="indicator-grid">
                    <?php foreach ($indicators as $indicator): ?>
                        <div class="indicator-item">
                            <div class="indicator-header">
                                <div class="indicator-name"><?= htmlspecialchars($indicator['name']) ?></div>
                                <div class="indicator-unit"><?= htmlspecialchars($indicator['unit']) ?></div>
                            </div>
                            <input type="number" step="0.01" name="indicator[<?= $indicator['id'] ?>]" 
                                   class="form-input indicator-input" min="0" 
                                   placeholder="أدخل القيمة..." data-indicator-id="<?= $indicator['id'] ?>">
                            <div class="field-error indicator-error" id="indicatorError_<?= $indicator['id'] ?>" style="display: none;">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                <span>الرجاء إدخال قيمة لهذا المؤشر</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="submit-btn">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>حفظ بيانات المؤشرات</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- قسم التوصيات الذكية -->
        <?php if ($data_saved && $saved_month && $saved_year): ?>
        <div class="form-card" id="recommendationsSection" style="margin-top: 30px;">
            <div class="section-title">
                <i class="fa-solid fa-robot"></i>
                <span>توصيات الذكاء الاصطناعي</span>
            </div>

            <div id="recommendationsContent">
                <?php if ($faculty_recommendations): ?>
                    <div class="recommendations-display">
                        <div class="recommendations-text" id="recommendationsText">
                            <?= nl2br(htmlspecialchars($faculty_recommendations['recommendations'])) ?>
                        </div>
                        <div class="recommendations-date" style="margin-top: 20px; color: #94a3b8; font-size: 0.9rem;">
                            <i class="fa-solid fa-clock"></i>
                            تم إنشاؤها: <?= date('Y-m-d H:i', strtotime($faculty_recommendations['created_at'])) ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="recommendations-placeholder">
                        <p style="color: #94a3b8; margin-bottom: 20px; text-align: center;">
                            انقر على الزر أدناه للحصول على توصيات ذكية مخصصة لتحسين أداء كليتك
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- الرسوم البيانية -->
            <div id="chartsContainer" style="display: none; margin-top: 30px;">
                <div class="charts-grid">
                    <div class="chart-card">
                        <h4><i class="fa-solid fa-chart-pie"></i> ملخص التقييمات</h4>
                        <canvas id="evaluationChart"></canvas>
                    </div>
                    <div class="chart-card">
                        <h4><i class="fa-solid fa-chart-bar"></i> استهلاك الكلية - أداء المؤشرات</h4>
                        <canvas id="indicatorsChart"></canvas>
                    </div>
                    <div class="chart-card">
                        <h4><i class="fa-solid fa-chart-line"></i> استهلاك الكلية - مقارنة مع المعايير</h4>
                        <canvas id="standardsChart"></canvas>
                    </div>
                    <div class="chart-card">
                        <h4><i class="fa-solid fa-chart-area"></i> توزيع الاستهلاك</h4>
                        <canvas id="consumptionChart"></canvas>
                    </div>
                </div>
            </div>

            <div style="margin-top: 25px; text-align: center;">
                <button type="button" id="generateRecommendationsBtn" class="submit-btn" style="max-width: 400px; margin: 0 auto;">
                    <i class="fa-solid fa-sparkles"></i>
                    <span><?= $faculty_recommendations ? 'تحديث التوصيات' : 'إنشاء توصيات ذكية' ?></span>
                </button>
            </div>

            <div id="recommendationsLoading" style="display: none; text-align: center; padding: 40px; color: #94a3b8;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 3rem; color: #00d9a5; margin-bottom: 20px;"></i>
                <p>جاري تحليل البيانات وإنشاء التوصيات...</p>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- الفوتر -->
    <div class="footer">
        نظام Beyond You - جامعة اليرموك © 2026
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const form = document.getElementById('dataForm');
        const indicatorInputs = document.querySelectorAll('.indicator-input');

        // Hide error messages when user starts typing
        indicatorInputs.forEach(input => {
            input.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('error-input');
                    const errorElement = document.getElementById('indicatorError_' + this.dataset.indicatorId);
                    if (errorElement) {
                        errorElement.classList.remove('show');
                        errorElement.style.display = 'none';
                    }
                }
            });
        });

        // Form validation on submit
        form.addEventListener('submit', function(e) {
            // Clear previous errors
            document.querySelectorAll('.form-input').forEach(input => {
                input.classList.remove('error-input');
            });
            document.querySelectorAll('.field-error').forEach(error => {
                error.classList.remove('show');
                error.style.display = 'none';
            });

            let isValid = true;

            // Validate month and year
            const monthInput = document.getElementById('month');
            const yearInput = document.getElementById('year');
            
            if (!monthInput.value || monthInput.value < 1 || monthInput.value > 12) {
                monthInput.classList.add('error-input');
                document.getElementById('monthError').classList.add('show');
                document.getElementById('monthError').style.display = 'flex';
                isValid = false;
            }

            if (!yearInput.value || yearInput.value < 2020 || yearInput.value > 2030) {
                yearInput.classList.add('error-input');
                document.getElementById('yearError').classList.add('show');
                document.getElementById('yearError').style.display = 'flex';
                isValid = false;
            }

            // Validate all indicators are filled
            indicatorInputs.forEach(input => {
                const value = input.value.trim();
                if (!value || value === '' || parseFloat(value) < 0) {
                    input.classList.add('error-input');
                    const errorElement = document.getElementById('indicatorError_' + input.dataset.indicatorId);
                    if (errorElement) {
                        errorElement.classList.add('show');
                        errorElement.style.display = 'flex';
                    }
                    isValid = false;
                }
            });

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
            const submitBtnText = submitBtn.querySelector('span');
            const submitBtnIcon = submitBtn.querySelector('i');
            
            submitBtn.disabled = true;
            submitBtnIcon.className = 'fa-solid fa-spinner fa-spin';
            submitBtnText.textContent = 'جاري الحفظ...';
        });

        // Clear month/year errors when user types
        document.getElementById('month').addEventListener('input', function() {
            if (this.value && this.value >= 1 && this.value <= 12) {
                this.classList.remove('error-input');
                document.getElementById('monthError').classList.remove('show');
                document.getElementById('monthError').style.display = 'none';
            }
        });

        document.getElementById('year').addEventListener('input', function() {
            if (this.value && this.value >= 2020 && this.value <= 2030) {
                this.classList.remove('error-input');
                document.getElementById('yearError').classList.remove('show');
                document.getElementById('yearError').style.display = 'none';
            }
        });

        // طلب التوصيات من الذكاء الاصطناعي
        <?php if ($data_saved && $saved_month && $saved_year): ?>
        const generateBtn = document.getElementById('generateRecommendationsBtn');
        const recommendationsContent = document.getElementById('recommendationsContent');
        const recommendationsLoading = document.getElementById('recommendationsLoading');
        const chartsContainer = document.getElementById('chartsContainer');
        let evaluationChart = null;
        let indicatorsChart = null;
        let standardsChart = null;
        let consumptionChart = null;
        
        // تحميل البيانات وعرض الرسوم البيانية عند تحميل الصفحة إذا كانت هناك توصيات محفوظة
        <?php if ($faculty_recommendations): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // جلب بيانات الرسوم البيانية
            const month = <?= $saved_month ?>;
            const year = <?= $saved_year ?>;
            
            fetch('get_faculty_recommendations.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'month=' + month + '&year=' + year
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.chart_data && data.chart_data.length > 0) {
                    chartsContainer.style.display = 'block';
                    setTimeout(() => {
                        renderCharts(data.chart_data, data.evaluation_stats);
                    }, 500);
                }
            })
            .catch(error => {
                console.error('Error loading chart data:', error);
            });
        });
        <?php endif; ?>

        if (generateBtn) {
            generateBtn.addEventListener('click', function() {
                const month = <?= $saved_month ?>;
                const year = <?= $saved_year ?>;
                
                // إظهار حالة التحميل
                recommendationsContent.style.display = 'none';
                chartsContainer.style.display = 'none';
                recommendationsLoading.style.display = 'block';
                generateBtn.disabled = true;
                generateBtn.querySelector('span').textContent = 'جاري الإنشاء...';
                
                // طلب التوصيات
                fetch('get_faculty_recommendations.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'month=' + month + '&year=' + year
                })
                .then(response => response.json())
                .then(data => {
                    recommendationsLoading.style.display = 'none';
                    
                    if (data.success) {
                        // عرض التوصيات
                        const fullRecommendations = '<div class="recommendations-section"><h3 style="color: #00d9a5; margin-bottom: 15px;">📊 توصيات بناءً على المعايير</h3>' + data.standards_recommendations + '</div><hr style="margin: 30px 0; border-color: rgba(148,163,184,0.2);"><div class="recommendations-section"><h3 style="color: #6366f1; margin-bottom: 15px;">💡 توصيات عامة</h3>' + data.general_recommendations + '</div>';
                        
                        recommendationsContent.innerHTML = `
                            <div class="recommendations-display">
                                <div class="recommendations-text">${fullRecommendations}</div>
                                <div class="recommendations-date" style="margin-top: 20px; color: #94a3b8; font-size: 0.9rem;">
                                    <i class="fa-solid fa-clock"></i>
                                    تم إنشاؤها: ${new Date().toLocaleString('ar-SA')}
                                </div>
                            </div>
                        `;
                        recommendationsContent.style.display = 'block';
                        
                        // عرض الرسوم البيانية
                        console.log('Chart Data:', data.chart_data);
                        console.log('Evaluation Stats:', data.evaluation_stats);
                        
                        if (data.chart_data && data.chart_data.length > 0 && data.evaluation_stats) {
                            chartsContainer.style.display = 'block';
                            // تأخير بسيط لضمان تحميل العناصر
                            setTimeout(() => {
                                renderCharts(data.chart_data, data.evaluation_stats);
                            }, 100);
                        } else {
                            console.warn('لا توجد بيانات للرسوم البيانية');
                            // إظهار رسالة إذا لم تكن هناك بيانات
                            if (chartsContainer) {
                                chartsContainer.innerHTML = '<div class="message" style="text-align: center; padding: 20px; color: #94a3b8;"><i class="fa-solid fa-info-circle"></i> لا توجد بيانات كافية لعرض الرسوم البيانية</div>';
                                chartsContainer.style.display = 'block';
                            }
                        }
                        
                        generateBtn.querySelector('span').textContent = 'تحديث التوصيات';
                    } else {
                        recommendationsContent.innerHTML = `
                            <div class="message error">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                ${data.error}
                            </div>
                        `;
                        recommendationsContent.style.display = 'block';
                        generateBtn.querySelector('span').textContent = 'إعادة المحاولة';
                    }
                    
                    generateBtn.disabled = false;
                })
                .catch(error => {
                    recommendationsLoading.style.display = 'none';
                    recommendationsContent.innerHTML = `
                        <div class="message error">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            حدث خطأ أثناء الاتصال بالخادم
                        </div>
                    `;
                    recommendationsContent.style.display = 'block';
                    generateBtn.disabled = false;
                    generateBtn.querySelector('span').textContent = 'إعادة المحاولة';
                });
            });
        }

        function renderCharts(chartData, evaluationStats) {
            console.log('Rendering charts with data:', chartData, evaluationStats);
            
            // التحقق من وجود العناصر
            const evalCanvas = document.getElementById('evaluationChart');
            const indCanvas = document.getElementById('indicatorsChart');
            const stdCanvas = document.getElementById('standardsChart');
            const consCanvas = document.getElementById('consumptionChart');
            
            if (!evalCanvas || !indCanvas || !stdCanvas || !consCanvas) {
                console.error('Canvas elements not found!');
                return;
            }
            
            // تدمير الرسوم البيانية القديمة
            if (evaluationChart) {
                try {
                    evaluationChart.destroy();
                } catch(e) {
                    console.warn('Error destroying evaluation chart:', e);
                }
            }
            if (indicatorsChart) {
                try {
                    indicatorsChart.destroy();
                } catch(e) {
                    console.warn('Error destroying indicators chart:', e);
                }
            }
            if (standardsChart) {
                try {
                    standardsChart.destroy();
                } catch(e) {
                    console.warn('Error destroying standards chart:', e);
                }
            }
            if (consumptionChart) {
                try {
                    consumptionChart.destroy();
                } catch(e) {
                    console.warn('Error destroying consumption chart:', e);
                }
            }

            // رسم بياني للتقييمات
            const evalCtx = evalCanvas.getContext('2d');
            evaluationChart = new Chart(evalCtx, {
                type: 'doughnut',
                data: {
                    labels: ['ممتاز ✔', 'منخفض ⚠', 'خارج المعيار ✖'],
                    datasets: [{
                        data: [
                            evaluationStats.excellent || 0,
                            evaluationStats.warning || 0,
                            evaluationStats.error || 0
                        ],
                        backgroundColor: [
                            'rgba(0, 217, 165, 0.8)',
                            'rgba(251, 191, 36, 0.8)',
                            'rgba(239, 68, 68, 0.8)'
                        ],
                        borderColor: [
                            '#00d9a5',
                            '#fbbf24',
                            '#ef4444'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: {
                                    family: 'Tajawal',
                                    size: 14
                                },
                                color: '#e2e8f0',
                                padding: 15
                            }
                        },
                        tooltip: {
                            rtl: true,
                            bodyFont: {
                                family: 'Tajawal'
                            },
                            titleFont: {
                                family: 'Tajawal'
                            }
                        }
                    }
                }
            });

            // رسم بياني لأداء المؤشرات
            const indCtx = indCanvas.getContext('2d');
            const labels = chartData.map(d => d.name);
            const values = chartData.map(d => d.value);
            const colors = chartData.map(d => {
                if (d.status === 'excellent') return 'rgba(0, 217, 165, 0.8)';
                if (d.status === 'warning') return 'rgba(251, 191, 36, 0.8)';
                return 'rgba(239, 68, 68, 0.8)';
            });

            indicatorsChart = new Chart(indCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'القيمة',
                        data: values,
                        backgroundColor: colors,
                        borderColor: colors.map(c => c.replace('0.8', '1')),
                        borderWidth: 2,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    aspectRatio: 2,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            rtl: true,
                            bodyFont: {
                                family: 'Tajawal'
                            },
                            titleFont: {
                                family: 'Tajawal'
                            },
                            callbacks: {
                                label: function(context) {
                                    const dataIndex = context.dataIndex;
                                    const unit = chartData[dataIndex]?.unit || '';
                                    const status = chartData[dataIndex]?.status || '';
                                    const statusText = status === 'excellent' ? '✔ ممتاز' : 
                                                      status === 'warning' ? '⚠ منخفض' : '✖ خارج المعيار';
                                    return `${context.parsed.y} ${unit} - ${statusText}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                font: {
                                    family: 'Tajawal'
                                },
                                color: '#94a3b8'
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.1)'
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    family: 'Tajawal',
                                    size: 11
                                },
                                color: '#94a3b8',
                                maxRotation: 45,
                                minRotation: 45
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.1)'
                            }
                        }
                    }
                }
            });

            // رسم بياني للمقارنة مع المعايير
            const stdCtx = stdCanvas.getContext('2d');
            const stdLabels = chartData.map(d => d.name);
            const stdValues = chartData.map(d => d.value);
            const stdMinValues = chartData.map(d => d.standard_min || 0);
            const stdMaxValues = chartData.map(d => d.standard_max || 0);
            
            standardsChart = new Chart(stdCtx, {
                type: 'line',
                data: {
                    labels: stdLabels,
                    datasets: [
                        {
                            label: 'الاستهلاك الفعلي',
                            data: stdValues,
                            borderColor: '#00d9a5',
                            backgroundColor: 'rgba(0, 217, 165, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 6,
                            pointHoverRadius: 8,
                            pointBackgroundColor: '#00d9a5',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        },
                        {
                            label: 'الحد الأدنى للمعيار',
                            data: stdMinValues,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            fill: false,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'الحد الأعلى للمعيار',
                            data: stdMaxValues,
                            borderColor: '#fbbf24',
                            backgroundColor: 'rgba(251, 191, 36, 0.1)',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            fill: false,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    aspectRatio: 2,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                font: {
                                    family: 'Tajawal',
                                    size: 12
                                },
                                color: '#e2e8f0',
                                padding: 15
                            }
                        },
                        tooltip: {
                            rtl: true,
                            bodyFont: {
                                family: 'Tajawal'
                            },
                            titleFont: {
                                family: 'Tajawal'
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                font: {
                                    family: 'Tajawal'
                                },
                                color: '#94a3b8'
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.1)'
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    family: 'Tajawal',
                                    size: 10
                                },
                                color: '#94a3b8',
                                maxRotation: 45,
                                minRotation: 45
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.1)'
                            }
                        }
                    }
                }
            });

            // رسم بياني لتوزيع الاستهلاك (Polar Area)
            const consCtx = consCanvas.getContext('2d');
            const consLabels = chartData.map(d => d.name);
            const consValues = chartData.map(d => d.value);
            const consColors = chartData.map(d => {
                if (d.status === 'excellent') return 'rgba(0, 217, 165, 0.7)';
                if (d.status === 'warning') return 'rgba(251, 191, 36, 0.7)';
                return 'rgba(239, 68, 68, 0.7)';
            });
            
            consumptionChart = new Chart(consCtx, {
                type: 'polarArea',
                data: {
                    labels: consLabels,
                    datasets: [{
                        label: 'الاستهلاك',
                        data: consValues,
                        backgroundColor: consColors,
                        borderColor: consColors.map(c => c.replace('0.7', '1')),
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    aspectRatio: 1.2,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: {
                                    family: 'Tajawal',
                                    size: 11
                                },
                                color: '#e2e8f0',
                                padding: 10
                            }
                        },
                        tooltip: {
                            rtl: true,
                            bodyFont: {
                                family: 'Tajawal'
                            },
                            titleFont: {
                                family: 'Tajawal'
                            },
                            callbacks: {
                                label: function(context) {
                                    const dataIndex = context.dataIndex;
                                    const unit = chartData[dataIndex]?.unit || '';
                                    return `${context.label}: ${context.parsed.r} ${unit}`;
                                }
                            }
                        }
                    },
                    scales: {
                        r: {
                            beginAtZero: true,
                            ticks: {
                                font: {
                                    family: 'Tajawal'
                                },
                                color: '#94a3b8',
                                backdropColor: 'transparent'
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.2)'
                            },
                            pointLabels: {
                                font: {
                                    family: 'Tajawal',
                                    size: 10
                                },
                                color: '#e2e8f0'
                            }
                        }
                    }
                }
            });
        }
        <?php endif; ?>

    </script>
</body>
</html>