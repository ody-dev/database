<?php
/*
 *  This file is part of ODY framework.
 *
 *  @link     https://ody.dev
 *  @document https://ody.dev/docs
 *  @license  https://github.com/ody-dev/ody-foundation/blob/master/LICENSE
 */

declare(strict_types=1);

namespace Ody\DB\Migrations\Command;

use Ody\DB\Migrations\Database\Adapter\AdapterFactory;
use Ody\DB\Migrations\Database\Element\Structure;
use Ody\DB\Migrations\Dumper\Dumper;
use Ody\DB\Migrations\Exception\InvalidArgumentValueException;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

final class DiffCommand extends AbstractDumpCommand
{
    public function __construct(string $name = 'diff')
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDescription('Makes diff of source and target database or diff of migrations and database')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Source environment from config. If not set, migrations are used as source.')
            ->addOption('target', null, InputOption::VALUE_REQUIRED, 'Target environment from config. If not set, migrations are used as target.')
        ;

        parent::configure();
    }

    protected function migrationDefaultName(): string
    {
        return 'Diff';
    }

    protected function createDumper(string $indent): Dumper
    {
        return new Dumper($indent, 2);
    }

    protected function sourceStructure(): Structure
    {
        return $this->getStructure('source');
    }

    protected function targetStructure(): Structure
    {
        return $this->getStructure('target');
    }

    protected function loadData(array $tables): array
    {
        return [];
    }

    private function getStructure(string $type): Structure
    {
        /** @var string|null $env */
        $env = $this->input->getOption($type);
        if (!$env) {
            return $this->createStructureFromMigrations();
        }

        $config = $this->getConfig()->getEnvironmentConfig($env);
        if (!$config) {
            throw new InvalidArgumentValueException(ucfirst($type) . ' environment "' . $env . '" doesn\'t exist in config');
        }

        $adapter = AdapterFactory::instance($config);
        return $adapter->getStructure();
    }

    private function createStructureFromMigrations(): Structure
    {
        $structure = new Structure();
        $migrationClasses = $this->manager->findMigrationClasses();
        foreach ($migrationClasses as $migration) {
            try {
                $migration->updateStructure($structure);
            } catch (Throwable $e) {
                $this->output->writeln('<error>Warning: Migration "' . $migration->getFullClassName() . '" throws exception / error: "' . $e->getMessage() . '"</error>');
            }
        }
        return $structure;
    }
}
