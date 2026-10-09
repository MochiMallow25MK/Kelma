<?php
$title = 'Login'; require 'includes/config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $role  = $_POST['role'] ?? '';

    $stmt = $db->prepare("SELECT id, full_name, password_hash, role FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // Same message for every failure, so attackers learn nothing about which emails exist
    if ($user && password_verify($pass, $user['password_hash']) && $user['role'] === $role) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name']    = $user['full_name'];
        $_SESSION['role']    = $user['role'];
        if ($role === 'parent') {   // remember the parent's first child for the game
            $c = $db->query("SELECT id, name FROM children WHERE parent_id = " . (int)$user['id'] . " LIMIT 1")->fetch_assoc();
            $_SESSION['child_id']   = $c['id'] ?? null;
            $_SESSION['child_name'] = $c['name'] ?? '';
        }
        header('Location: /kelma/web/' . $role . '_dashboard.php');   // automatic routing by role
        exit;
    }
    $error = 'Wrong email, password, or account role. Please try again.';
}
require 'includes/header.php';
?>
<div class="container">
  <div class="card form-card">
    <h2>Parent &amp; Therapist Login</h2>
    <?php if (isset($_GET['registered'])): ?><div class="success">Account created! Please sign in.</div><?php endif; ?>
    <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <label>Select your account role</label>
      <div class="role-pick">
        <label><input type="radio" name="role" value="parent" checked> Parent Account</label>
        <label><input type="radio" name="role" value="therapist"> Speech Therapist Account</label>
      </div>
      <label>Email address</label><input type="email" name="email" placeholder="enter email address..." required>
      <label>Password</label>
      <div class="password-row">
        <input type="password" name="password" id="pw" placeholder="••••••••" required>
        <button type="button" onclick="var p=document.getElementById('pw'); p.type = p.type==='password' ? 'text' : 'password';">👁️</button>
      </div>
      <p><button class="btn btn-coral btn-big" type="submit">Sign In to Dashboard</button></p>
    </form>
    <p>Need an account? <a href="/kelma/web/register.php">Register</a></p>
  </div>
</div>
<?php require 'includes/footer.php'; ?>
