<?php

$srcRoot = "src";
$buildRoot = "build";

$phar = new Phar("wpupload.phar",
    FilesystemIterator::CURRENT_AS_FILEINFO |       FilesystemIterator::KEY_AS_FILENAME, "wpupload.phar");

$phar["index.php"] = file_get_contents($srcRoot . "/index.php");
$phar["args.php"] = file_get_contents($srcRoot . "/lib/args.php");
$phar["progress.php"] = file_get_contents($srcRoot . "/lib/vendor/progressbar.php");
$phar["conf.ini"] = file_get_contents($buildRoot . "/conf.ini");
$phar->addEmptyDir('.tmp');

$phar->startBuffering();

$default_stub = $phar->createDefaultStub("index.php");

$stub = "#!/usr/bin/env php \n" . $default_stub;

$phar->setStub($stub);

$phar->stopBuffering();

