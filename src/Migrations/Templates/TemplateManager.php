<?php
/*
 *  This file is part of ODY framework.
 *
 *  @link     https://ody.dev
 *  @document https://ody.dev/docs
 *  @license  https://github.com/ody-dev/ody-foundation/blob/master/LICENSE
 */

declare(strict_types=1);

namespace Ody\DB\Migrations\Templates;

use Ody\DB\Migrations\Exception\InvalidArgumentValueException;
use Ody\DB\Migrations\Migration\MigrationNameCreator;

final class TemplateManager
{
    private MigrationNameCreator $migrationNameCreator;

    private string $indent;

    private string $templatePath;

    public function __construct(MigrationNameCreator $migrationNameCreator, string $indent, ?string $templatePath = null)
    {
        $this->migrationNameCreator = $migrationNameCreator;
        $this->indent = $indent;
        $templatePath = $templatePath ?: __DIR__ . '/DefaultTemplate.ody';
        if (!is_file($templatePath)) {
            throw new InvalidArgumentValueException('Template "' . $templatePath . '" not found');
        }
        $this->templatePath = $templatePath;
    }

    public function createMigrationFromTemplate(string $up, string $down): string
    {
        $template = (string)file_get_contents($this->templatePath);
        $namespace = '';
        if ($this->migrationNameCreator->getNamespace()) {
            $namespace .= "namespace {$this->migrationNameCreator->getNamespace()};\n\n";
        }
        $template = str_replace('###NAMESPACE###', $namespace, $template);
        $template = str_replace('###CLASSNAME###', $this->migrationNameCreator->getClassName(), $template);
        $template = str_replace('###INDENT###', $this->indent, $template);
        $template = str_replace('###UP###', $up, $template);
        $template = str_replace('###DOWN###', $down, $template);
        return $template;
    }
}
