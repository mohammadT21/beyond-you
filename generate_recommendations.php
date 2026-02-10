<?php
// generate_recommendations.php - API endpoint لإنشاء التوصيات عبر AJAX
// زيادة timeout للطلبات الطويلة
set_time_limit(180); // 3 دقائق
ini_set('max_execution_time', 180);

require_once 'config.php';
require_once 'gemini_ai.php';

header('Content-Type: application/json; charset=utf-8');

// التحقق من الجلسة (المدير فقط)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'error' => 'غير مصرح لك بالوصول'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'طريقة الطلب غير صحيحة'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$conn = db_connect();
$month = intval($_POST['month']);
$year = intval($_POST['year']);
$faculty_id = isset($_POST['faculty_id']) && $_POST['faculty_id'] !== '' ? intval($_POST['faculty_id']) : null;

try {
    if ($faculty_id) {
        // توصيات لكلية واحدة
        $faculty_data = get_faculty_data($conn, $faculty_id, $month, $year);
        
        if (!$faculty_data['has_data']) {
            echo json_encode([
                'success' => false,
                'error' => 'لا توجد بيانات متاحة لهذه الكلية في الشهر والسنة المحددة.'
            ], JSON_UNESCAPED_UNICODE);
            db_close($conn);
            exit;
        }
        
        // تحضير البيانات للذكاء الاصطناعي
        $data_text = prepare_faculty_data_for_ai($faculty_data);
        
        // الحصول على التوصيات
        $result = get_faculty_ai_recommendations($data_text, $faculty_data['faculty_name']);
        
        if ($result['success']) {
            // حفظ التوصيات
            save_faculty_recommendations($conn, $faculty_id, $month, $year, $result['recommendations']);
            
            // تحويل النص إلى HTML
            $recommendations_html = htmlspecialchars($result['recommendations'], ENT_QUOTES, 'UTF-8');
            $recommendations_html = nl2br($recommendations_html);
            
            echo json_encode([
                'success' => true,
                'message' => 'تم إنشاء التوصيات بنجاح لـ ' . $faculty_data['faculty_name'] . '!',
                'recommendations' => $recommendations_html
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'success' => false,
                'error' => $result['error']
            ], JSON_UNESCAPED_UNICODE);
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
            echo json_encode([
                'success' => false,
                'error' => 'لا توجد بيانات متاحة للشهر والسنة المحددة.'
            ], JSON_UNESCAPED_UNICODE);
            db_close($conn);
            exit;
        }
        
        // إعداد البيانات للذكاء الاصطناعي
        $data_text = prepare_data_for_ai($data);
        
        // الحصول على التوصيات من Gemini AI
        $result = get_ai_recommendations($data_text);
        
        if ($result['success']) {
            // حفظ التوصيات
            save_faculty_recommendations($conn, null, $month, $year, $result['recommendations']);
            
            // تحويل النص إلى HTML
            $recommendations_html = htmlspecialchars($result['recommendations'], ENT_QUOTES, 'UTF-8');
            $recommendations_html = nl2br($recommendations_html);
            
            echo json_encode([
                'success' => true,
                'message' => 'تم إنشاء التوصيات بنجاح لجميع الكليات!',
                'recommendations' => $recommendations_html
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'success' => false,
                'error' => $result['error']
            ], JSON_UNESCAPED_UNICODE);
        }
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'حدث خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

db_close($conn);
?>

