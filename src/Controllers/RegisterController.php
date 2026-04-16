<?php

namespace YourProject\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RegisterController
{
    public function register(Request $request, Response $response)
    {
        $data = json_decode($request->getBody()->getContents(), true);
        
        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $mode = $data['mode'] ?? 'test';
        
        if ($mode === 'real') {
            $sender = new \YourProject\Email\RealEmailSend();
            $responseMode = 'Реальный режим отправки писем';
        } else {
            $sender = new \YourProject\Email\FakeEmailSend();
            $responseMode = 'Тестовый режим (письма не отправляются)';
        }
        
        $result = $this->registerUser($email, $username, $password, $sender);
        
        if ($mode !== 'real' && $sender instanceof \YourProject\Email\FakeEmailSend) {
            $result['test_logs'] = $sender->getSendEmails();
            $result['mode'] = 'test';
        } else {
            $result['mode'] = 'real';
        }
        
        $result['mode_message'] = $responseMode;
        
        $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    private function registerUser($email, $username, $password, $sender)
    {
        if (empty($email) || empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Заполни все поля!'];
        }
        
        $userId = rand(1000, 9999);
        $subject = "Добро пожаловать, $username!";
        
        $body = "<html><head><title>Добро пожаловать!</title></head>
        <body>
        <h1>Здравствуйте, $username!</h1>
        <p>Спасибо за регистрацию</p>
        <p>Ваш Id: <strong>$userId</strong></p>
        <p>Email: $email</p>
        </body></html>";
        
        $sender->send($email, $subject, $body);
        
        return [
            'success' => true,
            'message' => "Пользователь $username успешно зарегистрирован",
            'user_id' => $userId,
            'email' => $email
        ];
    }
}
