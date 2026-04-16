<?php

namespace YourProject\Email;

interface EmailSendInterface
{
    public function send($to, $subject, $body);
}
