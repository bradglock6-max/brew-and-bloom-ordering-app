<?php
declare(strict_types=1);

require_once __DIR__ . '/Security.php';

final class Auth
{
    public static function requireAdmin(): array
    {
        startSecureSession();
        $login = BASE_URL . '/admin/login.php';
        $user = $_SESSION['admin_user'] ?? null;
        if (!is_array($user) || empty($user['id'])) {
            header('Location: ' . $login);
            exit;
        }
        $stmt = db()->prepare('SELECT id, username, role FROM users WHERE id = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([(int)$user['id']]);
        $row = $stmt->fetch();
        if (!$row) {
            self::logout();
            header('Location: ' . $login);
            exit;
        }
        $_SESSION['admin_user'] = ['id' => (int)$row['id'], 'username' => $row['username'], 'role' => $row['role']];
        return $_SESSION['admin_user'];
    }

    public static function login(PDO $pdo, string $username, string $password): bool
    {
        startSecureSession();
        $stmt = $pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE username = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        // Constant-ish timing: always run a hash verification.
        $hash = $user['password_hash'] ?? '$2y$10$usesomesillystringforsalt$abcdefghijklmnopqrstuvwxyzABCDE';
        $ok = password_verify($password, $hash);
        if (!$user || !$ok) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['admin_user'] = ['id' => (int)$user['id'], 'username' => $user['username'], 'role' => $user['role']];
        return true;
    }

    public static function logout(): void
    {
        startSecureSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $p['path'],
                'domain'   => $p['domain'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => 'Strict',
            ]);
        }
        session_destroy();
    }
}

function requireAdmin(): array { return Auth::requireAdmin(); }
function loginAdmin(PDO $pdo, string $username, string $password): bool { return Auth::login($pdo, $username, $password); }
function logoutAdmin(): void { Auth::logout(); }