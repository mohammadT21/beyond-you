<?php
// faculty_dashboard.php - لوحة عرض أداء الكلية في جميع المؤشرات
require_once 'config.php';

// تحقق من جلسة المدير
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: faculties.php');
    exit;
}

$conn = db_connect();
$faculty_id = intval($_GET['id']);

// جلب اسم الكلية
$sql_faculty = "SELECT name FROM faculties WHERE id = $faculty_id LIMIT 1";
$result_faculty = mysqli_query($conn, $sql_faculty);
if (!$result_faculty || mysqli_num_rows($result_faculty) === 0) {
    die('⚠️ الكلية غير موجودة.');
}
$faculty = mysqli_fetch_assoc($result_faculty);
$faculty_name = htmlspecialchars($faculty['name']);

// تحديد أحدث شهر وسنة لهذه الكلية
$month_sql = "SELECT MAX(year) AS max_year, MAX(month) AS max_month FROM records WHERE faculty_id = $faculty_id";
$month_result = mysqli_query($conn, $month_sql);
$row = mysqli_fetch_assoc($month_result);
$current_year = $row['max_year'] ?? date('Y');
$current_month = $row['max_month'] ?? date('n');

// جلب بيانات جميع مؤشرات الاستدامة للكلية
$data_sql = "
    SELECT i.id, i.name AS indicator_name, i.unit AS unit, COALESCE(r.value, 0) AS value
    FROM indicators i
    LEFT JOIN records r ON i.id = r.indicator_id 
        AND r.faculty_id = $faculty_id 
        AND r.year = $current_year 
        AND r.month = $current_month
    ORDER BY i.id ASC
";
$data_result = mysqli_query($conn, $data_sql);

$indicators = [];
$values = [];
$units = [];

while ($row = mysqli_fetch_assoc($data_result)) {
    $indicators[] = $row['indicator_name'];
    $values[] = floatval($row['value']);
    $units[] = $row['unit'];
}

// جلب متوسط أداء الكليات لكل مؤشر للمقارنة
$comparison_data = [];
foreach ($indicators as $index => $indicator) {
    $indicator_id = $index + 1;
    $avg_sql = "SELECT AVG(value) as avg_value FROM records 
                WHERE indicator_id = $indicator_id 
                AND year = $current_year 
                AND month = $current_month";
    $avg_result = mysqli_query($conn, $avg_sql);
    $avg_row = mysqli_fetch_assoc($avg_result);
    $comparison_data[] = floatval($avg_row['avg_value'] ?? 0);
}

db_close($conn);

