<?php
namespace kilyakus\imageprocessor;

use Yii;
use yii\helpers\Html;

class Preloader
{
    static function get($filename, $width = null, $height = null, $percent = 1.5, $options = [])
    {
        $attributes = self::__getAttributes($filename, $width, $height, $percent, $options);

        $attributes = array_merge($attributes,['class' => 'img ' . $options['class']]);

        $view = Yii::$app->view;

        $view->registerCss('#' . $attributes['id'] . ' {width:'.$width.'px;height:'.$height.'px;}');

        echo Html::tag('div','',$attributes);
    }

    static function setAttributes($filename, $width = null, $height = null, $percent = 1.5, $options = [])
    {
        $attributes = self::__getAttributes($filename, $width, $height, $percent, $options);

        echo Html::renderTagAttributes($attributes);
    }

    static function __getAttributes($filename, $width = null, $height = null, $percent = 1.5, $options = [])
    {
        $filename = Image::thumb($filename, $width, $height, true);

        $container = 'p-' . substr(md5($filename), 0, 6) . rand(1000,10);

        $attributes = [
            'id' => $container,
            'data-image' => $filename,
            'style' => 'background-image:url(' . Image::blur($filename,$width,$height,$percent) . ');'
        ];

        $view = Yii::$app->view;

        $view->registerJs("$('#".$container."').addClass('preload');",$view::POS_READY);

        PreloaderAsset::register($view);

        return $attributes;
    }
}