<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php');
}

$errors = [];
$old = ['full_name' => '', 'username' => '', 'email' => '', 'phone' => '', 'zone_id' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Your session expired. Please try again.';
    }

    $old['full_name'] = clean($_POST['full_name'] ?? '');
    $old['username']  = clean($_POST['username'] ?? '');
    $old['email']     = clean($_POST['email'] ?? '');
    $old['phone']     = clean($_POST['phone'] ?? '');
    $old['zone_id']   = clean($_POST['zone_id'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirmPassword  = $_POST['confirm_password'] ?? '';

    // ---- Server-side validation (never trust the client) ----
    if (mb_strlen($old['full_name']) < 2) {
        $errors[] = 'Please enter your full name.';
    }
    if (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $old['username'])) {
        $errors[] = 'Username must be 4-20 characters: letters, numbers or underscore only.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }
    if ($old['zone_id'] === '' || !ctype_digit($old['zone_id'])) {
        $errors[] = 'Please select your collection zone.';
    }
    // Phone: if provided, must be 10-15 digits (allows leading +)
    if ($old['phone'] !== '' && !preg_match('/^\+?[0-9]{10,15}$/', $old['phone'])) {
        $errors[] = 'Please enter a valid phone number (10-15 digits).';
    }

    if (empty($errors)) {
        // Uniqueness checks (also enforced at the DB layer via UNIQUE constraints)
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :u OR email = :e');
        $stmt->execute(['u' => $old['username'], 'e' => $old['email']]);
        if ((int) $stmt->fetchColumn() > 0) {
            $errors[] = 'That username or email is already registered.';
        }
        // Phone uniqueness check (only if a phone was provided)
        if ($old['phone'] !== '') {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE phone = :p');
            $stmt->execute(['p' => $old['phone']]);
            if ((int) $stmt->fetchColumn() > 0) {
                $errors[] = 'That phone number is already registered to another account.';
            }
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'INSERT INTO users (full_name, username, email, password_hash, phone, role, zone_id)
             VALUES (:full_name, :username, :email, :hash, :phone, "resident", :zone_id)'
        );
        $stmt->execute([
            'full_name' => $old['full_name'],
            'username'  => $old['username'],
            'email'     => $old['email'],
            'hash'      => $hash,
            'phone'     => $old['phone'],
            'zone_id'   => $old['zone_id'],
        ]);

        set_flash('success', 'Account created. You can now log in.');
        redirect('login.php');
    }
}

$zones = $pdo->query('SELECT zone_id, zone_name FROM zones ORDER BY zone_name')->fetchAll();

$pageTitle = 'Register';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Create your account</h1>
        <p>Register as a resident to report issues, view your zone's schedule and request pickups.</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $err): ?>
            <div><?= e($err) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form class="form-card" method="post" id="registerForm" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <label for="full_name">Full name</label>
    <input type="text" id="full_name" name="full_name" value="<?= e($old['full_name']) ?>">

    <label for="username">Username</label>
    <input type="text" id="username" name="username" value="<?= e($old['username']) ?>" autocomplete="username">
    <div class="help-text" id="usernameStatus"></div>

    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="<?= e($old['email']) ?>">

    <label for="phone">Phone (optional)</label>
    <input type="tel" id="phone" name="phone" value="<?= e($old['phone']) ?>">

    <label for="zone_id">Collection zone</label>
    <select id="zone_id" name="zone_id">
        <option value="">-- Select your zone --</option>
        <?php foreach ($zones as $zone): ?>
            <option value="<?= (int) $zone['zone_id'] ?>" <?= $old['zone_id'] == $zone['zone_id'] ? 'selected' : '' ?>>
                <?= e($zone['zone_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" autocomplete="new-password">
    <div class="help-text">At least 8 characters.</div>

    <label for="confirm_password">Confirm password</label>
    <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password">

    <button type="submit" class="btn">Create account</button>
</form>

<p style="margin-top:16px;">Already have an account? <a href="login.php">Log in</a>.</p>

<?php require __DIR__ . '/includes/footer.php'; ?>
