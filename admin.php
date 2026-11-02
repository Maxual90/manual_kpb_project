<?php

session_start();

if (empty($_SESSION['is_admin'])) {
    header("Location: login.html");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$conn = new mysqli("localhost", "root", "", "firstdb");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$tab = $_GET['status'] ?? 'pending';

if (!in_array($tab, ['pending', 'approved', 'rejected'], true)) {
    $tab = 'pending';
}

// Approve / reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {

        $new_status = null;

        if (($_POST['action'] ?? '') === 'approve') {
            $new_status = 'approved';
        } elseif (($_POST['action'] ?? '') === 'reject') {
            $new_status = 'rejected';
        }

        $target = (int)($_POST['admin_no'] ?? 0);

        if ($new_status !== null && $target > 0) {
            $u = $conn->prepare("UPDATE students SET status = ? WHERE admin_no = ?");
            $u->bind_param("si", $new_status, $target);
            $u->execute();
            $u->close();
        }
    }

    header("Location: admin.php?status=" . $tab);
    exit();
}

$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];

$c = $conn->query("SELECT status, COUNT(*) AS total FROM students GROUP BY status");

while ($r = $c->fetch_assoc()) {
    $counts[$r['status']] = (int)$r['total'];
}

$stmt = $conn->prepare("SELECT admin_no, name, email, batch, course FROM students WHERE status = ? ORDER BY name");
$stmt->bind_param("s", $tab);
$stmt->execute();

$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin | College Alumni Connect</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Arial,Helvetica,sans-serif;}
body{background:#f4f6f9;}
header{background:#003366;color:#fff;padding:18px 30px;display:flex;justify-content:space-between;align-items:center;}
header a{color:#fff;text-decoration:none;background:#ff9800;padding:9px 18px;border-radius:5px;}
.container{max-width:900px;margin:30px auto;padding:0 15px;}
.tabs{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;}
.tabs a{padding:10px 20px;background:#fff;color:#003366;text-decoration:none;border-radius:6px;border:1px solid #ddd;font-weight:bold;}
.tabs a.active{background:#003366;color:#fff;border-color:#003366;}
.card{background:#fff;border-radius:10px;padding:20px;margin-bottom:15px;box-shadow:0 2px 8px rgba(0,0,0,.08);display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap;}
.card h3{color:#003366;margin-bottom:6px;}
.card p{color:#555;font-size:14px;margin-bottom:3px;}
.actions{display:flex;gap:8px;}
.btn{padding:9px 18px;border:none;border-radius:6px;cursor:pointer;font-size:14px;color:#fff;}
.approve{background:#28a745;}
.reject{background:#dc3545;}
.empty{text-align:center;color:#777;padding:40px 0;}
</style>
</head>
<body>

<header>
    <h1>Admin Panel</h1>
    <a href="logout.php">Logout</a>
</header>

<div class="container">

    <div class="tabs">
        <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label) { ?>
        <a href="admin.php?status=<?php echo $key; ?>" class="<?php echo ($tab === $key) ? 'active' : ''; ?>">
            <?php echo $label; ?> (<?php echo $counts[$key]; ?>)
        </a>
        <?php } ?>
    </div>

    <?php if ($result->num_rows === 0) { ?>
    <p class="empty">No <?php echo htmlspecialchars($tab); ?> users.</p>
    <?php } ?>

    <?php while ($row = $result->fetch_assoc()) { ?>

    <div class="card">

        <div>
            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
            <p>Admission No: <?php echo (int)$row['admin_no']; ?></p>
            <p><?php echo htmlspecialchars($row['email']); ?></p>
            <p><?php echo htmlspecialchars($row['course']); ?> &middot; <?php echo htmlspecialchars($row['batch']); ?></p>
        </div>

        <div class="actions">

            <?php foreach (['approve' => 'Approve', 'reject' => 'Reject'] as $act => $text) {
                if (($act === 'approve' && $tab === 'approved') || ($act === 'reject' && $tab === 'rejected')) {
                    continue;
                } ?>

            <form action="admin.php?status=<?php echo $tab; ?>" method="POST">
                <input type="hidden" name="admin_no" value="<?php echo (int)$row['admin_no']; ?>">
                <input type="hidden" name="action" value="<?php echo $act; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <button type="submit" class="btn <?php echo $act; ?>"><?php echo $text; ?></button>
            </form>

            <?php } ?>

        </div>

    </div>

    <?php } ?>

</div>

</body>
</html>
