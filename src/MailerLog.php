<?php

namespace Tu4k0\Tu4k0Monolog;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Dotenv\Dotenv;
use Monolog\Formatter\LineFormatter;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

require_once __DIR__.'/../vendor/autoload.php';

class MailerLog
{
    /**
     * @var string
     */
    public string $logPath;

    /**
     * @var \Monolog\Logger
     */
    private Logger $logger;

    /**
     * @var \Monolog\Handler\StreamHandler
     */
    private StreamHandler $streamHandler;

    /**
     * @var \Symfony\Component\Mailer\Transport\TransportInterface
     */
    private TransportInterface $connection;

    /**
     * @var \Symfony\Component\Mailer\Mailer
     */
    private Mailer $mailer;
    /**
     * @var \Symfony\Component\Mime\Email
     */
    private Email $email;

    const LOG_FORMAT = "LOG_STATUS | %level_name% \nTIME | %datetime% \nMESSAGE | %message% \nDATA | %context%\n";

    /**
     * @param string $name
     * @param string $filePath
     */
    public function __construct(string $name, string $filePath) {
        $dotenv = new Dotenv();
        $dotenv->load(__DIR__ . '/../.env');
        $this->logger = new Logger($name);
        $this->logPath = $filePath . '/log';
        $this->streamHandler = new StreamHandler($this->logPath);
        $this->streamHandler->setFormatter(new LineFormatter(self::LOG_FORMAT));
        $this->connection = Transport::fromDsn($_ENV['MAILER_DSN']);
        $this->mailer = new Mailer($this->connection);
        $this->email = new Email();
    }

    /**
     * @param int $level
     * @param string $message
     * @param array $data
     * @return void
     */
    public function addLog(int $level, string $message, array $data): void
    {
        $this->logger->addRecord($level, $message, $data);
    }

    /**
     * @return $this
     */
    public function pushLog(): MailerLog
    {
        $this->logger->pushHandler($this->streamHandler);

        return $this;
    }

    /**
     * @param string $from
     * @param string $to
     * @return void
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function sendLogEmail(string $from, string $to): void
    {
        $log = fopen($this->logPath, 'r');
        $logData = fread($log, filesize($this->logPath));
        $this->email->from($from);
        $this->email->to($to);
        $this->email->text($logData);
        $this->mailer->send($this->email);
    }
}
