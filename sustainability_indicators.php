<?php
// sustainability_indicators.php - صفحة مؤشرات الاستدامة
require_once 'config.php';
require_once 'evaluation_standards.php';

// تحقق من جلسة المدير
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$conn = db_connect();

// جلب جميع مؤشرات الاستدامة
$indicators_sql = "SELECT * FROM indicators ORDER BY id ASC";
$indicators_result = mysqli_query($conn, $indicators_sql);
$indicators = [];

while ($row = mysqli_fetch_assoc($indicators_result)) {
    $indicators[] = $row;
}

// جلب بيانات الكليات للتقييم
$faculties_sql = "SELECT id, name FROM faculties";
$faculties_result = mysqli_query($conn, $faculties_sql);
$faculties_list = [];
while ($row = mysqli_fetch_assoc($faculties_result)) {
    $faculties_list[$row['id']] = $row['name'];
}

// جلب أحدث شهر وسنة من السجلات (فقط إذا كانت هناك بيانات)
$month_sql = "SELECT MAX(year) AS max_year, MAX(month) AS max_month FROM records";
$month_result = mysqli_query($conn, $month_sql);
$month_data = mysqli_fetch_assoc($month_result);
// استخدام آخر شهر وسنة بهما بيانات، أو الشهر والسنة الحاليين إذا لم تكن هناك بيانات
if ($month_data['max_year'] && $month_data['max_month']) {
    $current_year = $month_data['max_year'];
    $current_month = $month_data['max_month'];
} else {
    $current_year = date('Y');
    $current_month = date('n');
}

// حساب ملخص التقييمات لكل مؤشر قبل إغلاق الاتصال
$evaluation_summaries = [];
foreach ($indicators as $indicator) {
    $evaluation_summary = ['excellent' => 0, 'warning' => 0, 'error' => 0, 'total' => 0];
    
    $records_sql = "
        SELECT r.faculty_id, r.value, f.name as faculty_name
        FROM records r
        JOIN faculties f ON r.faculty_id = f.id
        WHERE r.indicator_id = {$indicator['id']}
        AND r.year = $current_year
        AND r.month = $current_month
    ";
    $records_result = mysqli_query($conn, $records_sql);
    
    // جلب قيمة الورق المستخدم لحساب نسبة التدوير (للمؤشر 4)
    $paper_used_map = [];
    if ($indicator['id'] == 4) {
        $paper_sql = "
            SELECT r.faculty_id, r.value
            FROM records r
            WHERE r.indicator_id = 3
            AND r.year = $current_year
            AND r.month = $current_month
        ";
        $paper_result = mysqli_query($conn, $paper_sql);
        while ($paper_row = mysqli_fetch_assoc($paper_result)) {
            $paper_used_map[$paper_row['faculty_id']] = floatval($paper_row['value']);
        }
    }
    
    if ($records_result) {
        while ($record = mysqli_fetch_assoc($records_result)) {
            $is_lab = is_laboratory_faculty($record['faculty_name']);
            $related_value = ($indicator['id'] == 4 && isset($paper_used_map[$record['faculty_id']])) ? $paper_used_map[$record['faculty_id']] : null;
            $eval = evaluate_indicator(floatval($record['value']), $indicator['id'], $is_lab, $related_value);
            if (isset($evaluation_summary[$eval['status']])) {
                $evaluation_summary[$eval['status']]++;
            }
            $evaluation_summary['total']++;
        }
    }
    
    $evaluation_summaries[$indicator['id']] = $evaluation_summary;
}

