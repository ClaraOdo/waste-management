<?php
/**
 * functions.php - Session bootstrap, authentication/authorisation helpers,
 * CSRF protection and input helpers shared across the whole system.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

/** Escape output to prevent XSS. Use on every piece of user-supplied data echoed to HTML. */
function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Trim and strip tags from raw input before further validation/storage. */
function clean($value)
{
    return trim(strip_tags($value ?? ''));
}

/** Generate (once per session) and return a CSRF token. */
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Verify a submitted CSRF token against the session token. */
function verify_csrf($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

/** Redirect helper. */
function redirect($path)
{
    header('Location: ' . $path);
    exit;
}

/** Is the current visitor logged in? */
function is_logged_in()
{
    return ! empty($_SESSION['user_id']);
}

/** Force login; used at the top of every protected page. */
function require_login()
{
    if (! is_logged_in()) {
        redirect('login.php');
    }
}

/** Force a specific role (e.g. 'admin'); redirects unauthorised users away. */
function require_role($role)
{
    require_login();
    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        die('403 - You do not have permission to access this page.');
    }
}

/** Store a one-time flash message to show after a redirect. */
function set_flash($type, $message)
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Next National Cleaning Day: Uganda observes the last Saturday of every
 * month (7:00-10:00am) as a mandatory community clean-up exercise. This
 * finds the next occurrence relative to today, so the UI can surface it.
 */
function next_national_cleaning_day()
{
    // Get current time in Uganda timezone (EAT - UTC+3)
    $now = new DateTime('now', new DateTimeZone('Africa/Kampala'));
    $today = clone $now;
    $today->setTime(0, 0, 0); // Start of today
    
    for ($i = 0; $i < 3; $i++) {
        $ref = clone $today;
        $ref->modify("first day of +{$i} month");
        $lastDay = clone $ref;
        $lastDay->modify('last day of this month');
        while ((int) $lastDay->format('N') !== 6) { // 6 = Saturday
            $lastDay->modify('-1 day');
        }
        
        // If it's today, check if it's before 10:00 AM
        if ($lastDay->format('Y-m-d') === $now->format('Y-m-d')) {
            if ($now->format('H') >= 10) {
                // It's past 10 AM, so this cleaning day is over
                continue;
            }
        }
        
        if ($lastDay >= $today) {
            return $lastDay;
        }
    }
    return null;
}

/** Pop and render any queued flash messages. */
function render_flash()
{
    if (empty($_SESSION['flash'])) {
        return;
    }
    foreach ($_SESSION['flash'] as $flash) {
        $cls = $flash['type'] === 'error' ? 'alert-error' : 'alert-success';
        echo '<div class="alert ' . $cls . '">' . e($flash['message']) . '</div>';
    }
    unset($_SESSION['flash']);
}
