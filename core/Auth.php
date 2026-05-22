<?php
namespace Forge\Core;

/**
 * Auth - Authentication manager for user sessions and credentials.
 */
class Auth {
    private $db;
    private $currentUser = null;

    public function __construct() {
        $this->db = App::getInstance()->get('database');
    }

    /**
     * Get current logged-in user
     */
    public function user() {
        if ($this->currentUser !== null) {
            return $this->currentUser;
        }

        if (isset($_SESSION['user_id'])) {
            $user = $this->db->table('users')->where('id', '=', $_SESSION['user_id'])->first();
            if ($user) {
                // Remove password from memory representation for safety
                unset($user['password']);
                $this->currentUser = $user;
                return $this->currentUser;
            }
        }

        return null;
    }

    /**
     * Check if user is logged in
     */
    public function check() {
        return $this->user() !== null;
    }

    /**
     * Check if logged in user has a specific role
     */
    public function hasRole($role) {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        return isset($user['role']) && $user['role'] === $role;
    }

    /**
     * Attempt login with credentials
     */
    public function attempt($email, $password, $remember = false) {
        $user = $this->db->table('users')->where('email', '=', $email)->first();
        
        if (!$user) {
            return false;
        }

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'] ?? 'user';
            
            if ($remember) {
                // Generate a remember token
                $token = bin2hex(random_bytes(32));
                $this->db->table('users')->where('id', '=', $user['id'])->update([
                    'remember_token' => $token
                ]);
                // Set cookie for 30 days
                setcookie('remember_token', $token, time() + (86400 * 30), "/");
            }
            
            unset($user['password']);
            $this->currentUser = $user;
            return true;
        }

        return false;
    }

    /**
     * Register a new user
     */
    public function register(array $data) {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        
        if (!isset($data['role'])) {
            $data['role'] = 'user';
        }

        $userId = $this->db->table('users')->insert($data);
        return $userId;
    }

    /**
     * Log the current user out
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            // Clear remember token in DB
            $this->db->table('users')->where('id', '=', $_SESSION['user_id'])->update([
                'remember_token' => null
            ]);
        }

        $this->currentUser = null;
        unset($_SESSION['user_id']);
        unset($_SESSION['user_role']);
        
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, "/");
        }
        
        session_destroy();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Check remember cookie on startup
     */
    public function autoLoginByCookie() {
        if (!$this->check() && isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            $user = $this->db->table('users')->where('remember_token', '=', $token)->first();
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'] ?? 'user';
                unset($user['password']);
                $this->currentUser = $user;
                return true;
            }
        }
        return false;
    }
}
