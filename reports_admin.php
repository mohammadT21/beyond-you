<?php
// reports_admin.php - صفحة عرض التقارير الواردة للمدير العام
require_once 'config.php';

// التحقق من الجلسة (الرئيس فقط)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$conn = db_connect();

// جلب التقارير من قاعدة البيانات
$sql = "
    SELECT r.id, r.title, r.note, r.file_path, r.created_at, f.name AS faculty_name
    FROM reports r
    JOIN faculties f ON r.faculty_id = f.id
    ORDER BY r.created_at DESC
";
$result = mysqli_query($conn, $sql);

$reports = [];
while ($row = mysqli_fetch_assoc($result)) {
    $reports[] = $row;
}

// إحصائيات سريعة
$total_reports = count($reports);
$reports_today = 0;
$reports_with_files = 0;

foreach ($reports as $report) {
    if (date('Y-m-d', strtotime($report['created_at'])) == date('Y-m-d')) {
        $reports_today++;
    }
    if (!empty($report['file_path'])) {
        $reports_with_files++;
    }
}

db_close($conn);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقارير الواردة - نظام Beyond You</title>
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
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            position: relative;
            z-index: 1;
        }

        .page-header {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            margin-bottom: 30px;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            padding: 30px 25px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInUp 0.6s ease-out backwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }

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

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #00d9a5, #6366f1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 217, 165, 0.3);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(0, 217, 165, 0.2) 0%, rgba(99, 102, 241, 0.2) 100%);
            border-radius: 14px;
            font-size: 1.8rem;
            color: #00d9a5;
            transition: all 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
            background: linear-gradient(135deg, rgba(0, 217, 165, 0.3) 0%, rgba(99, 102, 241, 0.3) 100%);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
            line-height: 1;
        }

        .stat-label {
            color: #94a3b8;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .reports-section {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            padding: 35px;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(148, 163, 184, 0.1);
            animation: fadeInUp 0.8s ease-out 0.2s backwards;
        }

        .section-title {
            font-size: 1.6rem;
            font-weight: 800;
            color: #e2e8f0;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            color: #00d9a5;
            font-size: 1.5rem;
        }

        .reports-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            border-radius: 12px;
            overflow: hidden;
        }

        .reports-table th {
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            color: white;
            padding: 18px 20px;
            text-align: right;
            font-weight: 700;
            font-size: 0.95rem;
            border: none;
            position: relative;
        }

        .reports-table th:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 0;
            top: 20%;
            height: 60%;
            width: 1px;
            background: rgba(255, 255, 255, 0.2);
        }

        .reports-table td {
            padding: 20px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
            text-align: right;
            background: rgba(15, 23, 42, 0.4);
            transition: all 0.2s ease;
            color: #e2e8f0;
        }

        .reports-table tbody tr {
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .reports-table tbody tr:hover {
            background: rgba(0, 217, 165, 0.1);
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(0, 217, 165, 0.2);
        }

        .reports-table tbody tr:hover td {
            background: rgba(0, 217, 165, 0.1);
        }
        
        .reports-table tbody tr:active {
            transform: scale(0.99);
        }
        
        /* منع النقر على الروابط من إطلاق حدث الصف */
        .reports-table tbody tr a,
        .reports-table tbody tr button {
            pointer-events: auto;
            position: relative;
            z-index: 10;
        }

        .reports-table tbody tr:last-child td {
            border-bottom: none;
        }

        .faculty-badge {
            background: rgba(0, 217, 165, 0.15);
            backdrop-filter: blur(10px);
            color: #00d9a5;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid rgba(0, 217, 165, 0.3);
            transition: all 0.2s ease;
        }

        .faculty-badge:hover {
            background: rgba(0, 217, 165, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0, 217, 165, 0.3);
        }

        .faculty-badge i {
            font-size: 0.85rem;
        }

        .report-title {
            font-weight: 700;
            color: #e2e8f0;
            margin-bottom: 8px;
            font-size: 1rem;
            line-height: 1.5;
        }

        .report-note {
            color: #94a3b8;
            font-size: 0.9rem;
            line-height: 1.6;
            max-width: 400px;
        }

        .file-download {
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            color: white;
            padding: 10px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(0, 217, 165, 0.3);
            border: none;
            cursor: pointer;
        }

        .file-download:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 217, 165, 0.4);
        }

        .file-download:active {
            transform: translateY(-1px);
        }

        .file-download i {
            font-size: 0.9rem;
        }

        .no-file {
            color: #94a3b8;
            font-style: italic;
            font-size: 0.9rem;
        }

        .report-date {
            color: #cbd5e1;
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .report-time {
            color: #94a3b8;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .report-time i {
            font-size: 0.8rem;
        }

        .report-date {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .report-date i {
            font-size: 0.9rem;
            color: #009879;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #64748b;
        }

        .empty-state .icon {
            font-size: 5rem;
            margin-bottom: 25px;
            opacity: 0.4;
            color: #94a3b8;
            animation: pulse 2s ease-in-out infinite;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #475569;
            margin-bottom: 10px;
        }

        .empty-state p {
            font-size: 1rem;
            color: #94a3b8;
        }

        .search-section {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
            margin-bottom: 30px;
            border: 1px solid rgba(148, 163, 184, 0.1);
            animation: fadeInUp 0.8s ease-out 0.1s backwards;
        }

        .search-box {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .search-input {
            flex: 1;
            padding: 16px 20px 16px 50px;
            border: 2px solid rgba(148, 163, 184, 0.2);
            border-radius: 12px;
            font-size: 1rem;
            font-family: 'Tajawal', sans-serif;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(10px);
            color: #e2e8f0;
            position: relative;
        }

        .search-input:focus {
            outline: none;
            border-color: #00d9a5;
            box-shadow: 0 0 0 4px rgba(0, 217, 165, 0.15);
            background: rgba(15, 23, 42, 0.6);
        }

        .search-input::placeholder {
            color: #64748b;
        }

        .search-wrapper {
            position: relative;
            flex: 1;
        }

        .search-icon {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #00d9a5;
            font-size: 1rem;
            z-index: 2;
            pointer-events: none;
        }

        .search-btn {
            background: linear-gradient(135deg, #009879 0%, #007a62 100%);
            color: white;
            border: none;
            padding: 16px 28px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Tajawal', sans-serif;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0, 152, 121, 0.2);
        }

        .search-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 152, 121, 0.3);
            background: linear-gradient(135deg, #007a62 0%, #006550 100%);
        }

        .search-btn:active {
            transform: translateY(-1px);
        }

        .search-btn i {
            font-size: 0.95rem;
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

        /* Modal Styles */
        .report-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: fadeIn 0.3s ease;
        }

        .report-modal.active {
            display: flex;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .modal-content {
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(148, 163, 184, 0.2);
            position: relative;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(0, 217, 165, 0.2);
        }

        .modal-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-title i {
            color: #00d9a5;
            font-size: 1.6rem;
        }

        .modal-close {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }

        .modal-close:hover {
            background: rgba(239, 68, 68, 0.3);
            transform: rotate(90deg);
        }

        .modal-body {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .modal-info-group {
            background: rgba(15, 23, 42, 0.4);
            padding: 20px;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.1);
        }

        .modal-info-label {
            color: #00d9a5;
            font-weight: 700;
            font-size: 0.9rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-info-label i {
            font-size: 1rem;
        }

        .modal-info-value {
            color: #e2e8f0;
            font-size: 1.1rem;
            line-height: 1.6;
            word-wrap: break-word;
        }

        .modal-note {
            white-space: pre-wrap;
            background: rgba(0, 217, 165, 0.05);
            padding: 15px;
            border-radius: 12px;
            border-right: 3px solid #00d9a5;
        }

        .modal-file-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .modal-file-link {
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 217, 165, 0.3);
        }

        .modal-file-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 217, 165, 0.4);
        }

        .modal-no-file {
            color: #94a3b8;
            font-style: italic;
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

            .page-header {
                padding: 30px 25px;
            }

            .page-title {
                font-size: 1.8rem;
                flex-direction: column;
                gap: 10px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .search-section {
                padding: 25px 20px;
            }

            .search-box {
                flex-direction: column;
            }

            .search-btn {
                width: 100%;
                justify-content: center;
            }

            .reports-section {
                padding: 25px 20px;
            }

            .reports-table {
                display: block;
                overflow-x: auto;
            }

            .reports-table th,
            .reports-table td {
                padding: 12px 10px;
                font-size: 0.9rem;
            }

            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }

            .stat-number {
                font-size: 2rem;
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
                <p>التقارير الواردة</p>
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
        <!-- عنوان الصفحة -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fa-solid fa-envelope-open-text"></i>
                <span>التقارير الواردة</span>
            </h1>
            <p class="page-description">عرض ومتابعة التقارير المرسلة من عمداء الكليات</p>

            <!-- الإحصائيات السريعة -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fa-solid fa-file-lines"></i>
                    </div>
                    <div class="stat-number"><?= $total_reports ?></div>
                    <div class="stat-label">تقرير ورد</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                    <div class="stat-number"><?= $reports_today ?></div>
                    <div class="stat-label">تقرير اليوم</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fa-solid fa-paperclip"></i>
                    </div>
                    <div class="stat-number"><?= $reports_with_files ?></div>
                    <div class="stat-label">مرفق مع ملف</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fa-solid fa-building"></i>
                    </div>
                    <div class="stat-number">16</div>
                    <div class="stat-label">كلية</div>
                </div>
            </div>
        </div>

        <!-- شريط البحث -->
        <div class="search-section">
            <div class="search-box">
                <div class="search-wrapper">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" class="search-input" placeholder="ابحث في التقارير..." id="searchInput">
                </div>
                <button class="search-btn" onclick="filterReports()">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>بحث</span>
                </button>
            </div>
        </div>

        <!-- جدول التقارير -->
        <div class="reports-section">
            <div class="section-title">
                <i class="fa-solid fa-chart-line"></i>
                <span>جميع التقارير الواردة</span>
            </div>

            <?php if (count($reports) === 0): ?>
                <div class="empty-state">
                    <div class="icon">
                        <i class="fa-solid fa-inbox"></i>
                    </div>
                    <h3>لا توجد تقارير واردة بعد</h3>
                    <p>لم يتم إرسال أي تقارير من العمداء حتى الآن</p>
                </div>
            <?php else: ?>
                <table class="reports-table">
                    <thead>
                        <tr>
                            <th>الكلية</th>
                            <th>عنوان التقرير</th>
                            <th>الملاحظات</th>
                            <th>المرفقات</th>
                            <th>تاريخ الإرسال</th>
                        </tr>
                    </thead>
                    <tbody id="reportsTable">
                        <?php foreach ($reports as $report): ?>
                            <tr id="report-<?= $report['id'] ?>" 
                                class="report-row" 
                                data-report-id="<?= $report['id'] ?>"
                                data-report-title="<?= htmlspecialchars($report['title'], ENT_QUOTES, 'UTF-8') ?>"
                                data-report-note="<?= htmlspecialchars($report['note'] ?: 'لا توجد ملاحظات', ENT_QUOTES, 'UTF-8') ?>"
                                data-report-faculty="<?= htmlspecialchars($report['faculty_name'], ENT_QUOTES, 'UTF-8') ?>"
                                data-report-file="<?= htmlspecialchars($report['file_path'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-report-date="<?= date('Y-m-d', strtotime($report['created_at'])) ?>"
                                data-report-time="<?= date('H:i', strtotime($report['created_at'])) ?>">
                                <td>
                                    <span class="faculty-badge">
                                        <i class="fa-solid fa-building-columns"></i>
                                        <span><?= htmlspecialchars($report['faculty_name']) ?></span>
                                    </span>
                                </td>
                                <td>
                                    <div class="report-title"><?= htmlspecialchars($report['title']) ?></div>
                                </td>
                                <td>
                                    <div class="report-note">
                                        <?= nl2br(htmlspecialchars($report['note'] ?: 'لا توجد ملاحظات')) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($report['file_path']) && file_exists($report['file_path'])): ?>
                                        <a href="<?= htmlspecialchars($report['file_path']) ?>" class="file-download" download onclick="event.stopPropagation();">
                                            <i class="fa-solid fa-download"></i>
                                            <span>تحميل الملف</span>
                                        </a>
                                    <?php else: ?>
                                        <span class="no-file">
                                            <i class="fa-solid fa-minus"></i>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="report-date">
                                        <i class="fa-solid fa-calendar-days"></i>
                                        <?= date('Y-m-d', strtotime($report['created_at'])) ?>
                                    </div>
                                    <div class="report-time">
                                        <i class="fa-solid fa-clock"></i>
                                        <?= date('H:i', strtotime($report['created_at'])) ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal لعرض تفاصيل التقرير -->
    <div class="report-modal" id="reportModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class="fa-solid fa-file-lines"></i>
                    <span id="modalTitle">تفاصيل التقرير</span>
                </h2>
                <button class="modal-close" id="modalClose">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="modal-info-group">
                    <div class="modal-info-label">
                        <i class="fa-solid fa-building-columns"></i>
                        الكلية
                    </div>
                    <div class="modal-info-value" id="modalFaculty"></div>
                </div>

                <div class="modal-info-group">
                    <div class="modal-info-label">
                        <i class="fa-solid fa-heading"></i>
                        عنوان التقرير
                    </div>
                    <div class="modal-info-value" id="modalReportTitle"></div>
                </div>

                <div class="modal-info-group">
                    <div class="modal-info-label">
                        <i class="fa-solid fa-note-sticky"></i>
                        الملاحظات
                    </div>
                    <div class="modal-info-value modal-note" id="modalNote"></div>
                </div>

                <div class="modal-info-group">
                    <div class="modal-info-label">
                        <i class="fa-solid fa-paperclip"></i>
                        المرفقات
                    </div>
                    <div class="modal-info-value" id="modalFile"></div>
                </div>

                <div class="modal-info-group">
                    <div class="modal-info-label">
                        <i class="fa-solid fa-calendar-days"></i>
                        تاريخ الإرسال
                    </div>
                    <div class="modal-info-value" id="modalDate"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- الفوتر -->
    <div class="footer">
        نظام Beyond You - جامعة اليرموك © 2026
    </div>

    <script>
        // تصفية التقارير
        function filterReports() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#reportsTable tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // البحث عند الضغط على Enter
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                filterReports();
            }
        });

        // تحديث الإحصائيات تلقائياً كل 30 ثانية
        setInterval(() => {
            // يمكن إضافة تحديث تلقائي هنا إذا needed
        }, 30000);

        // الانتقال إلى التقرير المحدد عند تحميل الصفحة
        window.addEventListener('load', function() {
            const hash = window.location.hash;
            if (hash) {
                const targetElement = document.querySelector(hash);
                if (targetElement) {
                    // الانتقال إلى العنصر مع تأثير سلس
                    setTimeout(() => {
                        targetElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        // إضافة highlight مؤقت للصف
                        targetElement.style.backgroundColor = 'rgba(0, 217, 165, 0.2)';
                        targetElement.style.transition = 'background-color 0.3s ease';
                        setTimeout(() => {
                            targetElement.style.backgroundColor = '';
                        }, 2000);
                    }, 100);
                }
            }
        });

        // جعل صفوف الجدول قابلة للنقر لفتح التقرير
        document.addEventListener('DOMContentLoaded', function() {
            const reportRows = document.querySelectorAll('.report-row');
            const modal = document.getElementById('reportModal');
            const modalClose = document.getElementById('modalClose');
            
            // فتح الـ modal عند النقر على الصف
            reportRows.forEach(row => {
                row.addEventListener('click', function(e) {
                    // منع النقر إذا كان النقر على رابط أو زر
                    if (e.target.closest('a') || e.target.closest('button')) {
                        return;
                    }
                    
                    // جلب البيانات من data attributes
                    const reportId = row.getAttribute('data-report-id');
                    const reportTitle = row.getAttribute('data-report-title');
                    const reportNote = row.getAttribute('data-report-note');
                    const reportFaculty = row.getAttribute('data-report-faculty');
                    const reportFile = row.getAttribute('data-report-file');
                    const reportDate = row.getAttribute('data-report-date');
                    const reportTime = row.getAttribute('data-report-time');
                    
                    // ملء الـ modal بالبيانات
                    document.getElementById('modalTitle').textContent = reportTitle;
                    document.getElementById('modalReportTitle').textContent = reportTitle;
                    document.getElementById('modalFaculty').textContent = reportFaculty;
                    document.getElementById('modalNote').textContent = reportNote || 'لا توجد ملاحظات';
                    document.getElementById('modalDate').textContent = reportDate + ' في الساعة ' + reportTime;
                    
                    // عرض المرفق إن وجد
                    const modalFileDiv = document.getElementById('modalFile');
                    if (reportFile && reportFile.trim() !== '') {
                        modalFileDiv.innerHTML = `
                            <div class="modal-file-section">
                                <a href="${reportFile}" class="modal-file-link" download>
                                    <i class="fa-solid fa-download"></i>
                                    <span>تحميل الملف المرفق</span>
                                </a>
                            </div>
                        `;
                    } else {
                        modalFileDiv.innerHTML = '<span class="modal-no-file">لا يوجد ملف مرفق</span>';
                    }
                    
                    // فتح الـ modal
                    modal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
            });
            
            // إغلاق الـ modal
            modalClose.addEventListener('click', function() {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            });
            
            // إغلاق الـ modal عند النقر خارج المحتوى
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
            
            // إغلاق الـ modal عند الضغط على ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.classList.contains('active')) {
                    modal.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
    </script>
</body>
</html>