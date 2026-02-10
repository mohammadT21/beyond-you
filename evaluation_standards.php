<?php
// evaluation_standards.php - معايير تقييم مؤشرات الاستدامة

/**
 * تحديد الكليات المختبرية
 */
function get_laboratory_faculties() {
    return [
        'كلية الطب',
        'كلية الصيدلة',
        'كلية العلوم',
        'كلية الحجاوي للهندسة التكنولوجية',
        'كلية تكنولوجيا المعلومات وعلوم الحاسوب'
    ];
}

/**
 * التحقق من كون الكلية مختبرية
 */
function is_laboratory_faculty($faculty_name) {
    $laboratory_faculties = get_laboratory_faculties();
    return in_array($faculty_name, $laboratory_faculties);
}

/**
 * الحصول على معايير المؤشر حسب نوع الكلية
 */
function get_indicator_standards($indicator_id, $is_laboratory) {
    $standards = [
        // استهلاك الكهرباء (ID: 2)
        2 => [
            'laboratory' => ['min' => 0, 'max' => 30000],
            'non_laboratory' => ['min' => 0, 'max' => 15000],
            'type' => 'range', // نطاق (أقل من الحد الأدنى = ✖، ضمن النطاق = ✔، أعلى من الحد الأعلى = ✖)
            'less_is_better' => false
        ],
        // استهلاك المياه (ID: 1)
        1 => [
            'laboratory' => ['min' => 0, 'max' => 1000],
            'non_laboratory' => ['min' => 0, 'max' => 400],
            'type' => 'range',
            'less_is_better' => false
        ],
        // كمية الورق المستخدم (ID: 3)
        3 => [
            'laboratory' => ['min' => 0, 'max' => 150],
            'non_laboratory' => ['min' => 0, 'max' => 100],
            'type' => 'range',
            'less_is_better' => true // أقل = أفضل
        ],
        // كمية الورق المعاد تدويره (ID: 4) - نسبة مئوية
        4 => [
            'laboratory' => ['min' => 40, 'max' => 100], // ≥ 40%
            'non_laboratory' => ['min' => 50, 'max' => 100], // ≥ 50%
            'type' => 'percentage', // نسبة مئوية من الورق المستخدم
            'less_is_better' => false
        ],
        // كمية النفايات المنتجة (ID: 5)
        5 => [
            'laboratory' => ['min' => 500, 'max' => 1000],
            'non_laboratory' => ['min' => 200, 'max' => 500],
            'type' => 'range',
            'less_is_better' => true // كلية نظيفة إذا أقل من الحد الأعلى
        ],
        // عدد الفعاليات التوعوية (ID: 9)
        9 => [
            'laboratory' => ['min' => 2, 'max' => null], // ≥ 2 فعالية شهرياً
            'non_laboratory' => ['min' => 1, 'max' => null], // ≥ 1 فعالية شهرياً
            'type' => 'minimum',
            'less_is_better' => false
        ],
        // عدد المتطوعين (ID: 7)
        7 => [
            'laboratory' => ['min' => 15, 'max' => null], // ≥ 15 متطوع شهرياً
            'non_laboratory' => ['min' => 10, 'max' => null], // ≥ 10 متطوعين شهرياً
            'type' => 'minimum',
            'less_is_better' => false
        ],
        // عدد ساعات التطوع (ID: 8)
        8 => [
            'laboratory' => ['min' => 100, 'max' => null], // ≥ 100 ساعة شهرياً
            'non_laboratory' => ['min' => 60, 'max' => null], // ≥ 60 ساعة شهرياً
            'type' => 'minimum',
            'less_is_better' => false
        ],
        // درجة الالتزام البيئي للطلبة (ID: 10)
        10 => [
            'laboratory' => ['min' => 85, 'max' => 100], // ≥ 85%
            'non_laboratory' => ['min' => 80, 'max' => 100], // ≥ 80%
            'type' => 'range',
            'less_is_better' => false
        ],
        // نسبة المبادرات النوعية (ID: 11 - إذا كان موجوداً)
        11 => [
            'laboratory' => ['min' => 30, 'max' => 100], // ≥ 30%
            'non_laboratory' => ['min' => 25, 'max' => 100], // ≥ 25%
            'type' => 'range',
            'less_is_better' => false
        ],
        // عدد الأشجار المزروعة (ID: 6)
        6 => [
            'laboratory' => ['min' => 2, 'max' => null], // ≥ 2 شجرة
            'non_laboratory' => ['min' => 2, 'max' => null], // ≥ 2 شجرة
            'type' => 'minimum',
            'less_is_better' => false
        ]
    ];
    
    if (!isset($standards[$indicator_id])) {
        return null;
    }
    
    $standard = $standards[$indicator_id];
    $result = $is_laboratory ? $standard['laboratory'] : $standard['non_laboratory'];
    
    // إضافة معلومات إضافية
    $result['type'] = $standard['type'];
    $result['less_is_better'] = $standard['less_is_better'] ?? false;
    
    return $result;
}

/**
 * الحصول على نوع التقييم للمؤشر
 */
function get_indicator_type($indicator_id) {
    $standards = [
        2 => 'range',
        1 => 'range',
        3 => 'range',
        4 => 'percentage',
        5 => 'range',
        6 => 'minimum',  // عدد الأشجار المزروعة
        9 => 'minimum',
        7 => 'minimum',
        8 => 'minimum',
        10 => 'range',
        11 => 'range'
    ];
    
    return $standards[$indicator_id] ?? 'range';
}

