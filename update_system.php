<?php
// update_system.php - تحديث شامل للنظام
require_once 'config.php';

$conn = db_connect();

// 1. تحديث الكليات
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

// حذف الكليات القديمة
mysqli_query($conn, "DELETE FROM faculties");

// إضافة الكليات الجديدة
foreach ($yarmouk_faculties as $faculty) {
    $name = mysqli_real_escape_string($conn, $faculty);
    mysqli_query($conn, "INSERT INTO faculties (name) VALUES ('$name')");
}

// 2. تحديث حسابات العمداء
$dean_pass = password_hash('Dean123@', PASSWORD_DEFAULT);
$deans = [
    'dean_medicine' => 1,
    'dean_pharmacy' => 2, 
    'dean_science' => 3,
    'dean_engineering' => 4,
    'dean_it' => 5,
    'dean_arts' => 6,
    'dean_business' => 7,
    'dean_sharia' => 8,
    'dean_education' => 9,
    'dean_law' => 10,
    'dean_media' => 11,
    'dean_archaeology' => 12,
    'dean_sports' => 13,
    'dean_tourism' => 14,
    'dean_arts_fine' => 15,
    'dean_nursing' => 16
];

// حذف العمداء القدامى
mysqli_query($conn, "DELETE FROM users WHERE role = 'dean'");

// إضافة العمداء الجدد
foreach ($deans as $username => $faculty_id) {
    mysqli_query($conn, "INSERT INTO users (username, password, role, faculty_id) VALUES 
        ('$username', '$dean_pass', 'dean', $faculty_id)");
}

echo "✅ تم التحديث الشامل للنظام!<br><br>";
echo "🏫 <strong>كليات جامعة اليرموك (16 كلية):</strong><br>";
foreach ($yarmouk_faculties as $index => $faculty) {
    echo ($index + 1) . ". " . $faculty . "<br>";
}

echo "<br>👤 <strong>بيانات الدخول الجديدة:</strong><br>";
echo "المدير العام: <strong>admin / Admin123@</strong><br>";
foreach ($deans as $username => $faculty_id) {
    $faculty_name = $yarmouk_faculties[$faculty_id - 1];
    echo "$username / Dean123@ - $faculty_name<br>";
}

db_close($conn);
?>