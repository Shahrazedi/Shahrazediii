
<?php
session_start();
$db = new PDO('sqlite:data.db');

// إنشاء الجداول إن لم تكن موجودة
$db->exec("CREATE TABLE IF NOT EXISTS users (id TEXT PRIMARY KEY, created_at TEXT)");
$db->exec("CREATE TABLE IF NOT EXISTS visits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    visitor_hash TEXT,
    user_id TEXT,
    user_agent TEXT,
    visit_time TEXT
)");

// إذا لم يكن ref موجوداً
if (!isset($_GET['ref'])) {
    $new_id = uniqid();
    $stmt = $db->prepare("INSERT INTO users (id, created_at) VALUES (?, ?)");
    $stmt->execute([$new_id, date("Y-m-d H:i:s")]);
    header("Location: ?ref=$new_id");
    exit;
}

$ref = $_GET['ref'];
$visitor_ip = hash('sha256', $_SERVER['REMOTE_ADDR']); // تشفير IP
$user_agent = $_SERVER['HTTP_USER_AGENT'];
$visit_time = date("Y-m-d H:i:s");

// عدم تكرار تسجيل نفس الجلسة
if (!isset($_SESSION['visited'])) {
    $_SESSION['visited'] = true;
    $stmt = $db->prepare("INSERT INTO visits (visitor_hash, user_id, user_agent, visit_time) VALUES (?, ?, ?, ?)");
    $stmt->execute([$visitor_ip, $ref, $user_agent, $visit_time]);
}

// عدد الزوار
$stmt = $db->prepare("SELECT COUNT(DISTINCT visitor_hash) as unique_visits FROM visits WHERE user_id = ?");
$stmt->execute([$ref]);
$unique_visits = $stmt->fetch(PDO::FETCH_ASSOC)['unique_visits'] ?? 0;

// كل الزيارات
$stmt = $db->prepare("SELECT * FROM visits WHERE user_id = ? ORDER BY visit_time DESC");
$stmt->execute([$ref]);
$visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>إحصائيات الرابط</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body { font-family: sans-serif; padding: 20px; background: #f4f4f4; color: #333; direction: rtl; }
    h1 { color: #007BFF; }
    .visit-box { background: #fff; padding: 10px; margin: 10px 0; border-radius: 8px; box-shadow: 0 0 5px #ccc; }
    .info { font-size: 14px; color: #666; }
  </style>
</head>
<body>
  <h1>عدد الزوار الفريدين: <?= $unique_visits ?></h1>
  <h2>سجل الزيارات:</h2>
  <?php foreach ($visits as $visit): ?>
    <div class="visit-box">
      <div><strong>الوقت:</strong> <?= $visit['visit_time'] ?></div>
      <div class="info"><strong>المتصفح:</strong> <?= htmlspecialchars($visit['user_agent']) ?></div>
    </div>
  <?php endforeach; ?>
</body>
</html>
