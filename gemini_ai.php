<?php
// gemini_ai.php - دوال للتعامل مع Gemini AI API
require_once 'config.php';

/**
 * جلب بيانات الاستدامة من قاعدة البيانات
 */
function get_sustainability_data($conn, $month = null, $year = null) {
    // تحديد الشهر والسنة
    if ($month === null || $year === null) {
        $month_sql = "SELECT MAX(year) AS max_year, MAX(month) AS max_month FROM records";
        $month_result = mysqli_query($conn, $month_sql);
        $month_data = mysqli_fetch_assoc($month_result);
        $year = $month_data['max_year'] ?? date('Y');
        $month = $month_data['max_month'] ?? date('n');
    }
    
    // جلب جميع المؤشرات
    $indicators_sql = "SELECT * FROM indicators ORDER BY id ASC";
    $indicators_result = mysqli_query($conn, $indicators_sql);
    $indicators = [];
    while ($row = mysqli_fetch_assoc($indicators_result)) {
        $indicators[$row['id']] = $row;
    }
    
    // جلب بيانات جميع الكليات
    $data_sql = "
        SELECT 
            f.id as faculty_id,
            f.name as faculty_name,
            i.id as indicator_id,
            i.name as indicator_name,
            i.unit as indicator_unit,
            COALESCE(r.value, 0) as value
        FROM faculties f
        CROSS JOIN indicators i
        LEFT JOIN records r ON f.id = r.faculty_id 
            AND i.id = r.indicator_id 
            AND r.year = $year 
            AND r.month = $month
        ORDER BY f.name, i.id
    ";
    
    $data_result = mysqli_query($conn, $data_sql);
    $data = [];
    
    while ($row = mysqli_fetch_assoc($data_result)) {
        $faculty_id = $row['faculty_id'];
        $faculty_name = $row['faculty_name'];
        
        if (!isset($data[$faculty_id])) {
            $data[$faculty_id] = [
                'name' => $faculty_name,
                'indicators' => []
            ];
        }
        
        $data[$faculty_id]['indicators'][] = [
            'name' => $row['indicator_name'],
            'unit' => $row['indicator_unit'],
            'value' => floatval($row['value'])
        ];
    }
    
    // حساب الإحصائيات
    $stats = [];
    foreach ($indicators as $ind_id => $indicator) {
        $values = [];
        foreach ($data as $faculty) {
            foreach ($faculty['indicators'] as $ind) {
                if ($ind['name'] == $indicator['name'] && $ind['value'] > 0) {
                    $values[] = $ind['value'];
                }
            }
        }
        
        if (count($values) > 0) {
            $stats[$indicator['name']] = [
                'unit' => $indicator['unit'],
                'total' => array_sum($values),
                'average' => array_sum($values) / count($values),
                'max' => max($values),
                'min' => min($values),
                'count' => count($values)
            ];
        }
    }
    
    return [
        'month' => $month,
        'year' => $year,
        'faculties' => $data,
        'statistics' => $stats,
        'total_faculties' => count($data)
    ];
}

/**
 * إنشاء نص تحليلي للبيانات لإرساله إلى Gemini AI
 */
function prepare_data_for_ai($data) {
    $text = "تحليل بيانات الاستدامة لجامعة اليرموك - شهر {$data['month']}/{$data['year']}\n\n";
    $text .= "عدد الكليات: {$data['total_faculties']}\n\n";
    
    $text .= "الإحصائيات العامة:\n";
    foreach ($data['statistics'] as $ind_name => $stats) {
        $text .= "- {$ind_name} ({$stats['unit']}):\n";
        $text .= "  * المتوسط: " . number_format($stats['average'], 2) . "\n";
        $text .= "  * الأعلى: " . number_format($stats['max'], 2) . "\n";
        $text .= "  * الأدنى: " . number_format($stats['min'], 2) . "\n";
        $text .= "  * الإجمالي: " . number_format($stats['total'], 2) . "\n";
        $text .= "  * عدد الكليات التي أدخلت بيانات: {$stats['count']}\n\n";
    }
    
    $text .= "بيانات الكليات:\n";
    foreach ($data['faculties'] as $faculty) {
        $text .= "\n{$faculty['name']}:\n";
        foreach ($faculty['indicators'] as $ind) {
            if ($ind['value'] > 0) {
                $text .= "  - {$ind['name']}: {$ind['value']} {$ind['unit']}\n";
            }
        }
    }
    
    return $text;
}

