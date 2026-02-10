<?php
// ai_recommendations.php - صفحة توصيات الذكاء الاصطناعي للمدير
require_once 'config.php';
require_once 'gemini_ai.php';

// التحقق من الجلسة (المدير فقط)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$conn = db_connect();
$message = '';
$recommendations = '';
$loading = false;
$month = isset($_GET['month']) ? intval($_GET['month']) : null;
$year = isset($_GET['year']) ? intval($_GET['year']) : null;
$faculty_id = isset($_GET['faculty_id']) ? intval($_GET['faculty_id']) : null;

// جلب قائمة الكليات
$faculties_sql = "SELECT id, name FROM faculties ORDER BY name ASC";
$faculties_result = mysqli_query($conn, $faculties_sql);
$all_faculties = [];
while ($row = mysqli_fetch_assoc($faculties_result)) {
    $all_faculties[] = $row;
}

// جلب التوصيات المحفوظة إذا كانت موجودة
$saved_recommendations = null;
if ($month && $year) {
    $saved_recommendations = get_saved_recommendations($conn, $month, $year, $faculty_id);
}

// طلب توصيات جديدة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_recommendations'])) {
    $month = intval($_POST['month']);
    $year = intval($_POST['year']);
    $faculty_id = isset($_POST['faculty_id']) && $_POST['faculty_id'] !== '' ? intval($_POST['faculty_id']) : null;
    
    if ($faculty_id) {
        // توصيات لكلية واحدة
        $faculty_data = get_faculty_data($conn, $faculty_id, $month, $year);
        
        if (!$faculty_data['has_data']) {
            $message = '<i class="fa-solid fa-triangle-exclamation"></i> لا توجد بيانات متاحة لهذه الكلية في الشهر والسنة المحددة.';
        } else {
            // تحضير البيانات للذكاء الاصطناعي
            $data_text = prepare_faculty_data_for_ai($faculty_data);
            
            // الحصول على التوصيات
            $result = get_faculty_ai_recommendations($data_text, $faculty_data['faculty_name']);
            
            if ($result['success']) {
                $recommendations = $result['recommendations'];
                // حفظ التوصيات
                save_faculty_recommendations($conn, $faculty_id, $month, $year, $recommendations);
                $message = '<i class="fa-solid fa-circle-check"></i> تم إنشاء التوصيات بنجاح لـ ' . $faculty_data['faculty_name'] . '!';
            } else {
                $message = '<i class="fa-solid fa-triangle-exclamation"></i> ' . $result['error'];
            }
        }
    } else {
        // توصيات لجميع الكليات
        $data = get_sustainability_data($conn, $month, $year);
        
        // التحقق من وجود بيانات
        $has_data = false;
        foreach ($data['statistics'] as $stats) {
            if ($stats['count'] > 0) {
                $has_data = true;
                break;
            }
        }
        
        if (!$has_data) {
            $message = '<i class="fa-solid fa-triangle-exclamation"></i> لا توجد بيانات متاحة للشهر والسنة المحددة.';
        } else {
            // إعداد البيانات للذكاء الاصطناعي
            $data_text = prepare_data_for_ai($data);
            
            // الحصول على التوصيات من Gemini AI
            $result = get_ai_recommendations($data_text);
            
            if ($result['success']) {
                $recommendations = $result['recommendations'];
                // حفظ التوصيات
                save_faculty_recommendations($conn, null, $month, $year, $recommendations);
                $message = '<i class="fa-solid fa-circle-check"></i> تم إنشاء التوصيات بنجاح لجميع الكليات!';
            } else {
                $message = '<i class="fa-solid fa-triangle-exclamation"></i> ' . $result['error'];
            }
        }
    }
}

// إذا كانت هناك توصيات محفوظة، عرضها
if ($saved_recommendations && empty($recommendations)) {
    $recommendations = $saved_recommendations['recommendations'];
}

// جلب أحدث شهر وسنة
$latest_sql = "SELECT MAX(year) AS max_year, MAX(month) AS max_month FROM records";
$latest_result = mysqli_query($conn, $latest_sql);
$latest_data = mysqli_fetch_assoc($latest_result);
$latest_year = $latest_data['max_year'] ?? date('Y');
$latest_month = $latest_data['max_month'] ?? date('n');

