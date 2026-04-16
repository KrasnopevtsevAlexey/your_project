<?php

namespace YourProject\Email;

class RealEmailSend implements EmailSendInterface
{
    public function send($to, $subject, $body)
    {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: fktrctq321@gmail.com" . "\r\n";

        $result = mail($to, $subject, $body, $headers);

        if ($result) {
            echo "Письмо отправлено!";
        } else {
            echo "Ошибка отправки!";
        }
        return $result;
    }
}
