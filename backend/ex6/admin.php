<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit();
}

// Подключение к БД
$db = new PDO("mysql:host=localhost;dbname=u68775", 'u68775', '7631071', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// Обработка удаления пользователя
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $db->beginTransaction();

        // Удаляем языки пользователя
        $db->prepare("DELETE FROM application_languages WHERE application_id = ?")
           ->execute([$id]);

        // Удаляем основную запись
        $db->prepare("DELETE FROM applications WHERE id = ?")
           ->execute([$id]);

        $db->commit();
        header("Location: admin.php?deleted=1");
        exit();
    } catch (PDOException $e) {
        $db->rollBack();
        die("Ошибка при удалении: " . $e->getMessage());
    }
}

// Получение списка всех пользователей
$users = $db->query("SELECT * FROM applications ORDER BY id")->fetchAll();

// Получение статистики по языкам
$stats = $db->query("
    SELECT pl.name, COUNT(al.application_id) as user_count
    FROM programming_languages pl
    LEFT JOIN application_languages al ON pl.id = al.language_id
    GROUP BY pl.name
    ORDER BY user_count DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        h1 {
            color: #4361ee;
            margin: 0;
        }
        .admin-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logout-btn {
            background-color: #f44336;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
        }
        .logout-btn:hover {
            background-color: #d32f2f;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #4361ee;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #4361ee;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .actions {
            white-space: nowrap;
        }
        .btn {
            display: inline-block;
            padding: 6px 12px;
            margin: 0 3px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn-edit {
            background-color: #4CAF50;
            color: white;
        }
        .btn-delete {
            background-color: #f44336;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Панель администратора</h1>
            <div class="admin-info">
                <span>Вы вошли как: <strong><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Администратор') ?></strong></span>
                <a href="logout.php" class="logout-btn">Выйти</a>
            </div>
        </header>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">
                Пользователь успешно удален!
            </div>
        <?php endif; ?>

        <h2>Статистика по языкам программирования</h2>
        <div class="stats-container">
            <?php foreach ($stats as $stat): ?>
                <div class="stat-card">
                    <h3><?= htmlspecialchars($stat['name']) ?></h3>
                    <div class="stat-value"><?= $stat['user_count'] ?></div>
                    <p>пользователей</p>
                </div>
            <?php endforeach; ?>
        </div>

        <h2>Список пользователей</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ФИО</th>
                    <th>Телефон</th>
                    <th>Email</th>
                    <th>Дата рождения</th>
                    <th>Пол</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['fio']) ?></td>
                        <td><?= htmlspecialchars($user['phone']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= $user['birthdate'] ?></td>
                        <td><?= $user['gender'] == 'male' ? 'Мужской' : 'Женский' ?></td>
                        <td class="actions">
                            <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-edit">Редактировать</a>
                            <a href="admin.php?delete=<?= $user['id'] ?>" class="btn btn-delete" onclick="return confirm('Вы уверены, что хотите удалить этого пользователя?')">Удалить</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>