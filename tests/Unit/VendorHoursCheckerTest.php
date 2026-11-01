<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../html/apps/neighborhub/includes/classes/VendorHoursChecker.class.php';

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
}
