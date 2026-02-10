<?php
// clean_install.php - تثبيت نظيف مع كليات جامعة اليرموك الحقيقية
session_start();

// إعدادات الاتصال
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); 
define('DB_PASS', '');
define('DB_NAME', 'beyondyou');

// إنشاء الاتصال
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS);
if (!$conn) {
    die('فشل الاتصال: ' . mysqli_connect_error());
}

// إنشاء الداتابيز إذا ما موجودة
if (!mysqli_select_db($conn, DB_NAME)) {
    $create_db = "CREATE DATABASE " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if (mysqli_query($conn, $create_db)) {
        echo "✅ تم إنشاء قاعدة البيانات<br>";
    } else {
        die("❌ فشل إنشاء قاعدة البيانات: " . mysqli_error($conn));
    }
}

mysqli_select_db($conn, DB_NAME);
mysqli_set_charset($conn, 'utf8mb4');

// الجداول
$tables = [
    "DROP TABLE IF EXISTS reports, records, users, faculties, indicators",
    
    "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin','dean') NOT NULL DEFAULT 'dean',
        faculty_id INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE faculties (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE indicators (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        unit VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        faculty_id INT NOT NULL,
        indicator_id INT NOT NULL,
        value DECIMAL(10,2) NOT NULL,
        month INT NOT NULL,
        year INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        faculty_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        note TEXT,
        file_path VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($tables as $sql) {
    if (!mysqli_query($conn, $sql)) {
        echo "❌ خطأ في: " . mysqli_error($conn) . "<br>";
    }
}

// إدخال البيانات
// كليات جامعة اليرموك الحقيقية
$yarmouk_faculties = [
    'كلية الطب',
    'كلية الصيدلة', 
    'كلية العلوم',
    'كلية الحجاوي للهندسة التكنولوجية',
    'كلية تكنولوجيا المعلومات وعلوم الحاسوب',
    'كلية الآداب',
    'كلية الاقتصاد والعلوم الإدارية',
    'كلية الشريعة والدراسات الإسلامية',
    'كلية العلوم التربوية',
    'كلية القانون',
    'كلية الإعلام',
    'كلية الآثار والأنثروبولوجيا',
    'كلية التربية البدنية وعلوم الرياضة',
    'كلية السياحة والفنادق',
    'كلية الفنون الجميلة',
    'كلية التمريض'
];

foreach ($yarmouk_faculties as $faculty) {
    $name = mysqli_real_escape_string($conn, $faculty);
    mysqli_query($conn, "INSERT INTO faculties (name) VALUES ('$name')");
}

// المؤشرات
$indicators = [
    ['استهلاك المياه', 'م³'],
    ['استهلاك الكهرباء', 'كيلوواط/ساعة'],
    ['كمية الورق المستهلك', 'ريمة'],
    ['كمية الورق المعاد تدويره', 'كغم'],
    ['كمية النفايات المعاد تدويرها', 'كغم'],
    ['عدد الأشجار المزروعة', 'شجرة'],
    ['عدد المتطوعين', 'متطوع'],
    ['عدد ساعات التطوع', 'ساعة'],
    ['عدد الفعاليات التوعوية', 'فعالية'],
    ['درجة الالتزام البيئي للطلبة', 'نقطة']
];

foreach ($indicators as $indicator) {
    $name = mysqli_real_escape_string($conn, $indicator[0]);
    $unit = mysqli_real_escape_string($conn, $indicator[1]);
    mysqli_query($conn, "INSERT INTO indicators (name, unit) VALUES ('$name', '$unit')");
}

// المستخدمين
$admin_pass = password_hash('Admin123@', PASSWORD_DEFAULT);
mysqli_query($conn, "INSERT INTO users (username, password, role) VALUES ('admin', '$admin_pass', 'admin')");

$dean_pass = password_hash('Dean123@', PASSWORD_DEFAULT);
// إضافة عمداء لبعض الكليات
mysqli_query($conn, "INSERT INTO users (username, password, role, faculty_id) VALUES 
    ('dean_medicine', '$dean_pass', 'dean', 1),
    ('dean_pharmacy', '$dean_pass', 'dean', 2),
    ('dean_science', '$dean_pass', 'dean', 3),
    ('dean_engineering', '$dean_pass', 'dean', 4),
    ('dean_it', '$dean_pass', 'dean', 5),
    ('dean_arts', '$dean_pass', 'dean', 6),
    ('dean_business', '$dean_pass', 'dean', 7)
");

echo "🎉 تم التثبيت بنجاح!<br><br>";
echo "🏫 <strong>كليات جامعة اليرموك (16 كلية):</strong><br>";
foreach ($yarmouk_faculties as $index => $faculty) {
    echo ($index + 1) . ". " . $faculty . "<br>";
}

echo "<br>👤 <strong>بيانات الدخول:</strong><br>";
echo "المدير العام: <strong>admin / Admin123@</strong><br>";
echo "عميد الطب: <strong>dean_medicine / Dean123@</strong><br>";
echo "عميد الصيدلة: <strong>dean_pharmacy / Dean123@</strong><br>";
echo "عميد العلوم: <strong>dean_science / Dean123@</strong><br>";
echo "عميد الهندسة: <strong>dean_engineering / Dean123@</strong><br>";
echo "عميد تكنولوجيا المعلومات: <strong>dean_it / Dean123@</strong><br>";

echo "<br>➡️ <a href='login.php' style='color: #009879; font-weight: bold; text-decoration: none; font-size: 18px;'>🎯 اذهب لتسجيل الدخول الآن</a>";

mysqli_close($conn);
?>