/**
 * استدعاء Gemini AI API للحصول على التوصيات
 */
function get_ai_recommendations($data_text) {
    $api_key = GEMINI_API_KEY;
    
    if (empty($api_key) || $api_key === 'YOUR_GEMINI_API_KEY_HERE') {
        return [
            'success' => false,
            'error' => 'Gemini API Key غير معرّف. يرجى إضافة المفتاح في ملف CONFIG.php'
        ];
    }
    
    $prompt = "أنت خبير في الاستدامة البيئية وإدارة الموارد في الجامعات. قم بتحليل البيانات التالية وقدم توصيات عملية ومحددة لتحسين الأداء البيئي والاستدامة في جامعة اليرموك.\n\n";
    $prompt .= "البيانات:\n" . $data_text . "\n\n";
    $prompt .= "يرجى تقديم:\n";
    $prompt .= "1. تحليل شامل للأداء الحالي\n";
    $prompt .= "2. نقاط القوة والضعف\n";
    $prompt .= "3. توصيات عملية محددة لكل مؤشر\n";
    $prompt .= "4. أولويات التحسين\n";
    $prompt .= "5. خطوات تنفيذية قابلة للتطبيق\n\n";
    $prompt .= "أجب بالعربية بشكل واضح ومنظم.";
    
    // بناء URL الصحيح
    $model_name = defined('GEMINI_MODEL_NAME') ? GEMINI_MODEL_NAME : 'gemini-1.5-flash';
    $base_url = defined('GEMINI_API_BASE_URL') ? GEMINI_API_BASE_URL : 'https://generativelanguage.googleapis.com/v1beta';
    $url = $base_url . '/models/' . $model_name . ':generateContent?key=' . $api_key;
    
    $payload = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => $prompt
                    ]
                ]
            ]
        ]
    ];
    
    // التحقق من وجود cURL
    if (!function_exists('curl_init')) {
        return [
            'success' => false,
            'error' => 'cURL غير مفعّل في PHP. يرجى تفعيله من إعدادات PHP.'
        ];
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120); // مهلة 120 ثانية (زيادة الوقت للطلبات الطويلة)
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($curl_error) {
        return [
            'success' => false,
            'error' => 'خطأ في الاتصال: ' . $curl_error
        ];
    }
    
    if ($http_code !== 200) {
        $error_details = '';
        if ($response) {
            $error_data = json_decode($response, true);
            if (isset($error_data['error'])) {
                $error_details = ': ' . ($error_data['error']['message'] ?? json_encode($error_data['error']));
            } else {
                $error_details = ': ' . substr($response, 0, 200);
            }
        }
        return [
            'success' => false,
            'error' => 'خطأ في الاتصال بـ Gemini API. كود الخطأ: ' . $http_code . $error_details
        ];
    }
    
    $result = json_decode($response, true);
    
    if (!$result) {
        return [
            'success' => false,
            'error' => 'خطأ في تحليل الاستجابة من Gemini API. الاستجابة: ' . substr($response, 0, 200)
        ];
    }
    
    if (isset($result['error'])) {
        $error_msg = $result['error']['message'] ?? 'خطأ غير معروف من Gemini API';
        if (isset($result['error']['code'])) {
            $error_msg .= ' (كود: ' . $result['error']['code'] . ')';
        }
        return [
            'success' => false,
            'error' => $error_msg
        ];
    }
    
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        return [
            'success' => true,
            'recommendations' => $result['candidates'][0]['content']['parts'][0]['text']
        ];
    }
    
    // في حالة عدم وجود نص، إرجاع معلومات الاستجابة للمساعدة في التشخيص
    return [
        'success' => false,
        'error' => 'لم يتم الحصول على استجابة صحيحة من Gemini AI. الاستجابة: ' . json_encode($result, JSON_UNESCAPED_UNICODE)
    ];
}

