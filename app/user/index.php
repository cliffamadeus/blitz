<?php

require '../../config/config.php';

requireRole('user');

requireCsrfToken();

$section = $_GET['section'] ?? 'dashboard';
$action  = $_GET['action'] ?? '';

//-----------------------------------------------------------
// Password Reset Request
//-----------------------------------------------------------

if ($action === 'request_reset') {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $message = trim($_POST['reset_message'] ?? '');

        if (isset($_SESSION['user_id'])) {

            logActivity(
                $pdo,
                $_SESSION['user_id'],
                $_SESSION['user_email'] ?? null,
                'request-password-reset',
                'success'
            );
        }

        $_SESSION['alert'] =
            'Your password reset request has been submitted. '
            . 'Please wait for an administrator to reset your password.';

        header("Location: index.php");
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
</head>
<body>

    <form method="POST" action="../../auth/signout.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">
        <button type="submit">Sign Out</button>
    </form>

    <h1>User Dashboard</h1>

    <nav>
        <a href="index.php">Dashboard</a>
        |
        <a href="index.php?section=reset&action=request_reset">Request Password Reset</a>
    </nav>

    <hr>

    <?php if ($section === 'reset' && $action === 'request_reset'): ?>

        <h2>Request Password Reset</h2>

        <p>
            If you've forgotten your password or would like it reset,
            submit a request below. An administrator will reset it for you.
        </p>

        <form method="POST" action="index.php?section=reset&action=request_reset">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">

            <p>
                <label>Message (optional)</label>
                <br>
                <textarea name="reset_message" rows="4" cols="40"></textarea>
            </p>

            <button type="submit">Submit Request</button>

            <a href="index.php">Cancel</a>
        </form>

    <?php else: ?>

        <h2>Welcome, <?= htmlspecialchars($_SESSION['user_username'] ?? 'User') ?></h2>

        <p>You are signed in as <strong><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></strong>.</p>

        <p>
            Need a new password?
            <a href="index.php?section=reset&action=request_reset">Request a password reset</a>.
        </p>

    <?php endif; ?>

</body>

<?php if (isset($_SESSION['alert'])): ?>

    <script>
        alert(<?= json_encode($_SESSION['alert']) ?>);
    </script>

    <?php unset($_SESSION['alert']); ?>

<?php endif; ?>

</html>