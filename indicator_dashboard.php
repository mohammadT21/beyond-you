<?php
// indicator_dashboard.php - لوحة عرض مقارنة الكليات حسب المؤشر
require_once 'config.php';
require_once 'evaluation_standards.php';

// تحقق من جلسة المدير
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// استقبال معرف المؤشر من الرابط
if (!isset($_GET['id'])) {
    header('Location: sustainability_indicators.php');
    exit;
}

$conn = db_connect();
$indicator_id = intval($_GET['id']);

// جلب بيانات المؤشر
$indicator_sql = "SELECT * FROM indicators WHERE id = $indicator_id";
$indicator_result = mysqli_query($conn, $indicator_sql);
if (!$indicator_result || mysqli_num_rows($indicator_result) === 0) {
    die('⚠️ المؤشر غير موجود.');
}
$indicator = mysqli_fetch_assoc($indicator_result);
$indicator_name = htmlspecialchars($indicator['name']);
$indicator_unit = htmlspecialchars($indicator['unit']);

// تحديد آخر شهر وسنة مدخلة (فقط إذا كانت هناك بيانات)
$month_sql = "SELECT MAX(year) AS max_year, MAX(month) AS max_month FROM records WHERE indicator_id = $indicator_id";
$month_result = mysqli_query($conn, $month_sql);
$row = mysqli_fetch_assoc($month_result);
// استخدام آخر شهر وسنة بهما بيانات، أو الشهر والسنة الحاليين إذا لم تكن هناك بيانات
if ($row['max_year'] && $row['max_month']) {
    $current_year = $row['max_year'];
    $current_month = $row['max_month'];
} else {
    // إذا لم تكن هناك بيانات لهذا المؤشر، نبحث عن آخر شهر وسنة في جميع السجلات
    $all_month_sql = "SELECT MAX(year) AS max_year, MAX(month) AS max_month FROM records";
    $all_month_result = mysqli_query($conn, $all_month_sql);
    $all_row = mysqli_fetch_assoc($all_month_result);
    if ($all_row['max_year'] && $all_row['max_month']) {
        $current_year = $all_row['max_year'];
        $current_month = $all_row['max_month'];
    } else {
        $current_year = date('Y');
        $current_month = date('n');
    }
}

// جلب بيانات الكليات لذلك المؤشر (جميع الكليات حتى التي لا تحتوي بيانات)
$data_sql = "
    SELECT f.id, f.name AS faculty_name, COALESCE(r.value, 0) AS value
    FROM faculties f
    LEFT JOIN records r ON f.id = r.faculty_id 
        AND r.indicator_id = $indicator_id 
        AND r.year = $current_year 
        AND r.month = $current_month
    ORDER BY r.value DESC, f.name ASC
";
$data_result = mysqli_query($conn, $data_sql);

$faculties = [];
$values = [];
$faculty_ids = [];
$all_faculties_data = [];

// جلب قيمة الورق المستخدم لحساب نسبة التدوير (للمؤشر 4)
$paper_used_values = [];
if ($indicator_id == 4) {
    $paper_sql = "
        SELECT f.id, COALESCE(r.value, 0) AS value
        FROM faculties f
        LEFT JOIN records r ON f.id = r.faculty_id 
            AND r.indicator_id = 3 
            AND r.year = $current_year 
            AND r.month = $current_month
    ";
    $paper_result = mysqli_query($conn, $paper_sql);
    while ($paper_row = mysqli_fetch_assoc($paper_result)) {
        $paper_used_values[$paper_row['id']] = floatval($paper_row['value']);
    }
}

while ($row = mysqli_fetch_assoc($data_result)) {
    $faculties[] = $row['faculty_name'];
    $values[] = floatval($row['value']);
    $faculty_ids[] = $row['id'];
    $all_faculties_data[] = $row;
}

// حساب الإحصائيات (فقط للكليات الي فيها بيانات)
$values_with_data = array_filter($values, function($v) { return $v > 0; });
$total_value = array_sum($values_with_data);
$average_value = count($values_with_data) > 0 ? $total_value / count($values_with_data) : 0;
$max_value = count($values_with_data) > 0 ? max($values_with_data) : 0;
$min_value = count($values_with_data) > 0 ? min($values_with_data) : 0;

