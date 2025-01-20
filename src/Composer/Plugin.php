<?php

declare(strict_types=1);

/**
 * Copyright (c) 2023-2025 Dezső Biczó
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/mxr576/composer-audit-changes/LICENSE.md
 *
 */

namespace mxr576\ComposerAuditChanges\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;

final class Plugin implements \Composer\Plugin\PluginInterface, \Composer\Plugin\Capable
{
    public const PACKAGE_NAME = 'mxr576/composer-audit-changes';

    public function activate(Composer $composer, IOInterface $io): void
    {
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    public function getCapabilities(): array
    {
        return [
          'Composer\Plugin\Capability\CommandProvider' => 'mxr576\ComposerAuditChanges\Composer\CommandProvider',
        ];
    }
}
