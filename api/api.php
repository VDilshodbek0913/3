<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

try {
    session_start();
    require_once 'config.php';
    require_once 'email-verification.php';

    $db = new Database();
    $pdo = $db->getConnection();
    $emailVerification = new EmailVerification($pdo);

    $method = $_SERVER['REQUEST_METHOD'];
    $request = $_GET['action'] ?? '';

    switch ($request) {
        case 'register':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                $username = sanitizeInput($data['username']);
                $email = sanitizeInput($data['email']);
                $password = $data['password'];
                $captcha = $data['captcha'];
                
                // Validate captcha
                if (!validateCaptcha($captcha, $_SESSION['captcha'] ?? '')) {
                    jsonResponse(['success' => false, 'message' => 'Captcha noto\'g\'ri']);
                }
                
                // Validate email
                if (!validateEmail($email)) {
                    jsonResponse(['success' => false, 'message' => 'Faqat @gmail.com manzillari qabul qilinadi']);
                }
                
                // Check if user exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                if ($stmt->fetch()) {
                    jsonResponse(['success' => false, 'message' => 'Foydalanuvchi yoki email allaqachon mavjud']);
                }
                
                // Send verification email
                $result = $emailVerification->sendVerificationEmail($email, 'registration');
                if ($result['success']) {
                    // Store user data temporarily
                    $_SESSION['temp_user'] = [
                        'username' => $username,
                        'email' => $email,
                        'password' => password_hash($password, PASSWORD_DEFAULT)
                    ];
                    jsonResponse(['success' => true, 'message' => 'Tasdiqlash kodi emailingizga yuborildi']);
                } else {
                    jsonResponse(['success' => false, 'message' => $result['message']]);
                }
            }
            break;
            
        case 'verify-email':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $code = $data['code'];
                $email = $_SESSION['temp_user']['email'] ?? '';
                
                $result = $emailVerification->verifyCode($email, $code, 'registration');
                if ($result['success']) {
                    // Create user account
                    $tempUser = $_SESSION['temp_user'];
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_verified) VALUES (?, ?, ?, 1)");
                    $stmt->execute([$tempUser['username'], $tempUser['email'], $tempUser['password']]);
                    
                    unset($_SESSION['temp_user']);
                    jsonResponse(['success' => true, 'message' => 'Ro\'yxatdan o\'tish muvaffaqiyatli yakunlandi!']);
                } else {
                    jsonResponse(['success' => false, 'message' => $result['message']]);
                }
            }
            break;
            
        case 'login':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                $email = sanitizeInput($data['email']);
                $password = $data['password'];
                $captcha = $data['captcha'];
                
                // Validate captcha
                if (!validateCaptcha($captcha, $_SESSION['captcha'] ?? '')) {
                    jsonResponse(['success' => false, 'message' => 'Captcha noto\'g\'ri']);
                }
                
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_verified = 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // Create session
                    $sessionToken = generateSecureToken();
                    $expiresAt = date('Y-m-d H:i:s', time() + 86400); // 24 hours
                    
                    $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)");
                    $stmt->execute([$user['id'], $sessionToken, $expiresAt]);
                    
                    unset($user['password']);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Muvaffaqiyatli tizimga kirdingiz!',
                        'user' => $user,
                        'token' => $sessionToken
                    ]);
                } else {
                    jsonResponse(['success' => false, 'message' => 'Email yoki parol noto\'g\'ri']);
                }
            }
            break;
            
        case 'posts':
            if ($method === 'GET') {
                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 10);
                $search = $_GET['search'] ?? '';
                $offset = ($page - 1) * $limit;
                
                $whereClause = "WHERE 1=1";
                $params = [];
                
                if ($search) {
                    $whereClause .= " AND (p.title LIKE ? OR p.content LIKE ? OR p.hashtags LIKE ?)";
                    $searchTerm = "%$search%";
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                }
                
                $sql = "SELECT p.*, u.username, u.avatar,
                               COALESCE((SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id), 0) as like_count,
                               COALESCE((SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id), 0) as comment_count
                        FROM posts p 
                        JOIN users u ON p.author_id = u.id 
                        $whereClause
                        ORDER BY p.created_at DESC 
                        LIMIT $limit OFFSET $offset";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $posts = $stmt->fetchAll();
                
                jsonResponse(['success' => true, 'posts' => $posts]);
            }
            break;
            
        case 'post':
            if ($method === 'GET') {
                $id = $_GET['id'] ?? 0;
                
                $stmt = $pdo->prepare("SELECT p.*, u.username, u.avatar,
                                             COALESCE((SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id), 0) as like_count,
                                             COALESCE((SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id), 0) as comment_count
                                      FROM posts p 
                                      JOIN users u ON p.author_id = u.id 
                                      WHERE p.id = ?");
                $stmt->execute([$id]);
                $post = $stmt->fetch();
                
                if ($post) {
                    // Increment views
                    $stmt = $pdo->prepare("UPDATE posts SET views = COALESCE(views, 0) + 1 WHERE id = ?");
                    $stmt->execute([$id]);
                    
                    jsonResponse(['success' => true, 'post' => $post]);
                } else {
                    jsonResponse(['success' => false, 'message' => 'Post topilmadi'], 404);
                }
            }
            break;
            
        case 'like':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $token = $data['token'];
                $postId = $data['post_id'];
                
                // Verify user session
                $stmt = $pdo->prepare("SELECT user_id FROM user_sessions WHERE session_token = ? AND expires_at > NOW()");
                $stmt->execute([$token]);
                $session = $stmt->fetch();
                
                if (!$session) {
                    jsonResponse(['success' => false, 'message' => 'Tizimga kiring'], 401);
                }
                
                $userId = $session['user_id'];
                
                // Check if already liked
                $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
                $stmt->execute([$userId, $postId]);
                $existingLike = $stmt->fetch();
                
                if ($existingLike) {
                    // Unlike
                    $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
                    $stmt->execute([$userId, $postId]);
                    $action = 'unliked';
                } else {
                    // Like
                    $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
                    $stmt->execute([$userId, $postId]);
                    $action = 'liked';
                }
                
                // Get updated like count
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
                $stmt->execute([$postId]);
                $likeCount = $stmt->fetch()['count'];
                
                jsonResponse(['success' => true, 'action' => $action, 'like_count' => $likeCount]);
            }
            break;
            
        case 'comments':
            if ($method === 'GET') {
                $postId = $_GET['post_id'] ?? 0;
                
                $stmt = $pdo->prepare("SELECT c.*, u.username, u.avatar 
                                      FROM comments c 
                                      JOIN users u ON c.user_id = u.id 
                                      WHERE c.post_id = ? 
                                      ORDER BY c.created_at DESC");
                $stmt->execute([$postId]);
                $comments = $stmt->fetchAll();
                
                jsonResponse(['success' => true, 'comments' => $comments]);
            } elseif ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $token = $data['token'];
                $postId = $data['post_id'];
                $content = sanitizeInput($data['content']);
                
                // Verify user session
                $stmt = $pdo->prepare("SELECT user_id FROM user_sessions WHERE session_token = ? AND expires_at > NOW()");
                $stmt->execute([$token]);
                $session = $stmt->fetch();
                
                if (!$session) {
                    jsonResponse(['success' => false, 'message' => 'Tizimga kiring'], 401);
                }
                
                $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
                $stmt->execute([$postId, $session['user_id'], $content]);
                
                jsonResponse(['success' => true, 'message' => 'Izoh qo\'shildi']);
            }
            break;
            
        case 'contact':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                $name = sanitizeInput($data['name']);
                $email = sanitizeInput($data['email']);
                $message = sanitizeInput($data['message']);
                $captcha = $data['captcha'];
                
                // Validate captcha
                if (!validateCaptcha($captcha, $_SESSION['captcha'] ?? '')) {
                    jsonResponse(['success' => false, 'message' => 'Captcha noto\'g\'ri']);
                }
                
                $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
                $stmt->execute([$name, $email, $message]);
                
                jsonResponse(['success' => true, 'message' => 'Sizning habaringiz yuborildi']);
            }
            break;

        case 'newsletter-subscribe':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $email = sanitizeInput($data['email']);
                
                // Validate email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    jsonResponse(['success' => false, 'message' => 'Noto\'g\'ri email format']);
                }
                
                // Check if already subscribed
                $stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    jsonResponse(['success' => false, 'message' => 'Bu email allaqachon obuna bo\'lgan']);
                }
                
                // Add to newsletter
                $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, created_at) VALUES (?, NOW())");
                $stmt->execute([$email]);
                
                jsonResponse(['success' => true, 'message' => 'Muvaffaqiyatli obuna bo\'ldingiz! 🎉']);
            }
            break;

        case 'admin-newsletter':
            if ($method === 'GET') {
                // Verify admin session
                $token = $_GET['token'] ?? '';
                $stmt = $pdo->prepare("SELECT u.* FROM users u 
                                      JOIN user_sessions s ON u.id = s.user_id 
                                      WHERE s.session_token = ? AND s.expires_at > NOW() AND u.is_admin = 1");
                $stmt->execute([$token]);
                $admin = $stmt->fetch();
                
                if (!$admin) {
                    jsonResponse(['success' => false, 'message' => 'Admin huquqi kerak'], 401);
                }
                
                $stmt = $pdo->query("SELECT * FROM newsletter_subscribers ORDER BY created_at DESC");
                $subscribers = $stmt->fetchAll();
                
                jsonResponse(['success' => true, 'subscribers' => $subscribers]);
            }
            break;

        case 'test':
            jsonResponse(['success' => true, 'message' => 'API ishlamoqda', 'timestamp' => date('Y-m-d H:i:s')]);
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Noto\'g\'ri so\'rov'], 404);
    }

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Server xatosi yuz berdi'], 500);
}
?>
