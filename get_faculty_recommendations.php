<?php
// get_faculty_recommendations.php - API endpoint للحصول على توصيات الكلية
require_once 'config.php';
require_once 'gemini_ai.php';

header('Content-Type: application/json; charset=utf-8');

// التحقق من الجلسة (العميد فقط)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'dean') {
    echo json_encode([
        'success' => false,
        'error' => 'غير مصرح لك بالوصول'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'طريقة الطلب غير صحيحة'
    ]);
    exit;
}

$conn = db_connect();
$faculty_id = intval($_SESSION['faculty_id']);
$month = intval($_POST['month']);
$year = intval($_POST['year']);

// جلب بيانات الكلية
$faculty_data = get_faculty_data($conn, $faculty_id, $month, $year);

if (!$faculty_data['has_data']) {
    db_close($conn);
    echo json_encode([
        'success' => false,
        'error' => 'لا توجد بيانات متاحة للكلية في الشهر والسنة المحددة'
    ]);
    exit;
}

// توليد التوصيات بناءً على المعايير
$standards_recommendations = generate_standards_based_recommendations($conn, $faculty_id, $month, $year);

// تحضير البيانات للذكاء الاصطناعي (للتوصيات العامة)
$data_text = prepare_faculty_data_for_ai($faculty_data);

// الحصول على التوصيات العامة من AI
$result = get_faculty_ai_recommendations($data_text, $faculty_data['faculty_name']);

if ($result['success']) {
    // دمج التوصيات
    $full_recommendations = $standards_recommendations . "\n\n---\n\n## 💡 توصيات عامة\n\n" . $result['recommendations'];
    
    // حفظ التوصيات
    save_faculty_recommendations($conn, $faculty_id, $month, $year, $full_recommendations);
    
    // تحويل النص إلى HTML بشكل آمن
    $standards_html = htmlspecialchars($standards_recommendations, ENT_QUOTES, 'UTF-8');
    $standards_html = nl2br($standards_html);
    $standards_html = preg_replace('/##\s+(.+)/', '<h3>$1</h3>', $standards_html);
    $standards_html = preg_replace('/###\s+(.+)/', '<h4>$1</h4>', $standards_html);
    $standards_html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $standards_html);
    
    $general_html = htmlspecialchars($result['recommendations'], ENT_QUOTES, 'UTF-8');
    $general_html = nl2br($general_html);
    
    // إعداد بيانات الرسوم البيانية
    require_once 'evaluation_standards.php';
    $is_lab = is_laboratory_faculty($faculty_data['faculty_name']);
    $chart_data = [];
    $evaluation_stats = ['excellent' => 0, 'warning' => 0, 'error' => 0, 'total' => 0];
    
    // جلب قيمة الورق المستخدم
    $paper_used_value = null;
    foreach ($faculty_data['indicators'] as $ind) {
        if (stripos($ind['name'], 'الورق المستخدم') !== false || stripos($ind['name'], 'الورق المستهلك') !== false) {
            $paper_used_value = $ind['value'];
            break;
        }
    }
    
    foreach ($faculty_data['indicators'] as $indicator) {
        if ($indicator['value'] <= 0) continue;
        
        $indicator_id = null;
        $indicator_sql = "SELECT id FROM indicators WHERE name = '" . mysqli_real_escape_string($conn, $indicator['name']) . "' LIMIT 1";
        $indicator_result = mysqli_query($conn, $indicator_sql);
        if ($indicator_result && mysqli_num_rows($indicator_result) > 0) {
            $indicator_row = mysqli_fetch_assoc($indicator_result);
            $indicator_id = intval($indicator_row['id']);
        }
        
        if (!$indicator_id) continue;
        
        $related_value = ($indicator_id == 4 && $paper_used_value > 0) ? $paper_used_value : null;
        $evaluation = evaluate_indicator($indicator['value'], $indicator_id, $is_lab, $related_value);
        $standard = get_indicator_standards($indicator_id, $is_lab);
        
        if (isset($evaluation_stats[$evaluation['status']])) {
            $evaluation_stats[$evaluation['status']]++;
        }
        $evaluation_stats['total']++;
        
        $chart_data[] = [
            'name' => $indicator['name'],
            'value' => $indicator['value'],
            'unit' => $indicator['unit'],
            'status' => $evaluation['status'],
            'icon' => $evaluation['icon'],
            'standard_min' => $standard['min'] ?? null,
            'standard_max' => $standard['max'] ?? null
        ];
    }
    
    // التأكد من وجود بيانات للرسوم البيانية
    if (empty($chart_data)) {
        // إعادة حساب البيانات إذا كانت فارغة
        foreach ($faculty_data['indicators'] as $indicator) {
            if ($indicator['value'] <= 0) continue;
            
            $indicator_id = null;
            $indicator_sql = "SELECT id FROM indicators WHERE name = '" . mysqli_real_escape_string($conn, $indicator['name']) . "' LIMIT 1";
            $indicator_result = mysqli_query($conn, $indicator_sql);
            if ($indicator_result && mysqli_num_rows($indicator_result) > 0) {
                $indicator_row = mysqli_fetch_assoc($indicator_result);
                $indicator_id = intval($indicator_row['id']);
            }
            
            if (!$indicator_id) continue;
            
            $related_value = ($indicator_id == 4 && $paper_used_value > 0) ? $paper_used_value : null;
            $evaluation = evaluate_indicator($indicator['value'], $indicator_id, $is_lab, $related_value);
            $standard = get_indicator_standards($indicator_id, $is_lab);
            
            if (isset($evaluation_stats[$evaluation['status']])) {
                $evaluation_stats[$evaluation['status']]++;
            }
            $evaluation_stats['total']++;
            
            $chart_data[] = [
                'name' => $indicator['name'],
                'value' => floatval($indicator['value']),
                'unit' => $indicator['unit'],
                'status' => $evaluation['status'],
                'icon' => $evaluation['icon'],
                'standard_min' => $standard ? ($standard['min'] ?? null) : null,
                'standard_max' => $standard ? ($standard['max'] ?? null) : null
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'standards_recommendations' => $standards_html,
        'general_recommendations' => $general_html,
        'chart_data' => $chart_data,
        'evaluation_stats' => $evaluation_stats,
        'debug' => [
            'chart_data_count' => count($chart_data),
            'has_evaluation_stats' => !empty($evaluation_stats)
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    // حتى لو فشل AI، نعيد التوصيات بناءً على المعايير
    $standards_html = htmlspecialchars($standards_recommendations, ENT_QUOTES, 'UTF-8');
    $standards_html = nl2br($standards_html);
    $standards_html = preg_replace('/##\s+(.+)/', '<h3>$1</h3>', $standards_html);
    $standards_html = preg_replace('/###\s+(.+)/', '<h4>$1</h4>', $standards_html);
    $standards_html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $standards_html);
    
    echo json_encode([
        'success' => true,
        'standards_recommendations' => $standards_html,
        'general_recommendations' => '<p style="color: #fbbf24;">⚠️ تعذر الحصول على توصيات عامة من الذكاء الاصطناعي. يمكنك المحاولة مرة أخرى لاحقاً.</p>',
        'chart_data' => [],
        'evaluation_stats' => ['excellent' => 0, 'warning' => 0, 'error' => 0, 'total' => 0],
        'warning' => $result['error']
    ], JSON_UNESCAPED_UNICODE);
}

db_close($conn);
?>

