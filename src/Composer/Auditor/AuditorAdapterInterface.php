<?php

declare(strict_types=1);

/**
 * Copyright (c) 2023-2026 Dezső Biczó
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/mxr576/composer-audit-changes/LICENSE.md
 *
 */

namespace mxr576\ComposerAuditChanges\Composer\Auditor;

use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\RepositorySet;

/**
 * Abstracts the Composer version-specific Auditor::audit() signature
 * differences so that each concrete implementation can be analysed by
 * PHPStan against only the API available in its target Composer version.
 *
 * @internal
 */
interface AuditorAdapterInterface
{
    /**
     * @param PackageInterface[] $packages
     * @param \Composer\Advisory\Auditor::FORMAT_* $format
     */
    public function audit(
        IOInterface $io,
        RepositorySet $repoSet,
        array $packages,
        string $format,
        bool $warningOnly,
    ): int;
}
