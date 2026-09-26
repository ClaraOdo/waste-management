<?php
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ . '/includes/functions.php';

    if (is_logged_in()) {
    redirect($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php');
    }

    $errors      = [];
    $usernameOld = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (! verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Your session expired. Please try again.';
    }

    $usernameOld = clean($_POST['username'] ?? '');
    $password    = $_POST['password'] ?? '';

    $_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? 0;
    if ($_SESSION['login_attempts'] >= 6) {
        $errors[] = 'Too many failed attempts. Please wait a minute and try again.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $usernameOld]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true); // prevent session fixation
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['zone_id']   = $user['zone_id'];

            // Store zone name for display (residents only)
            if ($user['role'] === 'resident' && $user['zone_id']) {
                $zStmt = $pdo->prepare('SELECT zone_name FROM zones WHERE zone_id = :zid');
                $zStmt->execute(['zid' => $user['zone_id']]);
                $zRow                  = $zStmt->fetch();
                $_SESSION['zone_name'] = $zRow ? $zRow['zone_name'] : '';
            }

            unset($_SESSION['login_attempts']);

            redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php');
        } else {
            $_SESSION['login_attempts']++;
            $errors[] = 'Incorrect username or password.';
        }
    }
    }

    $pageTitle = 'Login';
    require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Log in</h1>
        <p>Access your dashboard to report issues, request pickups and check the schedule.</p>
    </div>
</div>

<?php if (! empty($errors)): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $err): ?><div><?php echo e($err) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<form class="form-card" method="post" novalidate>
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()) ?>">

    <label for="username">Username</label>
    <input type="text" id="username" name="username" value="<?php echo e($usernameOld) ?>" autocomplete="username">

    <label for="password">Password</label>
    <input type="password" id="password" name="password" autocomplete="current-password">

    <button type="submit" class="btn">Log in</button>
</form>

<p style="margin-top:16px;">No account yet? <a href="register.php">Register here</a>.</p>

<?php require __DIR__ . '/includes/footer.php'; ?>