db_close($conn);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مؤشرات الاستدامة - نظام Beyond You</title>
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
            background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo h1 i {
            font-size: 1.6rem;
            animation: pulse 2s ease-in-out infinite;
            color: #00d9a5;
            -webkit-text-fill-color: #00d9a5;
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
            background: rgba(0, 217, 165, 0.1);
            color: #00d9a5;
            border: 1px solid rgba(0, 217, 165, 0.2);
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
            background: rgba(0, 217, 165, 0.2);
            border-color: rgba(0, 217, 165, 0.4);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 217, 165, 0.3);
        }

        .nav-btn i {
            font-size: 1rem;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
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

        .period-info {
            background: rgba(0, 217, 165, 0.1);
            backdrop-filter: blur(10px);
            padding: 16px 24px;
            border-radius: 12px;
            margin-top: 20px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(0, 217, 165, 0.2);
            font-weight: 600;
            color: #00d9a5;
            position: relative;
            z-index: 1;
        }

        .period-info i {
            font-size: 1.1rem;
            color: #00d9a5;
        }

        .period-info strong {
            color: #00d9a5;
            font-size: 1.1rem;
        }

        .indicators-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .indicator-card {
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

        .indicator-card::before {
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

        .indicator-card:hover::before {
            opacity: 1;
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
            margin-bottom: 20px;
        }

        .indicator-icon {
            font-size: 2rem;
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, rgba(0, 217, 165, 0.2) 0%, rgba(99, 102, 241, 0.2) 100%);
            border: 1px solid rgba(0, 217, 165, 0.3);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #00d9a5;
            transition: all 0.4s ease;
            flex-shrink: 0;
            box-shadow: 0 4px 15px rgba(0, 217, 165, 0.2);
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
            font-size: 1.3rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 5px;
            position: relative;
            z-index: 1;
        }

        .indicator-unit {
            color: #00d9a5;
            font-weight: 700;
            font-size: 0.875rem;
            background: rgba(0, 217, 165, 0.1);
            backdrop-filter: blur(10px);
            padding: 6px 14px;
            border-radius: 20px;
            display: inline-block;
            border: 1px solid rgba(0, 217, 165, 0.2);
            position: relative;
            z-index: 1;
        }

        .indicator-desc {
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 20px;
            font-size: 0.95rem;
            position: relative;
            z-index: 1;
        }

        .indicator-stats {
            display: flex;
            justify-content: space-between;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(10px);
            padding: 18px;
            border-radius: 12px;
            margin-top: 20px;
            border: 1px solid rgba(148, 163, 184, 0.1);
            gap: 15px;
            position: relative;
            z-index: 1;
        }

        .stat {
            text-align: center;
            flex: 1;
            padding: 8px;
            border-radius: 10px;
            transition: background 0.3s ease;
        }

        .stat:hover {
            background: rgba(0, 217, 165, 0.1);
        }

        .stat-value {
            font-size: 1.4rem;
            font-weight: 800;
            background: linear-gradient(135deg, #00d9a5, #6366f1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 6px;
            line-height: 1.2;
        }

        .stat-label {
            color: #94a3b8;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .view-report {
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

        .view-report::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .view-report:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0, 217, 165, 0.4);
        }

        .view-report:hover::before {
            left: 100%;
        }

        .view-report i {
            font-size: 1rem;
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

            .page-header {
                padding: 30px 25px;
            }

            .page-header h2 {
                font-size: 1.8rem;
                flex-direction: column;
                gap: 10px;
            }

            .indicators-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .indicator-card {
                padding: 25px;
            }

            .indicator-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .indicator-icon {
                width: 60px;
                height: 60px;
                font-size: 1.8rem;
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
                <p>مؤشرات الاستدامة</p>
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
            <h2><i class="fa-solid fa-chart-line"></i> مؤشرات الاستدامة</h2>
            <p>عرض ومقارنة أداء الكليات في مؤشرات الاستدامة المختلفة</p>
            <div class="period-info">
                <i class="fa-solid fa-calendar-days"></i>
                <span>عرض بيانات شهر: <strong><?= $current_month ?></strong> / <strong><?= $current_year ?></strong></span>
            </div>
        </div>

        <!-- شبكة مؤشرات الاستدامة -->
        <div class="indicators-grid">
            <?php foreach ($indicators as $indicator): ?>
                <a href="indicator_dashboard.php?id=<?= $indicator['id'] ?>" class="indicator-card">
                    <div class="indicator-header">
                        <div class="indicator-icon">
                            <?php 
                            // اختيار أيقونة مناسبة حسب نوع المؤشر
                            $icons = [
                                1 => 'fa-droplet',           // استهلاك المياه
                                2 => 'fa-bolt',              // استهلاك الكهرباء
                                3 => 'fa-file',              // كمية الورق المستهلك
                                4 => 'fa-recycle',           // كمية الورق المعاد تدويره
                                5 => 'fa-trash-arrow-up',    // كمية النفايات المعاد تدويرها
                                6 => 'fa-tree',              // عدد الأشجار المزروعة
                                7 => 'fa-users',             // عدد المتطوعين
                                8 => 'fa-clock',             // عدد ساعات التطوع
                                9 => 'fa-bullhorn',          // عدد الفعاليات التوعوية
                                10 => 'fa-trophy'            // درجة الالتزام البيئي
                            ];
                            $icon_class = $icons[$indicator['id']] ?? 'fa-chart-bar';
                            echo '<i class="fa-solid ' . $icon_class . '"></i>';
                            ?>
                        </div>
                        <div class="indicator-title">
                            <div class="indicator-name"><?= htmlspecialchars($indicator['name']) ?></div>
                            <div class="indicator-unit"><?= htmlspecialchars($indicator['unit']) ?></div>
                        </div>
                    </div>
                    
                    <div class="indicator-desc">
                        <?php
                        // وصف لكل مؤشر
                        $descriptions = [
                            1 => 'متابعة استهلاك المياه في الكليات وترشيد الاستخدام',
                            2 => 'مراقبة استهلاك الطاقة الكهربائية وتحسين الكفاءة',
                            3 => 'تتبع كميات الورق المستخدمة والتحول نحو الرقمنة',
                            4 => 'قياس جهود إعادة تدوير الورق والمحافظة على البيئة',
                            5 => 'متابعة كميات النفايات المعاد تدويرها في الكليات',
                            6 => 'رصد الجهود في زيادة المساحات الخضراء بالحرم الجامعي',
                            7 => 'متابعة أعداد المتطوعين في الأنشطة البيئية',
                            8 => 'قياس حجم الجهد التطوعي في المبادرات الخضراء',
                            9 => 'رصد الفعاليات التوعوية ونشر الثقافة البيئية',
                            10 => 'تقييم مستوى الالتزام البيئي لدى طلبة الكليات'
                        ];
                        echo $descriptions[$indicator['id']] ?? 'مؤشر لقياس الأداء البيئي والاستدامة';
                        ?>
                    </div>

                    <div class="indicator-stats">
                        <div class="stat">
                            <div class="stat-value">16</div>
                            <div class="stat-label">كلية</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value"><?= $current_month ?></div>
                            <div class="stat-label">شهر</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value"><?= $current_year ?></div>
                            <div class="stat-label">سنة</div>
                        </div>
                    </div>
                    
                    <?php
                    // استخدام ملخص التقييمات المحسوب مسبقاً
                    $evaluation_summary = $evaluation_summaries[$indicator['id']] ?? ['excellent' => 0, 'warning' => 0, 'error' => 0, 'total' => 0];
                    
                    if ($evaluation_summary['total'] > 0):
                    ?>
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(148, 163, 184, 0.1);">
                        <div style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 8px; font-weight: 600;">ملخص التقييم:</div>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <?php if ($evaluation_summary['excellent'] > 0): ?>
                                <span style="background: rgba(0, 217, 165, 0.15); color: #00d9a5; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; border: 1px solid rgba(0, 217, 165, 0.3);">
                                    ✔ <?= $evaluation_summary['excellent'] ?> ممتاز
                                </span>
                            <?php endif; ?>
                            <?php if ($evaluation_summary['warning'] > 0): ?>
                                <span style="background: rgba(251, 191, 36, 0.15); color: #fbbf24; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; border: 1px solid rgba(251, 191, 36, 0.3);">
                                    ⚠ <?= $evaluation_summary['warning'] ?> منخفض
                                </span>
                            <?php endif; ?>
                            <?php if ($evaluation_summary['error'] > 0): ?>
                                <span style="background: rgba(239, 68, 68, 0.15); color: #fca5a5; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; border: 1px solid rgba(239, 68, 68, 0.3);">
                                    ✖ <?= $evaluation_summary['error'] ?> خارج المعيار
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <button type="button" class="view-report">
                        <i class="fa-solid fa-chart-area"></i>
                        <span>عرض تقرير المؤشر</span>
                    </button>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- الفوتر -->
    <div class="footer">
        نظام Beyond You - جامعة اليرموك © 2026
    </div>

    <script>
        // إضافة تأثيرات تفاعلية
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.indicator-card');
            
            cards.forEach((card, index) => {
                // تأخير ظهور البطاقات بشكل متسلسل
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });

        // Prevent default button behavior and let link handle navigation
        document.querySelectorAll('.view-report').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const card = this.closest('.indicator-card');
                if (card && card.href) {
                    window.location.href = card.href;
                }
            });
        });
    </script>
</body>
</html>