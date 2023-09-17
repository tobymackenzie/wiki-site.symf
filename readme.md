TJM Wiki Site
=======

Web-site front-end interface for a folder of markdown files, optionally managed with `tjm/wiki`, using Symfony.

Usage
-----

You can use this repo directly by creating a `wiki` directory and storing your markdown files in there.  You would set up `web` as the web root and rewrite requests through `index.php`.  However, you're more likely to have merge conflicts with upstream with this method.

Better would be to use composer to `composer require tjm/wiki-site`.  Then you can create your own php file with contents much like `web/index.php`, but passing a config file path as the argument to the `Kernel`, like:

``` php
<?php
namespace TJM\WikiSite;
require_once __DIR__ . '/../vendor/autoload.php';
(new Kernel(__DIR__ . '/../config.yml'))->run();
```

You would again set up your web server to rewrite requests to go through this file.

This repo is set up as a Symfony bundle, and may be used in an existing Symfony application.  See the `config` directory for settings.

License
------

<footer>
<p>SPDX-License-Identifier: 0BSD</p>
</footer>
