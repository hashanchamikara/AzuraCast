<?php

namespace App\Webhook\Connector;

use App\Entity;
use App\Service\Mail;
use GuzzleHttp\Client;
use Monolog\Logger;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class Email extends AbstractConnector
{
    public const NAME = 'email';

    protected Mail $mail;

    public function __construct(
        Logger $logger,
        Client $httpClient,
        Mail $mail
    ) {
        parent::__construct($logger, $httpClient);

        $this->mail = $mail;
    }

    public function dispatch(
        Entity\Station $station,
        Entity\StationWebhook $webhook,
        Entity\Api\NowPlaying $np,
        array $triggers,
        bool $isStandalone
    ): bool {
        if (!$this->mail->isEnabled()) {
            $this->logger->error('E-mail delivery is not currently enabled. Skipping webhook delivery...');
            return false;
        }

        $config = $webhook->getConfig();
        $emailTo = $config['to'];
        $emailSubject = $config['subject'];
        $emailBody = $config['message'];

        if (empty($emailTo) || empty($emailSubject) || empty($emailBody)) {
            $this->logger->error('Webhook ' . self::NAME . ' is missing necessary configuration. Skipping...');
            return false;
        }

        try {
            $email = $this->mail->createMessage();

            $emailToParts = explode(',', $emailTo);
            foreach ($emailToParts as $emailToPart) {
                $email->addTo(trim($emailToPart));
            }

            $vars = [
                'subject' => $emailSubject,
                'body' => $emailBody,
            ];
            $vars = $this->replaceVariables($vars, $np);

            $email->subject($vars['subject']);
            $email->text($vars['body']);

            $this->mail->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error(sprintf('Error from e-mail (%d): %s', $e->getCode(), $e->getMessage()));
            return false;
        }

        return true;
    }
}
