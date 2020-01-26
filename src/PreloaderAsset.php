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

        $this->js[] = 'js/widget-preloader.min.js';
        $this->css[] = 'css/widget-preloader.min.css';
        
        parent::init();
    }
}
