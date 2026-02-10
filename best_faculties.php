<?php
// best_faculties.php - صفحة الكليات المتميزة
require_once 'config.php';

// تحقق من جلسة المدير
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$conn = db_connect();

// جلب أحدث شهر وسنة من قاعدة البيانات
$month_sql = "SELECT MAX(year) AS max_year, MAX(month) AS max_month FROM records";
$month_result = mysqli_query($conn, $month_sql);
$row = mysqli_fetch_assoc($month_result);
$current_year = $row['max_year'] ?? date('Y');
$current_month = $row['max_month'] ?? date('n');

// مؤشرات لتقييم الأداء
$indicators = [
    1 => ['name' => 'استهلاك المياه', 'unit' => 'م³', 'type' => 'min', 'icon' => 'fa-droplet'],
    2 => ['name' => 'استهلاك الكهرباء', 'unit' => 'كيلوواط/ساعة', 'type' => 'min', 'icon' => 'fa-bolt'],
    3 => ['name' => 'كمية الورق المستهلك', 'unit' => 'ريمة', 'type' => 'min', 'icon' => 'fa-file-lines'],
    4 => ['name' => 'كمية الورق المعاد تدويره', 'unit' => 'كغم', 'type' => 'max', 'icon' => 'fa-recycle'],
    5 => ['name' => 'كمية النفايات المعاد تدويرها', 'unit' => 'كغم', 'type' => 'max', 'icon' => 'fa-trash'],
    6 => ['name' => 'عدد الأشجار المزروعة', 'unit' => 'شجرة', 'type' => 'max', 'icon' => 'fa-tree'],
    7 => ['name' => 'عدد المتطوعين', 'unit' => 'متطوع', 'type' => 'max', 'icon' => 'fa-users'],
    8 => ['name' => 'عدد ساعات التطوع', 'unit' => 'ساعة', 'type' => 'max', 'icon' => 'fa-clock'],
    9 => ['name' => 'عدد الفعاليات التوعوية', 'unit' => 'فعالية', 'type' => 'max', 'icon' => 'fa-bullhorn'],
    10 => ['name' => 'درجة الالتزام البيئي للطلبة', 'unit' => 'نقطة', 'type' => 'max', 'icon' => 'fa-trophy']
];

$winners = [];
$performance_data = [];

foreach ($indicators as $id => $info) {
    $order = $info['type'] === 'max' ? 'DESC' : 'ASC';
    $sql = "
        SELECT f.id, f.name AS faculty_name, r.value
        FROM records r
        JOIN faculties f ON f.id = r.faculty_id
        WHERE r.indicator_id = $id
        AND r.year = $current_year
        AND r.month = $current_month
        AND r.value > 0
        ORDER BY r.value $order
        LIMIT 3
    ";
    $result = mysqli_query($conn, $sql);
    
    $top_faculties = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $top_faculties[] = $row;
    }
    $winners[$id] = $top_faculties;
}

// حساب التصنيف العام للكليات
$overall_ranking_sql = "
    SELECT f.id, f.name AS faculty_name, 
           COUNT(r.id) as indicators_count,
           AVG(CASE 
               WHEN i.id IN (1,2,3) THEN (1 - (r.value / (SELECT MAX(value) FROM records r2 WHERE r2.indicator_id = r.indicator_id AND r2.year = $current_year AND r2.month = $current_year)))
               ELSE (r.value / (SELECT MAX(value) FROM records r2 WHERE r2.indicator_id = r.indicator_id AND r2.year = $current_year AND r2.month = $current_year))
           END) as performance_score
    FROM faculties f
    LEFT JOIN records r ON f.id = r.faculty_id AND r.year = $current_year AND r.month = $current_year
    LEFT JOIN indicators i ON r.indicator_id = i.id
    WHERE r.value > 0
    GROUP BY f.id, f.name
    HAVING indicators_count >= 5
    ORDER BY performance_score DESC
    LIMIT 5
