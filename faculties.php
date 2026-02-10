<?php
// faculties.php - صفحة عرض وإدارة الكليات
require_once 'config.php';

// تحقق من جلسة المدير
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$conn = db_connect();

// جلب الكليات من قاعدة البيانات مع إحصائيات
$sql = "SELECT f.id, f.name, 
               COUNT(DISTINCT u.id) as deans_count,
               COUNT(DISTINCT r.id) as records_count,
               MAX(r.created_at) as last_update
        FROM faculties f
        LEFT JOIN users u ON f.id = u.faculty_id AND u.role = 'dean'
        LEFT JOIN records r ON f.id = r.faculty_id
        GROUP BY f.id, f.name
        ORDER BY f.id ASC";
$result = mysqli_query($conn, $sql);

$faculties = [];
while ($row = mysqli_fetch_assoc($result)) {
    $faculties[] = $row;
}

// جلب إحصائيات عامة
$total_faculties = count($faculties);
$total_deans = array_sum(array_column($faculties, 'deans_count'));
$active_faculties = count(array_filter($faculties, function($f) { 
    return $f['records_count'] > 0; 
}));

db_close($conn);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الكليات - نظام Beyond You</title>
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
            0% {
                background-position: -200% 0;
            }
            100% {
                background-position: 200% 0;
            }
        }

        .page-header h2 {
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.2rem;
            margin-bottom: 12px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            position: relative;
            z-index: 1;
        }

        .page-header h2 i {
            font-size: 2rem;
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-header p {
            color: #94a3b8;
            font-size: 1.15rem;
            line-height: 1.8;
            position: relative;
            z-index: 1;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin: 35px 0 0 0;
        }

        .stat-card {
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

        .stat-card:hover {
            transform: translateY(-10px) scale(1.03);
            box-shadow: 0 20px 60px rgba(0, 217, 165, 0.3);
            border-color: rgba(0, 217, 165, 0.3);
        }

        .stat-icon {
            font-size: 2.8rem;
            margin-bottom: 18px;
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

        .stat-label {
            color: #94a3b8;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: -0.01em;
            position: relative;
            z-index: 1;
        }

        .faculties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .faculty-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            color: inherit;
            border: 1px solid rgba(148, 163, 184, 0.1);
            display: block;
            position: relative;
            overflow: hidden;
            opacity: 0;
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .faculty-card::before {
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

        .faculty-card::after {
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

        .faculty-card:hover::after {
            opacity: 1;
        }

        .faculty-card:hover {
            transform: translateY(-10px) scale(1.03);
            box-shadow: 0 20px 60px rgba(0, 217, 165, 0.3);
            border-color: rgba(0, 217, 165, 0.3);
        }

        .faculty-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .faculty-icon {
            font-size: 2.2rem;
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(0, 217, 165, 0.2) 0%, rgba(99, 102, 241, 0.2) 100%);
            border: 1px solid rgba(0, 217, 165, 0.3);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #00d9a5;
            flex-shrink: 0;
            transition: all 0.4s ease;
            box-shadow: 0 4px 15px rgba(0, 217, 165, 0.2);
            position: relative;
            z-index: 1;
        }

        .faculty-card:hover .faculty-icon {
            transform: scale(1.15) rotate(5deg);
            box-shadow: 0 6px 25px rgba(0, 217, 165, 0.4);
            background: linear-gradient(135deg, rgba(0, 217, 165, 0.3) 0%, rgba(99, 102, 241, 0.3) 100%);
            border-color: rgba(0, 217, 165, 0.5);
        }

        .faculty-info {
            flex: 1;
        }

        .faculty-name {
            font-size: 1.4rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 5px;
            line-height: 1.3;
            position: relative;
            z-index: 1;
        }

        .faculty-id {
            color: #94a3b8;
            font-size: 0.9rem;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(10px);
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
            border: 1px solid rgba(148, 163, 184, 0.2);
            position: relative;
            z-index: 1;
        }

        .faculty-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 20px 0;
            padding: 20px;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.1);
            position: relative;
            z-index: 1;
        }

        .faculty-stat {
            text-align: center;
            padding: 10px;
            border-radius: 10px;
            transition: background 0.3s ease;
        }

        .faculty-stat:hover {
            background: rgba(0, 217, 165, 0.1);
        }

        .faculty-stat-value {
            font-size: 1.6rem;
            font-weight: 800;
            background: linear-gradient(135deg, #00d9a5, #6366f1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 6px;
            line-height: 1.2;
        }

        .faculty-stat-label {
            color: #94a3b8;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .last-update {
            color: #94a3b8;
            font-size: 0.85rem;
            text-align: center;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 12px;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            border: 1px solid rgba(148, 163, 184, 0.1);
            position: relative;
            z-index: 1;
        }

        .last-update i {
            font-size: 0.85rem;
            color: #00d9a5;
        }

        .view-dashboard {
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            color: white;
            border: none;
            padding: 14px 25px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            width: 100%;
            margin-top: 20px;
            font-family: 'Tajawal', sans-serif;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 217, 165, 0.3);
            z-index: 1;
        }

        .view-dashboard::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .view-dashboard:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0, 217, 165, 0.4);
        }

        .view-dashboard:hover::before {
            left: 100%;
        }

        .view-dashboard i {
            font-size: 1rem;
        }

        .status-indicator {
            position: absolute;
            top: 20px;
            left: 20px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .status-active {
            background: #00d9a5;
            box-shadow: 0 0 10px rgba(0, 217, 165, 0.5);
        }

        .status-inactive {
            background: #ef4444;
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.5);
        }

        .no-results {
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 30px;
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 2px dashed rgba(148, 163, 184, 0.3);
            color: #94a3b8;
            font-size: 1.1rem;
            font-weight: 600;
            gap: 15px;
            animation: fadeInUp 0.4s ease-out;
            grid-column: 1 / -1;
        }

        .no-results i {
            font-size: 3rem;
            background: linear-gradient(135deg, #64748b 0%, #475569 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            opacity: 0.5;
        }

        .no-results span {
            color: #94a3b8;
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

        .search-section {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            margin-bottom: 30px;
            border: 1px solid rgba(148, 163, 184, 0.1);
            animation: fadeInUp 0.8s ease-out 0.5s both;
        }

        .search-box {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .search-input-wrapper {
            flex: 1;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 15px 20px 15px 50px;
            border: 2px solid rgba(148, 163, 184, 0.2);
            border-radius: 12px;
            font-size: 1rem;
            font-family: 'Tajawal', sans-serif;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(10px);
            color: #e2e8f0;
        }

        .search-input::placeholder {
            color: #64748b;
        }

        .search-input:focus {
            outline: none;
            border-color: #00d9a5;
            box-shadow: 0 0 0 4px rgba(0, 217, 165, 0.1);
            background: rgba(30, 41, 59, 0.8);
        }

        .search-icon {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 1.1rem;
            pointer-events: none;
        }

        .search-input-wrapper:focus-within .search-icon {
            color: #00d9a5;
        }

        .search-btn {
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Tajawal', sans-serif;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 20px rgba(0, 217, 165, 0.3);
        }

        .search-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0, 217, 165, 0.4);
        }

        .search-btn i {
            font-size: 1rem;
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

            .container {
                padding: 0;
            }

            .page-header {
                padding: 30px 25px;
            }

            .page-header h2 {
                font-size: 1.8rem;
                flex-direction: column;
                gap: 10px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .stat-card {
                padding: 20px;
            }

            .stat-icon {
                font-size: 2.2rem;
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

            .faculties-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .faculty-card {
                padding: 25px;
            }

            .faculty-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .faculty-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
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
                <p>إدارة الكليات</p>
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
            <div style="display: flex; align-items: center; gap: 15px;">
                <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="قائمة">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h1 class="topbar-title">إدارة الكليات</h1>
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
            <h2><i class="fa-solid fa-building-columns"></i> الكليات الجامعية</h2>
            <p>إدارة ومتابعة أداء الكليات في مؤشرات الاستدامة</p>

            <!-- الإحصائيات السريعة -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-building"></i></div>
                    <div class="stat-number"><?= $total_faculties ?></div>
                    <div class="stat-label">كلية جامعية</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-user-tie"></i></div>
                    <div class="stat-number"><?= $total_deans ?></div>
                    <div class="stat-label">عميد مسجل</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-chart-line"></i></div>
                    <div class="stat-number"><?= $active_faculties ?></div>
                    <div class="stat-label">كلية نشطة</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
                    <div class="stat-number"><?= $total_faculties - $active_faculties ?></div>
                    <div class="stat-label">قيد التنشيط</div>
                </div>
            </div>
        </div>

        <!-- شريط البحث -->
        <div class="search-section">
            <div class="search-box">
                <div class="search-input-wrapper">
                    <input type="text" class="search-input" placeholder="ابحث عن كلية..." id="searchInput">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                </div>
                <button class="search-btn" onclick="filterFaculties()">
                    <i class="fa-solid fa-search"></i>
                    <span>بحث</span>
                </button>
            </div>
        </div>

        <!-- شبكة الكليات -->
        <div class="faculties-grid" id="facultiesGrid">
            <?php foreach ($faculties as $faculty): ?>
                <?php 
                $is_active = $faculty['records_count'] > 0;
                $status_class = $is_active ? 'status-active' : 'status-inactive';
                $status_text = $is_active ? 'نشطة' : 'غير نشطة';
                ?>
                
                <a href="faculty_dashboard.php?id=<?= $faculty['id'] ?>" class="faculty-card">
                    <div class="status-indicator <?= $status_class ?>" title="<?= $status_text ?>"></div>
                    
                    <div class="faculty-header">
                        <div class="faculty-icon">
                            <i class="fa-solid fa-building-columns"></i>
                        </div>
                        <div class="faculty-info">
                            <div class="faculty-name"><?= htmlspecialchars($faculty['name']) ?></div>
                            <div class="faculty-id">ID: <?= $faculty['id'] ?></div>
                        </div>
                    </div>

                    <div class="faculty-stats">
                        <div class="faculty-stat">
                            <div class="faculty-stat-value"><?= $faculty['deans_count'] ?></div>
                            <div class="faculty-stat-label">عمداء</div>
                        </div>
                        <div class="faculty-stat">
                            <div class="faculty-stat-value"><?= $faculty['records_count'] ?></div>
                            <div class="faculty-stat-label">سجل</div>
                        </div>
                        <div class="faculty-stat">
                            <div class="faculty-stat-value">10</div>
                            <div class="faculty-stat-label">مؤشر</div>
                        </div>
                        <div class="faculty-stat">
                            <div class="faculty-stat-value">
                                <?php if ($is_active): ?>
                                    <i class="fa-solid fa-check-circle" style="color: #10b981;"></i>
                                <?php else: ?>
                                    <i class="fa-solid fa-clock" style="color: #f59e0b;"></i>
                                <?php endif; ?>
                            </div>
                            <div class="faculty-stat-label">الحالة</div>
                        </div>
                    </div>

                    <?php if ($faculty['last_update']): ?>
                        <div class="last-update">
                            <i class="fa-solid fa-calendar-days"></i>
                            آخر تحديث: <?= date('Y-m-d', strtotime($faculty['last_update'])) ?>
                        </div>
                    <?php else: ?>
                        <div class="last-update">
                            <i class="fa-solid fa-clock"></i>
                            لم يتم تحديث البيانات بعد
                        </div>
                    <?php endif; ?>

                    <button type="button" class="view-dashboard">
                        <i class="fa-solid fa-chart-area"></i>
                        <span>عرض لوحة الأداء</span>
                    </button>
                </a>
            <?php endforeach; ?>
        </div>
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
        // تصفية الكليات
        function filterFaculties() {
            const searchInput = document.getElementById('searchInput');
            if (!searchInput) return;
            
            const searchTerm = searchInput.value.toLowerCase().trim();
            const faculties = document.querySelectorAll('.faculty-card');
            const facultiesGrid = document.getElementById('facultiesGrid');
            let visibleCount = 0;
            
            faculties.forEach(faculty => {
                const facultyName = faculty.querySelector('.faculty-name')?.textContent.toLowerCase() || '';
                const facultyId = faculty.querySelector('.faculty-id')?.textContent.toLowerCase() || '';
                
                // إذا كان البحث فارغاً، إظهار جميع الكليات
                if (!searchTerm) {
                    faculty.style.display = 'block';
                    faculty.style.opacity = '1';
                    visibleCount++;
                } else {
                    // البحث في الاسم و ID
                    if (facultyName.includes(searchTerm) || facultyId.includes(searchTerm)) {
                        faculty.style.display = 'block';
                        faculty.style.opacity = '1';
                        faculty.style.animation = 'fadeInUp 0.4s ease-out';
                        visibleCount++;
                    } else {
                        faculty.style.display = 'none';
                        faculty.style.opacity = '0';
                    }
                }
            });

            // إظهار رسالة إذا لم توجد نتائج
            let noResultsMsg = document.getElementById('noResultsMessage');
            if (searchTerm && visibleCount === 0) {
                if (!noResultsMsg && facultiesGrid) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.id = 'noResultsMessage';
                    noResultsMsg.className = 'no-results';
                    noResultsMsg.innerHTML = '<i class="fa-solid fa-search"></i> <span>لم يتم العثور على نتائج للبحث: "' + searchTerm + '"</span>';
                    facultiesGrid.appendChild(noResultsMsg);
                }
                if (noResultsMsg) {
                    noResultsMsg.style.display = 'flex';
                }
            } else {
                if (noResultsMsg) {
                    noResultsMsg.style.display = 'none';
                }
            }
        }

        // البحث في الوقت الفعلي أثناء الكتابة
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            
            if (searchInput) {
                // البحث عند الكتابة (real-time search)
                searchInput.addEventListener('input', function() {
                    filterFaculties();
                });

                // البحث عند الضغط على Enter
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        filterFaculties();
                    }
                });

                // مسح البحث عند الضغط على Escape
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        this.value = '';
                        filterFaculties();
                        this.focus();
                    }
                });
            }

            // إضافة تأثيرات ظهور البطاقات
            const cards = document.querySelectorAll('.faculty-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.08}s`;
            });

            // Prevent default button behavior and let link handle navigation
            document.querySelectorAll('.view-dashboard').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const card = this.closest('.faculty-card');
                    if (card && card.href) {
                        window.location.href = card.href;
                    }
                });
            });
        });
    </script>
</body>
</html>