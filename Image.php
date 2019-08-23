<?php
namespace kilyakus\helpers;

use Yii;
use yii\web\UploadedFile;
use yii\web\HttpException;
use yii\helpers\FileHelper;
use yii\helpers\Url;
use yii\helpers\Html;

class Image
{
    public static function upload(UploadedFile $fileInstance, $dir = '', $resizeWidth = null, $resizeHeight = null, $resizeCrop = false)
    {
        $fileName = Upload::getUploadPath($dir) . DIRECTORY_SEPARATOR . Upload::getFileName($fileInstance);

        $uploaded = $resizeWidth
            ? self::copyResizedImage($fileInstance->tempName, $fileName, $resizeWidth, $resizeHeight, $resizeCrop)
            : $fileInstance->saveAs($fileName);

        if(!$uploaded){
            throw new HttpException(500, 'Cannot upload file "'.$fileName.'". Please check write permissions.');
        }

        return Upload::getLink($fileName);
    }

    static function thumb($filename, $width = null, $height = null, $crop = true)
    {
        if(!($filename && is_file(($filename = Yii::getAlias('@webroot') . $filename))))
        {
            $filename = __DIR__ . DIRECTORY_SEPARATOR . 'noimage.png';
        }

        $info = pathinfo($filename);
        $thumbName = $info['filename'] . '-' . md5( filemtime($filename) . (int)$width . (int)$height . (int)$crop ) . '.' . $info['extension'];
        $thumbFile = Yii::getAlias('@webroot') . DIRECTORY_SEPARATOR . Upload::$UPLOADS_DIR . DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR . $thumbName;
        $thumbWebFile = '/' . Upload::$UPLOADS_DIR . '/thumbs/' . $thumbName;
        if(file_exists($thumbFile)){
            return $thumbWebFile;
        }
        elseif(FileHelper::createDirectory(dirname($thumbFile), 0777) && self::copyResizedImage($filename, $thumbFile, $width, $height, $crop)){
            return $thumbWebFile;
        }

        return '';
    }

    static function copyResizedImage($inputFile, $outputFile, $width, $height = null, $crop = true)
    {
        if (extension_loaded('gd'))
        {
            $image = new GD($inputFile);

            if($height) {
                if($width && $crop){
                    $image->cropThumbnail($width, $height);
                } else {
                    $image->resize($width, $height);
                }
            } else {
                $image->resize($width);
            }
            return $image->save($outputFile);
        }
        elseif(extension_loaded('imagick'))
        {
            $image = new \Imagick($inputFile);

            if($height && !$crop) {
                $image->resizeImage($width, $height, \Imagick::FILTER_LANCZOS, 1, true);
            }
            else{
                $image->resizeImage($width, null, \Imagick::FILTER_LANCZOS, 1);
            }

            if($height && $crop){
                $image->cropThumbnailImage($width, $height);
            }

            return $image->writeImage($outputFile);
        }
        else {
            throw new HttpException(500, 'Please install GD or Imagick extension');
        }
    }

    public static  function blur($filename,$w = null,$h = null,$percent = 1.5)
    {
        if(!file_exists($filename) && !file_exists(Yii::getAlias('@webroot') . $filename)){
            return false;
        }
        
        if(!$w){
            $w = 480;
        }

        $filename = Image::thumb($filename, ($w/$percent), ($h/$percent));

        $file = Yii::getAlias('@webroot').$filename;
        $tmp = md5($filename);
        $filename = '/' . Upload::$UPLOADS_DIR . '/thumbs/'.$tmp.'.jpeg';
        $temp = Yii::getAlias('@webroot').$filename;
        if(!file_exists($temp)){
            copy($file, $temp);
            if (exif_imagetype($temp) == IMAGETYPE_JPEG) {
                $result = imagecreatefromjpeg($temp);
            }
            else if (exif_imagetype($temp) == IMAGETYPE_PNG) {
                $result = imagecreatefrompng($temp);
            }
            if($result){
                for ($x=1; $x<=10; $x++){
                    imagefilter($result, IMG_FILTER_GAUSSIAN_BLUR,999);
                } 
                imagefilter($result, IMG_FILTER_SMOOTH,99);

                imagejpeg($result, $temp);

                imagedestroy($result);
            }
        }

        return Image::thumb($filename, $w, $h);
    }

    static function preload($filename, $width = null, $height = null, $percent = 1.5, $options = [])
    {
        $filename = self::thumb($filename, $width, $height, true);

        $container = 'preload-' . substr(md5($filename), 0, 6);

        echo Html::tag('div','',[
            'id' => $container,
            'class' => 'img preload '.$options['class'],
            'data-image' => $filename,
            'style' => 'background-image:url('.self::blur($filename,$width,$height,$percent).');'
        ]);

        $view = Yii::$app->view;

        $view->registerCss('#'.$container.' {width:'.$width.'px;height:'.$height.'px;}');

        $view->registerJs("var list = document.querySelectorAll('[data-image]');for(var i=0;i<list.length;i++) {var el = list[i], url=el.getAttribute('data-image');if($(el).hasClass('preload')){jQuery(window).bind('load',function(){if(getImage(url)){preload(el,url);}})}};function preload(el,url){el.style.backgroundImage='url(\"' + url + '\"),'+el.style.backgroundImage;$(el).removeClass('preload');$(el).removeAttr('data-image');}function getImage(url){return new Promise(function(resolve,reject){var img=new Image();img.onload=function(){resolve(url);};img.onerror=function(){reject(url);};img.src=url;});}setTimeout(function(){for(var i=0;i<list.length;i++) {var el = list[i], url=el.getAttribute('data-image');if($(el).hasClass('preload')){if(preload(el,url)){console.log($(el))}}};},3500)",$view::POS_READY,'helpers-image');
        $view->registerCss('.preload{position:relative;}.preload:before {content:\'\';position:absolute;width:75px;height:75px;left:0;right:0;bottom:0;top:0;margin:auto;border-radius:50%;background-color:transparent;border:2px solid #222;border-top:2px solid #03A9F4;-webkit-animation:1s helper-preloader linear infinite;animation: 1s helper-preloader linear infinite;box-shadow:0 0 15px rgba(0,0,0,0.15);}.preview-image{width:0px!important;}.preview-image[src]{width:100%!important;}@keyframes helper-preloader{from{-webkit-transform:rotate(0deg);transform:rotate(0deg);}to{-webkit-transform:rotate(360deg);transform:rotate(360deg);}}',["type" => "text/css"],'helpers-image');

    }
}