<?php

define('MB_RUNNING', true);
$_SESSION = [];

function render($filename): void
{
    include __DIR__ . '/../../html/views/' . $filename;
}

class FixturePage
{
    public $config = ['recaptcha_site_key' => 'test-key'];
    public $user;

    public function __construct()
    {
        $this->user = (object)['stripe_connect_id' => null];
    }

    public function renderPage(string $path): void
    {
        include $path;
    }
}

$page = new FixturePage();
$file = $argv[1] === 'signup'
    ? 'components/modals/signup.modal.php'
    : 'pages/customer/merchant_onboarding.php';
$page->renderPage(__DIR__ . '/../../html/apps/neighborhub/views/' . $file);