/**
 * حفظ التوصيات في قاعدة البيانات (اختياري)
 */
function save_recommendations($conn, $month, $year, $recommendations) {
    // إنشاء جدول إذا لم يكن موجوداً
    $create_table_sql = "
        CREATE TABLE IF NOT EXISTS ai_recommendations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            month INT NOT NULL,
            year INT NOT NULL,
            recommendations TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_month_year (month, year)
        )
    ";
    mysqli_query($conn, $create_table_sql);
    
    // حذف التوصيات القديمة لنفس الشهر والسنة
    $delete_sql = "DELETE FROM ai_recommendations WHERE month = $month AND year = $year";
    mysqli_query($conn, $delete_sql);
    
    // إدخال التوصيات الجديدة
    $recommendations_escaped = mysqli_real_escape_string($conn, $recommendations);
    $insert_sql = "INSERT INTO ai_recommendations (month, year, recommendations) 
                   VALUES ($month, $year, '$recommendations_escaped')";
    
    return mysqli_query($conn, $insert_sql);
}

/**
 * جلب بيانات كلية واحدة
 */
function get_faculty_data($conn, $faculty_id, $month = null, $year = null) {
    // تحديد الشهر والسنة
    if ($month === null || $year === null) {
        $month_sql = "SELECT MAX(year) AS max_year, MAX(month) AS max_month FROM records WHERE faculty_id = $faculty_id";
        $month_result = mysqli_query($conn, $month_sql);
        $month_data = mysqli_fetch_assoc($month_result);
        $year = $month_data['max_year'] ?? date('Y');
        $month = $month_data['max_month'] ?? date('n');
    }
    
    // جلب اسم الكلية
    $faculty_sql = "SELECT name FROM faculties WHERE id = $faculty_id";
    $faculty_result = mysqli_query($conn, $faculty_sql);
    $faculty_name = 'كلية غير معروفة';
    if ($faculty_result && mysqli_num_rows($faculty_result) > 0) {
        $faculty_row = mysqli_fetch_assoc($faculty_result);
        $faculty_name = $faculty_row['name'];
    }
    
    // جلب بيانات المؤشرات للكلية
    $data_sql = "
        SELECT 
            i.id as indicator_id,
            i.name as indicator_name,
            i.unit as indicator_unit,
            COALESCE(r.value, 0) as value
        FROM indicators i
        LEFT JOIN records r ON i.id = r.indicator_id 
            AND r.faculty_id = $faculty_id
            AND r.year = $year 
            AND r.month = $month
        ORDER BY i.id
    ";
    
    $data_result = mysqli_query($conn, $data_sql);
    $indicators = [];
    $has_data = false;
    
    while ($row = mysqli_fetch_assoc($data_result)) {
        $value = floatval($row['value']);
        if ($value > 0) {
            $has_data = true;
        }
        $indicators[] = [
            'name' => $row['indicator_name'],
            'unit' => $row['indicator_unit'],
            'value' => $value
        ];
    }
    
    // جلب المتوسطات العامة للمقارنة
    $avg_sql = "
        SELECT 
            i.id,
            i.name,
            AVG(r.value) as avg_value,
            MAX(r.value) as max_value,
            MIN(r.value) as min_value
        FROM indicators i
        LEFT JOIN records r ON i.id = r.indicator_id 
            AND r.year = $year 
            AND r.month = $month
        GROUP BY i.id, i.name
    ";
    
    $avg_result = mysqli_query($conn, $avg_sql);
    $averages = [];
    while ($row = mysqli_fetch_assoc($avg_result)) {
        if ($row['avg_value'] !== null) {
            $averages[$row['name']] = [
                'average' => floatval($row['avg_value']),
                'max' => floatval($row['max_value']),
                'min' => floatval($row['min_value'])
            ];
        }
    }
    
    return [
        'faculty_id' => $faculty_id,
        'faculty_name' => $faculty_name,
        'month' => $month,
        'year' => $year,
        'indicators' => $indicators,
        'averages' => $averages,
        'has_data' => $has_data
    ];
}

