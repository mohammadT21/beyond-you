<?php
// deans_list.php - صفحة عرض العمداء المسجلين وغير المسجلين
require_once 'config.php';

// تحقق من جلسة المدير
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$conn = db_connect();

// معالجة حذف العميد
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_dean'])) {
    $dean_id = intval($_POST['dean_id'] ?? 0);
    
    if ($dean_id > 0) {
        // التحقق من أن المستخدم هو عميد وليس admin
        $check_sql = "SELECT id, username, faculty_id FROM users WHERE id = ? AND role = 'dean'";
        $stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt, 'i', $dean_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $dean = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($dean) {
            // حذف العميد
            $delete_sql = "DELETE FROM users WHERE id = ? AND role = 'dean'";
            $stmt2 = mysqli_prepare($conn, $delete_sql);
            mysqli_stmt_bind_param($stmt2, 'i', $dean_id);
            
            if (mysqli_stmt_execute($stmt2)) {
                $message = 'تم حذف العميد "' . htmlspecialchars($dean['username']) . '" بنجاح. يمكنك الآن تسجيل عميد جديد لهذه الكلية.';
                $message_type = 'success';
                // إعادة توجيه لتحديث الصفحة بعد الحذف
                header('Location: deans_list.php?deleted=1&username=' . urlencode($dean['username']));
                exit;
            } else {
                $message = 'حدث خطأ أثناء حذف العميد. حاول مرة أخرى.';
                $message_type = 'error';
            }
            mysqli_stmt_close($stmt2);
        } else {
            $message = 'العميد المحدد غير موجود.';
            $message_type = 'error';
        }
    } else {
        $message = 'معرف العميد غير صحيح.';
        $message_type = 'error';
    }
}

// عرض رسالة النجاح إذا تم الحذف بنجاح
if (isset($_GET['deleted']) && $_GET['deleted'] == '1' && isset($_GET['username'])) {
    $message = 'تم حذف العميد "' . htmlspecialchars($_GET['username']) . '" بنجاح. يمكنك الآن تسجيل عميد جديد لهذه الكلية.';
    $message_type = 'success';
}

// جلب جميع العمداء المسجلين مع أسماء كلياتهم
$registered_sql = "SELECT 
    u.id as dean_id,
    u.username,
    u.created_at as registration_date,
    f.id as faculty_id,
    f.name as faculty_name
FROM users u
INNER JOIN faculties f ON u.faculty_id = f.id
WHERE u.role = 'dean'
ORDER BY f.name ASC";

$registered_result = mysqli_query($conn, $registered_sql);
$registered_deans = [];
while ($row = mysqli_fetch_assoc($registered_result)) {
    $registered_deans[] = $row;
}

// جلب الكليات بدون عمداء
$unregistered_sql = "SELECT 
    f.id,
    f.name
FROM faculties f
LEFT JOIN users u ON f.id = u.faculty_id AND u.role = 'dean'
WHERE u.id IS NULL
ORDER BY f.name ASC";

$unregistered_result = mysqli_query($conn, $unregistered_sql);
$unregistered_faculties = [];
while ($row = mysqli_fetch_assoc($unregistered_result)) {
    $unregistered_faculties[] = $row;
}

// إحصائيات
$total_faculties = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM faculties"))['count'];
$total_registered = count($registered_deans);
$total_unregistered = count($unregistered_faculties);

