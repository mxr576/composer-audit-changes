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
use Composer\FilterList\FilterListProvider\FilterListProviderSet;
use Composer\IO\IOInterface;
use Composer\Policy\PolicyConfig;
use Composer\Repository\RepositoryManager;
use Composer\Repository\RepositorySet;
use Composer\Util\HttpDownloader;

/**
 * Audit adapter for Composer >= 2.10.0.
 *
 * @see https://github.com/composer/composer/commit/37825e9851291b969be52cf98894af17505f2766
 *
 * @internal
 */
final class PolicyConfigAuditorAdapter implements AuditorAdapterInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly RepositoryManager $repositoryManager,
        private readonly HttpDownloader $httpDownloader,
    ) {
    }

    public function audit(
        IOInterface $io,
        RepositorySet $repoSet,
        array $packages,
        string $format,
        bool $warningOnly,
    ): int {
        $policyConfig = PolicyConfig::fromConfig($this->config);

        $filterListProviderSet = $policyConfig->enabled
            ? FilterListProviderSet::create(
                $policyConfig,
                $this->repositoryManager->getRepositories(),
                $this->httpDownloader,
            )
            : null;

        $auditor = new Auditor();

        return $auditor->audit(
            $io,
            $repoSet,
            $policyConfig,
            $packages,
            $format,
            $warningOnly,
            $filterListProviderSet,
        );
    }
}
