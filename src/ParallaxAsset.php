<?php
namespace kilyakus\imageprocessor;

use yii\web\AssetBundle;

class ParallaxAsset extends AssetBundle
{
    public $depends = [
        'yii\web\JqueryAsset'
    ];

    public function init()
    {
        $this->sourcePath = __DIR__ . '/assets/parallax';

        $this->js[] = 'js/app.js';
        // $this->js[] = 'js/src.js';
        
        parent::init();
    }
}
