<?php

use PHPUnit\Framework\TestCase;

final class SessionNotificationsTest extends TestCase
{
    private array $originalSession;

    protected function setUp(): void
    {
        $this->originalSession = $_SESSION;
        unset($_SESSION['notifications'], $_SESSION['notification']);
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->originalSession;
    }

    private function renderNotices(): string
    {
        ob_start();
        include __DIR__ . '/../../html/views/components/session_notifications.php';
        return ob_get_clean();
    }

    public function testQueueRendersMultipleEscapedNoticesOnceAndPreservesLegacyNotice(): void
    {
        $_SESSION['notification'] = ['type' => 'success', 'message' => 'Order saved'];
        session_notify('<script>alert("unsafe")</script>', 'warning');
        session_notify('Application submitted', 'success');
        session_notify('Information');

        self::assertCount(3, $_SESSION['notifications']);
        $html = $this->renderNotices();
        self::assertStringContainsString('Order saved', $html);
        self::assertStringContainsString('&lt;script&gt;alert(&quot;unsafe&quot;)&lt;/script&gt;', $html);
        self::assertStringNotContainsString('<script>alert("unsafe")</script>', $html);
        self::assertStringContainsString('session-notice-warning', $html);
        self::assertStringContainsString('session-notice-success', $html);
        self::assertStringContainsString('session-notice-info', $html);
        self::assertLessThan(strpos($html, 'Application submitted'), strpos($html, 'Order saved'));
        self::assertArrayNotHasKey('notification', $_SESSION);
        self::assertArrayNotHasKey('notifications', $_SESSION);
        self::assertStringNotContainsString('session-notice', $this->renderNotices());
    }

    public function testErrorNoticeUsesAlertRole(): void
    {
        session_notify('An error occurred', 'error');
        self::assertStringContainsString('session-notice-error" role="alert"', $this->renderNotices());
    }

    public function testInvalidTypeAndEmptyMessageAreRejected(): void
    {
        try {
            session_notify('Message', 'unknown');
            self::fail('Invalid type was accepted.');
        } catch (InvalidArgumentException $exception) {
            self::assertArrayNotHasKey('notifications', $_SESSION);
        }
        $this->expectException(InvalidArgumentException::class);
        session_notify('   ');
    }

    public function testInvalidExistingQueueIsNotConsumed(): void
    {
        $_SESSION['notifications'] = [['type' => 'bad', 'message' => 'Message']];
        try {
            $this->renderNotices();
            self::fail('Invalid queue was accepted.');
        } catch (UnexpectedValueException $exception) {
            ob_end_clean();
            self::assertCount(1, $_SESSION['notifications']);
        }
    }

    public function testMerchantSubmissionQueuesOnlyAfterEmailSucceeds(): void
    {
        foreach (['success', 'fail'] as $mode) {
            $process = proc_open(
                [PHP_BINARY, __DIR__ . '/../fixtures/merchant-application-notification-worker.php', $mode],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes
            );
            self::assertIsResource($process);
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            self::assertSame(0, proc_close($process), $error);
            $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
            if ($mode === 'success') {
                self::assertSame(200, $result['status']);
                self::assertTrue($result['response']['success']);
                self::assertSame([['type' => 'success', 'message' => 'Your merchant application was submitted. Our team will review it and reach out to you.']], $result['notifications']);
            } else {
                self::assertSame(500, $result['status']);
                self::assertFalse($result['response']['success']);
                self::assertSame([], $result['notifications']);
            }
        }
    }

    public function testHomeAndDashboardConsumeNoticesAndPreserveCheckoutBehavior(): void
    {
        foreach (['home', 'checkout-success', 'checkout-error'] as $mode) {
            $process = proc_open(
                [PHP_BINARY, '-d', 'short_open_tag=1', __DIR__ . '/../fixtures/session-notification-page-worker.php', $mode],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes
            );
            self::assertIsResource($process);
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            self::assertSame(0, proc_close($process), $error);
            $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
            self::assertTrue($result['queuedFirst']);
            self::assertFalse($result['queuedSecond']);
            self::assertSame($mode !== 'home', $result['checkoutNotice']);
            self::assertSame($mode === 'checkout-success', $result['clearCart']);
            self::assertTrue($result['pendingRemoved']);
        }
    }
}
