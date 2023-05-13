`composer audit-changes` only
---

The `audit-changes` Composer command works similarly to the built-in `composer audit` command but it only audits newly
installed or updated packages since a previous version of composer.lock.

### Why

Have you seen a pending CR/MR/PR before that was blocked because a security advisory has just been released for a
existing dependency?

This solution can be ideal for auditing only those package changes that were made in a CR/MR/PR but not the complete
content on composer.lock.

## Installation

```shell
$ composer require --dev mxr576/composer-audit-changes
```

### Usage

```shell
$ composer audit-changes [path-or-url-or-git-reference-to-previous-version-of-composer-lock] # the default is HEAD:composer.lock
```

Run `composer audit-changes --help` to see available command arguments and options.

### Background story

This package was created to showcase that maybe there is a better alternative for handling randomly failing builds
than adding an opt-out feature to `composer audit`. See the related issue feature request at https://github.com/composer/composer/issues/11298.
