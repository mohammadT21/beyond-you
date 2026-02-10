<?php
// test_gemini_all.php - اختبار شامل لجميع خيارات Gemini API
require_once 'config.php';

echo "<!DOCTYPE html><html lang='ar' dir='rtl'><head><meta charset='UTF-8'><title>اختبار Gemini API</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#1a1a1a;color:#fff;}";
echo ".success{color:#0f0;padding:10px;background:#0f0f0f;border:1px solid #0f0;margin:10px 0;}";
echo ".error{color:#f00;padding:10px;background:#0f0f0f;border:1px solid #f00;margin:10px 0;}";
echo ".info{color:#ff0;padding:10px;background:#0f0f0f;border:1px solid #ff0;margin:10px 0;}";
echo "pre{background:#000;padding:10px;overflow:auto;}</style></head><body>";

echo "<h1>🔍 اختبار شامل لـ Gemini API</h1>";

if (empty(GEMINI_API_KEY) || GEMINI_API_KEY === 'YOUR_GEMINI_API_KEY_HERE') {
    die("<div class='error'>❌ Gemini API Key غير معرّف</div>");
}

echo "<div class='info'>✅ API Key: " . substr(GEMINI_API_KEY, 0, 20) . "...</div>";

// قائمة الخيارات للاختبار
$options = [
    ['v1beta', 'gemini-1.5-flash', 'v1beta + flash'],
    ['v1beta', 'gemini-1.5-flash-001', 'v1beta + flash-001'],
    ['v1beta', 'gemini-1.5-pro', 'v1beta + pro'],
    ['v1beta', 'gemini-pro', 'v1beta + gemini-pro'],
    ['v1', 'gemini-1.5-flash', 'v1 + flash'],
    ['v1', 'gemini-1.5-flash-001', 'v1 + flash-001'],
    ['v1', 'gemini-1.5-pro', 'v1 + pro'],
    ['v1', 'gemini-pro', 'v1 + gemini-pro'],
];

$payload = [
    'contents' => [
        [
            'parts' => [
                [
                    'text' => 'مرحباً، أجب بكلمة "نجح" فقط.'
                ]
            ]
        ]
    ]
];

foreach ($options as $option) {
    $version = $option[0];
    $model = $option[1];
    $label = $option[2];
    
    $url = "https://generativelanguage.googleapis.com/{$version}/models/{$model}:generateContent?key=" . GEMINI_API_KEY;
    
    echo "<h3>🧪 اختبار: {$label}</h3>";
    echo "<div class='info'>URL: {$url}</div>";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($curl_error) {
        echo "<div class='error'>❌ خطأ cURL: {$curl_error}</div>";
        continue;
    }
    
    if ($http_code === 200) {
        $result = json_decode($response, true);
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            echo "<div class='success'>✅ <strong>نجح!</strong> الاستجابة: " . htmlspecialchars($result['candidates'][0]['content']['parts'][0]['text']) . "</div>";
            echo "<div class='success'><strong>✅ استخدم هذا الخيار في CONFIG.php:</strong><br>";
            echo "GEMINI_API_BASE_URL = 'https://generativelanguage.googleapis.com/{$version}'<br>";
            echo "GEMINI_MODEL_NAME = '{$model}'</div>";
            break; // توقف عند أول نجاح
        } else {
            echo "<div class='error'>⚠️ كود 200 لكن الاستجابة غير متوقعة</div>";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
        }
    } else {
        $error_data = json_decode($response, true);
        $error_msg = isset($error_data['error']['message']) ? $error_data['error']['message'] : 'خطأ غير معروف';
        echo "<div class='error'>❌ فشل (HTTP {$http_code}): {$error_msg}</div>";
    }
    
    echo "<hr>";
}

echo "<p><a href='dashboard_admin.php' style='color:#0ff;'>العودة للوحة التحكم</a></p>";
echo "</body></html>";
?>









