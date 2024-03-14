<?php

declare(strict_types=1);

/**
 * Copyright (c) 2023-2024 Dezső Biczó
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

    private bool $disabled = false;

    public function activate(Composer $composer, IOInterface $io): void
    {
        // This is a runtime check instead of a dependency constraint to allow
        // installation of this package on projects where composer/composer also
        // installed as a project dependency (but a global version used instead).
        if (version_compare($composer::getVersion(), '2.4.0', '<')) {
            // By pushing this to STDERR we guarantee that the output on
            // STDOUT (e.g, in JSON) do not get malformed when it is captured.
            $io->writeError(sprintf('%s is disabled because audit command is only available since Composer 2.4.0. Your version is: %s.', self::PACKAGE_NAME, $composer::getVersion()));

            $this->disabled = true;
        }
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    public function getCapabilities(): array
    {
        if ($this->disabled) {
            return [];
        }

        return [
          'Composer\Plugin\Capability\CommandProvider' => 'mxr576\ComposerAuditChanges\Composer\CommandProvider',
        ];
    }
}
