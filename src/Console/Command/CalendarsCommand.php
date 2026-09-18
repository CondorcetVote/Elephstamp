<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Console\Command;

use CondorcetVote\ElephStamp\ElephStamp;
use CondorcetVote\ElephStamp\Verify\Explorer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'calendars',
    description: 'List the default calendar servers, the upgrade whitelist and the known block explorers',
    help: <<<'HELP'
        Shows which calendars <info>stamp</info> submits to when no <comment>--calendar</comment>
        option is given, how many of them must accept a stamp, and which hosts
        <info>upgrade</info> is allowed to contact when no <comment>--whitelist</comment> option is given, and which
        block explorers <info>verify</info> can consult.
        HELP,
)]
final class CalendarsCommand
{
    public function __invoke(SymfonyStyle $io): int
    {
        $io->section('Default calendars (used by "stamp")');
        $io->listing(ElephStamp::DEFAULT_CALENDAR_URLS);
        $io->text(\sprintf(
            'A stamp succeeds once <info>%d</info> of these %d calendars accept it (override with <comment>--required</comment>).',
            min(2, \count(ElephStamp::DEFAULT_CALENDAR_URLS)),
            \count(ElephStamp::DEFAULT_CALENDAR_URLS),
        ));

        $io->section('Default upgrade whitelist (used by "upgrade" and "info")');
        $io->listing(ElephStamp::DEFAULT_UPGRADE_WHITELIST);
        $io->text([
            'A proof embeds the calendar URIs to poll. Only hosts matching one of these',
            'patterns are ever contacted, so a hostile <comment>.ots</comment> cannot point this tool at',
            'arbitrary servers. Add your own calendars with <comment>--whitelist</comment>.',
        ]);

        $io->section('Block explorers (used by "verify")');
        $io->listing(array_map(
            static fn(Explorer $explorer): string => \sprintf('%s — <comment>--explorer %s</comment> — %s%s', $explorer->label(), $explorer->value, $explorer->baseUrl(), $explorer === Explorer::DEFAULT ? ' (default)' : ''),
            Explorer::cases(),
        ));
        $io->text('All speak the Esplora API without authentication; point <comment>--explorer-url</comment> at any other Esplora instance.');
        $io->newLine();

        return Command::SUCCESS;
    }
}