";

$overall_ranking_result = mysqli_query($conn, $overall_ranking_sql);
$top_faculties_overall = [];
while ($row = mysqli_fetch_assoc($overall_ranking_result)) {
    $top_faculties_overall[] = $row;
}

db_close($conn);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الكليات المتميزة - نظام Beyond You</title>
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
            text-align: center;
            margin-bottom: 50px;
            padding: 40px 20px;
        }

        .page-title {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
            letter-spacing: -0.02em;
        }

        .page-description {
            color: #94a3b8;
            font-size: 1.2rem;
            margin-bottom: 25px;
            font-weight: 400;
        }

        .period-info {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            background: rgba(0, 217, 165, 0.15);
            border: 1px solid rgba(0, 217, 165, 0.3);
            border-radius: 50px;
            color: #00d9a5;
            font-weight: 600;
            font-size: 1rem;
            backdrop-filter: blur(10px);
        }

        .period-info i {
            font-size: 1.1rem;
        }

        .trophy-section {
            text-align: center;
            margin-bottom: 60px;
            padding: 0;
        }

        .trophy-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ffd700;
            animation: float 3s ease-in-out infinite;
            filter: drop-shadow(0 0 20px rgba(255, 215, 0, 0.4));
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-15px);
            }
        }

        .trophy-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 10px;
            text-shadow: 0 0 30px rgba(255, 215, 0, 0.3);
        }

        .trophy-description {
            color: #94a3b8;
            font-size: 1.1rem;
            font-weight: 400;
        }

        .ranking-section {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 24px;
            border: 1px solid rgba(148, 163, 184, 0.1);
            margin-bottom: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 35px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(148, 163, 184, 0.2);
        }

        .section-title i {
            color: #00d9a5;
            font-size: 1.6rem;
        }

        .podium-container {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 30px;
            margin-bottom: 50px;
            padding: 40px 20px;
            min-height: 400px;
        }

        .podium-item {
            flex: 1;
            max-width: 280px;
            text-align: center;
            position: relative;
            animation: slideUp 0.8s ease-out backwards;
        }

        .podium-item:nth-child(1) {
            animation-delay: 0.2s;
            order: 2;
        }

        .podium-item:nth-child(2) {
            animation-delay: 0.1s;
            order: 1;
        }

        .podium-item:nth-child(3) {
            animation-delay: 0.3s;
            order: 3;
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

        .podium-card {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 20px 20px 0 0;
            padding: 30px 25px 40px;
            position: relative;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
        }

        .podium-item.gold .podium-card {
            background: linear-gradient(180deg, rgba(255, 215, 0, 0.2) 0%, rgba(30, 41, 59, 0.9) 100%);
            border-color: rgba(255, 215, 0, 0.4);
            height: 320px;
            box-shadow: 0 20px 60px rgba(255, 215, 0, 0.3);
        }

        .podium-item.silver .podium-card {
            background: linear-gradient(180deg, rgba(192, 192, 192, 0.2) 0%, rgba(30, 41, 59, 0.9) 100%);
            border-color: rgba(192, 192, 192, 0.4);
            height: 260px;
            box-shadow: 0 20px 60px rgba(192, 192, 192, 0.2);
        }

        .podium-item.bronze .podium-card {
            background: linear-gradient(180deg, rgba(205, 127, 50, 0.2) 0%, rgba(30, 41, 59, 0.9) 100%);
            border-color: rgba(205, 127, 50, 0.4);
            height: 220px;
            box-shadow: 0 20px 60px rgba(205, 127, 50, 0.2);
        }

        .podium-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.5);
        }

        .rank-medal {
            font-size: 3.5rem;
            margin-bottom: 20px;
            display: block;
            filter: drop-shadow(0 0 15px currentColor);
        }

        .podium-item.gold .rank-medal {
            color: #ffd700;
        }

        .podium-item.silver .rank-medal {
            color: #c0c0c0;
        }

        .podium-item.bronze .rank-medal {
            color: #cd7f32;
        }

        .rank-position {
            font-size: 1rem;
            font-weight: 700;
            color: #94a3b8;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .rank-faculty {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 15px;
            line-height: 1.3;
        }

        .rank-score {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .rank-indicators {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(148, 163, 184, 0.2);
            color: #64748b;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .indicators-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .indicator-winners {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            padding: 25px;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeIn 0.6s ease-out backwards;
        }

        .indicator-winners:nth-child(1) { animation-delay: 0.05s; }
        .indicator-winners:nth-child(2) { animation-delay: 0.1s; }
        .indicator-winners:nth-child(3) { animation-delay: 0.15s; }
        .indicator-winners:nth-child(4) { animation-delay: 0.2s; }
        .indicator-winners:nth-child(5) { animation-delay: 0.25s; }
        .indicator-winners:nth-child(6) { animation-delay: 0.3s; }
        .indicator-winners:nth-child(7) { animation-delay: 0.35s; }
        .indicator-winners:nth-child(8) { animation-delay: 0.4s; }
        .indicator-winners:nth-child(9) { animation-delay: 0.45s; }
        .indicator-winners:nth-child(10) { animation-delay: 0.5s; }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .indicator-winners:hover {
            transform: translateY(-5px);
            border-color: rgba(0, 217, 165, 0.4);
            box-shadow: 0 15px 40px rgba(0, 217, 165, 0.2);
        }

        .indicator-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
        }

        .indicator-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 217, 165, 0.1);
            border: 1px solid rgba(0, 217, 165, 0.2);
            border-radius: 12px;
            font-size: 1.4rem;
            color: #00d9a5;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .indicator-winners:hover .indicator-icon {
            transform: scale(1.1);
            background: rgba(0, 217, 165, 0.2);
            border-color: rgba(0, 217, 165, 0.4);
        }

        .indicator-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #e2e8f0;
            flex: 1;
            line-height: 1.4;
        }

        .winner-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .winner-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            background: rgba(15, 23, 42, 0.5);
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.1);
            transition: all 0.2s ease;
        }

        .winner-item:hover {
            background: rgba(30, 41, 59, 0.8);
            border-color: rgba(0, 217, 165, 0.3);
            transform: translateX(-3px);
        }

        .winner-info {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .winner-rank {
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            color: #0a0e27;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.9rem;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }

        .winner-item:hover .winner-rank {
            transform: scale(1.1);
            box-shadow: 0 0 15px rgba(0, 217, 165, 0.5);
        }

        .winner-name {
            font-weight: 500;
            color: #e2e8f0;
            font-size: 0.95rem;
        }

        .winner-value {
            font-weight: 700;
            color: #00d9a5;
            font-size: 1rem;
            display: flex;
            align-items: baseline;
            gap: 4px;
        }

        .winner-unit {
            color: #94a3b8;
            font-size: 0.75rem;
            font-weight: 400;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .no-data .icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
            color: #64748b;
            animation: pulse 2s ease-in-out infinite;
        }

        .no-data h3 {
            font-size: 1.4rem;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 10px;
        }

        .no-data p {
            font-size: 0.95rem;
            color: #64748b;
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

            .page-title {
                font-size: 2rem;
            }

            .trophy-icon {
                font-size: 3rem;
            }

            .trophy-title {
                font-size: 1.8rem;
            }

            .ranking-section {
                padding: 25px 20px;
            }

            .podium-container {
                flex-direction: column;
                align-items: center;
                gap: 20px;
                min-height: auto;
            }

            .podium-item.gold .podium-card,
            .podium-item.silver .podium-card,
            .podium-item.bronze .podium-card {
                height: auto;
                min-height: 280px;
            }

            .indicators-grid {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 1.5rem;
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
                <p>الكليات المتميزة</p>
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
        <!-- قسم البطل -->
        <div class="trophy-section">
            <div class="trophy-icon">
                <i class="fa-solid fa-trophy"></i>
            </div>
            <div class="trophy-title">الكليات المتميزة</div>
            <div class="trophy-description">تصنيف الكليات الأفضل أداءً في مؤشرات الاستدامة</div>
            
            <div class="period-info">
                <i class="fa-solid fa-calendar-days"></i>
                <span>تصنيف شهر: <strong><?= $current_month ?></strong> / <strong><?= $current_year ?></strong></span>
            </div>
        </div>

        <!-- التصنيف العام -->
        <div class="ranking-section">
            <div class="section-title">
                <i class="fa-solid fa-trophy"></i>
                <span>التصنيف العام للكليات</span>
            </div>

            <?php if (count($top_faculties_overall) > 0): ?>
                <div class="podium-container">
                    <?php 
                    // Show top 3 in podium style
                    $top_three = array_slice($top_faculties_overall, 0, 3);
                    foreach ($top_three as $index => $faculty): 
                        $rank_class = '';
                        $medal_icon_class = '';
                        if ($index === 0) {
                            $rank_class = 'gold';
                            $medal_icon_class = 'fa-medal';
                        } elseif ($index === 1) {
                            $rank_class = 'silver';
                            $medal_icon_class = 'fa-medal';
                        } elseif ($index === 2) {
                            $rank_class = 'bronze';
                            $medal_icon_class = 'fa-medal';
                        }
                    ?>
                        <div class="podium-item <?= $rank_class ?>">
                            <div class="podium-card">
                                <div class="rank-medal">
                                    <i class="fa-solid <?= $medal_icon_class ?>"></i>
                                </div>
                                <div class="rank-position">المركز <?= $index + 1 ?></div>
                                <div class="rank-faculty"><?= htmlspecialchars($faculty['faculty_name']) ?></div>
                                <div class="rank-score">
                                    <?= number_format($faculty['performance_score'] * 100, 1) ?>%
                                </div>
                                <div class="rank-indicators">
                                    <i class="fa-solid fa-chart-line"></i>
                                    <span><?= $faculty['indicators_count'] ?> مؤشر</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <div class="icon">
                        <i class="fa-solid fa-chart-bar"></i>
                    </div>
                    <h3>لا توجد بيانات كافية للتصنيف</h3>
                    <p>يحتاج النظام إلى بيانات من 5 مؤشرات على الأقل لكل كلية</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- الكليات المتميزة في كل مؤشر -->
        <div class="ranking-section">
            <div class="section-title">
                <i class="fa-solid fa-star"></i>
                <span>المتميزون في كل مؤشر</span>
            </div>

            <div class="indicators-grid">
                <?php foreach ($indicators as $id => $info): ?>
                    <div class="indicator-winners">
                        <div class="indicator-header">
                            <div class="indicator-icon">
                                <i class="fa-solid <?= $info['icon'] ?>"></i>
                            </div>
                            <div class="indicator-title"><?= $info['name'] ?></div>
                        </div>

                        <div class="winner-list">
                            <?php if (!empty($winners[$id])): ?>
                                <?php foreach ($winners[$id] as $rank => $winner): ?>
                                    <div class="winner-item">
                                        <div class="winner-info">
                                            <div class="winner-rank"><?= $rank + 1 ?></div>
                                            <div class="winner-name"><?= htmlspecialchars($winner['faculty_name']) ?></div>
                                        </div>
                                        <div class="winner-value">
                                            <span class="winner-unit"><?= $info['unit'] ?></span>
                                            <?= number_format($winner['value'], 2) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-data" style="padding: 20px;">
                                    <div class="icon">
                                        <i class="fa-solid fa-inbox"></i>
                                    </div>
                                    <p>لا توجد بيانات</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- الفوتر -->
    <div class="footer">
        نظام Beyond You - جامعة اليرموك © 2026
    </div>

</body>
</html>