db_close($conn);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>توصيات الذكاء الاصطناعي - نظام Beyond You</title>
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
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
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
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
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
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            color: #cbd5e1;
            font-weight: 600;
            font-size: 0.95rem;
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
            backdrop-filter: blur(10px);
        }

        .form-input:focus {
            outline: none;
            border-color: #00d9a5;
            box-shadow: 0 0 0 4px rgba(0, 217, 165, 0.15);
            background: rgba(15, 23, 42, 0.6);
        }

        .month-year-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 217, 165, 0.4);
            background: linear-gradient(135deg, #00f5d4 0%, #00b894 100%);
        }

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
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
        }

        .message.error {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .recommendations-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(148, 163, 184, 0.1);
        }

        .recommendations-content {
            color: #e2e8f0;
            line-height: 2;
            font-size: 1.05rem;
            white-space: pre-wrap;
        }

        .recommendations-content h3,
        .recommendations-content h4 {
            color: #00d9a5;
            margin-top: 25px;
            margin-bottom: 15px;
        }

        .recommendations-content ul,
        .recommendations-content ol {
            margin-right: 25px;
            margin-top: 10px;
            margin-bottom: 15px;
        }

        .recommendations-content li {
            margin-bottom: 10px;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
        }

        .loading i {
            font-size: 3rem;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
            color: #00d9a5;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
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
            .month-year-group {
                grid-template-columns: 1fr;
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
            
            <div class="nav-buttons">
                <a href="dashboard_admin.php" class="nav-btn"><i class="fa-solid fa-house"></i> الرئيسية</a>
                <a href="sustainability_indicators.php" class="nav-btn"><i class="fa-solid fa-chart-bar"></i> المؤشرات</a>
                <a href="logout.php" class="nav-btn"><i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج</a>
            </div>
        </div>
    </header>

    <!-- المحتوى الرئيسي -->
    <div class="container">
        <!-- عنوان الصفحة -->
        <div class="page-header">
            <h2><i class="fa-solid fa-robot"></i> توصيات الذكاء الاصطناعي</h2>
            <p>احصل على توصيات ذكية لتحسين الأداء البيئي والاستدامة بناءً على تحليل بيانات الكليات</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?= strpos($message, 'fa-circle-check') !== false ? '' : 'error' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- نموذج اختيار الشهر والسنة والكلية -->
        <div class="form-card">
            <form id="recommendationsForm" method="post" action="">
                <div class="form-group" style="margin-bottom: 25px;">
                    <label for="faculty_id" class="form-label"><i class="fa-solid fa-building-columns"></i> الكلية</label>
                    <select id="faculty_id" name="faculty_id" class="form-input">
                        <option value="">جميع الكليات</option>
                        <?php foreach ($all_faculties as $faculty): ?>
                            <option value="<?= $faculty['id'] ?>" <?= ($faculty_id == $faculty['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($faculty['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p style="color: #94a3b8; font-size: 0.85rem; margin-top: 8px;">
                        <i class="fa-solid fa-info-circle"></i> اختر كلية محددة للحصول على توصيات مخصصة لها، أو اتركه فارغاً للحصول على توصيات عامة لجميع الكليات
                    </p>
                </div>

                <div class="month-year-group">
                    <div class="form-group">
                        <label for="month" class="form-label"><i class="fa-solid fa-calendar"></i> الشهر</label>
                        <input type="number" id="month" name="month" class="form-input" 
                               min="1" max="12" value="<?= $month ?? $latest_month ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="year" class="form-label"><i class="fa-solid fa-calendar-days"></i> السنة</label>
                        <input type="number" id="year" name="year" class="form-input" 
                               min="2020" max="2030" value="<?= $year ?? $latest_year ?>" required>
                    </div>
                </div>

                <button type="submit" id="generateBtn" class="submit-btn">
                    <i class="fa-solid fa-sparkles" id="btnIcon"></i>
                    <span id="btnText">إنشاء توصيات ذكية</span>
                </button>
            </form>
        </div>

        <!-- Loading Indicator -->
        <div id="loadingIndicator" style="display: none; text-align: center; padding: 40px; margin: 30px 0;">
            <div style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(10px); padding: 40px; border-radius: 16px; border: 1px solid rgba(148, 163, 184, 0.2);">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 3rem; color: #00d9a5; margin-bottom: 20px;"></i>
                <h3 style="color: #00d9a5; margin-bottom: 10px;">جاري إنشاء التوصيات...</h3>
                <p style="color: #94a3b8;">يرجى الانتظار، قد يستغرق هذا الأمر 30-60 ثانية</p>
                <div style="margin-top: 20px; color: #64748b; font-size: 0.9rem;">
                    <i class="fa-solid fa-info-circle"></i> يتم تحليل البيانات وإرسالها إلى Gemini AI
                </div>
            </div>
        </div>

        <!-- عرض التوصيات -->
        <div id="recommendationsContainer">
            <?php if ($recommendations): ?>
                <div class="recommendations-card">
                    <h3 style="color: #00d9a5; margin-bottom: 20px; font-size: 1.5rem;">
                        <i class="fa-solid fa-lightbulb"></i> التوصيات الذكية
                    </h3>
                    <div class="recommendations-content">
                        <?= nl2br(htmlspecialchars($recommendations)) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- الفوتر -->
    <div class="footer">
        نظام Beyond You - جامعة اليرموك © 2026
    </div>

    <script>
        // تحويل النموذج إلى AJAX
        document.getElementById('recommendationsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const generateBtn = document.getElementById('generateBtn');
            const btnIcon = document.getElementById('btnIcon');
            const btnText = document.getElementById('btnText');
            const loadingIndicator = document.getElementById('loadingIndicator');
            const recommendationsContainer = document.getElementById('recommendationsContainer');
            const messageDiv = document.querySelector('.message');
            
            // إخفاء الرسائل السابقة
            if (messageDiv) {
                messageDiv.remove();
            }
            
            // إخفاء التوصيات السابقة
            recommendationsContainer.innerHTML = '';
            
            // إظهار loading indicator
            loadingIndicator.style.display = 'block';
            
            // تعطيل الزر
            generateBtn.disabled = true;
            btnIcon.className = 'fa-solid fa-spinner fa-spin';
            btnText.textContent = 'جاري الإنشاء...';
            
            // جمع البيانات
            const formData = new FormData(form);
            
            // إرسال الطلب
            fetch('generate_recommendations.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // إخفاء loading indicator
                loadingIndicator.style.display = 'none';
                
                // تفعيل الزر
                generateBtn.disabled = false;
                btnIcon.className = 'fa-solid fa-sparkles';
                btnText.textContent = 'إنشاء توصيات ذكية';
                
                if (data.success) {
                    // إظهار رسالة النجاح
                    const successMsg = document.createElement('div');
                    successMsg.className = 'message';
                    successMsg.innerHTML = '<i class="fa-solid fa-circle-check"></i> ' + data.message;
                    form.parentElement.insertBefore(successMsg, form);
                    
                    // إظهار التوصيات
                    recommendationsContainer.innerHTML = `
                        <div class="recommendations-card">
                            <h3 style="color: #00d9a5; margin-bottom: 20px; font-size: 1.5rem;">
                                <i class="fa-solid fa-lightbulb"></i> التوصيات الذكية
                            </h3>
                            <div class="recommendations-content">
                                ${data.recommendations}
                            </div>
                        </div>
                    `;
                    
                    // التمرير إلى التوصيات
                    recommendationsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else {
                    // إظهار رسالة الخطأ
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'message error';
                    errorMsg.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ' + data.error;
                    form.parentElement.insertBefore(errorMsg, form);
                }
            })
            .catch(error => {
                // إخفاء loading indicator
                loadingIndicator.style.display = 'none';
                
                // تفعيل الزر
                generateBtn.disabled = false;
                btnIcon.className = 'fa-solid fa-sparkles';
                btnText.textContent = 'إنشاء توصيات ذكية';
                
                // إظهار رسالة الخطأ
                const errorMsg = document.createElement('div');
                errorMsg.className = 'message error';
                errorMsg.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> حدث خطأ أثناء الاتصال بالخادم: ' + error.message;
                form.parentElement.insertBefore(errorMsg, form);
            });
        });
    </script>
</body>
</html>

