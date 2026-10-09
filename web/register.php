<?php
$title = 'Register'; require 'includes/config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $role  = $_POST['role'] ?? '';
    $child = trim($_POST['child_name'] ?? '');

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6 || !in_array($role, ['parent', 'therapist'])) {
        $error = 'Please fill in all fields. The password needs at least 6 characters.';
    } elseif ($role === 'parent' && $child === '') {
        $error = "Please enter your child's first name.";
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);   // never store plain passwords
        $stmt = $db->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $name, $email, $hash, $role);
        if ($stmt->execute()) {
            if ($role === 'parent') {
                $parent_id = $db->insert_id;
                $stmt = $db->prepare("INSERT INTO children (parent_id, name) VALUES (?, ?)");
                $stmt->bind_param('is', $parent_id, $child);
                $stmt->execute();
            }
            header('Location: /kelma/kelma/web/login.php?registered=1');
            exit;
        }
        $error = 'This email is already registered.';
    }
}
require 'includes/header.php';
?>
<div class="container">
  <div class="card form-card">
    <h2>Create Your Account</h2>
    <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <label>Account role</label>
      <div class="role-pick">
        <label><input type="radio" name="role" value="parent" checked onchange="toggleChild()"> Parent</label>
        <label><input type="radio" name="role" value="therapist" onchange="toggleChild()"> Speech Therapist</label>
      </div>
      <label>Full name</label><input type="text" name="full_name" required>
      <label>Email address</label><input type="email" name="email" required>
      <label>Password</label><input type="password" name="password" required>
      <div id="childField"><label>Child's first name</label><input type="text" name="child_name"></div>
      <p><button class="btn btn-coral btn-big" type="submit">Register</button></p>
    </form>
    <p>Already have an account? <a href="/kelma/kelma/web/login.php">Login</a></p>
  </div>
</div>
<script>
  function toggleChild() {
    var isParent = document.querySelector('input[name=role]:checked').value === 'parent';
    document.getElementById('childField').style.display = isParent ? 'block' : 'none';
  }
</script>
<?php require 'includes/footer.php'; ?>
