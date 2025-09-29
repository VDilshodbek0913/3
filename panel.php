<?php
session_start();
require_once 'api/config.php';

$db = new Database();
$pdo = $db->getConnection();

// Multi-language support for admin panel
$adminLang = $_SESSION['admin_lang'] ?? 'uz';
if (isset($_POST['change_lang'])) {
    $adminLang = $_POST['lang'];
    $_SESSION['admin_lang'] = $adminLang;
}

$adminTranslations = [
    'uz' => [
        'title' => 'Admin Panel - CodeBlog',
        'login' => 'Tizimga kirish',
        'email' => 'Email',
        'password' => 'Parol',
        'captcha' => 'Captcha',
        'refresh' => 'Yangilash',
        'enter' => 'Kirish',
        'darkMode' => 'Tungi rejim',
        'welcome' => 'Xush kelibsiz',
        'viewSite' => 'Saytni ko\'rish',
        'logout' => 'Chiqish',
        'posts' => 'Postlar',
        'users' => 'Foydalanuvchilar',
        'comments' => 'Izohlar',
        'likes' => 'Likelar',
        'newMessages' => 'Yangi xabarlar',
        'subscribers' => 'Obunachilar',
        'createPost' => 'Post yaratish',
        'managePosts' => 'Postlarni boshqarish',
        'messages' => 'Xabarlar',
        'newsletter' => 'Newsletter Obunachilar',
        'postTitle' => 'Post nomi',
        'image' => 'Rasm',
        'hashtags' => 'Hashtag\'lar (vergul bilan ajrating)',
        'content' => 'Post matni (HTML)',
        'create' => 'Post yaratish',
        'author' => 'Muallif',
        'statistics' => 'Statistika',
        'date' => 'Sana',
        'actions' => 'Amallar',
        'delete' => 'O\'chirish',
        'status' => 'Holat',
        'registered' => 'Ro\'yxatdan o\'tgan',
        'verified' => 'Tasdiqlangan',
        'unverified' => 'Tasdiqlanmagan',
        'active' => 'Faol',
        'inactive' => 'Nofaol',
        'total' => 'Jami',
        'noSubscribers' => 'Hozircha hech kim obuna bo\'lmagan',
        'subscriptionDate' => 'Obuna bo\'lgan sana'
    ],
    'ru' => [
        'title' => 'Админ панель - CodeBlog',
        'login' => 'Вход в систему',
        'email' => 'Email',
        'password' => 'Пароль',
        'captcha' => 'Капча',
        'refresh' => 'Обновить',
        'enter' => 'Войти',
        'darkMode' => 'Темная тема',
        'welcome' => 'Добро пожаловать',
        'viewSite' => 'Посмотреть сайт',
        'logout' => 'Выйти',
        'posts' => 'Посты',
        'users' => 'Пользователи',
        'comments' => 'Комментарии',
        'likes' => 'Лайки',
        'newMessages' => 'Новые сообщения',
        'subscribers' => 'Подписчики',
        'createPost' => 'Создать пост',
        'managePosts' => 'Управление постами',
        'messages' => 'Сообщения',
        'newsletter' => 'Подписчики рассылки',
        'postTitle' => 'Название поста',
        'image' => 'Изображение',
        'hashtags' => 'Хештеги (через запятую)',
        'content' => 'Текст поста (HTML)',
        'create' => 'Создать пост',
        'author' => 'Автор',
        'statistics' => 'Статистика',
        'date' => 'Дата',
        'actions' => 'Действия',
        'delete' => 'Удалить',
        'status' => 'Статус',
        'registered' => 'Зарегистрирован',
        'verified' => 'Подтвержден',
        'unverified' => 'Не подтвержден',
        'active' => 'Активен',
        'inactive' => 'Неактивен',
        'total' => 'Всего',
        'noSubscribers' => 'Пока никто не подписался',
        'subscriptionDate' => 'Дата подписки'
    ],
    'en' => [
        'title' => 'Admin Panel - CodeBlog',
        'login' => 'Login',
        'email' => 'Email',
        'password' => 'Password',
        'captcha' => 'Captcha',
        'refresh' => 'Refresh',
        'enter' => 'Enter',
        'darkMode' => 'Dark mode',
        'welcome' => 'Welcome',
        'viewSite' => 'View site',
        'logout' => 'Logout',
        'posts' => 'Posts',
        'users' => 'Users',
        'comments' => 'Comments',
        'likes' => 'Likes',
        'newMessages' => 'New messages',
        'subscribers' => 'Subscribers',
        'createPost' => 'Create post',
        'managePosts' => 'Manage posts',
        'messages' => 'Messages',
        'newsletter' => 'Newsletter Subscribers',
        'postTitle' => 'Post title',
        'image' => 'Image',
        'hashtags' => 'Hashtags (comma separated)',
        'content' => 'Post content (HTML)',
        'create' => 'Create post',
        'author' => 'Author',
        'statistics' => 'Statistics',
        'date' => 'Date',
        'actions' => 'Actions',
        'delete' => 'Delete',
        'status' => 'Status',
        'registered' => 'Registered',
        'verified' => 'Verified',
        'unverified' => 'Unverified',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'total' => 'Total',
        'noSubscribers' => 'No subscribers yet',
        'subscriptionDate' => 'Subscription date'
    ]
];