/**
 * تحضير بيانات كلية واحدة للذكاء الاصطناعي
 */
function prepare_faculty_data_for_ai($data) {
    $text = "تحليل بيانات الاستدامة لـ {$data['faculty_name']} - شهر {$data['month']}/{$data['year']}\n\n";
    
    $text .= "بيانات المؤشرات:\n";
    foreach ($data['indicators'] as $ind) {
        if ($ind['value'] > 0) {
            $text .= "- {$ind['name']}: {$ind['value']} {$ind['unit']}\n";
            
            // إضافة مقارنة مع المتوسط إذا كان متوفراً
            if (isset($data['averages'][$ind['name']])) {
                $avg = $data['averages'][$ind['name']]['average'];
                $diff = $ind['value'] - $avg;
                $percent = $avg > 0 ? (($diff / $avg) * 100) : 0;
                
                if ($diff > 0) {
                    $text .= "  (أعلى من المتوسط بـ " . number_format($diff, 2) . " {$ind['unit']} - " . number_format($percent, 1) . "%)\n";
                } elseif ($diff < 0) {
                    $text .= "  (أقل من المتوسط بـ " . number_format(abs($diff), 2) . " {$ind['unit']} - " . number_format(abs($percent), 1) . "%)\n";
                } else {
                    $text .= "  (مساوي للمتوسط)\n";
                }
            }
        }
    }
    
    return $text;
}

/**
 * استدعاء Gemini AI للحصول على توصيات لكلية واحدة
 */
function get_faculty_ai_recommendations($data_text, $faculty_name) {
    $api_key = GEMINI_API_KEY;
    
    if (empty($api_key) || $api_key === 'YOUR_GEMINI_API_KEY_HERE') {
        return [
            'success' => false,
            'error' => 'Gemini API Key غير معرّف. يرجى إضافة المفتاح في ملف CONFIG.php'
        ];
    }
    
    $prompt = "أنت خبير في الاستدامة البيئية وإدارة الموارد في الجامعات. قم بتحليل البيانات التالية لـ {$faculty_name} وقدم توصيات عملية ومحددة لتحسين الأداء البيئي والاستدامة لهذه الكلية.\n\n";
    $prompt .= "البيانات:\n" . $data_text . "\n\n";
    $prompt .= "يرجى تقديم:\n";
    $prompt .= "1. تحليل شامل لأداء الكلية الحالي\n";
    $prompt .= "2. نقاط القوة والضعف في الأداء\n";
    $prompt .= "3. توصيات عملية محددة لكل مؤشر يحتاج تحسين\n";
    $prompt .= "4. أولويات التحسين حسب الأهمية\n";
    $prompt .= "5. خطوات تنفيذية قابلة للتطبيق فوراً\n";
    $prompt .= "6. أهداف قابلة للقياس للشهر القادم\n\n";
    $prompt .= "أجب بالعربية بشكل واضح ومنظم ومباشر.";
    
    // بناء URL الصحيح
    $model_name = defined('GEMINI_MODEL_NAME') ? GEMINI_MODEL_NAME : 'gemini-1.5-flash';
    $base_url = defined('GEMINI_API_BASE_URL') ? GEMINI_API_BASE_URL : 'https://generativelanguage.googleapis.com/v1beta';
    $url = $base_url . '/models/' . $model_name . ':generateContent?key=' . $api_key;
    
    $payload = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => $prompt
                    ]
                ]
            ]
        ]
    ];
    
    // التحقق من وجود cURL
    if (!function_exists('curl_init')) {
        return [
            'success' => false,
            'error' => 'cURL غير مفعّل في PHP. يرجى تفعيله من إعدادات PHP.'
        ];
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120); // مهلة 120 ثانية
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($curl_error) {
        return [
            'success' => false,
            'error' => 'خطأ في الاتصال: ' . $curl_error
        ];
    }
    
    if ($http_code !== 200) {
        $error_details = '';
        if ($response) {
            $error_data = json_decode($response, true);
            if (isset($error_data['error'])) {
                $error_details = ': ' . ($error_data['error']['message'] ?? json_encode($error_data['error']));
            } else {
                $error_details = ': ' . substr($response, 0, 200);
            }
        }
        return [
            'success' => false,
            'error' => 'خطأ في الاتصال بـ Gemini API. كود الخطأ: ' . $http_code . $error_details
        ];
    }
    
    $result = json_decode($response, true);
    
    if (!$result) {
        return [
            'success' => false,
            'error' => 'خطأ في تحليل الاستجابة من Gemini API. الاستجابة: ' . substr($response, 0, 200)
        ];
    }
    
    if (isset($result['error'])) {
        $error_msg = $result['error']['message'] ?? 'خطأ غير معروف من Gemini API';
        if (isset($result['error']['code'])) {
            $error_msg .= ' (كود: ' . $result['error']['code'] . ')';
        }
        return [
            'success' => false,
            'error' => $error_msg
        ];
    }
    
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        return [
            'success' => true,
            'recommendations' => $result['candidates'][0]['content']['parts'][0]['text']
        ];
    }
    
    // في حالة عدم وجود نص، إرجاع معلومات الاستجابة للمساعدة في التشخيص
    return [
        'success' => false,
        'error' => 'لم يتم الحصول على استجابة صحيحة من Gemini AI. الاستجابة: ' . json_encode($result, JSON_UNESCAPED_UNICODE)
    ];
}

