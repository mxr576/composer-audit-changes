<?php

/**
 * Dynamic PHPStan include file — adapts analysis to the installed
 * composer/composer version.
 */

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

if (PHP_SAPI !== 'cli') {
    return [];
}

use Composer\InstalledVersions;
use Composer\Semver\Semver;

$composerVersion = InstalledVersions::getVersion('composer/composer');
assert(is_string($composerVersion));

$config = [];

// Composer\Policy\PolicyConfig does not exist before 2.10.0 and the signature
// off audit() method is different.
if (Semver::satisfies($composerVersion, '<2.10.0')) {
    $config['parameters']['excludePaths']['analyseAndScan'][] = __DIR__ . '/src/Composer/Auditor/PolicyConfigAuditorAdapter.php';
    $config['includes'][] = __DIR__ . '/phpstan-legacy-baseline.neon';
} else {
    $config['parameters']['excludePaths']['analyseAndScan'][] = __DIR__ . '/src/Composer/Auditor/LegacyAuditorAdapter.php';
}

return $config;
