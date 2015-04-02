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

$phar->setStub($phar->createDefaultStub("index.php"));
