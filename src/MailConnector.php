<?php

namespace Lkt\Connectors;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class MailConnector extends AbstractMailConnector
{
    /** @var MailConnector[] */
    protected static array $connectors = [];

    public static function define(string $name): static
    {
        $r = new static($name);
        static::$connectors[$name] = $r;
        return $r;
    }

    public static function get(string $name): ?static
    {
        if (!isset(static::$connectors[$name])) {
            throw new \Exception("Connector '{$name}' doesn't exists");
        }
        return static::$connectors[$name];
    }

    /**
     * @return MailConnector[]
     */
    public static function getAllConnectors(): array
    {
        return static::$connectors;
    }

    protected const REGEX_MAIL_STRING = "/([a-zA-Z0-9_.])+@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)+/";

    const OK_SERVER = 'ok-server';
    const FAIL_SERVER = 'fail-server';
    const OK_PHP_MAILER = 'ok-php-mailer';
    const FAIL_PHP_MAILER = 'fail-php-mailer';

    private string $lastDeliveryStatus = '';

    public function deliveryIsSuccessWithServer(): bool
    {
        return $this->lastDeliveryStatus === static::OK_SERVER;
    }

    public function deliveryIsFailWithServer(): bool
    {
        return $this->lastDeliveryStatus === static::FAIL_SERVER;
    }

    public function deliveryIsSuccessWithPHPMailer(): bool
    {
        return $this->lastDeliveryStatus === static::OK_PHP_MAILER;
    }

    public function deliveryIsFailWithPHPMailer(): bool
    {
        return $this->lastDeliveryStatus === static::FAIL_PHP_MAILER;
    }

    public function mailFromPHPMailer(string $email, string $subject, string $message = '', string $replyTo = ''): static
    {
        $toName = $this->getNameFromEmail($email);
        $email = $this->getCleanedEmail($this->getEmailWithoutName($email));

        if ($replyTo === '') $replyTo = $this->getMailingFrom();
        $fromName = $this->getNameFromEmail($replyTo);

        try {
            $mailer = new PHPMailer();
            $mailer->isSMTP();
            $mailer->Host = $this->getHost();
            $mailer->Port = $this->getPort();

            if ($this->hasUserConfig()) {
                $mailer->SMTPAuth = true;
                $mailer->Username = $this->getUser();
                $mailer->Password = $this->getPassword();
            }

            if ($this->security !== '') $mailer->SMTPSecure = $this->getSecurity();

            $mailer->setFrom($replyTo, $fromName);
            $mailer->addReplyTo($replyTo);
            $mailer->CharSet = 'UTF-8';

            $mailer->Subject = $subject;
            $mailer->msgHTML($message);

            $message = str_ireplace('<p>', '', $message);
            $message = str_ireplace(['</p>', '<br>','<br />','<br/>'], "\n", $message);

            $mailer->AltBody = strip_tags($message);
            $mailer->clearAddresses();
            $mailer->addAddress($email, $toName);

            $isSent = $mailer->send();

        } catch (Exception) {

            $isSent = false;
        }
        $this->lastDeliveryStatus = $isSent ? static::OK_PHP_MAILER : self::FAIL_PHP_MAILER;
        return $this;
    }

    public function mailFromServer(string $email, string $subject, string $message = '', string $replyTo = ''): static
    {
        $email = $this->getCleanedEmail($this->getEmailWithoutName($email));
        if ($replyTo === '') $replyTo = $email;

        $isSent = mail($email, $subject, $message, implode('', [
            "MIME-Version: 1.0\n",
            "Content-type: message/html; charset=utf-8\n",
            "From: {$email} \n",
            "Return-path: {$replyTo}\n",
        ]));

        $this->lastDeliveryStatus = $isSent ? static::OK_SERVER : self::FAIL_SERVER;
        return $this;
    }

    public function getEmailWithoutName(string $email): string
    {
        $results = [];
        preg_match(static::REGEX_MAIL_STRING, $email, $results);
        return trim($results[0]);
    }

    public function getCleanedEmail(string $email): string
    {
        return trim(str_replace(' ', '', $email));
    }

    public function getNameFromEmail(string $email): string
    {
        $detectedEmail = $this->getEmailWithoutName($email);
        $email = str_replace('<', '', $email);
        $email = trim(str_replace('>', '', $email));
        return trim(str_replace($detectedEmail, '', $email));
    }
}