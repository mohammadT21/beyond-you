<?php
// dashboard_admin.php - لوحة تحكم المدير العام
require_once 'config.php';

// تحقق من أن المستخدم مسجل كـ Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// جلب إحصائيات سريعة
$conn = db_connect();

// عدد الكليات
$faculties_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM faculties"))['count'];

// عدد العمداء
$deans_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'dean'"))['count'];

// عدد التقارير هذا الشهر
$current_month = date('n');
$current_year = date('Y');
$reports_count = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as count FROM reports WHERE MONTH(created_at) = $current_month AND YEAR(created_at) = $current_year"
))['count'];

// أحدث التقارير
$recent_reports = [];
$reports_result = mysqli_query($conn, 
    "SELECT r.id, r.title, f.name as faculty_name, r.created_at 
     FROM reports r 
     JOIN faculties f ON r.faculty_id = f.id 
     ORDER BY r.created_at DESC 
     LIMIT 5"
);
while ($row = mysqli_fetch_assoc($reports_result)) {
    $recent_reports[] = $row;
}

db_close($conn);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة المدير العام - نظام Beyond You</title>
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

        .sidebar-image {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 200px;
            background: linear-gradient(180deg, transparent 0%, rgba(0, 217, 165, 0.1) 100%);
            opacity: 0.5;
            z-index: 0;
            pointer-events: none;
        }

        .sidebar-image::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 150px;
            background: 
                radial-gradient(ellipse at center, rgba(0, 217, 165, 0.15) 0%, transparent 70%),
                url('data:image/svg+xml,<svg width="300" height="300" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="sidegrad" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:rgba(0,217,165,0.2);stop-opacity:1" /><stop offset="100%" style="stop-color:rgba(99,102,241,0.15);stop-opacity:1" /></linearGradient></defs><circle cx="150" cy="150" r="120" fill="url(%23sidegrad)"/><path d="M100,150 Q150,100 200,150 T300,150" stroke="rgba(0,217,165,0.1)" stroke-width="2" fill="none"/></svg>');
            background-size: cover;
            background-position: center bottom;
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

        .sidebar-header::after {
            content: '';
            position: absolute;
            bottom: -1px;
            right: 0;
            left: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
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
        }

        .sidebar.collapsed .sidebar-logo h1 span {
            display: none;
        }

        .sidebar-logo i {
            font-size: 1.5rem;
            animation: pulse 2s ease-in-out infinite;
            flex-shrink: 0;
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

        .welcome-section {
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

        .welcome-section::before {
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

        .welcome-section::after {
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
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
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

        .welcome-section h2 {
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

        .welcome-section p {
            color: #94a3b8;
            font-size: 1.2rem;
            line-height: 1.8;
            position: relative;
            z-index: 1;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            padding: 35px 30px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(148, 163, 184, 0.1);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out both;
        }

        .stat-card::before {
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

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }

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

        .stat-card:hover {
            transform: translateY(-10px) scale(1.03);
            box-shadow: 0 20px 60px rgba(0, 217, 165, 0.3);
            border-color: rgba(0, 217, 165, 0.3);
        }

        .stat-icon {
            font-size: 3.5rem;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            transition: transform 0.4s ease;
            display: inline-block;
            position: relative;
            z-index: 1;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.15) rotate(5deg);
            filter: drop-shadow(0 0 20px rgba(0, 217, 165, 0.5));
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            line-height: 1.2;
            position: relative;
            z-index: 1;
        }

        .stat-label {
            color: #94a3b8;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: -0.01em;
            position: relative;
            z-index: 1;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 50px;
        }

        .action-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            padding: 40px 35px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            color: inherit;
            border: 1px solid rgba(148, 163, 184, 0.1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            animation: fadeInUp 0.6s ease-out both;
        }

        .action-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0, 217, 165, 0.1) 0%, rgba(99, 102, 241, 0.1) 100%);
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .action-card:hover::after {
            opacity: 1;
        }

        .action-card:nth-child(1) { animation-delay: 0.5s; }
        .action-card:nth-child(2) { animation-delay: 0.6s; }
        .action-card:nth-child(3) { animation-delay: 0.7s; }
        .action-card:nth-child(4) { animation-delay: 0.8s; }
        .action-card:nth-child(5) { animation-delay: 0.9s; }
        .action-card:nth-child(6) { animation-delay: 1s; }
        .action-card:nth-child(7) { animation-delay: 1.1s; }

        .action-card:hover {
            transform: translateY(-10px) scale(1.03);
            box-shadow: 0 25px 70px rgba(0, 217, 165, 0.4);
            border-color: rgba(0, 217, 165, 0.4);
        }

        .action-icon {
            font-size: 4rem;
            margin-bottom: 25px;
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            transition: all 0.4s ease;
            display: inline-block;
            position: relative;
            z-index: 1;
        }

        .action-card:hover .action-icon {
            transform: scale(1.2) translateY(-8px);
            filter: drop-shadow(0 10px 30px rgba(0, 217, 165, 0.6));
        }

        .action-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
            letter-spacing: -0.01em;
        }

        .action-desc {
            color: #94a3b8;
            line-height: 1.8;
            font-size: 1rem;
            position: relative;
            z-index: 1;
        }

        .action-card.warning {
            border-color: rgba(239, 68, 68, 0.3);
        }

        .action-card.warning::after {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);
        }

        .action-card.warning:hover {
            border-color: rgba(239, 68, 68, 0.5);
            box-shadow: 0 25px 70px rgba(239, 68, 68, 0.3);
        }

        .action-card.warning .action-icon {
            background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .reports-section {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(148, 163, 184, 0.1);
            animation: fadeInUp 0.8s ease-out 1.2s both;
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

        .reports-list {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .report-item {
            display: flex;
            align-items: center;
            padding: 22px 28px;
            background: rgba(15, 23, 42, 0.4);
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
            transition: all 0.3s ease;
            position: relative;
            gap: 20px;
            color: inherit;
            text-decoration: none;
        }
        
        .report-item:visited {
            color: inherit;
        }

        .report-item:first-child {
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }

        .report-item:last-child {
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
            border-bottom: none;
        }

        .report-item::before {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #009879;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .report-item:hover {
            background: rgba(0, 217, 165, 0.1);
            padding-right: 35px;
            box-shadow: 0 4px 20px rgba(0, 217, 165, 0.2);
            border-color: rgba(0, 217, 165, 0.2);
        }

        .report-item:hover::before {
            opacity: 1;
        }

        .report-icon {
            width: 52px;
            height: 52px;
            background: rgba(0, 217, 165, 0.1);
            border: 1px solid rgba(0, 217, 165, 0.2);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #00d9a5;
            font-size: 1.4rem;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .report-item:hover .report-icon {
            background: linear-gradient(135deg, rgba(0, 217, 165, 0.2) 0%, rgba(99, 102, 241, 0.2) 100%);
            border-color: rgba(0, 217, 165, 0.4);
            color: #00d9a5;
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 4px 15px rgba(0, 217, 165, 0.3);
        }

        .report-content {
            flex: 1;
            min-width: 0;
        }

        .report-info h4 {
            color: #ffffff;
            margin-bottom: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            line-height: 1.5;
        }

        .report-meta {
            color: #94a3b8;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .report-meta i {
            color: #00d9a5;
            font-size: 0.9rem;
        }

        .report-date-wrapper {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 8px;
            flex-shrink: 0;
        }

        .report-date {
            color: #cbd5e1;
            font-size: 0.9rem;
            font-weight: 600;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: rgba(0, 217, 165, 0.1);
            border: 1px solid rgba(0, 217, 165, 0.2);
            border-radius: 10px;
        }

        .report-date i {
            font-size: 0.85rem;
            color: #00d9a5;
        }

        .report-time {
            color: #94a3b8;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .report-time i {
            color: #64748b;
        }

        .empty-state {
            text-align: center;
            color: #94a3b8;
            padding: 80px 30px;
            font-size: 1.1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 20px;
        }

        .empty-state i {
            font-size: 5rem;
            background: linear-gradient(135deg, #64748b 0%, #475569 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            opacity: 0.4;
        }

        .empty-state p {
            color: #94a3b8;
            font-size: 1.1rem;
            margin: 0;
        }

        .footer {
            text-align: center;
            padding: 30px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            margin-top: 50px;
        }

        /* Mobile Styles */
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

        .welcome-section {
            padding: 35px 25px;
        }

        .welcome-section h2 {
            font-size: 2rem;
        }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .stat-card {
                padding: 25px;
            }

            .stat-icon {
                font-size: 2.5rem;
            }

            .reports-section {
                padding: 25px 20px;
            }

            .report-item {
                padding: 15px;
                gap: 12px;
                flex-wrap: wrap;
            }

            .report-icon {
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
            }

            .report-date-wrapper {
                width: 100%;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
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
                <a href="dashboard_admin.php" class="nav-item active">
                    <i class="fa-solid fa-house"></i>
                    <span>الرئيسية</span>
                </a>
                <a href="sustainability_indicators.php" class="nav-item">
                    <i class="fa-solid fa-chart-area"></i>
                    <span>مؤشرات الاستدامة</span>
                </a>
                <a href="faculties.php" class="nav-item">
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
                <a href="deans_list.php" class="nav-item">
                    <i class="fa-solid fa-users"></i>
                    <span>قائمة العمداء</span>
                </a>
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
            <div>
                <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="قائمة">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
            <div class="topbar-actions">
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <div class="container">
        <!-- قسم الترحيب -->
        <div class="welcome-section">
            <h2>مرحباً بك في نظام إدارة الاستدامة</h2>
            <p>إدارة الكليات، متابعة المؤشرات، وتحليل الأداء البيئي في مكان واحد</p>
        </div>

        <!-- الإحصائيات السريعة -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-file-lines"></i></div>
                <div class="stat-number"><?= $reports_count ?></div>
                <div class="stat-label">تقرير هذا الشهر</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-chart-line"></i></div>
                <div class="stat-number">10</div>
                <div class="stat-label">مؤشر استدامة</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-user-tie"></i></div>
                <div class="stat-number"><?= $deans_count ?></div>
                <div class="stat-label">عميد مسجل</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-building"></i></div>
                <div class="stat-number">16</div>
                <div class="stat-label">كلية جامعية</div>
            </div>
        </div>

        <!-- أحدث التقارير -->
        <div class="reports-section">
            <div class="section-title">
                <i class="fa-solid fa-house"></i>
                <span>أحدث التقارير الواردة</span>
            </div>
            
            <div class="reports-list">
                <?php if (count($recent_reports) > 0): ?>
                    <?php foreach ($recent_reports as $report): ?>
                        <a href="reports_admin.php#report-<?= $report['id'] ?>" class="report-item" style="text-decoration: none; display: block; cursor: pointer;">
                            <div class="report-icon">
                                <i class="fa-solid fa-file-lines"></i>
                            </div>
                            <div class="report-content">
                                <div class="report-info">
                                    <h4><?= htmlspecialchars($report['title']) ?></h4>
                                    <div class="report-meta">
                                        <i class="fa-solid fa-building"></i>
                                        <span><?= htmlspecialchars($report['faculty_name']) ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="report-date-wrapper">
                                <div class="report-date">
                                    <i class="fa-solid fa-calendar-days"></i>
                                    <?= date('Y-m-d', strtotime($report['created_at'])) ?>
                                </div>
                                <div class="report-time">
                                    <i class="fa-solid fa-clock"></i>
                                    <?= date('H:i', strtotime($report['created_at'])) ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-folder-open"></i>
                        <p>لا توجد تقارير واردة بعد</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

            </div>
        </div>
    </main>

    <!-- Mobile Overlay -->
    <div class="mobile-overlay" id="mobileOverlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 999; opacity: 0; transition: opacity 0.3s ease;"></div>

    <script>
        // Sidebar collapse toggle
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mobileOverlay = document.getElementById('mobileOverlay');

        // Load sidebar state from localStorage
        const sidebarState = localStorage.getItem('sidebarCollapsed');
        if (sidebarState === 'true') {
            sidebar.classList.add('collapsed');
        }

        // Toggle collapse
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                sidebar.classList.toggle('collapsed');
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            });
        }

        // Mobile menu toggle
        function toggleMobileSidebar() {
            sidebar.classList.toggle('active');
            if (sidebar.classList.contains('active')) {
                mobileOverlay.style.display = 'block';
                setTimeout(() => mobileOverlay.style.opacity = '1', 10);
            } else {
                mobileOverlay.style.opacity = '0';
                setTimeout(() => mobileOverlay.style.display = 'none', 300);
            }
        }

        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', toggleMobileSidebar);
            mobileOverlay.addEventListener('click', toggleMobileSidebar);
        }

        // Close sidebar on window resize if it's desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                mobileOverlay.style.display = 'none';
                mobileOverlay.style.opacity = '0';
            }
        });

        // Set active nav item based on current page
        const currentPage = window.location.pathname.split('/').pop();
        document.querySelectorAll('.nav-item').forEach(item => {
            const href = item.getAttribute('href');
            if (href && href.includes(currentPage)) {
                item.classList.add('active');
            }
        });

        // Tooltip for collapsed sidebar items
        if (sidebar.classList.contains('collapsed')) {
            document.querySelectorAll('.nav-item, .sidebar-logout').forEach(item => {
                item.setAttribute('title', item.querySelector('span')?.textContent || '');
            });
        }

        // Update tooltips when sidebar state changes
        sidebarToggle?.addEventListener('click', function() {
            setTimeout(() => {
                document.querySelectorAll('.nav-item, .sidebar-logout').forEach(item => {
                    if (sidebar.classList.contains('collapsed')) {
                        item.setAttribute('title', item.querySelector('span')?.textContent || '');
                    } else {
                        item.removeAttribute('title');
                    }
                });
            }, 300);
        });
    </script>
</body>
</html>