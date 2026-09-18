<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Console\Application;
use CondorcetVote\ElephStamp\ElephStamp;
use Symfony\Component\Console\Tester\ApplicationTester;

it('lists the default calendars and whitelist', function (): void {
    $application = new Application;
    $application->setAutoExit(false);
    $tester = new ApplicationTester($application);

    $tester->run(['calendars']);

    $tester->assertCommandIsSuccessful();

    foreach ([...ElephStamp::DEFAULT_CALENDAR_URLS, ...ElephStamp::DEFAULT_UPGRADE_WHITELIST] as $url) {
        expect($tester->getDisplay())->toContain($url);
    }

    expect($tester->getDisplay())->toContain('2 of these 4 calendars');
});

it('registers every command under a name and version', function (): void {
    $application = new Application;

    expect($application->getName())->toBe('ElephStamp')
        ->and($application->getVersion())->not->toBe('')
        ->and(array_keys($application->all()))->toContain('stamp', 'upgrade', 'info', 'tree', 'calendars');
});
