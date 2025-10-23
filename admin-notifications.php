<?php
// admin-notifications.php - Admin panel za upravljanje notifikacijama
require_once 'config.php';

// Jednostavna autentifikacija (možete dodati pravu autentifikaciju)
$admin_password = "admin123"; // PROMIJENITI U PRAVU AUTENTIFIKACIJU

if ($_POST['action'] === 'login') {
    if ($_POST['password'] === $admin_password) {
        session_start();
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin-notifications.php');
        exit;
    } else {
        $error = "Pogrešna lozinka!";
    }
}

if ($_POST['action'] === 'logout') {
    session_start();
    session_destroy();
    header('Location: admin-notifications.php');
    exit;
}

session_start();
$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];

if ($_POST['action'] === 'create_notification' && $is_logged_in) {
    $name = $_POST['name'];
    $kategorija = $_POST['kategorija'];
    $body = $_POST['body'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (name, kategorija, body) VALUES (?, ?, ?)");
        $stmt->execute([$name, $kategorija, $body]);
        $success = "Notifikacija uspješno kreirana!";
    } catch (PDOException $e) {
        $error = "Greška pri kreiranju notifikacije: " . $e->getMessage();
    }
}

if ($_POST['action'] === 'delete_notification' && $is_logged_in) {
    $id = $_POST['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Notifikacija uspješno obrisana!";
    } catch (PDOException $e) {
        $error = "Greška pri brisanju notifikacije: " . $e->getMessage();
    }
}

// Dohvati sve notifikacije
if ($is_logged_in) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM notifications ORDER BY created_at DESC");
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Greška pri dohvaćanju notifikacija: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Upravljanje Notifikacijama</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #064e3b; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        textarea { height: 100px; resize: vertical; }
        button { background: #10b981; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #059669; }
        .btn-danger { background: #dc2626; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-secondary { background: #6b7280; }
        .btn-secondary:hover { background: #4b5563; }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .table th { background: #f9fafb; font-weight: bold; }
        .login-form { max-width: 400px; margin: 100px auto; }
        .categories { display: flex; gap: 10px; flex-wrap: wrap; }
        .category-tag { background: #e5e7eb; padding: 5px 10px; border-radius: 15px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!$is_logged_in): ?>
            <!-- Login forma -->
            <div class="login-form">
                <div class="header">
                    <h1>🔐 Admin Login</h1>
                </div>
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label>Lozinka:</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit">Prijavi se</button>
                </form>
            </div>
        <?php else: ?>
            <!-- Admin panel -->
            <div class="header">
                <h1>🔔 Upravljanje Notifikacijama</h1>
                <form method="POST" style="display: inline; float: right;">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="btn-secondary">Odjavi se</button>
                </form>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Kreiranje nove notifikacije -->
            <h2>➕ Kreiraj Novu Notifikaciju</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create_notification">
                <div class="form-group">
                    <label>Naziv notifikacije:</label>
                    <input type="text" name="name" required placeholder="npr. Dobrodošli!">
                </div>
                <div class="form-group">
                    <label>Kategorija (Tab):</label>
                    <select name="kategorija" required>
                        <option value="">Odaberite kategoriju</option>
                        <option value="Moja stabla">Moja stabla</option>
                        <option value="Sva stabla">Sva stabla</option>
                        <option value="Moj Co2">Moj Co2</option>
                        <option value="RLG">RLG</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Sadržaj notifikacije:</label>
                    <textarea name="body" required placeholder="Tekst notifikacije koji će se prikazati korisniku..."></textarea>
                </div>
                <button type="submit">Kreiraj Notifikaciju</button>
            </form>

            <!-- Lista postojećih notifikacija -->
            <h2>📋 Postojeće Notifikacije</h2>
            <?php if (isset($notifications) && !empty($notifications)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Naziv</th>
                            <th>Kategorija</th>
                            <th>Sadržaj</th>
                            <th>Kreirano</th>
                            <th>Akcije</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notifications as $notif): ?>
                            <tr>
                                <td><?= htmlspecialchars($notif['id']) ?></td>
                                <td><?= htmlspecialchars($notif['name']) ?></td>
                                <td>
                                    <span class="category-tag"><?= htmlspecialchars($notif['kategorija']) ?></span>
                                </td>
                                <td><?= htmlspecialchars(substr($notif['body'], 0, 50)) ?>...</td>
                                <td><?= date('d.m.Y H:i', strtotime($notif['created_at'])) ?></td>
                                <td>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Jeste li sigurni da želite obrisati ovu notifikaciju?')">
                                        <input type="hidden" name="action" value="delete_notification">
                                        <input type="hidden" name="id" value="<?= $notif['id'] ?>">
                                        <button type="submit" class="btn-danger">Obriši</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nema notifikacija.</p>
            <?php endif; ?>

            <!-- Statistike -->
            <h2>📊 Statistike</h2>
            <?php
            try {
                $stats = $pdo->query("
                    SELECT 
                        COUNT(*) as total_notifications,
                        COUNT(DISTINCT kategorija) as total_categories,
                        (SELECT COUNT(*) FROM user_notif WHERE seen_at IS NOT NULL) as seen_count,
                        (SELECT COUNT(*) FROM user_notif WHERE seen_at IS NULL) as unseen_count
                    FROM notifications
                ")->fetch(PDO::FETCH_ASSOC);
            ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; text-align: center;">
                    <h3 style="margin: 0; color: #0369a1;"><?= $stats['total_notifications'] ?></h3>
                    <p style="margin: 5px 0 0 0;">Ukupno notifikacija</p>
                </div>
                <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; text-align: center;">
                    <h3 style="margin: 0; color: #166534;"><?= $stats['total_categories'] ?></h3>
                    <p style="margin: 5px 0 0 0;">Kategorija</p>
                </div>
                <div style="background: #fef3c7; padding: 20px; border-radius: 8px; text-align: center;">
                    <h3 style="margin: 0; color: #92400e;"><?= $stats['seen_count'] ?></h3>
                    <p style="margin: 5px 0 0 0;">Viđeno</p>
                </div>
                <div style="background: #fef2f2; padding: 20px; border-radius: 8px; text-align: center;">
                    <h3 style="margin: 0; color: #dc2626;"><?= $stats['unseen_count'] ?></h3>
                    <p style="margin: 5px 0 0 0;">Neviđeno</p>
                </div>
            </div>
            <?php } catch (PDOException $e) {
                echo "<p>Greška pri dohvaćanju statistika: " . $e->getMessage() . "</p>";
            } ?>
        <?php endif; ?>
    </div>
</body>
</html>

