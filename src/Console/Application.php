<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Console;

use Composer\InstalledVersions;
use CondorcetVote\ElephStamp\Console\Command\{CalendarsCommand, InfoCommand, StampCommand, TreeCommand, UpgradeCommand};
use OutOfBoundsException;
use Symfony\Component\Console\Application as BaseApplication;

/**
 * The `elephstamp` command-line tool.
 *
 * Pass a custom {@see ClientFactory} to run the commands against a fake
 * client, as the CLI test suite does.
 */
final class Application extends BaseApplication
{
    public const string PACKAGE = 'condorcet-vote/elephstamp';

    public function __construct(?ClientFactory $clientFactory = null)
    {
        parent::__construct('ElephStamp', self::version());

        $clientFactory ??= new HttpClientFactory;

        $this->addCommand(new StampCommand($clientFactory));
        $this->addCommand(new UpgradeCommand($clientFactory));
        $this->addCommand(new InfoCommand);
        $this->addCommand(new TreeCommand);
        $this->addCommand(new CalendarsCommand);
    }

    private static function version(): string
    {
        try {
            return InstalledVersions::getPrettyVersion(self::PACKAGE) ?? 'dev';
        } catch (OutOfBoundsException) {
            // Running from a checkout that is not installed as a Composer package.
            return 'dev';
        }
    }
}
