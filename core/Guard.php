<?php
namespace Forge\Core;

/**
 * Guard - Security firewall, CSRF engine, and Input validation service.
 */
class Guard {
    
    public function __construct() {
        $this->bootCsrf();
    }

    /**
     * Boot CSRF session tokens
     */
    private function bootCsrf() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * Get the active CSRF Token
     */
    public function csrfToken() {
        return $_SESSION['_csrf_token'] ?? '';
    }

    /**
     * Generate HTML input field for CSRF
     */
    public function csrfField() {
        return '<input type="hidden" name="_csrf_token" value="' . $this->csrfToken() . '">';
    }

    /**
     * Validate POST request CSRF token
     */
    public function validateCsrf() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (empty($token) || !hash_equals($this->csrfToken(), $token)) {
                http_response_code(403);
                die("<h1>403 Forbidden</h1><p>CSRF verification failed.</p>");
            }
        }
        return true;
    }

    /**
     * Sanitize input (XSS Prevention)
     */
    public function sanitize($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitize($value);
            }
            return $data;
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Rate Limit requests (Throttling)
     * Limit matches max attempts within window (seconds)
     */
    public function throttle($key, $maxAttempts = 60, $decaySeconds = 60) {
        $time = time();
        $sessionKey = "_rate_{$key}";
        
        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = [
                'attempts' => 1,
                'reset_time' => $time + $decaySeconds
            ];
            return true;
        }

        $rate = $_SESSION[$sessionKey];
        
        if ($time > $rate['reset_time']) {
            $_SESSION[$sessionKey] = [
                'attempts' => 1,
                'reset_time' => $time + $decaySeconds
            ];
            return true;
        }

        if ($rate['attempts'] >= $maxAttempts) {
            http_response_code(429);
            header("Retry-After: " . ($rate['reset_time'] - $time));
            die("<h1>429 Too Many Requests</h1><p>Rate limit exceeded. Please wait " . ($rate['reset_time'] - $time) . " seconds.</p>");
        }

        $_SESSION[$sessionKey]['attempts']++;
        return true;
    }

    /**
     * Validate data fields against validation rules
     */
    public function validate(array $data, array $rules) {
        $errors = [];
        $db = null;

        foreach ($rules as $field => $ruleSet) {
            $rulesArray = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
            $value = $data[$field] ?? null;

            foreach ($rulesArray as $rule) {
                $params = [];
                if (strpos($rule, ':') !== false) {
                    list($ruleName, $paramStr) = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                } else {
                    $ruleName = $rule;
                }

                switch ($ruleName) {
                    case 'required':
                        if ($value === null || $value === '') {
                            $errors[$field][] = "The " . str_replace('_', ' ', $field) . " field is required.";
                        }
                        break;
                    case 'email':
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "The " . str_replace('_', ' ', $field) . " must be a valid email address.";
                        }
                        break;
                    case 'numeric':
                        if (!empty($value) && !is_numeric($value)) {
                            $errors[$field][] = "The " . str_replace('_', ' ', $field) . " must be a number.";
                        }
                        break;
                    case 'min':
                        $minVal = (int)($params[0] ?? 0);
                        if (!empty($value)) {
                            if (is_numeric($value) && $value < $minVal) {
                                $errors[$field][] = "The " . str_replace('_', ' ', $field) . " must be at least {$minVal}.";
                            } elseif (is_string($value) && strlen($value) < $minVal) {
                                $errors[$field][] = "The " . str_replace('_', ' ', $field) . " must be at least {$minVal} characters.";
                            }
                        }
                        break;
                    case 'max':
                        $maxVal = (int)($params[0] ?? 0);
                        if (!empty($value)) {
                            if (is_numeric($value) && $value > $maxVal) {
                                $errors[$field][] = "The " . str_replace('_', ' ', $field) . " may not be greater than {$maxVal}.";
                            } elseif (is_string($value) && strlen($value) > $maxVal) {
                                $errors[$field][] = "The " . str_replace('_', ' ', $field) . " may not be greater than {$maxVal} characters.";
                            }
                        }
                        break;
                    case 'unique':
                        if (!empty($value)) {
                            $table = $params[0] ?? '';
                            $column = $params[1] ?? $field;
                            $ignoreId = $params[2] ?? null;
                            $ignoreCol = $params[3] ?? 'id';

                            if ($db === null) {
                                $db = App::getInstance()->get('database');
                            }

                            $query = $db->table($table)->where($column, '=', $value);
                            if ($ignoreId !== null) {
                                $query->where($ignoreCol, '!=', $ignoreId);
                            }

                            if ($query->first()) {
                                $errors[$field][] = "This " . str_replace('_', ' ', $field) . " is already taken.";
                            }
                        }
                        break;
                }
            }
        }

        return $errors;
    }
}
