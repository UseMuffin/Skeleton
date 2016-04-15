<?php
namespace Muffin\Skeleton\Console;

use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Script\Event;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class Installer
{

    /**
     * Author's name.
     *
     * @var string
     */
    private static $author;

    /**
     * Whether to leave the `config/bootstrap.php` file or not.
     * Accepted values are `Y` and `N`.
     *
     * @var string
     */
    private static $configuration;

    /**
     * Plugin's description. Used in the `composer.json` and
     * `README.md` files.
     *
     * @var string
     */
    private static $description;

    /**
     * The GitHub relative URL.
     *
     * @var string
     */
    private static $github;

    /**
     * Whether to leave the `config/Migrations/001_init.php` file
     * or not. Accepted values are `Y` and `N`.
     *
     * @var string
     */
    private static $migrations;

    /**
     * The plugin's name (without vendor).
     *
     * @var string
     */
    private static $name;

    /**
     * The plugin's FQDN.
     *
     * @var string
     */
    private static $namespace;

    /**
     * The plugin's package name (as used by packagist.org).
     *
     * @var string
     */
    private static $package;

    /**
     * The plugin's full name (including vendor prefix).
     *
     * @var string
     */
    private static $plugin;

    /**
     * Whether to leave the `config/routes.php` file or not.
     * Accepted values are `Y` and `N`.
     *
     * @var string
     */
    private static $routes;

    /**
     * The plugin's vendor name.
     *
     * @var string
     */
    private static $vendor;

    /**
     * Pre-install hook.
     *
     * @param \Composer\Script\Event $event Composer script event.
     * @return void
     */
    public static function preInstall(Event $event)
    {
        $io = $event->getIO();

        static::_configureInstallerProperties($io);
        static::_customizeComposerFile();
        $io->write(sprintf(
            "<info>The %s plugin for CakePHP was successfully created.</info>\n",
            static::$plugin
        ));
    }

    /**
     * Post-install hook.
     *
     * Responsible of customizes all files, creates necessary directories
     * before finally auto-destructing (007-style).
     *
     * @param \Composer\Script\Event $event Composer script event.
     * @return void
     */
    public static function postInstall(Event $event = null)
    {
        $path = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR;

        static::_removeUnusedConfigurationFiles($path);
        static::_recursivelyReplacePlaceholders($path);

        unlink(__FILE__);
        rmdir(__DIR__);
    }

    /**
     * Interactively configures the installer's properties.
     *
     * @param \Composer\IO\IOInterface $io Input/output.
     * @return void
     */
    protected static function _configureInstallerProperties(IOInterface $io)
    {
        static::$author = static::_ask(
            $io,
            'What is your name?',
            'This will be used as the author\'s name in the plugin\'s composer.json file',
            static::_gitConfig('user.name')
        );

        static::$name = static::_ask(
            $io,
            'What is your plugin\'s name?',
            'Do not include any vendor prefix just yet :)'
        );

        static::$vendor = $packagist = static::_ask(
            $io,
            'What is your plugin\'s vendor name?',
            'Leave empty if not prefixing the plugin with a vendor name.'
        );

        static::$description = static::_ask(
            $io,
            'How would you describe your plugin?',
            'This is used in the plugin\'s README and composer.json file'
        );

        while (!in_array(static::$configuration, ['Y', 'N'])) {
            static::$configuration = static::_ask(
                $io,
                'Does your plugin need configuration (Y/N)?',
                null,
                'Y'
            );
        }

        while (!in_array(static::$migrations, ['Y', 'N'])) {
            static::$migrations = static::_ask(
                $io,
                'Does your plugin need database migrations (Y/N)?',
                null,
                'Y'
            );
        }


        while (!in_array(static::$routes, ['Y', 'N'])) {
            static::$routes = static::_ask(
                $io,
                'Does your plugin need custom routes (Y/N)?',
                null,
                'Y'
            );
        }

        $githubUser = static::_gitConfig('github.user');
        $dashedName = static::_dasherize(static::$name);

        if (empty($packagist)) {
            $packagist = static::_ask(
                $io,
                'What is the Packagist\'s plugin vendor name?',
                null,
                $githubUser
            );

            static::$package = sprintf(
                '%s/%s',
                static::_dasherize($packagist),
                $dashedName
            );
        }

        static::$github = sprintf('%s/%s', $githubUser, 'cakephp-' . $dashedName);
        static::$github = static::_ask(
            $io,
            'What is the GitHub\'s plugin relative URL',
            'For the skeleton repo for example, it\'s usemuffin/skeleton',
            static::$github
        );

        static::$namespace = implode("\\", [static::$name, '']);
        if (!empty(static::$vendor)) {
            static::$namespace = static::$vendor . "\\" . static::$namespace;
        }

        static::$plugin = static::$name;
        if (!empty(static::$vendor)) {
            static::$plugin = static::$vendor . '/' . static::$name;
        }

        if (empty(static::$package)) {
            static::$package = sprintf(
                '%s/%s',
                static::_dasherize(static::$vendor),
                static::_dasherize(static::$name)
            );
        }
    }

    /**
     * Removes configuration/migration/routes files when needed.
     *
     * @param string $path Plugin's absolute path.
     * @return void
     */
    protected static function _removeUnusedConfigurationFiles($path)
    {
        $configPath = $path . 'config' . DIRECTORY_SEPARATOR;

        if (static::$migrations === 'N') {
            unlink($configPath . 'Migrations' . DIRECTORY_SEPARATOR . '001_init.php');
            rmdir($configPath . 'Migrations');
        }

        if (static::$routes === 'N') {
            unlink($configPath . 'routes.php');
        }

        if (static::$configuration === 'N') {
            unlink($configPath . 'bootstrap.php');
        }

        if (static::$configuration === 'N'
            && static::$migrations === 'N'
            && static::$routes === 'N'
        ) {
            rmdir($configPath);
        }
    }

    /**
     * Loops through the plugin's folder and replaces all placeholders.
     *
     * @param string $path Plugin's absolute path.
     * @return void
     */
    protected static function _recursivelyReplacePlaceholders($path)
    {
        $directory = new RecursiveDirectoryIterator($path);
        $iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $file) {
            static::_replacePlaceholders($file);
        }
    }

    /**
     * Wrapper around the original `IOInterface::ask()` for richer output.
     *
     * @param \Composer\IO\IOInterface $io IO object.
     * @param string $question Question to ask.
     * @param null|string $comment Optional comment/description.
     * @param null|string $default Optional default value to be used.
     * @return string
     */
    protected static function _ask(IOInterface $io, $question, $comment = null, $default = null)
    {
        $f = create_function('$k, $v', 'return "\n<$k>$v</$k>\n";');
        $ask = [$f('question', $question)];

        if ($comment !== null) {
            array_push($ask, ltrim($f('comment', $comment), "\n"));
        }

        if ($default !== null) {
            array_push($ask, "\n($default):");
        }

        if ($default === null) {
            array_push($ask, "\n");
        }

        return $io->ask($ask, $default);
    }

    /**
     * Customizes the skeleton's `composer.json` file.
     *
     * @return void
     */
    protected static function _customizeComposerFile()
    {
        $githubUrl = 'https://github.com/' . static::$github;
        $file = new JsonFile(Factory::getComposerFile());
        $json = $file->read();

        unset($json['scripts']['pre-install-cmd']);
        unset($json['scripts']['post-install-cmd']);

        $json['name'] = static::$package;
        $json['description'] = static::$description;
        $json['type'] = 'cakephp-plugin';
        $json['homepage'] = $githubUrl;
        $json['authors'] = [[
            'name' => static::$author,
            'homepage' => 'https://github.com/' . static::_gitConfig('github.user'),
        ]];
        $json['support']['issues'] = $githubUrl . '/issues';
        $json['support']['source'] = $githubUrl;
        $json['autoload']['psr-4'] = [static::$namespace => "src"];
        $json['autoload-dev']['psr-4'] = [static::$namespace . "Test\\" => "tests"];

        if (static::$migrations === 'Y') {
            $json['require']['cakephp/bake'] = '^1.0';
        }

        $file->write($json);
    }

    /**
     * Replaces placeholders in given file, or entire file if it's `README.md`.
     *
     * @param \SplFileInfo $file File object.
     * @return void
     */
    protected static function _replacePlaceholders(SplFileInfo $file)
    {
        $filename = $file->getFilename();

        if ($file->isDir() || strpos($filename, '.') === 0 || !is_writable($file)) {
            return;
        }

        if ($filename === 'README.md') {
            $contents = static::README;
        }

        if (!isset($contents)) {
            $contents = file_get_contents($file);
        }

        $contents = str_replace('2016 Use Muffin', '__YEAR__ __AUTHOR__', $contents);
        $contents = str_replace('__AUTHOR__', static::$author, $contents);
        $contents = str_replace('__DESCRIPTION__', static::$description, $contents);
        $contents = str_replace('__GITHUB__', static::$github, $contents);
        $contents = str_replace('__NAME__', static::$name, $contents);
        $contents = str_replace('__NAMESPACE__', static::$namespace, $contents);
        $contents = str_replace('__PACKAGE__', static::$package, $contents);
        $contents = str_replace('__PLUGIN__', static::$plugin, $contents);
        $contents = str_replace('__YEAR__', date('Y'), $contents);
        file_put_contents($file, $contents);
    }

    /**
     * Transforms a given camelCased string into a lowercase-dashed one.
     *
     * @param string $camelCase Camel-cased string to transform.
     * @return string
     */
    protected static function _dasherize($camelCase)
    {
        $dashed = preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $camelCase);
        return strtolower($dashed);
    }

    /**
     * Gets a Git configuration value.
     *
     * @param string $key Git configuration path (i.e. `user.name`).
     * @return string
     */
    protected static function _gitConfig($key)
    {
        $value = exec('git config ' . $key);
        return $value ? trim($value) : '';
    }

    /**
     * Readme file's content.
     *
     * @var string
     */
    const README = <<<README