/**
 * التحقق من كون المؤشر "أقل = أفضل"
 */
function is_less_better($indicator_id) {
    $less_better = [
        3 => true,  // كمية الورق المستخدم
        5 => true   // كمية النفايات المنتجة
    ];
    
    return isset($less_better[$indicator_id]) && $less_better[$indicator_id];
}

/**
 * تقييم قيمة المؤشر بناءً على المعايير
 * 
 * @param float $value القيمة المدخلة
 * @param int $indicator_id معرف المؤشر
 * @param bool $is_laboratory هل الكلية مختبرية
 * @param float|null $related_value قيمة مرتبطة (مثل: الورق المستخدم لحساب نسبة التدوير)
 * @return array ['status' => 'excellent|good|warning|error', 'icon' => '✔|⚠|✖', 'message' => '...']
 */
function evaluate_indicator($value, $indicator_id, $is_laboratory, $related_value = null) {
    $standards = get_indicator_standards($indicator_id, $is_laboratory);
    
    if (!$standards) {
        return [
            'status' => 'unknown',
            'icon' => '❓',
            'message' => 'لا توجد معايير لهذا المؤشر'
        ];
    }
    
    $type = $standards['type'] ?? get_indicator_type($indicator_id);
    $less_is_better = $standards['less_is_better'] ?? is_less_better($indicator_id);
    
    // معالجة خاصية: نسبة الورق المعاد تدويره
    if ($indicator_id == 4 && $related_value !== null && $related_value > 0) {
        $value = ($value / $related_value) * 100; // تحويل إلى نسبة مئوية
    }
    
    $min = $standards['min'] ?? null;
    $max = $standards['max'] ?? null;
    
    // تقييم حسب النوع
    if ($type === 'minimum') {
        // مؤشرات الحد الأدنى (عدد الفعاليات، المتطوعين، ساعات التطوع)
        if ($value >= $min) {
            return [
                'status' => 'excellent',
                'icon' => '✔',
                'message' => 'ممتاز - ضمن المعيار'
            ];
        } else {
            return [
                'status' => 'error',
                'icon' => '✖',
                'message' => 'أقل من الحد الأدنى المطلوب'
            ];
        }
    } elseif ($type === 'percentage') {
        // مؤشرات النسبة المئوية
        if ($value >= $min) {
            return [
                'status' => 'excellent',
                'icon' => '✔',
                'message' => 'ممتاز - ضمن المعيار'
            ];
        } else {
            return [
                'status' => 'warning',
                'icon' => '⚠',
                'message' => 'منخفض - أقل من المعيار المطلوب'
            ];
        }
    } else {
        // مؤشرات النطاق (range)
        if ($min !== null && $max !== null) {
            // نطاق محدد
            if ($value >= $min && $value <= $max) {
                return [
                    'status' => 'excellent',
                    'icon' => '✔',
                    'message' => 'ممتاز - ضمن النطاق المثالي'
                ];
            } elseif ($value < $min) {
                if ($less_is_better) {
                    // للمؤشرات التي "أقل = أفضل" (مثل الورق والنفايات)
                    return [
                        'status' => 'excellent',
                        'icon' => '✔',
                        'message' => 'ممتاز - أقل من الحد الأدنى (أفضل)'
                    ];
                } else {
                    return [
                        'status' => 'error',
                        'icon' => '✖',
                        'message' => 'أقل من الحد الأدنى'
                    ];
                }
            } else {
                // $value > $max
                if ($less_is_better) {
                    return [
                        'status' => 'warning',
                        'icon' => '⚠',
                        'message' => 'مرتفع - أعلى من الحد الأعلى'
                    ];
                } else {
                    return [
                        'status' => 'error',
                        'icon' => '✖',
                        'message' => 'مرتفع - أعلى من الحد الأعلى'
                    ];
                }
            }
        } elseif ($min !== null) {
            // فقط حد أدنى
            if ($value >= $min) {
                return [
                    'status' => 'excellent',
                    'icon' => '✔',
                    'message' => 'ممتاز - ضمن المعيار'
                ];
            } else {
                return [
                    'status' => 'error',
                    'icon' => '✖',
                    'message' => 'أقل من الحد الأدنى'
                ];
            }
        }
    }
    
    return [
        'status' => 'unknown',
        'icon' => '❓',
        'message' => 'لا يمكن تقييم هذه القيمة'
    ];
}

/**
 * الحصول على نص المعيار للمؤشر
 */
function get_standard_text($indicator_id, $is_laboratory) {
    $standards = get_indicator_standards($indicator_id, $is_laboratory);
    if (!$standards) {
        return 'لا توجد معايير';
    }
    
    $min = $standards['min'] ?? null;
    $max = $standards['max'] ?? null;
    $type = get_indicator_type($indicator_id);
    
    if ($type === 'minimum') {
        return "≥ {$min}";
    } elseif ($type === 'percentage') {
        return "≥ {$min}%";
    } else {
        if ($min !== null && $max !== null) {
            return "{$min} - {$max}";
        } elseif ($min !== null) {
            return "≥ {$min}";
        }
    }
    
    return 'لا توجد معايير';
}

?>

