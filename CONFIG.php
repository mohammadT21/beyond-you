<?php
// config.php — إعدادات الاتصال بقاعدة البيانات
// بداية الجلسة يجب تكون في كل ملف

// إعدادات الجلسة أولاً
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400, // 24 ساعة
        'read_and_close'  => false,
    ]);
}

// معلومات الاتصال
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'beyondyou');

// إعدادات الملفات
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 ميجابايت

// إعدادات Gemini AI
// احصل على API Key من: https://aistudio.google.com/app/apikey
define('GEMINI_API_KEY', 'AIzaSyAjXC1ObDnWCBCCqM6byEFYW-Rbw_ev8j8');
// استخدام v1beta مع gemini-2.5-flash (النسخة السريعة - تم اختبارها وتأكيد عملها)
define('GEMINI_API_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta');
define('GEMINI_MODEL_NAME', 'gemini-2.5-flash');

// أنواع الملفات المسموحة
$allowed_file_types = [
    'application/pdf', 
    'application/msword', 
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
    'image/jpeg', 
    'image/png'
];

// دالة إنشاء الاتصال
function db_connect() {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if (!$conn) {
        die('فشل الاتصال بقاعدة البيانات: ' . mysqli_connect_error());
    }

    mysqli_set_charset($conn, 'utf8mb4');
    return $conn;
}

// دالة إغلاق الاتصال
function db_close($conn) {
    if ($conn) {
        mysqli_close($conn);
    }
}

// إنشاء مجلد الرفع إذا لم يكن موجوداً
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// دالة لتنظيف المدخلات
function clean_input($data, $conn = null) {
    if ($conn) {
        $data = mysqli_real_escape_string($conn, $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// دالة للتحقق من تسجيل الدخول
function check_admin_login() {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: login.php');
        exit;
    }
}

function check_dean_login() {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'dean') {
        header('Location: login.php');
        exit;
    }
}
?>