<?php
/*
 *  This file is part of ODY framework.
 *
 *  @link     https://ody.dev
 *  @document https://ody.dev/docs
 *  @license  https://github.com/ody-dev/ody-foundation/blob/master/LICENSE
 */

declare(strict_types=1);

namespace Ody\DB\Tests\Command\InitCommand;

use Ody\DB\Tests\Command\MysqlCommandBehavior;

final class MysqlInitCommandTest extends InitCommandTest
{
    use MysqlCommandBehavior;
}