db_close($conn);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة العمداء - نظام Beyond You</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background: #0a0e27;
            background-image: 
                radial-gradient(at 0% 0%, rgba(0, 152, 121, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(99, 102, 241, 0.15) 0px, transparent 50%);
            color: #e2e8f0;
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="100" height="100" patternUnits="userSpaceOnUse"><path d="M 100 0 L 0 0 0 100" fill="none" stroke="rgba(148,163,184,0.05)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.5;
            pointer-events: none;
            z-index: 0;
        }

        /* Sidebar Styles - نفس الستايل من dashboard_admin.php */
        .sidebar {
            position: fixed;
            right: 0;
            top: 0;
            height: 100vh;
            width: 280px;
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            border-left: 1px solid rgba(148, 163, 184, 0.1);
            display: flex;
            flex-direction: column;
            z-index: 1000;
            transition: width 0.3s ease, transform 0.3s ease;
            overflow: hidden;
        }

        .sidebar.collapsed {
            width: 80px;
        }

        .sidebar-header {
            padding: 35px 25px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sidebar-logo h1 {
            font-size: 1.6rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #00d9a5;
        }

        .sidebar-toggle {
            background: rgba(0, 152, 121, 0.2);
            border: 1px solid rgba(0, 152, 121, 0.3);
            color: #00d9a5;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .sidebar-user {
            padding: 25px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .sidebar-user-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #00d9a5, #6366f1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .sidebar-user-name {
            font-weight: 700;
            font-size: 1rem;
        }

        .sidebar-user-role {
            font-size: 0.8rem;
            color: #94a3b8;
        }

        .sidebar-nav {
            flex: 1;
            padding: 20px 0;
            overflow-y: auto;
        }

        .nav-section {
            margin-bottom: 30px;
        }

        .nav-section-title {
            padding: 0 25px 15px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 25px;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-item:hover {
            background: rgba(0, 217, 165, 0.1);
            color: #00d9a5;
        }

        .nav-item.active {
            background: linear-gradient(90deg, rgba(0, 217, 165, 0.15), rgba(0, 217, 165, 0.05));
            color: #00d9a5;
        }

        .nav-item i {
            font-size: 1.2rem;
            width: 24px;
        }

        .sidebar-footer {
            padding: 25px;
            border-top: 1px solid rgba(148, 163, 184, 0.1);
        }

        .sidebar-logout {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 14px 20px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 12px;
            color: #fca5a5;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .sidebar-logout:hover {
            background: rgba(239, 68, 68, 0.25);
            border-color: rgba(239, 68, 68, 0.5);
        }

        .main-content {
            margin-right: 280px;
            min-height: 100vh;
            transition: margin-right 0.3s ease;
        }

        .sidebar.collapsed ~ .main-content {
            margin-right: 80px;
        }

        .topbar {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            padding: 20px 30px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #e2e8f0;
        }

        .mobile-menu-toggle {
            display: none;
            background: rgba(0, 217, 165, 0.1);
            border: 1px solid rgba(0, 217, 165, 0.2);
            color: #00d9a5;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
        }

        .content-area {
            padding: 30px;
        }

        .page-header {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            margin-bottom: 30px;
            border: 1px solid rgba(148, 163, 184, 0.1);
        }

        .page-header h2 {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #00d9a5 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .stat-card {
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 16px;
            text-align: center;
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-top: 4px solid #00d9a5;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #00d9a5, #6366f1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #94a3b8;
            font-weight: 600;
        }

        .section {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 24px;
            margin-bottom: 30px;
            border: 1px solid rgba(148, 163, 184, 0.1);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 25px;
            color: #00d9a5;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .dean-item {
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 15px;
            border-right: 4px solid #00d9a5;
            transition: all 0.3s ease;
        }

        .dean-item:hover {
            transform: translateX(-5px);
            box-shadow: 0 10px 30px rgba(0, 217, 165, 0.2);
        }

        .dean-item.no-dean {
            border-right-color: #fbbf24;
        }

        .faculty-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 12px;
            color: #e2e8f0;
        }

        .dean-info {
            color: #94a3b8;
            font-size: 0.95rem;
            line-height: 1.8;
        }

        .dean-info i {
            margin-left: 8px;
            color: #00d9a5;
        }

        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 12px;
        }

        .badge.registered {
            background: rgba(0, 217, 165, 0.15);
            color: #00d9a5;
            border: 1px solid rgba(0, 217, 165, 0.3);
        }

        .badge.unregistered {
            background: rgba(251, 191, 36, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(251, 191, 36, 0.3);
        }

        .register-btn {
            display: inline-block;
            margin-top: 12px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #00d9a5 0%, #009879 100%);
            color: white;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 217, 165, 0.4);
        }

        .dean-actions {
            display: flex;
            gap: 12px;
            margin-top: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .delete-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            cursor: pointer;
            font-family: 'Tajawal', sans-serif;
        }

        .delete-btn:hover {
            background: rgba(239, 68, 68, 0.25);
            border-color: rgba(239, 68, 68, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
        }

        .message {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.success {
            background: rgba(0, 217, 165, 0.15);
            color: #00d9a5;
            border: 1px solid rgba(0, 217, 165, 0.3);
        }

        .message.error {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(100%);
                width: 280px;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-right: 0;
            }

            .mobile-menu-toggle {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <h1>
                    <i class="fa-solid fa-seedling"></i>
                    <span>Beyond You</span>
                </h1>
            </div>
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="طي/فتح القائمة">
                <i class="fa-solid fa-angle-right"></i>
            </button>
        </div>

        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <i class="fa-solid fa-user-tie"></i>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                <div class="sidebar-user-role">المدير العام</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">القائمة الرئيسية</div>
                <a href="dashboard_admin.php" class="nav-item">
                    <i class="fa-solid fa-house"></i>
                    <span>الرئيسية</span>
                </a>
                <a href="sustainability_indicators.php" class="nav-item">
                    <i class="fa-solid fa-chart-area"></i>
                    <span>مؤشرات الاستدامة</span>
                </a>
                <a href="faculties.php" class="nav-item">
                    <i class="fa-solid fa-building-columns"></i>
                    <span>إدارة الكليات</span>
                </a>
                <a href="reports_admin.php" class="nav-item">
                    <i class="fa-solid fa-inbox"></i>
                    <span>التقارير الواردة</span>
                </a>
                <a href="best_faculties.php" class="nav-item">
                    <i class="fa-solid fa-trophy"></i>
                    <span>الكليات المتميزة</span>
                </a>
                <a href="ai_recommendations.php" class="nav-item">
                    <i class="fa-solid fa-robot"></i>
                    <span>توصيات الذكاء الاصطناعي</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">الإدارة</div>
                <a href="deans_list.php" class="nav-item active">
                    <i class="fa-solid fa-users"></i>
                    <span>قائمة العمداء</span>
                </a>
                <a href="create_dean.php" class="nav-item">
                    <i class="fa-solid fa-user-plus"></i>
                    <span>تسجيل عمداء جدد</span>
                </a>
                <a href="change_password.php" class="nav-item">
                    <i class="fa-solid fa-key"></i>
                    <span>تغيير كلمة المرور</span>
                </a>
                <a href="reset_system.php" class="nav-item">
                    <i class="fa-solid fa-broom"></i>
                    <span>تنظيف النظام</span>
                </a>
            </div>
        </nav>

        <div class="sidebar-footer">
            <a href="logout.php" class="sidebar-logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>تسجيل الخروج</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div style="display: flex; align-items: center; gap: 15px;">
                <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="قائمة">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h1 class="topbar-title">قائمة العمداء</h1>
            </div>
            <div class="topbar-actions">
                <div class="topbar-user">
                    <i class="fa-solid fa-user-tie" style="color: #009879;"></i>
                    <span class="topbar-user-name"><?= htmlspecialchars($_SESSION['username']) ?></span>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <?php if ($message): ?>
                <div class="message <?= $message_type ?>">
                    <i class="fa-solid <?= $message_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                    <span><?= $message ?></span>
                </div>
            <?php endif; ?>
            
            <div class="page-header">
                <h2><i class="fa-solid fa-users"></i> قائمة العمداء</h2>
                <p>عرض جميع العمداء المسجلين والكليات التي لا تملك عمداء</p>

                <!-- الإحصائيات -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?= $total_faculties ?></div>
                        <div class="stat-label">إجمالي الكليات</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $total_registered ?></div>
                        <div class="stat-label">عمداء مسجلين</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $total_unregistered ?></div>
                        <div class="stat-label">كليات بدون عمداء</div>
                    </div>
                </div>
            </div>

            <!-- العمداء المسجلين -->
            <div class="section">
                <h2 class="section-title">
                    <i class="fa-solid fa-check-circle"></i> العمداء المسجلين (<?= $total_registered ?>)
                </h2>
                <?php if (count($registered_deans) > 0): ?>
                    <?php foreach ($registered_deans as $dean): ?>
                        <div class="dean-item">
                            <div class="faculty-name"><?= htmlspecialchars($dean['faculty_name']) ?></div>
                            <div class="dean-info">
                                <div><i class="fa-solid fa-user"></i> اسم المستخدم: <strong><?= htmlspecialchars($dean['username']) ?></strong></div>
                                <div><i class="fa-solid fa-calendar"></i> تاريخ التسجيل: <?= date('Y-m-d H:i', strtotime($dean['registration_date'])) ?></div>
                                <div><i class="fa-solid fa-hashtag"></i> معرف العميد: <?= $dean['dean_id'] ?></div>
                            </div>
                            <div class="dean-actions">
                                <span class="badge registered">✓ مسجل</span>
                                <form method="POST" style="display: inline;" onsubmit="return confirmDelete('<?= htmlspecialchars($dean['username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($dean['faculty_name'], ENT_QUOTES) ?>');">
                                    <input type="hidden" name="dean_id" value="<?= $dean['dean_id'] ?>">
                                    <button type="submit" name="delete_dean" class="delete-btn">
                                        <i class="fa-solid fa-trash"></i>
                                        <span>حذف العميد</span>
                                    </button>
                                </form>
                                <a href="create_dean.php?faculty_id=<?= $dean['faculty_id'] ?>" class="register-btn">
                                    <i class="fa-solid fa-edit"></i> تعديل/إعادة تسجيل
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-user-slash"></i>
                        <p>لا يوجد عمداء مسجلين حالياً</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- الكليات بدون عمداء -->
            <div class="section">
                <h2 class="section-title">
                    <i class="fa-solid fa-exclamation-triangle"></i> الكليات بدون عمداء (<?= $total_unregistered ?>)
                </h2>
                <?php if (count($unregistered_faculties) > 0): ?>
                    <?php foreach ($unregistered_faculties as $faculty): ?>
                        <div class="dean-item no-dean">
                            <div class="faculty-name"><?= htmlspecialchars($faculty['name']) ?></div>
                            <div class="dean-info">
                                <div><i class="fa-solid fa-info-circle"></i> هذه الكلية لا تملك عميد مسجل حالياً</div>
                            </div>
                            <span class="badge unregistered">⚠ غير مسجل</span>
                            <a href="create_dean.php?faculty_id=<?= $faculty['id'] ?>" class="register-btn">
                                <i class="fa-solid fa-plus"></i> تسجيل عميد لهذه الكلية
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-check-circle"></i>
                        <p>جميع الكليات لديها عمداء مسجلين ✓</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Mobile Overlay -->
    <div class="mobile-overlay" id="mobileOverlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 999; opacity: 0; transition: opacity 0.3s ease;"></div>

    <script>
        // Sidebar Toggle
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mobileOverlay = document.getElementById('mobileOverlay');

        // Load sidebar state from localStorage
        const sidebarState = localStorage.getItem('sidebarCollapsed');
        if (sidebarState === 'true') {
            sidebar.classList.add('collapsed');
        }

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            });
        }

        // Mobile menu toggle
        function toggleMobileSidebar() {
            sidebar.classList.toggle('active');
            if (sidebar.classList.contains('active')) {
                mobileOverlay.style.display = 'block';
                setTimeout(() => mobileOverlay.style.opacity = '1', 10);
            } else {
                mobileOverlay.style.opacity = '0';
                setTimeout(() => mobileOverlay.style.display = 'none', 300);
            }
        }

        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', toggleMobileSidebar);
            mobileOverlay.addEventListener('click', toggleMobileSidebar);
        }

        // Close sidebar on window resize if it's desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                mobileOverlay.style.display = 'none';
                mobileOverlay.style.opacity = '0';
            }
        });

        // تأكيد حذف العميد
        function confirmDelete(username, facultyName) {
            return confirm(
                '⚠️ تحذير: هل أنت متأكد من حذف العميد؟\n\n' +
                'اسم المستخدم: ' + username + '\n' +
                'الكلية: ' + facultyName + '\n\n' +
                'سيتم حذف حساب العميد بشكل نهائي.\n' +
                'يمكنك بعد ذلك تسجيل عميد جديد لهذه الكلية.'
            );
        }

        // إخفاء رسالة النجاح/الخطأ بعد 5 ثوانٍ
        setTimeout(function() {
            const messages = document.querySelectorAll('.message');
            messages.forEach(function(msg) {
                msg.style.transition = 'opacity 0.5s ease-out';
                msg.style.opacity = '0';
                setTimeout(function() {
                    msg.remove();
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>

