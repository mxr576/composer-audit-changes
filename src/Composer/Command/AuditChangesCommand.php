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

namespace mxr576\ComposerAuditChanges\Composer\Command;

use Composer\Advisory\Auditor;
use Composer\Command\BaseCommand;
use Composer\Composer;
use Composer\Console\Input\InputOption;
use Composer\Json\JsonFile;
use Composer\Package\AliasPackage;
use Composer\Package\CompleteAliasPackage;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Locker;
use Composer\Repository\LockArrayRepository;
use Composer\Repository\RepositorySet;
use Composer\Semver\Constraint\MatchAllConstraint;
use Composer\Util\ProcessExecutor;
use Seld\JsonLint\ParsingException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
final class AuditChangesCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('audit-changes');
        $this->setDescription('Only audits those packages that were installed or updated since a previous version of composer.lock');
        $this->setDefinition([
          new InputArgument('base', InputArgument::OPTIONAL, 'A file path, URL or GIT reference for retrieving the base (original) composer.lock file.', 'HEAD:composer.lock'),
          new InputOption('no-dev', null, InputOption::VALUE_NONE, 'Disables auditing of require-dev packages.'),
          new InputOption('format', 'f', InputOption::VALUE_REQUIRED, 'Output format. Must be "table", "plain", "json", or "summary".', Auditor::FORMAT_TABLE, Auditor::FORMATS),
          new InputOption('locked', null, InputOption::VALUE_NONE, 'Audit based on the lock file instead of the installed packages.'),
        ])
          ->setHelp(
              <<<EOT
The <info>audit-changes</info> command works similarly to the built-in <info>audit</info> command but only audits newly
added or updated packages since a previous version of composer.lock.

If you do not want to include dev dependencies in the audit you can omit them with --no-dev

Read more at https://getcomposer.org/doc/03-cli.md#audit
EOT
          );
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $composer = $this->requireComposer();
        $packages = $this->getPackages($composer, $input);

        if (0 === count($packages)) {
            $this->getIO()->writeError('No packages - skipping audit.');

            return 0;
        }

        $auditor = new Auditor();
        $repoSet = new RepositorySet();
        foreach ($composer->getRepositoryManager()->getRepositories() as $repo) {
            $repoSet->addRepository($repo);
        }

        return min(255, $auditor->audit($this->getIO(), $repoSet, $packages, $this->getAuditFormat($input, 'format'), false));
    }

    /**
     * Gets packages to be audited.
     *
     * @throws \RuntimeException
     *
     * @return \Composer\Package\PackageInterface[]
     *   List of packages.
     */
    private function getPackages(Composer $composer, InputInterface $input): array
    {
        $packages = [];
        if (!$composer->getLocker()->isLocked()) {
            throw new \UnexpectedValueException('Valid composer.json and composer.lock files are required. Run "composer update --lock" and make sure the lock file is committed.');
        }

        $originalComposerLockRef = $input->getArgument('base');
        assert(is_string($originalComposerLockRef));

        $currentLockerRepository = $composer->getLocker()->getLockedRepository();
        $originalLockRepository = $this->lockRepositoryFactory($this->getFileContent($originalComposerLockRef), !$input->getOption('no-dev'));

        foreach ($currentLockerRepository->getPackages() as $currentPackage) {
            if ($currentPackage instanceof AliasPackage) {
                continue;
            }

            $originalPackage = $originalLockRepository->findPackage($currentPackage->getName(), new MatchAllConstraint());
            if (null === $originalPackage) {
                $packages[] = $currentPackage;
            } else {
                if ($originalPackage instanceof AliasPackage) {
                    continue;
                }

                if ($originalPackage->getFullPrettyVersion() !== $currentPackage->getFullPrettyVersion()) {
                    $packages[] = $currentPackage;
                }
            }
        }

        return $packages;
    }

    /**
     * Builds a Locker repository the same way as Locker service does today.
     *
     * It would have been better building a Locker object for and retrieving
     * the repository form there (no copy-paste coding) but the object only
     * accepts a new JsonFile object as parameter, and it can only be built from a
     * file, not from a string, today.
     *
     * @see \Composer\Package\Locker::getLockedRepository()
     *
     * @throws \RuntimeException
     */
    private function lockRepositoryFactory(string $composerLockContent, bool $withDevReqs): LockArrayRepository
    {
        $packages = new LockArrayRepository();
        $lockData = JsonFile::parseJson($composerLockContent);

        $lockedPackages = $lockData['packages'];
        if ($withDevReqs) {
            if (isset($lockData['packages-dev'])) {
                $lockedPackages = array_merge($lockedPackages, $lockData['packages-dev']);
            } else {
                throw new \RuntimeException('The lock file does not contain require-dev information, run install with the --no-dev option or delete it and run composer update to generate a new lock file.');
            }
        }

        if (empty($lockedPackages)) {
            return $packages;
        }

        $loader = new ArrayLoader(null, true);

        if (isset($lockedPackages[0]['name'])) {
            $packageByName = [];
            foreach ($lockedPackages as $info) {
                $package = $loader->load($info);
                $packages->addPackage($package);
                $packageByName[$package->getName()] = $package;

                if ($package instanceof AliasPackage) {
                    $packageByName[$package->getAliasOf()->getName()] = $package->getAliasOf();
                }
            }

            if (isset($lockData['aliases'])) {
                foreach ($lockData['aliases'] as $alias) {
                    if (isset($packageByName[$alias['package']])) {
                        $aliasPkg = new CompleteAliasPackage($packageByName[$alias['package']], $alias['alias_normalized'], $alias['alias']);
                        $aliasPkg->setRootPackageAlias(true);
                        $packages->addPackage($aliasPkg);
                    }
                }
            }

            return $packages;
        }

        throw new \RuntimeException('Your composer.lock is invalid. Run "composer update" to generate a new one.');
    }

    /**
     * Gets a content of a file.
     *
     * Was inspired by \IonBazan\ComposerDiff\PackageDiff::getFileContents().
     *
     * @param string $ref
     *   An URL, file path or a GIT reference.
     *
     * @throws \RuntimeException
     *
     * @return string
     *   The file content.
     */
    private function getFileContent(string $ref): string
    {
        $original = $ref;
        if (filter_var($ref, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED) || file_exists($ref)) {
            $file = file_get_contents($ref);
            if (false === $file) {
                throw new \RuntimeException(sprintf('Could not open file at "%s".', $original));
            }

            return $file;
        }

        $output = null;
        $process_executor = new ProcessExecutor();
        $exit_code = $process_executor->execute(sprintf('git show %s 2>&1', ProcessExecutor::escape($ref)), $output);

        if (0 !== $exit_code) {
            throw new \RuntimeException(sprintf('Could not open file %s or find it in git as %s: %s.', $original, $ref, $output));
        }

        if (null === $output) {
            throw new \RuntimeException('Output is null, that should not happen.');
        }

        // Even if "git show" exited with a non-zero exit code, the produced
        // can be still invalid. For example, when the HEAD@{YYYY-MM-DD} reference
        // is used and the date is older than the available history it yields
        // a warning like "warning: log for 'HEAD' only goes back to ... " on
        // STDERR - that we also capture to be able to expose command output to
        // callers on failure.
        try {
            // For consistency reasons, use the same JSON parser as Composer uses
            // everywhere.
            JsonFile::parseJson($output);
        } catch (ParsingException) {
            throw new \RuntimeException(sprintf('Malformed JSON returned by "git show". %s.', \PHP_EOL . $output));
        }

        return $output;
    }
}
