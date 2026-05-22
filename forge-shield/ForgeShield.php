<?php
/**
 * ForgeForm & Shield
 * Zero-Dependency AJAX Form Security and Anti-Spam Package
 * 
 * Safely processes and sanitizes POST requests, prevents spamming (anti-flood),
 * and implements CSRF validation and mathematical Captcha checks.
 */

// If requested directly or via forge_action=setup, bootstrap setup details
if (
    (isset($_GET['forge_action']) && $_GET['forge_action'] === 'setup') || 
    (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'ForgeShield.php') !== false)
) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $num1 = rand(1, 9);
    $num2 = rand(1, 9);
    $operators = ['+', '-'];
    $op = $operators[rand(0, 1)];
    
    if ($op === '-') {
        if ($num1 < $num2) {
            $temp = $num1;
            $num1 = $num2;
            $num2 = $temp;
        }
        $answer = $num1 - $num2;
    } else {
        $answer = $num1 + $num2;
    }
    
    $_SESSION['forge_captcha_answer'] = $answer;
    $_SESSION['forge_captcha_question'] = "{$num1} {$op} {$num2} = ?";
    
    if (empty($_SESSION['forge_csrf_token'])) {
        $_SESSION['forge_csrf_token'] = bin2hex(random_bytes(32));
    }
    
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    echo json_encode([
        'captcha' => $_SESSION['forge_captcha_question'],
        'csrf_token' => $_SESSION['forge_csrf_token']
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

class ForgeShield {
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Generates a new CSRF token if one does not exist.
     */
    public function generateCsrfToken() {
        if (empty($_SESSION['forge_csrf_token'])) {
            $_SESSION['forge_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['forge_csrf_token'];
    }
    
    /**
     * Generates a new mathematical Captcha and saves it in the session.
     */
    public function generateCaptcha() {
        $num1 = rand(1, 9);
        $num2 = rand(1, 9);
        $operators = ['+', '-'];
        $op = $operators[rand(0, 1)];
        
        if ($op === '-') {
            if ($num1 < $num2) {
                $temp = $num1;
                $num1 = $num2;
                $num2 = $temp;
            }
            $answer = $num1 - $num2;
        } else {
            $answer = $num1 + $num2;
        }
        
        $_SESSION['forge_captcha_answer'] = $answer;
        $_SESSION['forge_captcha_question'] = "{$num1} {$op} {$num2} = ?";
        
        return [
            'question' => $_SESSION['forge_captcha_question'],
            'answer' => $answer
        ];
    }
    
    /**
     * Main validation method to check CSRF, Captcha, and Rate Limiting.
     * Sanitizes inputs and returns clean array on success, or output error JSON and stops execution.
     * 
     * @param int $rateLimitSeconds Spam protection duration (Default: 20 seconds)
     * @return array Sanitized post fields
     */
    public static function validate($rateLimitSeconds = 20) {
        $shield = new self();
        
        // 1. Accept only POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            self::responseJSON('error', 'Geçersiz istek yöntemi. Sadece POST istekleri kabul edilir.');
        }
        
        // 2. Rate Limit Check (Anti-Flood)
        if (isset($_SESSION['forge_last_submit'])) {
            $timePassed = time() - $_SESSION['forge_last_submit'];
            if ($timePassed < $rateLimitSeconds) {
                $secondsLeft = $rateLimitSeconds - $timePassed;
                self::responseJSON('error', "Çok hızlı gönderim yapıyorsunuz, lütfen {$secondsLeft} saniye bekleyin.");
            }
        }
        
        // 3. CSRF Verification
        $submittedCsrf = $_POST['forge_csrf_token'] ?? '';
        $sessionCsrf = $_SESSION['forge_csrf_token'] ?? '';
        if (empty($sessionCsrf) || empty($submittedCsrf) || !hash_equals($sessionCsrf, $submittedCsrf)) {
            self::responseJSON('error', 'Güvenlik doğrulaması başarısız (Geçersiz CSRF Token). Sayfayı yenileyip tekrar deneyin.');
        }
        
        // 4. Captcha Verification
        $submittedCaptcha = isset($_POST['forge_captcha_answer']) ? trim($_POST['forge_captcha_answer']) : '';
        $sessionCaptcha = $_SESSION['forge_captcha_answer'] ?? '';
        if ($sessionCaptcha === '' || $submittedCaptcha === '' || (int)$submittedCaptcha !== (int)$sessionCaptcha) {
            self::responseJSON('error', 'Matematiksel güvenlik sorusu yanlış cevaplandı.');
        }
        
        // If validation passed, update submission timestamp
        $_SESSION['forge_last_submit'] = time();
        
        // Reset captcha so it can't be reused
        unset($_SESSION['forge_captcha_answer']);
        
        // 5. XSS Sanitization & Input Filtering
        $sanitizedData = $shield->sanitizeArray($_POST);
        
        // Remove internal package tokens from response payload
        unset($sanitizedData['forge_csrf_token']);
        unset($sanitizedData['forge_captcha_answer']);
        
        return $sanitizedData;
    }
    
    /**
     * Recursively sanitizes array key and values.
     */
    private function sanitizeArray($data) {
        $clean = [];
        foreach ($data as $key => $value) {
            $cleanKey = htmlspecialchars(strip_tags(trim($key)), ENT_QUOTES, 'UTF-8');
            if (is_array($value)) {
                $clean[$cleanKey] = $this->sanitizeArray($value);
            } else {
                $clean[$cleanKey] = htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
            }
        }
        return $clean;
    }
    
    /**
     * JSON output response helper.
     */
    public static function responseJSON($status, $message, $extra = []) {
        header('Content-Type: application/json; charset=utf-8');
        $response = array_merge([
            'status' => $status,
            'message' => $message
        ], $extra);
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
