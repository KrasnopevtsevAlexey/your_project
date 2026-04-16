<?php

// Определяем корень проекта
define('PROJECT_ROOT', dirname(__DIR__));

// Подключаем файлы из папки src
require_once PROJECT_ROOT . '/src/Car.php';
require_once PROJECT_ROOT . '/src/CarRepository.php';

$dbPath = getenv('DB_PATH') ?: PROJECT_ROOT . '/cars.db';

// Создаем папку для БД если её нет
$dbDir = dirname($dbPath);
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0777, true);
}

// Создаем подключение к БД (SQLite)
try {
    $pdo = new \PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    
    // Создаем таблицу если её нет
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cars (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            make VARCHAR(255) NOT NULL,
            model VARCHAR(255) NOT NULL
        )
    ");
    
} catch (\PDOException $e) {
    // В продакшене лучше логировать, а не выводить
    error_log("DB Error: " . $e->getMessage());
    die("Ошибка подключения к базе данных");
}

// ВАЖНО: используем полное имя класса с пространством имен
$carRepo = new App\CarRepository($pdo);

// Обработка действий
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// CREATE - добавление автомобиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $make = trim($_POST['make'] ?? '');
        $model = trim($_POST['model'] ?? '');
        
        if (empty($make) || empty($model)) {
            $error = "Заполните все поля!";
        } else {
            // ВАЖНО: используем полное имя класса
            $car = new App\Car($make, $model);
            $carRepo->save($car);
            $message = "Автомобиль успешно добавлен! ID: " . $car->getId();
        }
    }
    
    // UPDATE - обновление автомобиля
    if ($_POST['action'] === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $make = trim($_POST['make'] ?? '');
        $model = trim($_POST['model'] ?? '');
        
        if ($id <= 0 || empty($make) || empty($model)) {
            $error = "Неверные данные!";
        } else {
            $car = $carRepo->find($id);
            if ($car) {
                $updatedCar = new App\Car($make, $model);
                $updatedCar->setId($id);
                $carRepo->save($updatedCar);
                $message = "Автомобиль успешно обновлен!";
            } else {
                $error = "Автомобиль с ID $id не найден!";
            }
        }
    }
    
    // DELETE - удаление автомобиля
    if ($_POST['action'] === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            $error = "Неверный ID!";
        } else {
            $car = $carRepo->find($id);
            if ($car) {
                $carRepo->delete($id);
                $message = "Автомобиль '{$car->getMake()} {$car->getModel()}' удален!";
            } else {
                $error = "Автомобиль с ID $id не найден!";
            }
        }
    }
}

// Получаем список всех автомобилей
$cars = $carRepo->getEntities();

// Получаем автомобиль для редактирования
$editCar = null;
if (($action === 'edit' || $action === 'delete') && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $editCar = $carRepo->find($id);
    if (!$editCar && $action === 'edit') {
        $error = "Автомобиль не найден!";
        $action = 'list';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление автомобилями</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header, .form-card, .list-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header h1 { color: #333; margin-bottom: 10px; }
        .content { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; }
        .form-card h2, .list-card h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #555; font-weight: 500; }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102,126,234,0.3);
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        button:hover { background: #5a67d8; }
        .btn-edit { background: #48bb78; text-decoration: none; color: white; padding: 5px 10px; border-radius: 5px; display: inline-block; }
        .btn-edit:hover { background: #38a169; }
        .btn-delete { background: #f56565; }
        .btn-delete:hover { background: #e53e3e; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f7f7f7; color: #555; font-weight: 600; }
        tr:hover { background: #f9f9f9; }
        .message {
            background: #c6f6d5;
            color: #22543d;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid #38a169;
        }
        .error {
            background: #fed7d7;
            color: #742a2a;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid #e53e3e;
        }
        .empty-state { text-align: center; padding: 40px; color: #999; }
        .actions { display: flex; gap: 5px; align-items: center; }
        .btn-small { padding: 5px 10px; font-size: 12px; }
        .cancel-btn {
            background: #a0aec0;
            margin-left: 10px;
            text-decoration: none;
            display: inline-block;
            padding: 10px 20px;
            border-radius: 5px;
            color: white;
        }
        @media (max-width: 768px) { .content { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚗 Управление автомобилями</h1>
            <p>CRUD операции с использованием Car и CarRepository</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message">✅ <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="content">
            <div class="form-card">
                <?php if ($action === 'edit' && $editCar): ?>
                    <h2>✏️ Редактирование автомобиля</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= $editCar->getId() ?>">
                        <div class="form-group">
                            <label>Марка:</label>
                            <input type="text" name="make" value="<?= htmlspecialchars($editCar->getMake()) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Модель:</label>
                            <input type="text" name="model" value="<?= htmlspecialchars($editCar->getModel()) ?>" required>
                        </div>
                        <button type="submit">💾 Сохранить изменения</button>
                        <a href="?action=list" class="cancel-btn">❌ Отмена</a>
                    </form>
                <?php else: ?>
                    <h2>➕ Добавить автомобиль</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="create">
                        <div class="form-group">
                            <label>Марка (Make):</label>
                            <input type="text" name="make" placeholder="Например: Toyota, BMW, Mercedes" required>
                        </div>
                        <div class="form-group">
                            <label>Модель (Model):</label>
                            <input type="text" name="model" placeholder="Например: Camry, X5, E-Class" required>
                        </div>
                        <button type="submit">➕ Добавить автомобиль</button>
                    </form>
                <?php endif; ?>
            </div>
            
            <div class="list-card">
                <h2>📋 Список автомобилей</h2>
                <?php if (empty($cars)): ?>
                    <div class="empty-state">
                        <p>🚫 Нет добавленных автомобилей</p>
                        <p style="font-size: 12px; margin-top: 10px;">Добавьте первый автомобиль через форму слева</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr><th>ID</th><th>Марка</th><th>Модель</th><th>Действия</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cars as $car): ?>
                                <tr>
                                    <td><?= $car->getId() ?></td>
                                    <td><?= htmlspecialchars($car->getMake()) ?></td>
                                    <td><?= htmlspecialchars($car->getModel()) ?></td>
                                    <td class="actions">
                                        <a href="?action=edit&id=<?= $car->getId() ?>" class="btn-edit">✏️ Редактировать</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Удалить автомобиль <?= htmlspecialchars($car->getMake() . ' ' . $car->getModel()) ?>?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $car->getId() ?>">
                                            <button type="submit" class="btn-delete btn-small">🗑️ Удалить</button>
                                        </form>
                                     </td>
                                 </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div style="margin-top: 15px; padding: 10px; background: #f7f7f7; border-radius: 5px;">
                        <strong>📊 Статистика:</strong> Всего автомобилей: <?= count($cars) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>