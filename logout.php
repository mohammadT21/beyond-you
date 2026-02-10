<?php
// logout.php - تسجيل الخروج
require_once 'config.php';

// حفظ نوع المستخدم قبل الإنهاء
$user_role = $_SESSION['user_role'] ?? '';

// إنهاء كل بيانات الجلسة
$_SESSION = [];
session_unset();
session_destroy();

// توجيه المستخدم لصفحة الدخول المناسبة
if ($user_role === 'admin') {
    header('Location: login.php?message=logged_out');
} else {
    header('Location: login.php?message=logged_out');
}
exit;
?>