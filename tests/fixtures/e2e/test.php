#!/usr/bin/env php
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

$audit_output = $argv[1] ?? (stream_get_contents(STDIN) ?: null);
if (null === $audit_output) {
    throw new LogicException('Missing "composer audit" command output.');
}

fwrite(STDERR, $audit_output);

try {
    $audit_result = json_decode($audit_output, true, flags: JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    throw new LogicException(sprintf('Malformed JSON input: "%s". %s', base64_encode(gzdeflate($audit_output, 9)), $e->getMessage()), 0, $e);
}

assert(array_key_exists('drupal/core', $audit_result['advisories']), 'drupal/core was updated so it is flagged');

assert(array_key_exists('laminas/laminas-diactoros', $audit_result['advisories']), 'laminas/laminas-diactoros was updated as a drupal/core dependency so it is flagged');

assert(array_key_exists('swiftmailer/swiftmailer', $audit_result['advisories']), 'swiftmailer/swiftmailer was installed so it is flagged');
