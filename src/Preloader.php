<?php
namespace kilyakus\imageprocessor;

use Yii;
use yii\helpers\Html;

class Preloader
{
    static function init($filename, $width = null, $height = null, $percent = 1.5, $options = [])
    {
        $attributes = self::__getAttributes($filename, $width, $height, $percent, $options);

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

        $attributes = isset($options['class']) ?
            array_merge($attributes,$options['class']) : $attributes;

        $view = Yii::$app->view;

        PreloaderAsset::register($view);

        $view->registerJs("$('#" . $attributes['id'] . "').addClass('".$attributes['class']."')",$view::POS_END);

        return $attributes;
    }
}