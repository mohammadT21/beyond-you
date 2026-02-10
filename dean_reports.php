<?php
// dean_reports.php - صفحة رفع التقارير للعميد
require_once 'config.php';

// التحقق من الجلسة (العميد فقط)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'dean') {
    header('Location: login.php');
    exit;
}

$conn = db_connect();
$faculty_id = intval($_SESSION['faculty_id']);

// جلب اسم الكلية
$sql_faculty = "SELECT name FROM faculties WHERE id = $faculty_id";
$result_faculty = mysqli_query($conn, $sql_faculty);
$faculty = mysqli_fetch_assoc($result_faculty);
$faculty_name = $faculty ? $faculty['name'] : 'كلية غير معروفة';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $note = mysqli_real_escape_string($conn, $_POST['note']);
    $file_path = '';

    // التعامل مع رفع الملف
    if (!empty($_FILES['report_file']['name'])) {
        $file = $_FILES['report_file'];
        $filename = time() . '_' . basename($file['name']);
        $target_path = UPLOAD_DIR . '/' . $filename;
        
        // التحقق من نوع الملف
        $allowed_types = ['application/pdf', 'application/msword', 
                         'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                         'image/jpeg', 'image/png'];
        $file_type = mime_content_type($file['tmp_name']);
        
        if (in_array($file_type, $allowed_types)) {
            if ($file['size'] <= MAX_FILE_SIZE) {
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $file_path = 'uploads/' . $filename;
                } else {
                    $message = '<i class="fa-solid fa-triangle-exclamation"></i> حدث خطأ أثناء رفع الملف.';
                }
            } else {
                    $message = '<i class="fa-solid fa-triangle-exclamation"></i> حجم الملف كبير جدًا (الحد الأقصى 5 ميجابايت).';
            }
        } else {
            $message = '<i class="fa-solid fa-triangle-exclamation"></i> نوع الملف غير مدعوم. المسموح: PDF, Word, JPEG, PNG.';
        }
    }

    if (!$message) {
        $sql = "INSERT INTO reports (faculty_id, title, note, file_path) 
                VALUES ($faculty_id, '$title', '$note', '$file_path')";
        
        if (mysqli_query($conn, $sql)) {
            $message = '<i class="fa-solid fa-circle-check"></i> تم إرسال التقرير بنجاح إلى المدير العام.';
            // تفريغ الحقول
            $_POST['title'] = $_POST['note'] = '';
        } else {
            $message = '<i class="fa-solid fa-triangle-exclamation"></i> حدث خطأ أثناء حفظ التقرير.';
        }
    }
}

