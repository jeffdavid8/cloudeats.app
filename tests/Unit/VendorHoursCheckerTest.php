<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../html/apps/neighborhub/includes/classes/VendorHoursChecker.class.php';
require_once __DIR__ . '/../../html/apps/neighborhub/includes/models/merchant.model.php';

final class VendorHoursCheckerTest extends TestCase
{
    private array $hours = [
        'weekly' => [
            'monday' => [
                ['open' => '09:00', 'close' => '14:00'],
                ['open' => '16:00', 'close' => '21:00'],
            ],
        ],
        'holidays' => [
            '2026-12-25' => [],
        ],
    ];

    public function testSplitShiftIsOpenAndReportsClosingTime(): void
    {
        $now = new DateTimeImmutable('2026-11-02 13:40:00');

        self::assertTrue(VendorHoursChecker::isOpen($this->hours, $now));
        self::assertSame('Closes in 20 minutes', VendorHoursChecker::getMessage($this->hours, $now));
    }

    public function testHolidayOverrideClosesAWeeklySchedule(): void
    {
        $now = new DateTimeImmutable('2026-12-25 10:00:00');

        self::assertFalse(VendorHoursChecker::isOpen($this->hours, $now));
        self::assertSame('Monday at 9:00 AM', VendorHoursChecker::getNextOpenTime($this->hours, $now));
    }

    public function testOvernightShiftRemainsOpenAfterMidnight(): void
    {
        $hours = ['weekly' => ['monday' => [['open' => '22:00', 'close' => '02:00']]]];

        self::assertTrue(VendorHoursChecker::isOpen($hours, new DateTimeImmutable('2026-11-03 01:00:00')));
    }

    public function testOperationalStatusAtOpeningAndClosingBoundaries(): void
    {
        $merchant = ['status' => 'active', 'store_hours' => $this->hours];
        $before = Merchant::getOperationalStatus($merchant, new DateTimeImmutable('2026-11-02 08:45:00'));
        self::assertSame('opening-soon', $before['urgency']);
        self::assertSame('Opens in 15 minutes', $before['message']);
        self::assertSame('open', Merchant::getOperationalStatus($merchant, new DateTimeImmutable('2026-11-02 09:00:00'))['status']);
        self::assertSame('closing-soon', Merchant::getOperationalStatus($merchant, new DateTimeImmutable('2026-11-02 13:40:00'))['urgency']);
        self::assertSame('closed', Merchant::getOperationalStatus($merchant, new DateTimeImmutable('2026-11-02 14:00:00'))['status']);
    }

    public function testPausedAndUnlistedMerchantsAreClosed(): void
    {
        $paused = Merchant::getOperationalStatus(['status' => 'paused', 'store_hours' => $this->hours]);
        self::assertSame('manual_override', $paused['reason']);
        self::assertSame('Temporarily paused', $paused['label']);
        $unlisted = Merchant::getOperationalStatus(['status' => 'active', 'store_hours' => []]);
        self::assertSame('closed', $unlisted['status']);
        self::assertSame('Hours not listed', $unlisted['message']);
    }

    public function testNextOpeningUsesEarliestUnsortedInterval(): void
    {
        $hours = ['weekly' => ['monday' => [
            ['open' => '16:00', 'close' => '20:00'],
            ['open' => '09:00', 'close' => '12:00'],
        ]]];
        $status = VendorHoursChecker::getOperationalDetails($hours, new DateTimeImmutable('2026-11-02 08:00:00'));
        self::assertSame('Opens at 9:00 AM', $status['message']);
    }

    public function testOpeningMessagesUseCorrectSameAndFutureDayWording(): void
    {
        $hours = ['weekly' => ['wednesday' => [['open' => '09:00', 'close' => '17:00']]]];
        foreach ([
            ['2026-11-11 08:00:00', 'Opens at 9:00 AM', '9:00 AM'],
            ['2026-11-10 23:24:00', 'Opens Wednesday at 9:00 AM', 'Wednesday at 9:00 AM'],
        ] as [$time, $message, $nextOpen]) {
            $now = new DateTimeImmutable($time);
            self::assertSame($message, VendorHoursChecker::getMessage($hours, $now));
            self::assertSame($message, VendorHoursChecker::getOperationalDetails($hours, $now)['message']);
            self::assertSame($nextOpen, VendorHoursChecker::getNextOpenTime($hours, $now));
        }
    }

    public function testOvernightDetailsAndHolidayOverride(): void
    {
        $hours = ['weekly' => ['monday' => [['open' => '22:00', 'close' => '02:00']]], 'holidays' => ['2026-11-09' => []]];
        $status = VendorHoursChecker::getOperationalDetails($hours, new DateTimeImmutable('2026-11-03 01:45:00'));
        self::assertSame('closing-soon', $status['urgency']);
        self::assertSame('Closes in 15 minutes', $status['message']);
        self::assertSame('closed', VendorHoursChecker::getOperationalDetails($hours, new DateTimeImmutable('2026-11-09 23:00:00'))['status']);
    }

    public static function closingDurations(): array
    {
        return [
            ['13:59:30', 'Closes in 1 minute', 'closing-soon'],
            ['13:01:00', 'Closes in 59 minutes', ''],
            ['13:00:00', 'Closes in 1 hour', ''],
            ['12:59:00', 'Closes in 1 hour 1 minute', ''],
            ['12:00:00', 'Closes in 2 hours', ''],
            ['06:31:00', 'Closes in 7 hours 29 minutes', ''],
        ];
    }

    /** @dataProvider closingDurations */
    public function testClosingDurationIsHumanReadable(string $time, string $message, string $urgency): void
    {
        $hours = ['weekly' => ['monday' => [['open' => '06:00', 'close' => '14:00']]]];
        $now = new DateTimeImmutable('2026-11-02 ' . $time);
        $details = VendorHoursChecker::getOperationalDetails($hours, $now);
        self::assertSame($message, $details['message']);
        self::assertSame($message, VendorHoursChecker::getMessage($hours, $now));
        self::assertSame($urgency, $details['urgency']);
    }
}
