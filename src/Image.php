<?php
namespace kilyakus\imageprocessor;

use Yii;
use yii\web\UploadedFile;
use yii\web\HttpException;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\helpers\FileHelper;

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

    public function copyImage($image, $path)
    {
        $uploadUrl = '/' . Upload::$UPLOADS_DIR . '/' . $path . '/';
        
        $path = $uploadUrl . self::parseName($image);

        if (self::fileExists($image)) {

            if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $uploadUrl)) { 
                mkdir($_SERVER['DOCUMENT_ROOT'] . $uploadUrl, 0777, true);
            }

            copy($image, $_SERVER['DOCUMENT_ROOT'] . $path);

            return $path;

        } else {

            return false;

        }
    }

    public function parseName($path)
    {
        $info = pathinfo($path);

        $basename = Inflector::slug($info['filename']);

        if(!$info['extension']){

            $info = curl_file_create($path, 'image/png', $basename . '.png');

            $path = $basename . '-' . md5($path) . '.png';

        }else{

            $path = $basename . '-' . md5($path) . '.' . $info['extension'];

        }

        return $path;
    }


    public function fileExists($path)
    {
        return (@fopen($path, "r") == true);
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

        $filename = '/' . Upload::$UPLOADS_DIR . '/thumbs/blur-'.md5($filename).'.jpeg';
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

    public static  function bump($filename,$w = null,$h = null,$percent = 1.5)
    {
        if(!file_exists($filename) && !file_exists(Yii::getAlias('@webroot') . $filename)){
            return false;
        }
        
        if(!$w){
            $w = 480;
        }

        $filename = Image::thumb($filename, ($w/$percent), ($h/$percent));

        $file = Yii::getAlias('@webroot').$filename;

        $filename = '/' . Upload::$UPLOADS_DIR . '/thumbs/bump-'.md5($filename).'.jpeg';
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
                
                imagefilter($result, IMG_FILTER_MEAN_REMOVAL);

                imagefilter($result, IMG_FILTER_GRAYSCALE);

                // imagefilter($result, IMG_FILTER_CONTRAST,75);

                // imagefilter($result, IMG_FILTER_BRIGHTNESS,70);

                imagefilter($result, IMG_FILTER_CONTRAST,-35);

                imagefilter($result, IMG_FILTER_BRIGHTNESS,230);
                imagefilter($result, IMG_FILTER_NEGATE);

                for ($x=1; $x<=15; $x++){
                    imagefilter($result, IMG_FILTER_GAUSSIAN_BLUR,999);
                } 

                imagefilter($result, IMG_FILTER_SMOOTH,99);
                
                imagejpeg($result, $temp);

                imagedestroy($result);
            }
        }

        return Image::thumb($filename, $w, $h);
    }
}