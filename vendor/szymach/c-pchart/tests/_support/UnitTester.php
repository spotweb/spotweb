<?php

declare(strict_types=1);

namespace Tests\CpChart\Support;

use Codeception\Actor;
use Codeception\Lib\Friend;
use Tests\CpChart\Support\_generated\UnitTesterActions;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
final class UnitTester extends Actor
{
    use UnitTesterActions;

    public function getOutputPathForChart(string $chartFilename): string
    {
        return sprintf("%s/../_output/charts/%s", __DIR__, $chartFilename);
    }

    public function getCacheDirectory(): string
    {
        return sprintf("%s/../../cache", __DIR__);
    }
}