// أيقونات المؤشرات (Font Awesome)
$icons = [
    1 => 'fa-solid fa-droplet', 2 => 'fa-solid fa-bolt', 3 => 'fa-solid fa-file-lines', 4 => 'fa-solid fa-recycle', 5 => 'fa-solid fa-trash',
    6 => 'fa-solid fa-tree', 7 => 'fa-solid fa-users', 8 => 'fa-solid fa-clock', 9 => 'fa-solid fa-bullhorn', 10 => 'fa-solid fa-trophy'
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $faculty_name ?> - نظام Beyond You</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            margin: 0;
            padding: 0;
            display: flex;
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
                transform: scale(1.1);
            }
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            color: white;
            height: 100vh;
            position: fixed;
            right: 0;
            top: 0;
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: -4px 0 30px rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 1px solid rgba(148, 163, 184, 0.1);
            position: relative;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                linear-gradient(180deg, rgba(0, 152, 121, 0.15) 0%, transparent 60%),
                radial-gradient(ellipse at bottom, rgba(99, 102, 241, 0.2) 0%, transparent 70%),
                url('data:image/svg+xml,<svg width="600" height="800" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="60" height="60" patternUnits="userSpaceOnUse"><path d="M 60 0 L 0 0 0 60" fill="none" stroke="rgba(0,217,165,0.08)" stroke-width="1"/></pattern><radialGradient id="glow"><stop offset="0%" style="stop-color:rgba(0,217,165,0.3);stop-opacity:1" /><stop offset="100%" style="stop-color:rgba(99,102,241,0.2);stop-opacity:0" /></radialGradient></defs><rect width="600" height="800" fill="url(%23grid)"/><circle cx="300" cy="400" r="200" fill="url(%23glow)"/></svg>');
            background-size: 100% 100%, 100% 100%, cover;
            background-position: center, center, center;
            background-repeat: no-repeat;
            opacity: 0.6;
            pointer-events: none;
            z-index: 0;
        }

        .sidebar > * {
            position: relative;
            z-index: 1;
        }

        .sidebar.collapsed {
            width: 80px;
        }

        .sidebar-header {
            padding: 35px 25px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: padding 0.3s ease;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(10px);
        }

        .sidebar.collapsed .sidebar-header {
            padding: 35px 15px;
            justify-content: center;
        }

        .sidebar-toggle {
            background: rgba(0, 152, 121, 0.2);
            border: 1px solid rgba(0, 152, 121, 0.3);
            color: #00d9a5;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            flex-shrink: 0;
            backdrop-filter: blur(10px);
        }

        .sidebar-toggle:hover {
            background: rgba(0, 152, 121, 0.3);
            border-color: rgba(0, 152, 121, 0.5);
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(0, 217, 165, 0.3);
        }

        .sidebar-toggle i {
            font-size: 1rem;
            transition: transform 0.3s ease;
        }

        .sidebar.collapsed .sidebar-toggle i {
            transform: rotate(180deg);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .sidebar.collapsed .sidebar-logo {
            justify-content: center;
            margin-bottom: 0;
        }

        .sidebar-logo h1 {
            font-size: 1.6rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
            transition: opacity 0.3s ease;
            color: #ffffff;
        }

        .sidebar.collapsed .sidebar-logo h1 span {
            display: none;
        }

        .sidebar-logo i {
            font-size: 1.5rem;
            animation: pulse 2s ease-in-out infinite;
            flex-shrink: 0;
            color: #00d9a5;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }

        .sidebar-logo p {
            font-size: 0.85rem;
            opacity: 0.9;
            margin-top: 5px;
            white-space: nowrap;
            transition: opacity 0.3s ease, max-height 0.3s ease;
            overflow: hidden;
            max-height: 20px;
        }

        .sidebar.collapsed .sidebar-logo p {
            opacity: 0;
            max-height: 0;
            margin-top: 0;
        }

        .sidebar-user {
            padding: 25px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: padding 0.3s ease;
            justify-content: center;
            background: rgba(15, 23, 42, 0.3);
            backdrop-filter: blur(10px);
        }

        .sidebar:not(.collapsed) .sidebar-user {
            justify-content: flex-start;
        }

        .sidebar.collapsed .sidebar-user {
            padding: 25px 15px;
        }

        .sidebar-user-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, rgba(0, 217, 165, 0.2) 0%, rgba(99, 102, 241, 0.2) 100%);
            border: 2px solid rgba(0, 217, 165, 0.3);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
            color: #00d9a5;
            box-shadow: 0 4px 15px rgba(0, 217, 165, 0.2);
        }

        .sidebar-user-info {
            flex: 1;
            min-width: 0;
            transition: opacity 0.3s ease, max-width 0.3s ease;
            overflow: hidden;
        }

        .sidebar.collapsed .sidebar-user-info {
            opacity: 0;
            max-width: 0;
            margin: 0;
            padding: 0;
        }

        .sidebar-user-name {
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #ffffff;
        }

        .sidebar-user-role {
            font-size: 0.8rem;
            color: #94a3b8;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-nav {
            flex: 1;
            padding: 20px 0;
            overflow-y: auto;
        }

        .nav-section {
            margin-bottom: 30px;
        }

        .nav-section-title {
            padding: 0 25px 15px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #64748b;
            white-space: nowrap;
            transition: opacity 0.3s ease, max-height 0.3s ease, padding 0.3s ease;
            overflow: hidden;
            max-height: 30px;
        }

        .sidebar.collapsed .nav-section-title {
            opacity: 0;
            max-height: 0;
            padding: 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 25px;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            border-right: 3px solid transparent;
            justify-content: flex-start;
            margin: 2px 0;
            border-radius: 0 12px 12px 0;
        }

        .sidebar.collapsed .nav-item {
            justify-content: center;
            padding: 15px;
            border-radius: 12px;
            margin: 4px 12px;
        }

        .nav-item::before {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 0;
            background: linear-gradient(90deg, transparent, rgba(0, 217, 165, 0.1));
            transition: width 0.3s ease;
            border-radius: 0 12px 12px 0;
        }

        .nav-item:hover {
            background: rgba(0, 217, 165, 0.1);
            color: #00d9a5;
            border-right-color: rgba(0, 217, 165, 0.5);
        }

        .nav-item:hover::before {
            width: 100%;
        }

        .nav-item.active {
            background: linear-gradient(90deg, rgba(0, 217, 165, 0.15), rgba(0, 217, 165, 0.05));
            color: #00d9a5;
            border-right-color: #00d9a5;
            font-weight: 600;
            box-shadow: -2px 0 10px rgba(0, 217, 165, 0.2);
        }

        .nav-item.active::before {
            width: 100%;
        }

        .nav-item i {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
            flex-shrink: 0;
            transition: transform 0.3s ease;
        }

        .nav-item:hover i {
            transform: scale(1.1);
        }

        .nav-item span {
            font-size: 0.95rem;
            white-space: nowrap;
            transition: opacity 0.3s ease, max-width 0.3s ease;
            overflow: hidden;
        }

        .sidebar.collapsed .nav-item span {
            opacity: 0;
            max-width: 0;
        }

        .sidebar-footer {
            padding: 25px;
            border-top: 1px solid rgba(148, 163, 184, 0.1);
            transition: padding 0.3s ease;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(20px);
            position: relative;
            z-index: 2;
        }

        .sidebar.collapsed .sidebar-footer {
            padding: 25px 15px;
        }

        .sidebar-logout {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            border: 1px solid rgba(239, 68, 68, 0.3);
            justify-content: flex-start;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }

        .sidebar.collapsed .sidebar-logout {
            justify-content: center;
            padding: 14px;
        }

        .sidebar-logout:hover {
            background: rgba(239, 68, 68, 0.25);
            border-color: rgba(239, 68, 68, 0.5);
            color: #f87171;
            transform: translateX(-3px);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.2);
        }

        .sidebar.collapsed .sidebar-logout:hover {
            transform: scale(1.05);
        }

        .sidebar-logout i {
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .sidebar-logout span {
            white-space: nowrap;
            transition: opacity 0.3s ease, max-width 0.3s ease;
            overflow: hidden;
            font-size: 0.9rem;
        }

        .sidebar.collapsed .sidebar-logout span {
            opacity: 0;
            max-width: 0;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin-right: 280px;
            transition: margin-right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar.collapsed ~ .main-content {
            margin-right: 80px;
        }

        .topbar {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            padding: 25px 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }

        .topbar-title {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.02em;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 18px;
            background: rgba(0, 217, 165, 0.1);
            border: 1px solid rgba(0, 217, 165, 0.2);
            border-radius: 14px;
            backdrop-filter: blur(10px);
        }

        .topbar-user-name {
            font-weight: 600;
            color: #00d9a5;
            font-size: 0.95rem;
        }

        .mobile-menu-toggle {
            display: none;
            background: #009879;
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            font-size: 1.2rem;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            margin-left: 15px;
        }

        .content-area {
            flex: 1;
            padding: 30px;
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .page-header {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            margin-bottom: 35px;
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

        .faculty-info {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .faculty-icon {
            font-size: 2.5rem;
            background: linear-gradient(135deg, rgba(0, 217, 165, 0.2) 0%, rgba(99, 102, 241, 0.2) 100%);
            width: 100px;
            height: 100px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #00d9a5;
            border: 1px solid rgba(0, 217, 165, 0.3);
            box-shadow: 0 4px 15px rgba(0, 217, 165, 0.2);
            position: relative;
            z-index: 1;
        }

        .faculty-details h2 {
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2rem;
            margin-bottom: 5px;
            font-weight: 800;
            position: relative;
            z-index: 1;
        }

        .faculty-status {
            color: #94a3b8;
            font-size: 1.1rem;
            position: relative;
            z-index: 1;
        }

        .period-info {
            background: rgba(0, 217, 165, 0.1);
            backdrop-filter: blur(10px);
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 20px;
            border: 1px solid rgba(0, 217, 165, 0.2);
            color: #00d9a5;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }

        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin: 35px 0 0 0;
        }

        .overview-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            padding: 28px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            text-align: center;
            border: 1px solid rgba(148, 163, 184, 0.1);
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInUp 0.6s ease-out both;
        }

        .overview-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0, 217, 165, 0.05) 0%, rgba(99, 102, 241, 0.05) 100%);
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .overview-card:hover::before {
            opacity: 1;
        }

        .overview-card:hover {
            transform: translateY(-10px) scale(1.03);
            box-shadow: 0 20px 60px rgba(0, 217, 165, 0.3);
            border-color: rgba(0, 217, 165, 0.3);
        }

        .overview-card:nth-child(1) { animation-delay: 0.1s; }
        .overview-card:nth-child(2) { animation-delay: 0.2s; }
        .overview-card:nth-child(3) { animation-delay: 0.3s; }
        .overview-card:nth-child(4) { animation-delay: 0.4s; }

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

        .overview-value {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
            line-height: 1.2;
            position: relative;
            z-index: 1;
        }

        .overview-label {
            color: #94a3b8;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: -0.01em;
            position: relative;
            z-index: 1;
        }

        .chart-section {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            margin-bottom: 30px;
            border: 1px solid rgba(148, 163, 184, 0.1);
            position: relative;
            overflow: hidden;
        }

        .chart-section::before {
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
            position: relative;
            z-index: 1;
        }

        .section-title i {
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.8rem;
        }

        .chart-container {
            position: relative;
            height: 500px;
            width: 100%;
        }

        .indicators-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .indicator-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(148, 163, 184, 0.1);
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInUp 0.6s ease-out both;
        }

        .indicator-card::before {
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

        .indicator-card:hover {
            transform: translateY(-10px) scale(1.03);
            box-shadow: 0 20px 60px rgba(0, 217, 165, 0.3);
            border-color: rgba(0, 217, 165, 0.3);
        }

        .indicator-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .indicator-icon {
            font-size: 1.8rem;
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, rgba(0, 217, 165, 0.2) 0%, rgba(99, 102, 241, 0.2) 100%);
            border: 1px solid rgba(0, 217, 165, 0.3);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #00d9a5;
            box-shadow: 0 4px 15px rgba(0, 217, 165, 0.2);
            transition: all 0.4s ease;
            position: relative;
            z-index: 1;
        }

        .indicator-card:hover .indicator-icon {
            transform: scale(1.15) rotate(5deg);
            box-shadow: 0 6px 25px rgba(0, 217, 165, 0.4);
            background: linear-gradient(135deg, rgba(0, 217, 165, 0.3) 0%, rgba(99, 102, 241, 0.3) 100%);
            border-color: rgba(0, 217, 165, 0.5);
        }

        .indicator-title {
            flex: 1;
        }

        .indicator-name {
            font-size: 1.2rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 5px;
            position: relative;
            z-index: 1;
        }

        .indicator-unit {
            color: #94a3b8;
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
        }

        .indicator-value {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #00d9a5, #6366f1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-align: center;
            margin: 20px 0;
            position: relative;
            z-index: 1;
        }

        .comparison-bar {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(10px);
            height: 10px;
            border-radius: 8px;
            margin: 15px 0;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.2);
            position: relative;
            z-index: 1;
        }

        .faculty-progress {
            background: linear-gradient(90deg, #00d9a5, #6366f1);
            height: 100%;
            border-radius: 8px;
            transition: width 0.5s ease;
            box-shadow: 0 0 10px rgba(0, 217, 165, 0.5);
        }

        .comparison-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #94a3b8;
            margin-top: 10px;
            position: relative;
            z-index: 1;
        }

        .performance-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid;
            position: relative;
            z-index: 1;
        }

        .performance-excellent { 
            background: rgba(34, 197, 94, 0.15); 
            color: #4ade80; 
            border-color: rgba(34, 197, 94, 0.3);
        }
        .performance-good { 
            background: rgba(59, 130, 246, 0.15); 
            color: #60a5fa; 
            border-color: rgba(59, 130, 246, 0.3);
        }
        .performance-average { 
            background: rgba(234, 179, 8, 0.15); 
            color: #fbbf24; 
            border-color: rgba(234, 179, 8, 0.3);
        }
        .performance-poor { 
            background: rgba(239, 68, 68, 0.15); 
            color: #f87171; 
            border-color: rgba(239, 68, 68, 0.3);
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

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid rgba(148, 163, 184, 0.1);
        }

        .no-data .icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
            background: linear-gradient(135deg, #64748b 0%, #475569 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .mobile-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            backdrop-filter: blur(5px);
        }

        .mobile-overlay.active {
            display: block;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(100%);
                width: 280px;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .sidebar.collapsed {
                width: 280px;
            }

            .main-content {
                margin-right: 0 !important;
            }

            .sidebar-toggle {
                display: none;
            }

            .mobile-menu-toggle {
                display: flex;
            }

            .topbar {
                padding: 15px 20px;
            }

            .topbar-title {
                font-size: 1.2rem;
            }

            .topbar-user-name {
                display: none;
            }

            .content-area {
                padding: 20px 15px;
            }

            .faculty-info {
                flex-direction: column;
                text-align: center;
            }

            .stats-overview {
                grid-template-columns: 1fr;
            }

            .indicators-grid {
                grid-template-columns: 1fr;
            }

            .chart-container {
                height: 400px;
            }
        }

        /* Sidebar scrollbar */
        .sidebar-nav::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-nav::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }

        .sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <h1>
                    <i class="fa-solid fa-seedling"></i>
                    <span>Beyond You</span>
                </h1>
                <p>لوحة أداء الكلية</p>
            </div>
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="طي/فتح القائمة">
                <i class="fa-solid fa-angle-right"></i>
            </button>
        </div>

        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <i class="fa-solid fa-user-tie"></i>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                <div class="sidebar-user-role">المدير العام</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">القائمة الرئيسية</div>
                <a href="dashboard_admin.php" class="nav-item">
                    <i class="fa-solid fa-house"></i>
                    <span>الرئيسية</span>
                </a>
                <a href="sustainability_indicators.php" class="nav-item">
                    <i class="fa-solid fa-chart-area"></i>
                    <span>مؤشرات الاستدامة</span>
                </a>
                <a href="faculties.php" class="nav-item active">
                    <i class="fa-solid fa-building-columns"></i>
                    <span>إدارة الكليات</span>
                </a>
                <a href="reports_admin.php" class="nav-item">
                    <i class="fa-solid fa-inbox"></i>
                    <span>التقارير الواردة</span>
                </a>
                <a href="best_faculties.php" class="nav-item">
                    <i class="fa-solid fa-trophy"></i>
                    <span>الكليات المتميزة</span>
                </a>
                <a href="ai_recommendations.php" class="nav-item">
                    <i class="fa-solid fa-robot"></i>
                    <span>توصيات الذكاء الاصطناعي</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">الإدارة</div>
                <a href="create_dean.php" class="nav-item">
                    <i class="fa-solid fa-user-plus"></i>
                    <span>تسجيل عمداء جدد</span>
                </a>
                <a href="change_password.php" class="nav-item">
                    <i class="fa-solid fa-key"></i>
                    <span>تغيير كلمة المرور</span>
                </a>
                <a href="reset_system.php" class="nav-item">
                    <i class="fa-solid fa-broom"></i>
                    <span>تنظيف النظام</span>
                </a>
            </div>
        </nav>

        <div class="sidebar-footer">
            <a href="logout.php" class="sidebar-logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>تسجيل الخروج</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div style="display: flex; align-items: center; gap: 15px;">
                <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="قائمة">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h1 class="topbar-title"><?= $faculty_name ?></h1>
            </div>
            <div class="topbar-actions">
                <div class="topbar-user">
                    <i class="fa-solid fa-user-tie" style="color: #009879;"></i>
                    <span class="topbar-user-name"><?= htmlspecialchars($_SESSION['username']) ?></span>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <div class="container">
        <!-- عنوان الصفحة -->
        <div class="page-header">
            <div class="faculty-info">
                <div class="faculty-icon">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <div class="faculty-details">
                    <h2><?= $faculty_name ?></h2>
                    <div class="faculty-status">أداء الكلية في مؤشرات الاستدامة</div>
                </div>
            </div>
            
            <div class="period-info">
                <i class="fa-solid fa-calendar-days"></i>
                عرض بيانات شهر: <strong><?= $current_month ?></strong> / <strong><?= $current_year ?></strong>
            </div>

            <!-- نظرة عامة -->
            <div class="stats-overview">
                <div class="overview-card">
                    <div class="overview-value"><?= count($indicators) ?></div>
                    <div class="overview-label">مؤشر مستدام</div>
                </div>
                <div class="overview-card">
                    <div class="overview-value"><?= number_format(array_sum($values), 2) ?></div>
                    <div class="overview-label">إجمالي القيم</div>
                </div>
                <div class="overview-card">
                    <div class="overview-value"><?= number_format(array_sum($values) / max(count($values), 1), 2) ?></div>
                    <div class="overview-label">متوسط الأداء</div>
                </div>
                <div class="overview-card">
                    <div class="overview-value"><?= count(array_filter($values, function($v) { return $v > 0; })) ?></div>
                    <div class="overview-label">مؤشر مفعل</div>
                </div>
            </div>
        </div>

        <?php if (count($indicators) > 0): ?>
            <!-- الرسم البياني -->
            <div class="chart-section">
                <div class="section-title">
                    <i class="fa-solid fa-chart-area"></i>
                    <span>أداء الكلية في جميع المؤشرات</span>
                </div>
                <div class="chart-container">
                    <canvas id="facultyChart"></canvas>
                </div>
            </div>

            <!-- شبكة المؤشرات -->
            <div class="section-title" style="margin-top: 30px; margin-bottom: 20px;">
                <i class="fa-solid fa-chart-bar"></i>
                <span>تفصيل المؤشرات</span>
            </div>
            <div class="indicators-grid">
                <?php foreach ($indicators as $index => $indicator): ?>
                    <?php 
                    $current_value = $values[$index];
                    $avg_value = $comparison_data[$index];
                    $performance_ratio = $avg_value > 0 ? ($current_value / $avg_value) : 0;
                    
                    // تحديد لون الأداء
                    if ($performance_ratio >= 1.2) {
                        $performance_class = 'performance-excellent';
                        $performance_text = 'متميز';
                    } elseif ($performance_ratio >= 0.8) {
                        $performance_class = 'performance-good';
                        $performance_text = 'جيد';
                    } elseif ($performance_ratio >= 0.5) {
                        $performance_class = 'performance-average';
                        $performance_text = 'متوسط';
                    } else {
                        $performance_class = '';
                        $performance_text = '';
                    }
                    ?>
                    <div class="indicator-card">
                        <div class="indicator-header">
                            <div class="indicator-icon">
                                <i class="<?= $icons[$index + 1] ?? 'fa-solid fa-chart-bar' ?>"></i>
                            </div>
                            <div class="indicator-title">
                                <div class="indicator-name"><?= htmlspecialchars($indicator) ?></div>
                                <div class="indicator-unit"><?= $units[$index] ?></div>
                            </div>
                        </div>
                        
                        <div class="indicator-value">
                            <?= number_format($current_value, 2) ?>
                        </div>

                        <?php if ($avg_value > 0): ?>
                            <div class="comparison-bar">
                                <div class="faculty-progress" style="width: <?= min($performance_ratio * 100, 100) ?>%"></div>
                            </div>
                            <div class="comparison-info">
                                <span>متوسط الكليات: <?= number_format($avg_value, 2) ?></span>
                                <span><?= number_format($performance_ratio * 100, 1) ?>%</span>
                            </div>
                            <?php if (!empty($performance_text)): ?>
                            <div class="performance-badge <?= $performance_class ?>">
                                <?= $performance_text ?>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="text-align: center; color: #64748b; font-size: 0.9rem; margin-top: 15px;">
                                لا توجد بيانات للمقارنة
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-data">
                <div class="icon"><i class="fa-solid fa-chart-bar"></i></div>
                <h3>لا توجد بيانات متاحة</h3>
                <p>لم يتم إدخال بيانات لهذه الكلية بعد</p>
            </div>
        <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Mobile Overlay -->
    <div class="mobile-overlay" id="mobileOverlay"></div>

    <script>
        // Sidebar Toggle
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mobileOverlay = document.getElementById('mobileOverlay');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
            });
        }

        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                mobileOverlay.classList.toggle('active');
            });
        }

        if (mobileOverlay) {
            mobileOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                mobileOverlay.classList.remove('active');
            });
        }
    </script>

    <script>
        <?php if (count($indicators) > 0): ?>
        // الرسم البياني
        const ctx = document.getElementById('facultyChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: <?= json_encode($indicators, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [
                    {
                        label: 'أداء الكلية',
                        data: <?= json_encode($values) ?>,
                        backgroundColor: 'rgba(0, 152, 121, 0.2)',
                        borderColor: '#009879',
                        borderWidth: 2,
                        pointBackgroundColor: '#009879',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: '#009879'
                    },
                    {
                        label: 'متوسط الكليات',
                        data: <?= json_encode($comparison_data) ?>,
                        backgroundColor: 'rgba(100, 116, 139, 0.2)',
                        borderColor: '#64748b',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointBackgroundColor: '#64748b',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: '#64748b'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        rtl: true,
                        labels: {
                            font: {
                                family: 'Tajawal',
                                size: 14
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'مقارنة أداء الكلية مع المتوسط العام',
                        font: {
                            family: 'Tajawal',
                            size: 16,
                            weight: 'bold'
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
                    r: {
                        angleLines: {
                            display: true
                        },
                        suggestedMin: 0,
                        ticks: {
                            font: {
                                family: 'Tajawal'
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>