// جلب عدد الكليات الإجمالي
$faculty_count_sql = "SELECT COUNT(*) as total FROM faculties";
$faculty_count_result = mysqli_query($conn, $faculty_count_sql);
$faculty_count_total = mysqli_fetch_assoc($faculty_count_result)['total'];

db_close($conn);

// أيقونة المؤشر
$icons = [
    1 => '💧', 2 => '⚡', 3 => '📄', 4 => '🔄', 5 => '🗑️',
    6 => '🌳', 7 => '👥', 8 => '⏱️', 9 => '📢', 10 => '🏆'
];
$indicator_icon = $icons[$indicator_id] ?? '📊';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $indicator_name ?> - نظام Beyond You</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
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
            margin-bottom: 15px;
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
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
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
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        .indicator-info {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .indicator-icon {
            font-size: 4rem;
        }

        .indicator-details h2 {
            background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2rem;
            margin-bottom: 5px;
            font-weight: 800;
            position: relative;
            z-index: 1;
        }

        .indicator-unit {
            color: #94a3b8;
            font-size: 1.2rem;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }

        .period-info {
            background: rgba(0, 217, 165, 0.1);
            backdrop-filter: blur(10px);
            padding: 16px 24px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 20px;
            border: 1px solid rgba(0, 217, 165, 0.2);
            font-weight: 600;
            color: #00d9a5;
            position: relative;
            z-index: 1;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            text-align: center;
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-top: 4px solid #00d9a5;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 217, 165, 0.3);
            border-color: rgba(0, 217, 165, 0.4);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #00d9a5, #6366f1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #94a3b8;
            font-weight: 600;
        }

        .chart-section {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            margin-bottom: 30px;
            border: 1px solid rgba(148, 163, 184, 0.1);
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }

        .section-title {
            font-size: 1.6rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.01em;
        }

        .chart-container {
            position: relative;
            height: 500px;
            width: 100%;
        }

        .faculties-list {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(148, 163, 184, 0.1);
            animation: fadeInUp 0.6s ease-out 0.4s both;
        }

        .faculty-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            margin-bottom: 15px;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            border-right: 4px solid #00d9a5;
            border: 1px solid rgba(148, 163, 184, 0.2);
            transition: all 0.3s ease;
        }

        .faculty-item:hover {
            transform: translateX(-5px);
            box-shadow: 0 10px 30px rgba(0, 217, 165, 0.2);
            background: rgba(15, 23, 42, 0.6);
            border-color: rgba(0, 217, 165, 0.3);
        }

        .faculty-item.no-data {
            border-right-color: #64748b;
            background: rgba(15, 23, 42, 0.3);
        }

        .faculty-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .faculty-rank {
            background: linear-gradient(135deg, #00d9a5 0%, #009879 100%);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.1rem;
            box-shadow: 0 4px 15px rgba(0, 217, 165, 0.3);
        }

        .faculty-rank.no-data {
            background: #64748b;
        }

        .faculty-name {
            font-weight: 600;
            font-size: 1.1rem;
            color: #e2e8f0;
        }

        .faculty-value {
            font-size: 1.3rem;
            font-weight: 800;
            background: linear-gradient(135deg, #00d9a5, #6366f1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .faculty-value.no-data {
            color: #94a3b8;
            -webkit-text-fill-color: #94a3b8;
        }

        .faculty-unit {
            color: #94a3b8;
            font-size: 0.9rem;
            margin-right: 5px;
        }

        .evaluation-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-right: 10px;
        }

        .evaluation-badge.excellent {
            background: rgba(0, 217, 165, 0.15);
            color: #00d9a5;
            border: 1px solid rgba(0, 217, 165, 0.3);
        }

        .evaluation-badge.warning {
            background: rgba(251, 191, 36, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(251, 191, 36, 0.3);
        }

        .evaluation-badge.error {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .standard-info {
            font-size: 0.8rem;
            color: #94a3b8;
            margin-top: 5px;
        }

        .view-dashboard {
            background: linear-gradient(135deg, #00d9a5 0%, #009879 100%);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            font-family: 'Tajawal', sans-serif;
            box-shadow: 0 4px 15px rgba(0, 217, 165, 0.3);
        }

        .view-dashboard:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 217, 165, 0.4);
            background: linear-gradient(135deg, #00f5d4 0%, #00b894 100%);
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

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            border: 1px solid rgba(148, 163, 184, 0.1);
            position: relative;
            z-index: 1;
        }

        .no-data .icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .data-info {
            background: rgba(0, 217, 165, 0.1);
            backdrop-filter: blur(10px);
            padding: 16px 24px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            color: #00d9a5;
            border: 1px solid rgba(0, 217, 165, 0.2);
            font-weight: 600;
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

            .indicator-info {
                flex-direction: column;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .chart-container {
                height: 400px;
            }

            .faculty-item {
                flex-direction: column;
                gap: 15px;
                text-align: center;
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
                <p>مقارنة أداء الكليات</p>
            </div>
            
            <div class="user-info">
                <div class="user-welcome">
                    <div class="welcome">مرحباً بعودتك</div>
                    <div class="username"><?= htmlspecialchars($_SESSION['username']) ?></div>
                </div>
                
                <div class="nav-buttons">
                    <a href="sustainability_indicators.php" class="nav-btn"><i class="fa-solid fa-chart-bar"></i> المؤشرات</a>
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
            <div class="indicator-info">
                <div class="indicator-icon">
                    <?= $indicator_icon ?>
                </div>
                <div class="indicator-details">
                    <h2><?= $indicator_name ?></h2>
                    <div class="indicator-unit">الوحدة: <?= $indicator_unit ?></div>
                </div>
            </div>
            
            <div class="period-info">
                📅 عرض بيانات شهر: <strong><?= $current_month ?></strong> / <strong><?= $current_year ?></strong>
            </div>

            <!-- الإحصائيات السريعة -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= $faculty_count_total ?></div>
                    <div class="stat-label">كلية</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($total_value, 2) ?></div>
                    <div class="stat-label">إجمالي القيمة</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($average_value, 2) ?></div>
                    <div class="stat-label">متوسط القيمة</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($max_value, 2) ?></div>
                    <div class="stat-label">أعلى قيمة</div>
                </div>
            </div>
        </div>

        <?php if (count($values_with_data) > 0): ?>
            <!-- معلومات البيانات -->
            <div class="data-info">
                📊 عرض بيانات <strong><?= count($values_with_data) ?></strong> كلية من أصل <strong><?= $faculty_count_total ?></strong> كلية
            </div>

            <!-- الرسم البياني -->
            <div class="chart-section">
                <div class="section-title">
                    📈 رسم بياني لمقارنة أداء الكليات
                </div>
                <div class="chart-container">
                    <canvas id="comparisonChart"></canvas>
                </div>
            </div>

            <!-- قائمة الكليات -->
            <div class="faculties-list">
                <div class="section-title">
                    🏫 ترتيب الكليات حسب الأداء
                </div>
                
                <div class="faculties-ranking">
                    <?php 
                    // دمج المصفوفات وترتيبها حسب القيمة
                    $combined = [];
                    for ($i = 0; $i < count($faculties); $i++) {
                        $combined[] = [
                            'name' => $faculties[$i],
                            'value' => $values[$i],
                            'id' => $faculty_ids[$i],
                            'has_data' => ($values[$i] > 0)
                        ];
                    }
                    
                    // ترتيب تنازلي حسب القيمة (الكليات الي فيها بيانات أولاً)
                    usort($combined, function($a, $b) {
                        if ($b['value'] == $a['value']) return 0;
                        return ($b['value'] > $a['value']) ? 1 : -1;
                    });
                    
                    $rank = 0;
                    foreach ($combined as $item):
                        $has_data = $item['has_data'];
                        if ($has_data) $rank++;
                        
                        // تقييم المؤشر
                        $evaluation = null;
                        $standard_text = '';
                        if ($has_data) {
                            $is_lab = is_laboratory_faculty($item['name']);
                            $related_value = ($indicator_id == 4 && isset($paper_used_values[$item['id']])) ? $paper_used_values[$item['id']] : null;
                            $evaluation = evaluate_indicator($item['value'], $indicator_id, $is_lab, $related_value);
                            $standard_text = get_standard_text($indicator_id, $is_lab);
                        }
                    ?>
                        <div class="faculty-item <?= !$has_data ? 'no-data' : '' ?>">
                            <div class="faculty-info">
                                <div class="faculty-rank <?= !$has_data ? 'no-data' : '' ?>">
                                    <?= $has_data ? $rank : '-' ?>
                                </div>
                                <div>
                                    <div class="faculty-name"><?= htmlspecialchars($item['name']) ?></div>
                                    <?php if ($has_data && $evaluation): ?>
                                        <div class="evaluation-badge <?= $evaluation['status'] ?>">
                                            <span><?= $evaluation['icon'] ?></span>
                                            <span><?= htmlspecialchars($evaluation['message']) ?></span>
                                        </div>
                                        <div class="standard-info">
                                            المعيار: <?= $standard_text ?> <?= is_laboratory_faculty($item['name']) ? '(مختبرية)' : '(غير مختبرية)' ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="faculty-value <?= !$has_data ? 'no-data' : '' ?>">
                                <?php if ($has_data): ?>
                                    <span class="faculty-unit"><?= $indicator_unit ?></span>
                                    <?= number_format($item['value'], 2) ?>
                                <?php else: ?>
                                    لا توجد بيانات
                                <?php endif; ?>
                            </div>

                            <a href="faculty_dashboard.php?id=<?= $item['id'] ?>" class="view-dashboard">
                                عرض لوحة الكلية
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="no-data">
                <div class="icon">📊</div>
                <h3>لا توجد بيانات متاحة</h3>
                <p>لم يتم إدخال بيانات لهذا المؤشر بعد من قبل عمداء الكليات</p>
                <br>
                <a href="faculties.php" class="view-dashboard" style="padding: 12px 24px;">
                    🏫 عرض الكليات لإدخال البيانات
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- الفوتر -->
    <div class="footer">
        نظام Beyond You - جامعة اليرموك © 2026
    </div>

    <script>
        <?php if (count($values_with_data) > 0): ?>
        // الرسم البياني
        const ctx = document.getElementById('comparisonChart').getContext('2d');
        
        // فلترة البيانات لعرض فقط الكليات الي فيها بيانات
        const filteredLabels = [];
        const filteredData = [];
        const filteredColors = [];
        
        <?php 
        $colors = ['#009879', '#00b894', '#00d4aa', '#26de81', '#55efc4', '#81ecec', '#74b9ff', '#6c5ce7', '#a29bfe', '#fd79a8'];
        for ($i = 0; $i < count($all_faculties_data); $i++): 
            if ($all_faculties_data[$i]['value'] > 0): 
        ?>
            filteredLabels.push(<?= json_encode($all_faculties_data[$i]['faculty_name'], JSON_UNESCAPED_UNICODE) ?>);
            filteredData.push(<?= $all_faculties_data[$i]['value'] ?>);
            filteredColors.push('<?= $colors[$i % count($colors)] ?>');
        <?php 
            endif;
        endfor; 
        ?>

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: filteredLabels,
                datasets: [{
                    label: '<?= $indicator_name ?> (<?= $indicator_unit ?>)',
                    data: filteredData,
                    backgroundColor: filteredColors,
                    borderColor: filteredColors.map(color => color.replace('0.8', '1')),
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
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
                        text: 'مقارنة أداء الكليات في مؤشر <?= $indicator_name ?>',
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
                        },
                        callbacks: {
                            label: function(context) {
                                return `القيمة: ${context.parsed.y} <?= $indicator_unit ?>`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: '<?= $indicator_unit ?>',
                            font: {
                                family: 'Tajawal',
                                size: 14,
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            font: {
                                family: 'Tajawal'
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'الكليات',
                            font: {
                                family: 'Tajawal',
                                size: 14,
                                weight: 'bold'
                            }
                        },
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