# __NAME__

[![Build Status](https://img.shields.io/travis/__GITHUB__/master.svg?style=flat-square)](https://travis-ci.org/__GITHUB__)
[![Coverage](https://img.shields.io/codecov/c/github/__GITHUB__.svg?style=flat-square)](https://codecov.io/github/__GITHUB__)
[![Total Downloads](https://img.shields.io/packagist/dt/__PACKAGE__.svg?style=flat-square)](https://packagist.org/packages/__PACKAGE__)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

__DESCRIPTION__

## Install

Using [Composer][composer]:

```
composer require __PACKAGE__:1.0.x-dev
```

You then need to load the plugin. You can use the shell command:

```
bin/cake plugin load __PLUGIN__
```

or by manually adding statement shown below to your app's `config/bootstrap.php`:

```php
Plugin::load('__PLUGIN__');
```

## Usage

{{@TODO documentation}}

## Patches & Features

* Fork
* Mod, fix
* Test - this is important, so it's not unintentionally broken
* Commit - do not mess with license, todo, version, etc. (if you do change any, bump them into commits of
their own that I can ignore when I pull)
* Pull request - bonus point for topic branches

To ensure your PRs are considered for upstream, you MUST follow the [CakePHP coding standards][standards].

## Bugs & Feedback

http://github.com/__GITHUB__/issues

## License

Copyright (c) __YEAR__, __AUTHOR__ and licensed under [The MIT License][mit].

[cakephp]:http://cakephp.org
[composer]:http://getcomposer.org
[mit]:http://www.opensource.org/licenses/mit-license.php
[standards]:http://book.cakephp.org/3.0/en/contributing/cakephp-coding-conventions.html
README;
}