/**
 * حفظ التوصيات لكلية
 */
function save_faculty_recommendations($conn, $faculty_id, $month, $year, $recommendations) {
    // إنشاء جدول إذا لم يكن موجوداً
    $create_table_sql = "
        CREATE TABLE IF NOT EXISTS ai_recommendations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            faculty_id INT DEFAULT NULL,
            month INT NOT NULL,
            year INT NOT NULL,
            recommendations TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_faculty_month_year (faculty_id, month, year)
        )
    ";
    mysqli_query($conn, $create_table_sql);
    
    // حذف التوصيات القديمة
    if ($faculty_id) {
        $delete_sql = "DELETE FROM ai_recommendations WHERE faculty_id = $faculty_id AND month = $month AND year = $year";
    } else {
        $delete_sql = "DELETE FROM ai_recommendations WHERE faculty_id IS NULL AND month = $month AND year = $year";
    }
    mysqli_query($conn, $delete_sql);
    
    // إدخال التوصيات الجديدة
    $recommendations_escaped = mysqli_real_escape_string($conn, $recommendations);
    if ($faculty_id) {
        $insert_sql = "INSERT INTO ai_recommendations (faculty_id, month, year, recommendations) 
                       VALUES ($faculty_id, $month, $year, '$recommendations_escaped')";
    } else {
        $insert_sql = "INSERT INTO ai_recommendations (faculty_id, month, year, recommendations) 
                       VALUES (NULL, $month, $year, '$recommendations_escaped')";
    }
    
    return mysqli_query($conn, $insert_sql);
}

/**
 * جلب التوصيات المحفوظة
 */
