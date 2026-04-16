<?php

namespace YourProject\Email;

class FakeEmailSend implements EmailSendInterface
{
    public $sendEmails = [];

    public function send($to, $subject, $body)
    {
        $this->sendEmails[] = [
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'time' => date('Y-m-d H:i:s')
        ];

        echo "Письмо не отправлено, а запомнено для $to";
        return true;
    }

    public function getSendEmails()
    {
        return $this->sendEmails;
    }
}
