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

use Composer\Advisory\Auditor;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Repository\RepositorySet;

/**
 * Audit adapter for Composer < 2.10.0.
 *
 * @internal
 */
final class LegacyAuditorAdapter implements AuditorAdapterInterface
{
    public function __construct(private readonly Config $config)
    {
    }

    /**
     * @throws \RuntimeException
     */
    public function audit(
        IOInterface $io,
        RepositorySet $repoSet,
        array $packages,
        string $format,
        bool $warningOnly,
    ): int {
        $auditConfig = $this->config->get('audit');
        if (!is_array($auditConfig)) {
            $auditConfig = [];
        }

        $auditor = new Auditor();

        return $auditor->audit(
            $io,
            $repoSet,
            $packages,
            $format,
            $warningOnly,
            $auditConfig['ignore'],
            $auditConfig['abandoned'] ?? Auditor::ABANDONED_REPORT,
        );
    }
}
