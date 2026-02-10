<?php
// find_gemini_model.php - البحث عن النموذج الصحيح
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html lang='ar' dir='rtl'><head><meta charset='UTF-8'><title>البحث عن نموذج Gemini</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#1a1a1a;color:#fff;}";
echo ".success{color:#0f0;padding:15px;background:#0f0f0f;border:2px solid #0f0;margin:10px 0;border-radius:5px;}";
echo ".error{color:#f00;padding:15px;background:#0f0f0f;border:2px solid #f00;margin:10px 0;border-radius:5px;}";
echo ".info{color:#ff0;padding:15px;background:#0f0f0f;border:2px solid #ff0;margin:10px 0;border-radius:5px;}";
echo "pre{background:#000;padding:10px;overflow:auto;border:1px solid #333;}</style></head><body>";

echo "<h1>🔍 البحث عن نموذج Gemini الصحيح</h1>";

$api_key = GEMINI_API_KEY;

// أولاً: جلب قائمة النماذج المتاحة
echo "<h2>📋 جلب قائمة النماذج المتاحة...</h2>";

$list_url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $api_key;

$ch = curl_init($list_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$list_response = curl_exec($ch);
$list_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($list_http_code === 200 && $list_response) {
    $models_data = json_decode($list_response, true);
    
    if (isset($models_data['models'])) {
        echo "<div class='success'>✅ تم جلب قائمة النماذج بنجاح!</div>";
        echo "<h3>النماذج المتاحة:</h3>";
        echo "<ul>";
        
        $available_models = [];
        foreach ($models_data['models'] as $model) {
            $model_name = $model['name'] ?? 'غير معروف';
            $display_name = $model['displayName'] ?? $model_name;
            $supported_methods = $model['supportedGenerationMethods'] ?? [];
            
            if (in_array('generateContent', $supported_methods)) {
                $available_models[] = $model_name;
                echo "<li><strong>{$display_name}</strong> ({$model_name})";
                echo " - يدعم generateContent ✅</li>";
            } else {
                echo "<li>{$display_name} ({$model_name}) - لا يدعم generateContent</li>";
            }
        }
        echo "</ul>";
        
        // الآن اختبر النماذج التي تدعم generateContent
        echo "<h2>🧪 اختبار النماذج التي تدعم generateContent...</h2>";
        
        $test_payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => 'أجب بكلمة "نجح" فقط.'
                        ]
                    ]
                ]
            ]
        ];
        
        $working_model = null;
        $working_version = null;
        
        // جرب v1beta أولاً
        foreach ($available_models as $model_name) {
            // استخراج اسم النموذج فقط (بدون models/)
            $model_short = str_replace('models/', '', $model_name);
            
            $test_url = "https://generativelanguage.googleapis.com/v1beta/models/{$model_short}:generateContent?key=" . $api_key;
            
            echo "<div class='info'>🧪 اختبار: v1beta/{$model_short}</div>";
            
            $ch = curl_init($test_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $test_response = curl_exec($ch);
            $test_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($test_http_code === 200) {
                $test_result = json_decode($test_response, true);
                if (isset($test_result['candidates'][0]['content']['parts'][0]['text'])) {
                    echo "<div class='success'>✅ <strong>نجح!</strong> النموذج: {$model_short}</div>";
                    $working_model = $model_short;
                    $working_version = 'v1beta';
                    break;
                }
            } else {
                $error_data = json_decode($test_response, true);
                $error_msg = isset($error_data['error']['message']) ? $error_data['error']['message'] : 'خطأ غير معروف';
                echo "<div class='error'>❌ فشل (HTTP {$test_http_code}): " . substr($error_msg, 0, 100) . "</div>";
            }
        }
        
        // إذا لم يعمل v1beta، جرب v1
        if (!$working_model) {
            echo "<h3>جرب v1...</h3>";
            foreach ($available_models as $model_name) {
                $model_short = str_replace('models/', '', $model_name);
                
                $test_url = "https://generativelanguage.googleapis.com/v1/models/{$model_short}:generateContent?key=" . $api_key;
                
                echo "<div class='info'>🧪 اختبار: v1/{$model_short}</div>";
                
                $ch = curl_init($test_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                
                $test_response = curl_exec($ch);
                $test_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($test_http_code === 200) {
                    $test_result = json_decode($test_response, true);
                    if (isset($test_result['candidates'][0]['content']['parts'][0]['text'])) {
                        echo "<div class='success'>✅ <strong>نجح!</strong> النموذج: {$model_short}</div>";
                        $working_model = $model_short;
                        $working_version = 'v1';
                        break;
                    }
                }
            }
        }
        
        // عرض الحل النهائي
        if ($working_model) {
            echo "<div class='success' style='font-size:18px;padding:20px;'>";
            echo "<h2>✅ الحل الصحيح:</h2>";
            echo "<p><strong>استخدم هذه القيم في CONFIG.php:</strong></p>";
            echo "<pre>";
            echo "define('GEMINI_API_BASE_URL', 'https://generativelanguage.googleapis.com/{$working_version}');\n";
            echo "define('GEMINI_MODEL_NAME', '{$working_model}');";
            echo "</pre>";
            echo "</div>";
        } else {
            echo "<div class='error'>❌ لم يتم العثور على نموذج يعمل</div>";
        }
        
    } else {
        echo "<div class='error'>❌ لم يتم العثور على قائمة النماذج في الاستجابة</div>";
        echo "<pre>" . htmlspecialchars($list_response) . "</pre>";
    }
} else {
    echo "<div class='error'>❌ فشل جلب قائمة النماذج (HTTP {$list_http_code})</div>";
    if ($list_response) {
        $error_data = json_decode($list_response, true);
        if (isset($error_data['error'])) {
            echo "<div class='error'>" . htmlspecialchars($error_data['error']['message'] ?? 'خطأ غير معروف') . "</div>";
        }
    }
}

echo "<p><a href='dashboard_admin.php' style='color:#0ff;'>العودة للوحة التحكم</a></p>";
echo "</body></html>";
?>









