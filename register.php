<?php
// register.php - Registration Page (Pure CSS, no Bootstrap)
session_start();
require_once 'config/database.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$full_name = $username = $email = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($full_name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'Username must be between 3 and 50 characters.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'Username or email already taken. Please choose another.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password, role) VALUES (?, ?, ?, ?, 'staff')");
                $stmt->execute([$full_name, $username, $email, $hashed]);
                $success = 'Registration successful! You will be redirected to login.';
                $full_name = $username = $email = '';
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
    <title>Poultry Management – Register</title>
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

        /* ---------- CARD ---------- */
        .register-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            padding: 2.5rem 2rem;
            max-width: 480px;
            width: 100%;
            animation: fadeUp 0.6s ease-out;
        }
        @keyframes fadeUp {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .register-card .logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .register-card .logo i {
            font-size: 3rem;
            color: #00a67e;
            background: rgba(0, 166, 126, 0.12);
            padding: 18px;
            border-radius: 50%;
        }
        .register-card h4 {
            font-weight: 700;
            color: #1e2a3a;
            text-align: center;
            margin-bottom: 0.2rem;
        }
        .register-card .subtitle {
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
        .alert-success {
            background: #e3f7ec;
            color: #0e7c4b;
            border: 1px solid #b7e4c7;
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

        /* Error state for inputs */
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

        /* ---------- LOGIN LINK ---------- */
        .login-link {
            text-align: center;
            color: #4a5568;
            font-size: 0.95rem;
        }
        .login-link a {
            color: #00a67e;
            font-weight: 600;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }

        /* ---------- RESPONSIVE ---------- */
        @media (max-width: 480px) {
            .register-card {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="logo">
            <i class="fas fa-user-plus"></i>
        </div>
        <h4>Create Account</h4>
        <p class="subtitle">Join the Poultry Management System</p>

        <!-- Error / Success Messages -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" id="alertMessage">
                <span><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></span>
                <button class="alert-close" onclick="dismissAlert(this)">&times;</button>
            </div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success" id="alertMessage">
                <span><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></span>
                <button class="alert-close" onclick="dismissAlert(this)">&times;</button>
            </div>
            <script>
                // Redirect to login after 3 seconds
                setTimeout(function() {
                    window.location.href = 'login.php';
                }, 3000);
            </script>
        <?php endif; ?>

        <form method="POST" action="" id="registerForm" novalidate>
            <!-- Full Name -->
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <div class="input-wrapper" id="fullNameWrapper">
                    <span class="input-icon"><i class="fas fa-user"></i></span>
                    <input type="text" id="full_name" name="full_name" placeholder="Your full name" value="<?= htmlspecialchars($full_name) ?>" required>
                </div>
                <div class="error-message" id="fullNameError">Please enter your full name.</div>
            </div>

            <!-- Username -->
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper" id="usernameWrapper">
                    <span class="input-icon"><i class="fas fa-user-tag"></i></span>
                    <input type="text" id="username" name="username" placeholder="Choose a username" value="<?= htmlspecialchars($username) ?>" required>
                </div>
                <div class="error-message" id="usernameError">Username must be at least 3 characters.</div>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper" id="emailWrapper">
                    <span class="input-icon"><i class="fas fa-envelope"></i></span>
                    <input type="email" id="email" name="email" placeholder="you@example.com" value="<?= htmlspecialchars($email) ?>" required>
                </div>
                <div class="error-message" id="emailError">Please enter a valid email address.</div>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper" id="passwordWrapper">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" id="password" name="password" placeholder="Min 6 characters" required>
                    <span class="toggle-password" id="togglePassword"><i class="fas fa-eye"></i></span>
                </div>
                <div class="error-message" id="passwordError">Password must be at least 6 characters.</div>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-wrapper" id="confirmWrapper">
                    <span class="input-icon"><i class="fas fa-check-circle"></i></span>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                    <span class="toggle-password" id="toggleConfirm"><i class="fas fa-eye"></i></span>
                </div>
                <div class="error-message" id="confirmError">Passwords do not match.</div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-submit">
                <i class="fas fa-user-plus"></i> Register
            </button>

            <hr class="divider">

            <!-- Login Link -->
            <div class="login-link">
                Already have an account? <a href="login.php">Sign in here</a>
            </div>
        </form>
    </div>

    <script>
        (function() {
            'use strict';

            // ---------- TOGGLE PASSWORD VISIBILITY ----------
            function toggleVisibility(toggleBtn, field) {
                toggleBtn.addEventListener('click', function() {
                    const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
                    field.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            }
            toggleVisibility(document.getElementById('togglePassword'), document.getElementById('password'));
            toggleVisibility(document.getElementById('toggleConfirm'), document.getElementById('confirm_password'));

            // ---------- CLIENT-SIDE VALIDATION ----------
            const form = document.getElementById('registerForm');
            const fullName = document.getElementById('full_name');
            const username = document.getElementById('username');
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const confirm = document.getElementById('confirm_password');

            // References to wrappers and error messages
            const fullNameWrapper = document.getElementById('fullNameWrapper');
            const usernameWrapper = document.getElementById('usernameWrapper');
            const emailWrapper = document.getElementById('emailWrapper');
            const passwordWrapper = document.getElementById('passwordWrapper');
            const confirmWrapper = document.getElementById('confirmWrapper');
            const fullNameError = document.getElementById('fullNameError');
            const usernameError = document.getElementById('usernameError');
            const emailError = document.getElementById('emailError');
            const passwordError = document.getElementById('passwordError');
            const confirmError = document.getElementById('confirmError');

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

            // Real-time validation on blur
            fullName.addEventListener('blur', function() {
                validateField(this, fullNameWrapper, fullNameError, this.value.trim().length > 0);
            });
            username.addEventListener('blur', function() {
                validateField(this, usernameWrapper, usernameError, this.value.trim().length >= 3);
            });
            email.addEventListener('blur', function() {
                const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                validateField(this, emailWrapper, emailError, pattern.test(this.value.trim()));
            });
            password.addEventListener('blur', function() {
                validateField(this, passwordWrapper, passwordError, this.value.length >= 6);
            });

            // Real-time password match
            confirm.addEventListener('input', function() {
                const match = this.value === password.value;
                if (!match) {
                    confirmWrapper.classList.add('error');
                    confirmError.classList.add('show');
                } else {
                    confirmWrapper.classList.remove('error');
                    confirmError.classList.remove('show');
                }
            });

            // Form submit validation
            form.addEventListener('submit', function(e) {
                let valid = true;

                // Full name
                if (!validateField(fullName, fullNameWrapper, fullNameError, fullName.value.trim().length > 0)) valid = false;
                // Username
                if (!validateField(username, usernameWrapper, usernameError, username.value.trim().length >= 3)) valid = false;
                // Email
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!validateField(email, emailWrapper, emailError, emailPattern.test(email.value.trim()))) valid = false;
                // Password
                if (!validateField(password, passwordWrapper, passwordError, password.value.length >= 6)) valid = false;
                // Confirm password match
                const match = confirm.value === password.value;
                if (!match) {
                    confirmWrapper.classList.add('error');
                    confirmError.classList.add('show');
                    valid = false;
                } else {
                    confirmWrapper.classList.remove('error');
                    confirmError.classList.remove('show');
                }

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