db_close($conn);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رفع التقارير - نظام Beyond You</title>
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
        .actions-grid{
            width: 100%;
    display: flex;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 30px;
    align-items: center;
    justify-content: center
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
            max-width: 750px;
            margin: 30px auto;
            padding: 0 20px;
            position: relative;
            z-index: 1;
        }

        .page-header {
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

        .page-header::before {
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
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        .page-header h2 {
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

        .page-header h2 i {
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-header p {
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

        .form-section {
            margin-bottom: 35px;
        }

        .form-section:last-of-type {
            margin-bottom: 30px;
        }

        .form-section-title {
            font-size: 1.3rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.01em;
        }

        .form-section-title i {
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.3rem;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            color: #cbd5e1;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: -0.01em;
        }

        .form-label.required::after {
            content: ' *';
            color: #fca5a5;
            font-weight: 700;
        }

        .form-label i, .message i, .error i, h2 i, .file-info strong i {
            margin-left: 8px;
            color: #00d9a5;
        }

        .nav-btn i, .action-btn i {
            margin-left: 5px;
        }

        .form-input, .form-textarea, .form-file {
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

        .form-input:focus, .form-textarea:focus, .form-file:focus {
            outline: none;
            border-color: #00d9a5;
            box-shadow: 0 0 0 4px rgba(0, 217, 165, 0.15);
            background: rgba(15, 23, 42, 0.6);
        }

        .form-input::placeholder, .form-textarea::placeholder {
            color: #64748b;
            opacity: 1;
        }

        .form-input.error-input, .form-textarea.error-input {
            border-color: #ef4444;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.15);
        }

        .form-input.error-input:focus, .form-textarea.error-input:focus {
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

        .form-textarea {
            resize: vertical;
            min-height: 130px;
            line-height: 1.6;
        }

        .form-file {
            position: absolute;
            opacity: 0;
            width: 0.1px;
            height: 0.1px;
            overflow: hidden;
            z-index: -1;
        }

        .custom-file-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px dashed rgba(148, 163, 184, 0.3);
            border-radius: 16px;
            padding: 40px 20px;
            text-align: center;
            background: rgba(15, 23, 42, 0.3);
            backdrop-filter: blur(10px);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            width: 100%;
            box-sizing: border-box;
            min-height: 150px;
        }

        .custom-file-upload:hover {
            border-color: #00d9a5;
            background: rgba(0, 217, 165, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 217, 165, 0.2);
        }

        .custom-file-upload.drag-over {
            border-color: #00d9a5;
            background: rgba(0, 217, 165, 0.15);
            border-style: solid;
        }

        .custom-file-upload-icon {
            font-size: 3rem;
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
        }

        .custom-file-upload-text {
            color: #e2e8f0;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 8px;
        }

        .custom-file-upload-hint {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .file-selected {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(0, 217, 165, 0.3);
        }

        .file-selected.show {
            display: block;
        }

        .file-selected-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .file-selected-details {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .file-selected-icon {
            font-size: 2rem;
            color: #009879;
        }

        .file-selected-icon .fa-file-pdf {
            color: #dc2626;
        }

        .file-selected-icon .fa-file-word {
            color: #2563eb;
        }

        .file-selected-icon .fa-file-image {
            color: #16a34a;
        }

        .file-selected-text {
            flex: 1;
        }

        .file-selected-name {
            font-weight: 600;
            color: #e2e8f0;
            margin-bottom: 4px;
            word-break: break-all;
        }

        .file-selected-size {
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .file-remove-btn {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            font-family: 'Tajawal', sans-serif;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }

        .file-remove-btn:hover {
            background: rgba(239, 68, 68, 0.3);
            border-color: rgba(239, 68, 68, 0.5);
            transform: scale(1.05);
        }

        .file-remove-btn i {
            margin-left: 5px;
        }

        .form-actions {
            margin-top: 35px;
            padding-top: 25px;
            border-top: 1px solid rgba(148, 163, 184, 0.2);
            display: flex;
            flex-direction: column;
            gap: 15px;
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.2);
            animation: fadeInDown 0.5s ease-out;
        }

        .file-info {
            background: rgba(0, 217, 165, 0.1);
            backdrop-filter: blur(10px);
            padding: 18px 20px;
            border-radius: 12px;
            margin-top: 20px;
            font-size: 0.875rem;
            color: #94a3b8;
            border: 1px solid rgba(0, 217, 165, 0.2);
            line-height: 1.8;
        }

        .file-info strong {
            display: block;
            margin-bottom: 12px;
            font-size: 0.95rem;
            color: #00d9a5;
            font-weight: 700;
        }

        .file-info ul {
            list-style: none;
            padding: 0;
            margin: 0;
            padding-right: 0;
        }

        .file-info li {
            padding: 6px 0;
            padding-right: 22px;
            position: relative;
        }

        .file-info li::before {
            content: '•';
            position: absolute;
            right: 0;
            font-weight: bold;
            color: #00d9a5;
            font-size: 1.2rem;
            line-height: 1;
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

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 0;
            padding-top: 20px;
            border-top: 1px solid rgba(148, 163, 184, 0.2);
        }

        .action-btn {
            flex: 1;
            padding: 14px 20px;
            border-radius: 10px;
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
            background: linear-gradient(135deg, #00d9a5 0%, #009879 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 217, 165, 0.3);
        }

        .action-primary:hover {
            background: linear-gradient(135deg, #00f5d4 0%, #00b894 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 217, 165, 0.4);
        }

        .action-secondary {
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(10px);
            color: #cbd5e1;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        .action-secondary:hover {
            background: rgba(15, 23, 42, 0.6);
            border-color: rgba(0, 217, 165, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 217, 165, 0.2);
            color: #e2e8f0;
        }

        .action-btn i {
            font-size: 0.9rem;
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

            .form-section {
                margin-bottom: 28px;
            }

            .form-section-title {
                font-size: 1rem;
                margin-bottom: 16px;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .form-actions {
                margin-top: 30px;
                padding-top: 20px;
            }

            .actions {
                flex-direction: column;
                gap: 10px;
            }

            .submit-btn {
                padding: 14px 20px;
                font-size: 0.95rem;
            }

            .custom-file-upload {
                padding: 30px 15px;
                min-height: 140px;
            }

            .custom-file-upload-icon {
                font-size: 2.5rem;
                margin-bottom: 12px;
            }

            .custom-file-upload-text {
                font-size: 1rem;
            }

            .custom-file-upload-hint {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <!-- الهيدر -->
    <header class="header">
        <div class="header-top">
            <div class="logo">
                <h1><i class="fa-solid fa-seedling"></i>Beyond You</h1>
            </div>
            
            <div class="user-info">
                <div class="user-welcome">
                    <div class="welcome">مرحباً بعودتك</div>
                    <div class="username"><?= htmlspecialchars($_SESSION['username']) ?></div>
                </div>
                
                <div class="nav-buttons">
                    <a href="dashboard_dean.php" class="nav-btn"><i class="fa-solid fa-chart-bar"></i> إدخال البيانات</a>
                    <a href="logout.php" class="nav-btn"><i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج</a>
                </div>
            </div>
        </div>
    </header>

    <!-- المحتوى الرئيسي -->
    <div class="container">
        <!-- عنوان الصفحة -->
        <div class="page-header">
            <h2><i class="fa-solid fa-paperclip"></i> رفع التقارير</h2>
            <p>إرسال تقارير وملاحظات للمدير العام - <?= htmlspecialchars($faculty_name) ?></p>
        </div>

        <?php if ($message): ?>
            <div class="<?= strpos($message, 'fa-circle-check') !== false ? 'message' : 'error' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- نموذج رفع التقرير -->
        <div class="form-card">
            <form method="post" action="" enctype="multipart/form-data">
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fa-solid fa-file-lines"></i>
                        <span>معلومات التقرير</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="title" class="form-label required"><i class="fa-solid fa-clipboard"></i> عنوان التقرير</label>
                        <input type="text" id="title" name="title" class="form-input" 
                               value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>" 
                               placeholder="أدخل عنوان التقرير">
                        <div class="field-error" id="titleError">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>الرجاء إدخال عنوان التقرير</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="note" class="form-label required"><i class="fa-solid fa-note-sticky"></i> الملاحظات</label>
                        <textarea id="note" name="note" class="form-textarea" 
                                  placeholder="أدخل أي ملاحظات أو تفاصيل إضافية..."><?= isset($_POST['note']) ? htmlspecialchars($_POST['note']) : '' ?></textarea>
                        <div class="field-error" id="noteError">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>الرجاء إدخال الملاحظات</span>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fa-solid fa-paperclip"></i>
                        <span>مرفقات التقرير</span>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-label"><i class="fa-solid fa-cloud-arrow-up"></i> رفع ملف التقرير</div>
                        <input type="file" id="report_file" name="report_file" class="form-file" 
                               accept=".pdf,.doc,.docx,.jpg,.png">
                        <label for="report_file" class="custom-file-upload" id="customFileUpload">
                            <div class="custom-file-upload-icon">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                            </div>
                            <div class="custom-file-upload-text">اضغط لاختيار الملف أو اسحب الملف هنا</div>
                            <div class="custom-file-upload-hint">PDF, Word, JPEG, PNG (حتى 5 ميجابايت)</div>
                        </label>
                        <div class="file-selected" id="fileSelected">
                            <div class="file-selected-info">
                                <div class="file-selected-details">
                                    <div class="file-selected-icon">
                                        <i class="fa-solid fa-file"></i>
                                    </div>
                                    <div class="file-selected-text">
                                        <div class="file-selected-name" id="fileName"></div>
                                        <div class="file-selected-size" id="fileSize"></div>
                                    </div>
                                </div>
                                <button type="button" class="file-remove-btn" id="removeFile">
                                    <i class="fa-solid fa-times"></i> إزالة
                                </button>
                            </div>
                        </div>
                        <div class="file-info">
                            <strong><i class="fa-solid fa-info-circle"></i> معلومات الملفات:</strong>
                            <ul>
                                <li>الأنواع المسموحة: PDF, Word, JPEG, PNG</li>
                                <li>الحد الأقصى للحجم: 5 ميجابايت</li>
                                <li>رفع الملف اختياري</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="submit-btn">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span>إرسال التقرير للمدير العام</span>
                    </button>
                    
                    <div class="actions">
                        <a href="dashboard_dean.php" class="action-btn action-secondary">
                            <i class="fa-solid fa-arrow-right"></i>
                            <span>العودة لإدخال البيانات</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- الفوتر -->
    <div class="footer">
        نظام Beyond You - جامعة اليرموك © 2026
    </div>

    <script>
        // Custom File Upload Functionality
        const fileInput = document.getElementById('report_file');
        const customFileUpload = document.getElementById('customFileUpload');
        const fileSelected = document.getElementById('fileSelected');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const removeFileBtn = document.getElementById('removeFile');

        // Function to format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        // Function to get file icon based on file type
        function getFileIcon(fileName) {
            const extension = fileName.split('.').pop().toLowerCase();
            const iconMap = {
                'pdf': 'fa-file-pdf',
                'doc': 'fa-file-word',
                'docx': 'fa-file-word',
                'jpg': 'fa-file-image',
                'jpeg': 'fa-file-image',
                'png': 'fa-file-image'
            };
            return iconMap[extension] || 'fa-file';
        }

        // Function to update file display
        function updateFileDisplay(file) {
            if (file) {
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                
                // Update icon based on file type
                const fileIcon = document.querySelector('.file-selected-icon i');
                fileIcon.className = 'fa-solid ' + getFileIcon(file.name);
                
                fileSelected.classList.add('show');
                customFileUpload.style.display = 'none';
            } else {
                fileSelected.classList.remove('show');
                customFileUpload.style.display = 'block';
            }
        }

        // Handle file input change
        fileInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                updateFileDisplay(this.files[0]);
            }
        });

        // Handle remove file button
        removeFileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            fileInput.value = '';
            updateFileDisplay(null);
        });

        // Drag and drop functionality
        customFileUpload.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('drag-over');
        });

        customFileUpload.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('drag-over');
        });

        customFileUpload.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('drag-over');
            
            const files = e.dataTransfer.files;
            if (files && files[0]) {
                fileInput.files = files;
                updateFileDisplay(files[0]);
            }
        });

        // Prevent default drag behaviors on the document
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            document.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
            });
        });

        const form = document.querySelector('form');
        
        form.addEventListener('submit', function(e) {
            // إزالة أي أخطاء سابقة
            const inputs = document.querySelectorAll('.form-input, .form-textarea');
            inputs.forEach(input => {
                input.classList.remove('error-input');
            });
            
            const errorMessages = document.querySelectorAll('.field-error');
            errorMessages.forEach(error => {
                error.classList.remove('show');
            });

            let isValid = true;

            // التحقق من عنوان التقرير (مطلوب)
            const titleInput = document.getElementById('title');
            const titleError = document.getElementById('titleError');
            if (!titleInput.value.trim()) {
                titleInput.classList.add('error-input');
                titleError.classList.add('show');
                isValid = false;
            }

            // التحقق من الملاحظات (مطلوب أيضاً)
            const noteInput = document.getElementById('note');
            const noteError = document.getElementById('noteError');
            if (!noteInput.value.trim()) {
                noteInput.classList.add('error-input');
                noteError.classList.add('show');
                isValid = false;
            }

            // إذا كانت الحقول غير صحيحة، منع إرسال النموذج
            if (!isValid) {
                e.preventDefault();
                e.stopImmediatePropagation();
                
                // تمرير تلقائي للحقول الفارغة
                const firstError = document.querySelector('.error-input');
                if (firstError) {
                    firstError.focus();
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }
            
            // إذا كانت جميع الحقول صحيحة، إظهار حالة التحميل
            const submitBtn = document.querySelector('.submit-btn');
            const submitBtnText = submitBtn.querySelector('span');
            const submitBtnIcon = submitBtn.querySelector('i');
            
            submitBtn.disabled = true;
            submitBtnIcon.className = 'fa-solid fa-spinner fa-spin';
            submitBtnText.textContent = 'جاري الإرسال...';
            
            // السماح بإرسال النموذج بشكل طبيعي
        });

        // إزالة حالة الخطأ عند البدء بالكتابة
        document.getElementById('title').addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('error-input');
                document.getElementById('titleError').classList.remove('show');
            }
        });

        document.getElementById('note').addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('error-input');
                document.getElementById('noteError').classList.remove('show');
            }
        });
    </script>
</body>
</html> 