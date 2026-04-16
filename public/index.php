<?php

session_start();

use Slim\Factory\AppFactory;
use DI\Container;

require __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();

// Добавляем обработку ошибок
$app->addErrorMiddleware(true, true, true);

// Маршрут для главной страницы (ОБЯЗАТЕЛЬНО)
$app->get('/', function ($request, $response, $args) {
    $html = file_get_contents(__DIR__ . '/index.html');
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

// Маршрут для регистрации
$app->post('/api/register', function ($request, $response, $args) {
    $data = json_decode($request->getBody()->getContents(), true);
    
    $username = $data['username'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $mode = $data['mode'] ?? 'test';
    
    // Простая реализация без классов
    if ($mode === 'real') {
        $responseMode = 'Реальный режим отправки писем';
        $test_logs = null;
    } else {
        $responseMode = 'Тестовый режим (письма не отправляются)';
        $test_logs = [[
            'to' => $email,
            'subject' => "Добро пожаловать, $username!",
            'time' => date('Y-m-d H:i:s')
        ]];
    }
    
    $userId = rand(1000, 9999);
    
    $result = [
        'success' => true,
        'message' => "Пользователь $username успешно зарегистрирован",
        'user_id' => $userId,
        'email' => $email,
        'mode' => $mode,
        'mode_message' => $responseMode
    ];
    
    if ($test_logs) {
        $result['test_logs'] = $test_logs;
    }
    
    $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