$t = $adminTranslations[$adminLang];

// Check if user is admin
$isAdmin = false;
$currentUser = null;

if (isset($_SESSION['admin_token'])) {
    $stmt = $pdo->prepare("SELECT u.* FROM users u 
                          JOIN user_sessions s ON u.id = s.user_id 
                          WHERE s.session_token = ? AND s.expires_at > NOW() AND u.is_admin = 1");
    $stmt->execute([$_SESSION['admin_token']]);
    $currentUser = $stmt->fetch();
    $isAdmin = $currentUser ? true : false;
}

// Handle admin login
if ($_POST['action'] ?? '' === 'admin_login') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $captcha = $_POST['captcha'];
    
    if (validateCaptcha($captcha, $_SESSION['captcha'] ?? '')) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_admin = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $sessionToken = generateSecureToken();
            $expiresAt = date('Y-m-d H:i:s', time() + 86400);
            
            $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $sessionToken, $expiresAt]);
            
            $_SESSION['admin_token'] = $sessionToken;
            header('Location: panel.php');
            exit;
        } else {
            $error = 'Email yoki parol noto\'g\'ri';
        }
    } else {
        $error = 'Captcha noto\'g\'ri';
    }
}

// Handle post creation
if ($_POST['action'] ?? '' === 'create_post' && $isAdmin) {
    $title = sanitizeInput($_POST['title']);
    $content = $_POST['content']; // HTML content
    $hashtags = sanitizeInput($_POST['hashtags']);
    $slug = createSlug($title);
    
    // Handle image upload
    $imagePath = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $imageExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName = $slug . '_' . time() . '.' . $imageExtension;
        $imagePath = $uploadDir . $imageName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
            $imagePath = $imageName; // Store only filename
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO posts (title, slug, content, image, hashtags, author_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $slug, $content, $imagePath, $hashtags, $currentUser['id']]);
    
    $success = 'Post muvaffaqiyatli yaratildi';
}

// Handle post deletion
if ($_POST['action'] ?? '' === 'delete_post' && $isAdmin) {
    $postId = (int)$_POST['post_id'];
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $success = 'Post o\'chirildi';
}

