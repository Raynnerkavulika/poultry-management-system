<?php
// login.php - Complete Login Page (Pure CSS, no Bootstrap)
session_start();
require_once 'config/database.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$username = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expiry = time() + (86400 * 30);
                    setcookie('remember_token', $token, $expiry, '/', '', false, true);
                }

                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poultry Management – Login</title>
    <!-- Font Awesome (icons only) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ---------- RESET & BASE ---------- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #00b894, #00a67e);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }

        /* ---------- LOGIN CARD ---------- */
        .login-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            padding: 2.5rem 2rem;
            max-width: 440px;
            width: 100%;
            animation: fadeUp 0.6s ease-out;
        }
        @keyframes fadeUp {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .login-card .logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .login-card .logo i {
            font-size: 3rem;
            color: #00a67e;
            background: rgba(0, 166, 126, 0.12);
            padding: 18px;
            border-radius: 50%;
        }
        .login-card h4 {
            font-weight: 700;
            color: #1e2a3a;
            text-align: center;
            margin-bottom: 0.2rem;
        }
        .login-card .subtitle {
            color: #6c7a8a;
            font-size: 0.95rem;
            text-align: center;
            margin-bottom: 1.8rem;
        }

        /* ---------- ALERTS ---------- */
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
        }
        .alert-danger {
            background: #fde8e8;
            color: #c0392b;
            border: 1px solid #f5c6cb;
        }
        .alert .alert-close {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
            padding: 0 0.25rem;
            line-height: 1;
        }
        .alert .alert-close:hover {
            opacity: 1;
        }

        /* ---------- FORM ELEMENTS ---------- */
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #1e2a3a;
            margin-bottom: 0.4rem;
            font-size: 0.95rem;
        }
        .input-wrapper {
            display: flex;
            align-items: stretch;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .input-wrapper:focus-within {
            border-color: #00a67e;
            box-shadow: 0 0 0 3px rgba(0, 166, 126, 0.25);
            background: #ffffff;
        }
        .input-wrapper .input-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 1rem;
            color: #6c7a8a;
            background: transparent;
            flex-shrink: 0;
        }
        .input-wrapper input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 0.75rem 1rem 0.75rem 0;
            font-size: 1rem;
            outline: none;
            color: #1e2a3a;
            min-width: 0;
        }
        .input-wrapper input::placeholder {
            color: #b0bec5;
        }
        .input-wrapper .toggle-password {
            display: flex;
            align-items: center;
            padding: 0 1rem;
            cursor: pointer;
            color: #6c7a8a;
            background: transparent;
            border: none;
            font-size: 1rem;
        }
        .input-wrapper .toggle-password:hover {
            color: #1e2a3a;
        }

        /* Error state */
        .input-wrapper.error {
            border-color: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.2);
        }
        .input-wrapper.error:focus-within {
            border-color: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.25);
        }
        .error-message {
            color: #e74c3c;
            font-size: 0.8rem;
            margin-top: 0.3rem;
            display: none;
        }
        .error-message.show {
            display: block;
        }

        /* ---------- REMEMBER & FORGOT ROW ---------- */
        .row-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .row-actions .checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #4a5568;
            cursor: pointer;
        }
        .row-actions .checkbox input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #00a67e;
            cursor: pointer;
        }
        .row-actions .forgot-link {
            color: #00a67e;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .row-actions .forgot-link:hover {
            text-decoration: underline;
        }

        /* ---------- BUTTON ---------- */
        .btn-submit {
            background: linear-gradient(135deg, #00b894, #00a67e);
            border: none;
            border-radius: 12px;
            padding: 0.75rem;
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
            color: white;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 0.25rem;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 166, 126, 0.35);
        }
        .btn-submit:active {
            transform: scale(0.98);
        }

        /* ---------- DIVIDER ---------- */
        .divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 1.5rem 0;
        }

        /* ---------- REGISTER LINK ---------- */
        .register-link {
            text-align: center;
            color: #4a5568;
            font-size: 0.95rem;
        }
        .register-link a {
            color: #00a67e;
            font-weight: 600;
            text-decoration: none;
        }
        .register-link a:hover {
            text-decoration: underline;
        }

        /* ---------- RESPONSIVE ---------- */
        @media (max-width: 480px) {
            .login-card {
                padding: 2rem 1.5rem;
            }
            .row-actions {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo">
            <i class="fas fa-egg"></i>
        </div>
        <h4>Poultry Manager</h4>
        <p class="subtitle">Sign in to your account</p>

        <!-- Error Alert -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" id="alertMessage">
                <span><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></span>
                <button class="alert-close" onclick="dismissAlert(this)">&times;</button>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm" novalidate>
            <!-- Username / Email -->
            <div class="form-group">
                <label for="username">Username or Email</label>
                <div class="input-wrapper" id="usernameWrapper">
                    <span class="input-icon"><i class="fas fa-user"></i></span>
                    <input type="text" id="username" name="username" 
                           placeholder="Enter your username or email" 
                           value="<?= htmlspecialchars($username) ?>" required autofocus>
                </div>
                <div class="error-message" id="usernameError">Please enter your username or email.</div>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper" id="passwordWrapper">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <span class="toggle-password" id="togglePassword"><i class="fas fa-eye"></i></span>
                </div>
                <div class="error-message" id="passwordError">Please enter your password.</div>
            </div>

            <!-- Remember me & Forgot password -->
            <div class="row-actions">
                <label class="checkbox">
                    <input type="checkbox" name="remember" id="remember">
                    Remember me
                </label>
                <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
            </div>

            <!-- Login Button -->
            <button type="submit" class="btn-submit">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>

            <hr class="divider">

            <!-- Register Link -->
            <div class="register-link">
                Don't have an account? <a href="register.php">Create one now</a>
            </div>
        </form>
    </div>

    <script>
        (function() {
            'use strict';

            // ---------- TOGGLE PASSWORD VISIBILITY ----------
            const togglePassword = document.getElementById('togglePassword');
            const passwordField = document.getElementById('password');
            togglePassword.addEventListener('click', function() {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });

            // ---------- CLIENT-SIDE VALIDATION ----------
            const form = document.getElementById('loginForm');
            const username = document.getElementById('username');
            const password = document.getElementById('password');
            const usernameWrapper = document.getElementById('usernameWrapper');
            const passwordWrapper = document.getElementById('passwordWrapper');
            const usernameError = document.getElementById('usernameError');
            const passwordError = document.getElementById('passwordError');

            function validateField(input, wrapper, errorEl, condition) {
                if (condition) {
                    wrapper.classList.remove('error');
                    errorEl.classList.remove('show');
                    return true;
                } else {
                    wrapper.classList.add('error');
                    errorEl.classList.add('show');
                    return false;
                }
            }

            // Real-time blur validation
            username.addEventListener('blur', function() {
                validateField(this, usernameWrapper, usernameError, this.value.trim().length > 0);
            });
            password.addEventListener('blur', function() {
                validateField(this, passwordWrapper, passwordError, this.value.trim().length > 0);
            });

            form.addEventListener('submit', function(e) {
                let valid = true;
                if (!validateField(username, usernameWrapper, usernameError, username.value.trim().length > 0)) valid = false;
                if (!validateField(password, passwordWrapper, passwordError, password.value.trim().length > 0)) valid = false;

                if (!valid) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });

            // ---------- DISMISS ALERT ----------
            window.dismissAlert = function(btn) {
                const alert = btn.closest('.alert');
                if (alert) alert.remove();
            };

            // Auto-dismiss alerts after 5 seconds
            const alertEl = document.getElementById('alertMessage');
            if (alertEl) {
                setTimeout(function() {
                    if (alertEl) alertEl.remove();
                }, 5000);
            }
        })();
    </script>
</body>
</html>