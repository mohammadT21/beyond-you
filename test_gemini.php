<?php
// test_gemini.php - ملف اختبار للاتصال بـ Gemini API
require_once 'config.php';

echo "<h2>اختبار الاتصال بـ Gemini API</h2>";

// التحقق من وجود API Key
if (empty(GEMINI_API_KEY) || GEMINI_API_KEY === 'YOUR_GEMINI_API_KEY_HERE') {
    die("<p style='color: red;'>❌ Gemini API Key غير معرّف في CONFIG.php</p>");
}

echo "<p>✅ API Key موجود: " . substr(GEMINI_API_KEY, 0, 20) . "...</p>";

// بناء URL الصحيح
$model_name = defined('GEMINI_MODEL_NAME') ? GEMINI_MODEL_NAME : 'gemini-1.5-flash';
$base_url = defined('GEMINI_API_BASE_URL') ? GEMINI_API_BASE_URL : 'https://generativelanguage.googleapis.com/v1beta';
$url = $base_url . '/models/' . $model_name . ':generateContent?key=' . GEMINI_API_KEY;

echo "<p>🔗 Base URL: " . $base_url . "</p>";
echo "<p>🔗 Model: " . $model_name . "</p>";
echo "<p>🔗 Full URL: " . $url . "</p>";

// اختبار بسيط

$payload = [
    'contents' => [
        [
            'parts' => [
                [
                    'text' => 'مرحباً، هل تعمل؟ أجب بنعم أو لا فقط.'
                ]
            ]
        ]
    ]
];

echo "<h3>إرسال طلب اختبار...</h3>";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

$response = curl_exec($ch);
$curl_error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h3>النتائج:</h3>";
echo "<p><strong>كود HTTP:</strong> " . $http_code . "</p>";

if ($curl_error) {
    echo "<p style='color: red;'>❌ خطأ cURL: " . htmlspecialchars($curl_error) . "</p>";
} else {
    echo "<p style='color: green;'>✅ لا توجد أخطاء في cURL</p>";
}

if ($response) {
    $result = json_decode($response, true);
    
    if ($http_code === 200) {
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            echo "<p style='color: green;'>✅ <strong>نجح الاتصال!</strong></p>";
            echo "<p><strong>الاستجابة:</strong> " . htmlspecialchars($result['candidates'][0]['content']['parts'][0]['text']) . "</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ تم الاتصال لكن الاستجابة غير متوقعة:</p>";
            echo "<pre>" . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
        }
    } else {
        echo "<p style='color: red;'>❌ <strong>فشل الاتصال!</strong></p>";
        if (isset($result['error'])) {
            echo "<p><strong>رسالة الخطأ:</strong> " . htmlspecialchars($result['error']['message'] ?? 'غير معروف') . "</p>";
            if (isset($result['error']['code'])) {
                echo "<p><strong>كود الخطأ:</strong> " . htmlspecialchars($result['error']['code']) . "</p>";
            }
        } else {
            echo "<p><strong>الاستجابة الكاملة:</strong></p>";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 1000)) . "</pre>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ لم يتم الحصول على استجابة من الخادم</p>";
}

echo "<hr>";
echo "<p><a href='dashboard_admin.php'>العودة للوحة التحكم</a></p>";
?>

