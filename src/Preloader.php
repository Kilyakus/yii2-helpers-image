<?php
namespace kilyakus\imageprocessor;

use Yii;
use yii\helpers\Html;

class Preloader
{
    static function setContainer($filename, $width = null, $height = null, $percent = 1.5, $options = [])
    {
        $filename = Image::thumb($filename, $width, $height, true);

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

        $container = 'preload-' . substr(md5($filename), 0, 6);

        $attributes = [
            'id' => $container,
            'data-image' => $filename,
            'style' => 'background-image:url(' . Image::blur($filename,$width,$height,$percent) . ');'
        ];

        $view = Yii::$app->view;

        $view->registerJs("$('#".$container."').addClass('preload');",$view::POS_READY);

        self::__registerAssets();

        return $attributes;
    }

    static function __registerAssets()
    {
        $view = Yii::$app->view;

        $view->registerJs("var list = document.querySelectorAll('[data-image]');for(var i=0;i<list.length;i++) {var el = list[i], url=el.getAttribute('data-image');if($(el).hasClass('preload')){jQuery(window).bind('load',function(){if(getImage(url)){preload(el,url);}})}};function preload(el,url){el.style.backgroundImage='url(\"' + url + '\"),'+el.style.backgroundImage;$(el).removeClass('preload');$(el).removeAttr('data-image');}function getImage(url){return new Promise(function(resolve,reject){var img=new Image();img.onload=function(){resolve(url);};img.onerror=function(){reject(url);};img.src=url;});}setTimeout(function(){for(var i=0;i<list.length;i++) {var el = list[i], url=el.getAttribute('data-image');if($(el).hasClass('preload')){if(preload(el,url)){console.log($(el))}}};},3500)",$view::POS_READY,'helpers-image');
        $view->registerCss('.preload{position:relative;}.preload:before {content:\'\';position:absolute;width:75px;height:75px;left:0;right:0;bottom:0;top:0;margin:auto;border-radius:50%;background-color:transparent;border:2px solid #222;border-top:2px solid #03A9F4;-webkit-animation:1s helper-preloader linear infinite;animation: 1s helper-preloader linear infinite;box-shadow:0 0 15px rgba(0,0,0,0.15);}.preview-image{width:0px!important;}.preview-image[src]{width:100%!important;}@keyframes helper-preloader{from{-webkit-transform:rotate(0deg);transform:rotate(0deg);}to{-webkit-transform:rotate(360deg);transform:rotate(360deg);}}',["type" => "text/css"],'helpers-image');
    }
}