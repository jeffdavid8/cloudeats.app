<?php

namespace PHPMailer\PHPMailer {
    #[\AllowDynamicProperties]
    class PHPMailer
    {
        public const ENCRYPTION_SMTPS = 'ssl';
        public const ENCRYPTION_STARTTLS = 'tls';
        public function __construct($exceptions) {}
        public function isSMTP(): void {}
        public function setFrom($email, $name): void {}
        public function addAddress($email, $name): void {}
        public function addBCC($email, $name): void {}
        public function isHTML($html): void {}
        public function send(): void
        {
            if ($GLOBALS['failMail']) {
                throw new \RuntimeException('Simulated mail failure');
            }
        }
    }
}

namespace {
    session_start();
    $_SESSION = ['user' => ['id' => 17, 'email' => 'example@example.test', 'username' => 'Example']];
    require __DIR__ . '/../../html/includes/util.php';

    class App
    {
        public array $config = [
            'recaptcha_secret_key' => '', 'mail_host' => 'test', 'mail_user' => 'test',
            'mail_pass' => 'test', 'mail_port' => 587, 'mail_encryption' => 'tls',
            'mail_from_email' => 'example@example.test', 'site_name' => 'Test',
        ];
        public static function getInstance($name): self { return new self(); }
    }

    function send_json_response(array $data, int $status = 200): void
    {
        echo json_encode(['status' => $status, 'response' => $data, 'notifications' => $_SESSION['notifications'] ?? []]);
        exit;
    }

    $failMail = ($argv[1] ?? '') === 'fail';
    $source = file_get_contents(__DIR__ . '/../../html/apps/neighborhub/neighborhub.api.php');
    $start = strpos($source, 'function handle_submit_merchant_application(');
    $end = strpos($source, 'function handle_provision_merchant(', $start);
    eval(substr($source, $start, $end - $start));
    handle_submit_merchant_application(['business_name' => 'Example', 'address' => 'Example address']);
}
