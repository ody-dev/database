<?php
/*
 *  This file is part of ODY framework.
 *
 *  @link     https://ody.dev
 *  @document https://ody.dev/docs
 *  @license  https://github.com/ody-dev/ody-foundation/blob/master/LICENSE
 */

declare(strict_types=1);

namespace Ody\DB\Tests\Migration;

use Ody\DB\Migrations\Migration\ClassNameCreator;
use PHPUnit\Framework\TestCase;

final class ClassNameCreatorTest extends TestCase
{
    public function testClassName(): void
    {
        $filepath = __DIR__ . '/../fake/structure/migration_directory_1/20150428140909_first_migration.php';
        $creator = new ClassNameCreator($filepath);
        $this->assertEquals('\Fake\Migration\First', $creator->getClassName());
        $this->assertEquals('20150428140909', $creator->getDatetime());

        $filepath = __DIR__ . '/../fake/structure/migration_directory_1/20150518091732_second_change_of_something.php';
        $creator = new ClassNameCreator($filepath);
        $this->assertEquals('\SecondChangeOfSomething', $creator->getClassName());
        $this->assertEquals('20150518091732', $creator->getDatetime());

        $filepath = __DIR__ . '/../fake/structure/migration_directory_3/20150709132012_third.php';
        $creator = new ClassNameCreator($filepath);
        $this->assertEquals('\Ody\DB\Tests\Fake\Structure\Third', $creator->getClassName());
        $this->assertEquals('20150709132012', $creator->getDatetime());

        $filepath = __DIR__ . '/../fake/structure/migration_directory_2/20150921111111_fourth_add.php';
        $creator = new ClassNameCreator($filepath);
        $this->assertEquals('\FourthAdd', $creator->getClassName());
        $this->assertEquals('20150921111111', $creator->getDatetime());
    }
}
