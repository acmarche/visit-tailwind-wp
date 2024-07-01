<?php

namespace VisitMarche\ThemeTail\Lib;

class Mailer
{
    public static function sendError(string $subject, string $message): void
    {
        $to = $_ENV['WEBMASTER_EMAIL'] ?? 'jf@marche.be';
        wp_mail($to, $subject, $message);
    }

    public static function sendNewsletter(string $email): void
    {
        $to = $_ENV['WEBMASTER_EMAIL'] ?? 'jf@marche.be';
        $message = 'Une nouvelle inscription à la newsletter depuis le site visitmarche.be: '.$email;
        wp_mail($to, 'Visit: Inscription newsletter', $message);
    }
}
