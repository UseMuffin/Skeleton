# {{pluginName}}

[![Build Status](https://img.shields.io/travis/UseMuffin/{{pluginName}}/master.svg?style=flat-square)](https://travis-ci.org/UseMuffin/{{pluginName}})
[![Coverage](https://img.shields.io/coveralls/UseMuffin/{{pluginName}}/master.svg?style=flat-square)](https://coveralls.io/r/UseMuffin/{{pluginName}})
[![Total Downloads](https://img.shields.io/packagist/dt/muffin/{{lcPluginName}}.svg?style=flat-square)](https://packagist.org/packages/muffin/{{lcPluginName}})
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

{{@TODO description}}

## Install

Using [Composer][composer]:

```
composer require muffin/{{lcPluginName}}:1.0.x-dev
```

You then need to load the plugin. You can use the shell command:

```
bin/cake plugin load Muffin/{{pluginName}}
```

or by manually adding statement shown below to `bootstrap.php`:

```php
Plugin::load('Muffin/{{pluginName}}');
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

http://github.com/usemuffin/{{lcPluginName}}/issues

## License

Copyright (c) 2015, [Use Muffin][muffin] and licensed under [The MIT License][mit].

[cakephp]:http://cakephp.org
[composer]:http://getcomposer.org
[mit]:http://www.opensource.org/licenses/mit-license.php
[muffin]:http://usemuffin.com
[standards]:http://book.cakephp.org/3.0/en/contributing/cakephp-coding-conventions.html
