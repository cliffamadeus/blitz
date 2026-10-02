<?php

require '../../config/config.php';

requireRole('admin');

requireCsrfToken();

// Determine current section
$section = $_GET['section'] ?? 'users';

// CRUD Operations
$action = $_GET['action'] ?? '';

//-----------------------------------------------------------
// Users
//-----------------------------------------------------------

// Fetch users
if ($section === 'users') {
    $stmt = $pdo->query("
        SELECT *
        FROM users
        ORDER BY user_id DESC
    ");

    $users = $stmt->fetchAll();
}

// Create User
if ($section === 'users' && $action === 'create') {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $email    = trim($_POST['user_email'] ?? '');
        $username = trim($_POST['user_username'] ?? '');
        $password = $_POST['user_password'] ?? '';
        $role     = $_POST['user_role'] ?? 'user';

        // Validate role whitelist
        $allowedRoles = ['admin', 'manager', 'user'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'user';
        }

        if ($email !== '' && $username !== '' && $password !== '') {

            // Check for duplicate email/username
            $stmt = $pdo->prepare("
                SELECT user_id
                FROM users
                WHERE user_email = ? OR user_username = ?
                LIMIT 1
            ");
            $stmt->execute([$email, $username]);

            if ($stmt->fetch()) {

                $_SESSION['alert'] = 'Email or username already exists.';

            } else {

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
                    INSERT INTO users (
                        user_email,
                        user_username,
                        user_password,
                        user_role,
                        user_verified
                    )
                    VALUES (?, ?, ?, ?, 0)
                ");

                $stmt->execute([
                    $email,
                    $username,
                    $hashedPassword,
                    $role
                ]);

                if (isset($_SESSION['user_id'])) {
                    logActivity(
                        $pdo,
                        $_SESSION['user_id'],
                        $_SESSION['user_email'] ?? null,
                        'create-user',
                        'success'
                    );
                }

                $_SESSION['alert'] = 'User created successfully. Account is pending verification.';

                header("Location: users.php?section=users");
                exit;
            }
        } else {
            $_SESSION['alert'] = 'Please fill in all required fields.';
        }
    }
}

// Update User
if ($section === 'users' && $action === 'update') {

    $userId = (int) ($_GET['id'] ?? 0);

    // Retrieve User Information
    $stmt = $pdo->prepare("
        SELECT *
        FROM users
        WHERE user_id = ?
    ");

    $stmt->execute([$userId]);

    $user = $stmt->fetch();

    if (!$user) {
        die("User not found.");
    }

    // Update User Info
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $email    = trim($_POST['user_email'] ?? '');
        $username = trim($_POST['user_username'] ?? '');
        $password = $_POST['user_password'] ?? '';
        $role     = $_POST['user_role'] ?? 'user';

        $allowedRoles = ['admin', 'manager', 'user'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'user';
        }

        if ($email !== '' && $username !== '') {

            // Check for duplicates excluding current user
            $stmt = $pdo->prepare("
                SELECT user_id
                FROM users
                WHERE (user_email = ? OR user_username = ?)
                    AND user_id != ?
                LIMIT 1
            ");
            $stmt->execute([$email, $username, $userId]);

            if ($stmt->fetch()) {

                $_SESSION['alert'] = 'Email or username already in use.';

            } else {

                if ($password !== '') {
                    // Update with new password
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET
                            user_email = ?,
                            user_username = ?,
                            user_password = ?,
                            user_role = ?
                        WHERE user_id = ?
                    ");

                    $stmt->execute([
                        $email,
                        $username,
                        $hashedPassword,
                        $role,
                        $userId
                    ]);
                } else {
                    // Update without changing password
                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET
                            user_email = ?,
                            user_username = ?,
                            user_role = ?
                        WHERE user_id = ?
                    ");

                    $stmt->execute([
                        $email,
                        $username,
                        $role,
                        $userId
                    ]);
                }

                if (isset($_SESSION['user_id'])) {
                    logActivity(
                        $pdo,
                        $_SESSION['user_id'],
                        $_SESSION['user_email'] ?? null,
                        'update-user',
                        'success'
                    );
                }

                $_SESSION['alert'] = 'User updated successfully.';

                header("Location: users.php?section=users");
                exit;
            }
        }
    }
}

// Verify User
if ($section === 'users' && $action === 'verify') {

    $userId = (int) ($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("
        UPDATE users
        SET user_verified = 1
        WHERE user_id = ?
    ");

    $stmt->execute([$userId]);

    if (isset($_SESSION['user_id'])) {
        logActivity(
            $pdo,
            $_SESSION['user_id'],
            $_SESSION['user_email'] ?? null,
            'verify-user',
            'success'
        );
    }

    $_SESSION['alert'] = 'User verified successfully.';

    header("Location: users.php?section=users");
    exit;
}

// Unverify User
if ($section === 'users' && $action === 'unverify') {

    $userId = (int) ($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("
        UPDATE users
        SET user_verified = 0
        WHERE user_id = ?
    ");

    $stmt->execute([$userId]);

    if (isset($_SESSION['user_id'])) {
        logActivity(
            $pdo,
            $_SESSION['user_id'],
            $_SESSION['user_email'] ?? null,
            'unverify-user',
            'success'
        );
    }

    $_SESSION['alert'] = 'User unverified successfully.';

    header("Location: users.php?section=users");
    exit;
}

// Delete User
if ($section === 'users' && $action === 'delete') {

    $userId = (int) ($_GET['id'] ?? 0);

    // Prevent admin from deleting themselves
    if (isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] === $userId) {

        $_SESSION['alert'] = 'You cannot delete your own account.';

        header("Location: users.php?section=users");
        exit;
    }

    $stmt = $pdo->prepare("
        DELETE FROM users
        WHERE user_id = ?
    ");

    $stmt->execute([$userId]);

    if (isset($_SESSION['user_id'])) {
        logActivity(
            $pdo,
            $_SESSION['user_id'],
            $_SESSION['user_email'] ?? null,
            'delete-user',
            'success'
        );
    }

    $_SESSION['alert'] = 'User deleted successfully.';

    header("Location: users.php?section=users");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
</head>
<body>
    <form method="POST" action="../../auth/signout.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">
        <button type="submit">Sign Out</button>
    </form>

    <h1>User Management</h1>

    <nav>
        <a href="users.php?section=users">Users</a>
    </nav>

    <hr>

    <!--USERS SECTION-->
    <?php if ($section === 'users'): ?>

        <h1>Users</h1>

        <p>
            <a href="users.php?section=users&action=create">
                Add User
            </a>
        </p>

        <?php if ($action === 'create'): ?>

            <h2>Add User</h2>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">

                <p>
                    <label>Email</label>
                    <br>
                    <input type="email" name="user_email" required>
                </p>

                <p>
                    <label>Username</label>
                    <br>
                    <input type="text" name="user_username" required>
                </p>

                <p>
                    <label>Password</label>
                    <br>
                    <input type="password" name="user_password" required>
                </p>

                <p>
                    <label>Role</label>
                    <br>
                    <select name="user_role" required>
                        <option value="user">User</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Admin</option>
                    </select>
                </p>

                <p>
                    <em>Note: Newly created accounts are unverified and must be verified by an admin.</em>
                </p>

                <button type="submit">Save</button>

                <a href="users.php?section=users">Cancel</a>
            </form>

        <?php elseif ($action === 'update'): ?>

            <h2>Update User</h2>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">

                <p>
                    <label>Email</label>
                    <br>
                    <input
                        type="email"
                        name="user_email"
                        value="<?= htmlspecialchars($user['user_email']) ?>"
                        required
                    >
                </p>

                <p>
                    <label>Username</label>
                    <br>
                    <input
                        type="text"
                        name="user_username"
                        value="<?= htmlspecialchars($user['user_username']) ?>"
                        required
                    >
                </p>

                <p>
                    <label>Password</label>
                    <br>
                    <input type="password" name="user_password" placeholder="Leave blank to keep current password">
                </p>

                <p>
                    <label>Role</label>
                    <br>
                    <select name="user_role" required>
                        <option value="user"    <?= $user['user_role'] === 'user'    ? 'selected' : '' ?>>User</option>
                        <option value="manager" <?= $user['user_role'] === 'manager' ? 'selected' : '' ?>>Manager</option>
                        <option value="admin"   <?= $user['user_role'] === 'admin'   ? 'selected' : '' ?>>Admin</option>
                    </select>
                </p>

                <button type="submit">Update</button>

                <a href="users.php?section=users">Cancel</a>
            </form>

        <?php else: ?>

            <table border="1" cellpadding="8">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Verified</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($user['user_id']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($user['user_email']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($user['user_username']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($user['user_role']) ?>
                            </td>
                            <td>
                                <?php if ($user['user_verified']): ?>
                                    <strong style="color: green;">Verified</strong>
                                <?php else: ?>
                                    <strong style="color: red;">Unverified</strong>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($user['user_created_at']) ?>
                            </td>
                            <td>
                                <a href="users.php?section=users&action=update&id=<?= $user['user_id'] ?>">Edit</a>
                                |
                                <?php if ($user['user_verified']): ?>
                                    <a
                                        href="users.php?section=users&action=unverify&id=<?= $user['user_id'] ?>"
                                        onclick="return confirm('Unverify this user?');"
                                    >
                                        Unverify
                                    </a>
                                <?php else: ?>
                                    <a
                                        href="users.php?section=users&action=verify&id=<?= $user['user_id'] ?>"
                                        onclick="return confirm('Verify this user?');"
                                    >
                                        Verify
                                    </a>
                                <?php endif; ?>
                                |
                                <a
                                    href="users.php?section=users&action=delete&id=<?= $user['user_id'] ?>"
                                    onclick="return confirm('Delete this user?');"
                                >
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>

    <?php endif; ?>

</body>

<?php if (isset($_SESSION['alert'])): ?>

    <script>
        alert(<?= json_encode($_SESSION['alert']) ?>);
    </script>

    <?php unset($_SESSION['alert']); ?>

<?php endif; ?>

</html>