// Get statistics
$stats = [];
if ($isAdmin) {
    $stmt = $pdo->query("SELECT COUNT(*) as total_posts FROM posts");
    $stats['total_posts'] = $stmt->fetch()['total_posts'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE is_admin = 0");
    $stats['total_users'] = $stmt->fetch()['total_users'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_comments FROM comments");
    $stats['total_comments'] = $stmt->fetch()['total_comments'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_likes FROM likes");
    $stats['total_likes'] = $stmt->fetch()['total_likes'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as unread_contacts FROM contact_messages WHERE is_read = 0");
    $stats['unread_contacts'] = $stmt->fetch()['unread_contacts'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as newsletter_subscribers FROM newsletter_subscribers WHERE is_active = 1");
    $stats['newsletter_subscribers'] = $stmt->fetch()['newsletter_subscribers'];
}

// Get posts for management
$posts = [];
if ($isAdmin) {
    $stmt = $pdo->query("SELECT p.*, u.username, 
                        (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) as like_count,
                        (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) as comment_count
                        FROM posts p 
                        JOIN users u ON p.author_id = u.id 
                        ORDER BY p.created_at DESC LIMIT 20");
    $posts = $stmt->fetchAll();
}

// Get users
$users = [];
if ($isAdmin) {
    $stmt = $pdo->query("SELECT id, username, email, avatar, created_at, is_verified FROM users WHERE is_admin = 0 ORDER BY created_at DESC LIMIT 50");
    $users = $stmt->fetchAll();
}

// Get contact messages
$contacts = [];
if ($isAdmin) {
    $stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 50");
    $contacts = $stmt->fetchAll();
}

$newsletter_subscribers = [];
if ($isAdmin) {
    $stmt = $pdo->query("SELECT * FROM newsletter_subscribers ORDER BY created_at DESC LIMIT 100");
    $newsletter_subscribers = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="<?= $adminLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['title'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#0f172a',
                        secondary: '#64748b'
                    }
                }
            }
        }
    </script>
    <style>
        .animate-in {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
    <?php if (!$isAdmin): ?>
    <!-- Admin Login Form -->
    <div class="min-h-screen flex items-center justify-center px-4 gradient-bg">
        <div class="max-w-md w-full bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-xl shadow-2xl p-8 border border-white/20">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <span class="text-white font-bold text-xl">DB</span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Admin Panel</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2"><?= $t['login'] ?></p>
            </div>
            
            <!-- Language Selector -->
            <div class="flex justify-center mb-6">
                <form method="POST" class="flex space-x-2">
                    <input type="hidden" name="change_lang" value="1">
                    <button type="submit" name="lang" value="uz" class="px-3 py-1 text-xs rounded-full <?= $adminLang === 'uz' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600' ?> hover:bg-blue-100 hover:text-blue-800 transition-colors">🇺🇿 UZ</button>
                    <button type="submit" name="lang" value="ru" class="px-3 py-1 text-xs rounded-full <?= $adminLang === 'ru' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600' ?> hover:bg-blue-100 hover:text-blue-800 transition-colors">🇷🇺 RU</button>
                    <button type="submit" name="lang" value="en" class="px-3 py-1 text-xs rounded-full <?= $adminLang === 'en' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600' ?> hover:bg-blue-100 hover:text-blue-800 transition-colors">🇺🇸 EN</button>
                </form>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 animate-in">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <?= htmlspecialchars($error) ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="admin_login">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= $t['email'] ?></label>
                    <input type="email" name="email" required 
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= $t['password'] ?></label>
                    <input type="password" name="password" required 
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= $t['captcha'] ?></label>
                    <div class="flex items-center space-x-3">
                        <img src="/api/captcha.php?<?= time() ?>" alt="Captcha" class="border rounded-lg shadow-sm">
                        <input type="text" name="captcha" required 
                               class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                        <button type="button" onclick="refreshCaptcha()" 
                                class="px-4 py-3 bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-500 transition-colors">
                            🔄
                        </button>
                    </div>
                </div>
                
                <button type="submit" 
                        class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white py-3 px-4 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-lg hover:shadow-xl">
                    <?= $t['enter'] ?>
                </button>
            </form>
            
            <!-- Theme Toggle -->
            <div class="mt-6 text-center">
                <button onclick="toggleTheme()" 
                        class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    🌙 <?= $t['darkMode'] ?>
                </button>
            </div>
        </div>
    </div>
    
    <script>
        function refreshCaptcha() {
            document.querySelector('img[alt="Captcha"]').src = '/api/captcha.php?' + Date.now();
        }
        
        function toggleTheme() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
        }
        
        // Load saved theme
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>
    
    <?php else: ?>
    <!-- Admin Dashboard -->
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Header -->
        <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center shadow-lg">
                            <span class="text-white font-bold">DB</span>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Admin Panel</h1>
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                <?= $t['welcome'] ?>, <?= htmlspecialchars($currentUser['username']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Language Selector -->
                        <form method="POST" class="flex space-x-1">
                            <input type="hidden" name="change_lang" value="1">
                            <button type="submit" name="lang" value="uz" class="px-2 py-1 text-xs rounded <?= $adminLang === 'uz' ? 'bg-blue-100 text-blue-800' : 'text-gray-600 hover:bg-gray-100' ?> transition-colors">🇺🇿</button>
                            <button type="submit" name="lang" value="ru" class="px-2 py-1 text-xs rounded <?= $adminLang === 'ru' ? 'bg-blue-100 text-blue-800' : 'text-gray-600 hover:bg-gray-100' ?> transition-colors">🇷🇺</button>
                            <button type="submit" name="lang" value="en" class="px-2 py-1 text-xs rounded <?= $adminLang === 'en' ? 'bg-blue-100 text-blue-800' : 'text-gray-600 hover:bg-gray-100' ?> transition-colors">🇺🇸</button>
                        </form>
                        
                        <button onclick="toggleTheme()" 
                                class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white rounded-md transition-colors">
                            🌙
                        </button>
                        <a href="/" target="_blank" 
                           class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors">
                            <?= $t['viewSite'] ?>
                        </a>
                        <a href="?logout=1" 
                           class="bg-gradient-to-r from-red-500 to-red-600 text-white px-4 py-2 rounded-lg hover:from-red-600 hover:to-red-700 transition-all duration-200 shadow-md hover:shadow-lg">
                            <?= $t['logout'] ?>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-xl">
                            📝
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400"><?= $t['posts'] ?></p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['total_posts'] ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 dark:bg-green-900 rounded-xl">
                            👥
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400"><?= $t['users'] ?></p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['total_users'] ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-xl">
                            💬
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400"><?= $t['comments'] ?></p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['total_comments'] ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-red-100 dark:bg-red-900 rounded-xl">
                            ❤️
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400"><?= $t['likes'] ?></p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['total_likes'] ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-xl">
                            📧
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400"><?= $t['newMessages'] ?></p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['unread_contacts'] ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-indigo-100 dark:bg-indigo-900 rounded-xl">
                            📬
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400"><?= $t['subscribers'] ?></p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['newsletter_subscribers'] ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex space-x-8 px-6">
                        <button onclick="showTab('posts')" id="tab-posts" 
                                class="tab-button py-4 px-1 border-b-2 border-blue-500 font-medium text-sm text-blue-600 dark:text-blue-400 transition-colors">
                            <?= $t['createPost'] ?>
                        </button>
                        <button onclick="showTab('manage-posts')" id="tab-manage-posts" 
                                class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                            <?= $t['managePosts'] ?>
                        </button>
                        <button onclick="showTab('users')" id="tab-users" 
                                class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                            <?= $t['users'] ?>
                        </button>
                        <button onclick="showTab('contacts')" id="tab-contacts" 
                                class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                            <?= $t['messages'] ?>
                        </button>
                        <button onclick="showTab('newsletter')" id="tab-newsletter" 
                                class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                            <?= $t['newsletter'] ?>
                        </button>
                    </nav>
                </div>

                <!-- Create Post Tab -->
                <div id="content-posts" class="tab-content p-6">
                    <?php if (isset($success)): ?>
                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 animate-in">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <?= htmlspecialchars($success) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <input type="hidden" name="action" value="create_post">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= $t['postTitle'] ?></label>
                            <input type="text" name="title" required 
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= $t['image'] ?></label>
                            <input type="file" name="image" accept="image/*" required 
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= $t['hashtags'] ?></label>
                            <input type="text" name="hashtags" placeholder="python, javascript, react" 
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= $t['content'] ?></label>
                            <textarea name="content" rows="15" required 
                                      class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                                      placeholder="<h2>Sarlavha</h2><p>Matn...</p><a href='#' class='btn'>Tugma</a>"></textarea>
                        </div>
                        
                        <button type="submit" 
                                class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-lg hover:shadow-xl">
                            <?= $t['create'] ?>
                        </button>
                    </form>
                </div>

                <!-- Manage Posts Tab -->
                <div id="content-manage-posts" class="tab-content p-6 hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"><?= $t['postTitle'] ?></th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"><?= $t['author'] ?></th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"><?= $t['statistics'] ?></th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"><?= $t['date'] ?></th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"><?= $t['actions'] ?></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($posts as $post): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <img class="h-10 w-10 rounded object-cover" src="<?= htmlspecialchars($post['image']) ?>" alt="">
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($post['title']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($post['username']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        ❤️ <?= $post['like_count'] ?> | 💬 <?= $post['comment_count'] ?> | 👁️ <?= $post['views'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <?= date('d.m.Y', strtotime($post['created_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <form method="POST" class="inline" onsubmit="return confirm('Rostdan ham o\'chirmoqchimisiz?')">
                                            <input type="hidden" name="action" value="delete_post">
                                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors"><?= $t['delete'] ?></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Users Tab -->
                <div id="content-users" class="tab-content p-6 hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"><?= $t['users'] ?></th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"><?= $t['email'] ?></th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"><?= $t['status'] ?></th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"><?= $t['registered'] ?></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <img class="h-8 w-8 rounded-full" src="/uploads/<?= htmlspecialchars($user['avatar']) ?>" alt="">
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($user['username']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($user['email']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $user['is_verified'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' ?>">
                                            <?= $user['is_verified'] ? $t['verified'] : $t['unverified'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <?= date('d.m.Y', strtotime($user['created_at'])) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Contacts Tab -->
                <div id="content-contacts" class="tab-content p-6 hidden">
                    <div class="space-y-4">
                        <?php foreach ($contacts as $contact): ?>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 <?= !$contact['is_read'] ? 'border-l-4 border-blue-500' : '' ?> hover:shadow-sm transition-shadow">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($contact['name']) ?></h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400"><?= htmlspecialchars($contact['email']) ?></p>
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    <?= date('d.m.Y H:i', strtotime($contact['created_at'])) ?>
                                </span>
                            </div>
                            <p class="text-gray-700 dark:text-gray-300"><?= nl2br(htmlspecialchars($contact['message'])) ?></p>
                            <?php if (!$contact['is_read']): ?>
                                <form method="POST" class="mt-2">
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="contact_id" value="<?= $contact['id'] ?>">
                                    <button type="submit" class="text-xs text-blue-600 hover:text-blue-800">O'qildi deb belgilash</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Newsletter Subscribers Tab -->
                <div id="content-newsletter" class="tab-content p-6 hidden">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2"><?= $t['newsletter'] ?></h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400"><?= $t['total'] ?> <?= count($newsletter_subscribers) ?> ta obunachi</p>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"><?= $t['email'] ?></th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"><?= $t['status'] ?></th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"><?= $t['subscriptionDate'] ?></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($newsletter_subscribers as $subscriber): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="p-2 bg-indigo-100 dark:bg-indigo-900 rounded-lg mr-3">
                                                📧
                                            </div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                <?= htmlspecialchars($subscriber['email']) ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $subscriber['is_active'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' ?>">
                                            <?= $subscriber['is_active'] ? $t['active'] : $t['inactive'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <?= date('d.m.Y H:i', strtotime($subscriber['created_at'])) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (empty($newsletter_subscribers)): ?>
                    <div class="text-center py-12">
                        <div class="text-gray-400 text-6xl mb-4">📬</div>
                        <p class="text-gray-500 dark:text-gray-400"><?= $t['noSubscribers'] ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                button.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
            });
            
            // Show selected tab content
            document.getElementById('content-' + tabName).classList.remove('hidden');
            
            // Add active class to selected tab button
            const activeButton = document.getElementById('tab-' + tabName);
            activeButton.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');
            activeButton.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
        }
        
        function toggleTheme() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
        }
        
        // Load saved theme
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>
    <?php endif; ?>

    <?php
    // Handle logout
    if (isset($_GET['logout'])) {
        session_destroy();
        header('Location: panel.php');
        exit;
    }
    ?>
</body>
</html>