function get_saved_recommendations($conn, $month, $year, $faculty_id = null) {
    if ($faculty_id) {
        $sql = "SELECT recommendations, created_at FROM ai_recommendations 
                WHERE faculty_id = $faculty_id AND month = $month AND year = $year 
                ORDER BY created_at DESC LIMIT 1";
    } else {
        $sql = "SELECT recommendations, created_at FROM ai_recommendations 
                WHERE faculty_id IS NULL AND month = $month AND year = $year 
                ORDER BY created_at DESC LIMIT 1";
    }
    
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

/**
 * توليد التوصيات بناءً على المعايير
 */
function generate_standards_based_recommendations($conn, $faculty_id, $month, $year) {
    require_once 'evaluation_standards.php';
    
    $faculty_data = get_faculty_data($conn, $faculty_id, $month, $year);
    if (!$faculty_data['has_data']) {
        return '';
    }
    
    $is_lab = is_laboratory_faculty($faculty_data['faculty_name']);
    $recommendations = [];
    $indicators_need_improvement = [];
    
    // جلب قيمة الورق المستخدم لحساب نسبة التدوير
    $paper_used_value = null;
    foreach ($faculty_data['indicators'] as $ind) {
        if (stripos($ind['name'], 'الورق المستخدم') !== false || stripos($ind['name'], 'الورق المستهلك') !== false) {
            $paper_used_value = $ind['value'];
            break;
        }
    }
    
    // تقييم كل مؤشر
    foreach ($faculty_data['indicators'] as $indicator) {
        $indicator_id = null;
        $indicator_sql = "SELECT id FROM indicators WHERE name = '" . mysqli_real_escape_string($conn, $indicator['name']) . "' LIMIT 1";
        $indicator_result = mysqli_query($conn, $indicator_sql);
        if ($indicator_result && mysqli_num_rows($indicator_result) > 0) {
            $indicator_row = mysqli_fetch_assoc($indicator_result);
            $indicator_id = intval($indicator_row['id']);
        }
        
        if (!$indicator_id || $indicator['value'] <= 0) continue;
        
        $related_value = ($indicator_id == 4 && $paper_used_value > 0) ? $paper_used_value : null;
        $evaluation = evaluate_indicator($indicator['value'], $indicator_id, $is_lab, $related_value);
        $standard = get_standard_text($indicator_id, $is_lab);
        
        if ($evaluation['status'] !== 'excellent') {
            $indicators_need_improvement[] = [
                'name' => $indicator['name'],
                'value' => $indicator['value'],
                'unit' => $indicator['unit'],
                'status' => $evaluation['status'],
                'message' => $evaluation['message'],
                'standard' => $standard,
                'indicator_id' => $indicator_id
            ];
        }
    }
    
    if (empty($indicators_need_improvement)) {
        return "🎉 **تهانينا!** جميع مؤشرات الكلية ضمن المعايير المطلوبة.\n\n";
    }
    
    $text = "## 📊 توصيات بناءً على المعايير\n\n";
    $text .= "الكلية: **{$faculty_data['faculty_name']}**\n";
    $text .= "الشهر: **{$month}/{$year}**\n\n";
    
    foreach ($indicators_need_improvement as $ind) {
        $text .= "### {$ind['name']}\n";
        $text .= "- **القيمة الحالية:** {$ind['value']} {$ind['unit']}\n";
        $text .= "- **المعيار المطلوب:** {$ind['standard']}\n";
        $text .= "- **التقييم:** {$ind['message']}\n";
        
        // إضافة توصيات محددة حسب المؤشر
        $specific_recommendations = get_specific_recommendations($ind['indicator_id'], $ind['value'], $ind['standard'], $is_lab);
        if ($specific_recommendations) {
            $text .= "- **التوصيات:**\n";
            foreach ($specific_recommendations as $rec) {
                $text .= "  • {$rec}\n";
            }
        }
        $text .= "\n";
    }
    
    return $text;
}

/**
 * الحصول على توصيات محددة لكل مؤشر
 */
function get_specific_recommendations($indicator_id, $current_value, $standard, $is_laboratory) {
    $recommendations = [];
    
    switch ($indicator_id) {
        case 1: // استهلاك المياه
            if ($current_value > ($is_laboratory ? 1000 : 400)) {
                $recommendations[] = "تركيب محولات مياه موفرة للطاقة في جميع الحمامات";
                $recommendations[] = "إصلاح أي تسريبات في شبكة المياه";
                $recommendations[] = "توعية الطلاب والموظفين بترشيد استهلاك المياه";
            } elseif ($current_value < ($is_laboratory ? 500 : 150)) {
                $recommendations[] = "التحقق من دقة قراءات العدادات";
                $recommendations[] = "ضمان توفر المياه الكافية للأنشطة التعليمية";
            }
            break;
            
        case 2: // استهلاك الكهرباء
            if ($current_value > ($is_laboratory ? 30000 : 15000)) {
                $recommendations[] = "استبدال المصابيح التقليدية بمصابيح LED موفرة للطاقة";
                $recommendations[] = "تركيب أجهزة استشعار الحركة لإطفاء الأنوار تلقائياً";
                $recommendations[] = "إيقاف تشغيل الأجهزة غير المستخدمة";
            } elseif ($current_value < ($is_laboratory ? 20000 : 5000)) {
                $recommendations[] = "التحقق من دقة قراءات العدادات";
            }
            break;
            
        case 3: // كمية الورق المستخدم
            if ($current_value > ($is_laboratory ? 150 : 100)) {
                $recommendations[] = "التحول التدريجي نحو الرقمنة الكاملة";
                $recommendations[] = "استخدام الطباعة على الوجهين بشكل إلزامي";
                $recommendations[] = "تقليل عدد النسخ المطبوعة من المستندات";
            }
            break;
            
        case 4: // كمية الورق المعاد تدويره
            $target_percentage = $is_laboratory ? 40 : 50;
            $recommendations[] = "وضع صناديق إعادة التدوير في جميع المكاتب والفصول";
            $recommendations[] = "تنظيم حملات توعوية حول أهمية إعادة التدوير";
            $recommendations[] = "التعاون مع شركات إعادة التدوير المحلية";
            break;
            
        case 5: // كمية النفايات
            if ($current_value > ($is_laboratory ? 1000 : 500)) {
                $recommendations[] = "تنفيذ برنامج فصل النفايات من المصدر";
                $recommendations[] = "تقليل استخدام المواد البلاستيكية ذات الاستخدام الواحد";
                $recommendations[] = "تشجيع استخدام الأكواب والأطباق القابلة لإعادة الاستخدام";
            }
            break;
            
        case 6: // عدد الأشجار المزروعة
            if ($current_value < 2) {
                $recommendations[] = "تنظيم حملة زراعة أشجار في الحرم الجامعي";
                $recommendations[] = "التعاون مع قسم البستنة لاختيار أنواع مناسبة من الأشجار";
                $recommendations[] = "إشراك الطلاب في أنشطة الزراعة كجزء من التوعية البيئية";
            }
            break;
            
        case 7: // عدد المتطوعين
            $target = $is_laboratory ? 15 : 10;
            if ($current_value < $target) {
                $recommendations[] = "تنظيم فعاليات تطوعية جذابة للطلاب";
                $recommendations[] = "تقديم شهادات تقدير للمتطوعين";
                $recommendations[] = "التعاون مع النوادي الطلابية لزيادة المشاركة";
            }
            break;
            
        case 8: // عدد ساعات التطوع
            $target = $is_laboratory ? 100 : 60;
            if ($current_value < $target) {
                $recommendations[] = "تنظيم برامج تطوعية منتظمة (أسبوعية أو شهرية)";
                $recommendations[] = "تسجيل ساعات التطوع بشكل دقيق";
                $recommendations[] = "ربط التطوع ببرامج التوعية البيئية";
            }
            break;
            
        case 9: // عدد الفعاليات التوعوية
            $target = $is_laboratory ? 2 : 1;
            if ($current_value < $target) {
                $recommendations[] = "تنظيم ورش عمل شهرية حول الاستدامة";
                $recommendations[] = "استضافة محاضرات من خبراء في البيئة";
                $recommendations[] = "تنظيم معارض بيئية داخل الكلية";
            }
            break;
            
        case 10: // درجة الالتزام البيئي
            $target = $is_laboratory ? 85 : 80;
            if ($current_value < $target) {
                $recommendations[] = "تنفيذ برامج توعية مكثفة للطلاب";
                $recommendations[] = "إدراج موضوعات الاستدامة في المناهج الدراسية";
                $recommendations[] = "تنظيم مسابقات بيئية لتحفيز الطلاب";
            }
            break;
    }
    
    return $recommendations;
}
?>

