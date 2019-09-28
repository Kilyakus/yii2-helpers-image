<?php
namespace kilyakus\imageprocessor;

use yii\web\AssetBundle;

class PreloaderAsset extends AssetBundle
{
    public $depends = [
        'yii\web\JqueryAsset'
    ];

    public function init()
    {
        $this->sourcePath = __DIR__ . '/assets/preloader';

        $this->js[] = 'js/preloader.js';
        $this->css[] = 'css/preloader.css';
        
        parent::init();
    }
}
