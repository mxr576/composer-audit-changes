<?php

declare(strict_types=1);

/**
 * Copyright (c) 2023 Dezső Biczó
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/mxr576/composer-audit-changes/LICENSE.md
 *
 */

namespace mxr576\ComposerAuditChanges\Composer;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use mxr576\ComposerAuditChanges\Composer\Command\AuditChangesCommand;

final class CommandProvider implements CommandProviderCapability
{
    public function getCommands(): array
    {
        return [
          new AuditChangesCommand(),
        ];
    }
}
