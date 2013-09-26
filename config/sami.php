<?php

use Sami\Sami;
use Sami\Version\GitVersionCollection;
use Symfony\Component\Finder\Finder;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->exclude('Tests')
    ->in($dir = __DIR__ . '/../src')
;

$versions = GitVersionCollection::create($dir)
    ->add('master', 'master branch')
    // ->addFromTags('v0.1.*')
;

return new Sami($iterator, array(
    // 'theme'                => 'default',
    'versions'             => $versions,
    'title'                => 'Opsone utils',
    'build_dir'            => __DIR__ . '/../build/%version%',
    'cache_dir'            => __DIR__ . '/../cache/sami/%version%',
    'default_opened_level' => 2,
));

