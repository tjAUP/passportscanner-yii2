<?php

namespace TjAup\PassportScanner\Yii2;

use Composer\InstalledVersions;
use yii\web\AssetBundle;

/** Publishes the browser scanner and its complete local OCR asset tree. */
class PassportScannerAsset extends AssetBundle
{
    public $js = [
        'passport-scanner.js',
    ];

    public function init()
    {
        $this->sourcePath = InstalledVersions::getInstallPath('tjaup/passportscanner') . '/dist';
        parent::init();
    }
}
