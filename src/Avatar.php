<?php
namespace kilyakus\imageprocessor;

use Yii;
use yii\web\UploadedFile;
use yii\web\HttpException;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\helpers\Inflector;

class Avatar
{
    static function get($filename, $text = null)
    {
        if(!($filename && is_file(($filename = Yii::getAlias('@webroot') . $filename))))
        {
            $text = trim($text);
            
            if(count(explode(' ', $text)) > 1){
                $text = explode(' ', $text);
                $letters = [];
                for ($i=0; $i < 2; $i++) { 
                    $letters[] = substr(Inflector::slug($text[$i]), 0, 1);
                }
                $text = implode('',$letters);
            }else{
                $text = substr($text, 0, 2);
            }
            $text = mb_strtoupper($text);

            if(!is_file($filename = Yii::getAlias('@webroot') . DIRECTORY_SEPARATOR . Upload::$UPLOADS_DIR . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . Inflector::slug($text) .'.png')){

                $im = imagecreatetruecolor(300, 300);

                $FONT = __DIR__ . '/assets/avatar/fonts/text.ttf';

                if($text == null){
                    $r = 0 & 0xFF;
                    $g = 0 & 0xFF;
                    $b = 0 & 0xFF;
                }else{
                    $r = rand(60, 180) & 0xFF;
                    $g = rand(60, 180) & 0xFF;
                    $b = rand(60, 180) & 0xFF;
                }

                imagefill($im, 1, 1, imagecolorallocate($im, $r, $g, $b ));

                $box = imagettfbbox(100, 0, $FONT, $text);

                $left = 140-round(($box[2]-$box[0])/2);

                imagettftext($im, 100, 0, $left, 195, imagecolorallocate($im, 0xf8, 0xf8, 0xfb), $FONT, $text);

                imagepng($im, Yii::getAlias('@webroot') . DIRECTORY_SEPARATOR . Upload::$UPLOADS_DIR . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . Inflector::slug($text) .'.png');
                 
                imagedestroy($im);

                $filename = Yii::getAlias('@webroot') . '/' . Upload::$UPLOADS_DIR . '/avatars/' . Inflector::slug($text) .'.png';

            }
        }

        $info = pathinfo($filename);
        $thumbName = $info['filename'] . '-' . md5( filemtime($filename) . (int)$width . (int)$height . (int)$crop ) . '.' . $info['extension'];
        $thumbFile = Yii::getAlias('@webroot') . DIRECTORY_SEPARATOR . Upload::$UPLOADS_DIR . DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR . $thumbName;
        $thumbWebFile = '/' . Upload::$UPLOADS_DIR . '/thumbs/' . $thumbName;
        if(file_exists($thumbFile)){
            return $thumbWebFile;
        }
        elseif(FileHelper::createDirectory(dirname($thumbFile), 0777) && Image::copyResizedImage($filename, $thumbFile, $width, $height, $crop)){
            return $thumbWebFile;
        }

        return '';
    }

    static function merge($files, $size = 300)
    {
        $width = $height = $size;
        $resImage = imagecreatetruecolor($width, $height);

        $count = count($files);

        $filename = '';

        for ($i = 0; $i < $count; $i++) {

            if($i > 4){
                break;
            }

            $info = pathinfo($files[$i]);
            $image = Yii::getAlias('@webroot') . $files[$i];
            $filename .= md5( $info['filename'] . '-' . filemtime($image) . (int)$width . (int)$height );

            $image = imagecreatefromstring(file_get_contents($image));
            $srcWidth = imagesx($image);
            $srcHeight = imagesy($image);

            if($count == 1){

                $x = ($i == 1 || $i == 2 ? $width / 2 : 0);
                $y = ($i > 1 ? $height / 2 : 0);
                $h2 = intval(($i == 0) ? $height : $height / 2);
                $w2 = intval(($i == 0) ? $width : $width / 2);

            }elseif($count == 2){

                $x = ($i == 1 ? ($width / 2) : 0);
                $y = ($i > 1 ? $height / 2 : 0);
                $h2 = intval($height);
                $w2 = intval($width / 2);

            }elseif($count == 3){

                $x = ($i == 1 || $i == 2 ? $width / 2 : -($width / 7));
                $y = ($i > 1 ? $height / 2 : 0);
                $h2 = intval(($i == 0) ? $height : $height / 2);
                $w2 = intval(($i == 0) ? $width / 1.3 : $width / 2);

            }elseif($count > 3){

                $x = ($i == 1 || $i == 2 ? $width / 2 : 0);
                $y = ($i > 1 ? $height / 2 : 0);
                $h2 = intval($height / 2);
                $w2 = intval($width / 2);

            }

            imagecopyresampled($resImage, $image, $x, $y, 0, 0, $w2, $h2, $srcWidth, $srcHeight);
        
            imagedestroy($image);
        }

        $filename = 'group-' . md5($filename);

        imagejpeg($resImage, Yii::getAlias('@webroot') . DIRECTORY_SEPARATOR . Upload::$UPLOADS_DIR . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . $filename . '.png');

        imagedestroy($resImage);

        $thumbFile = '/' . Upload::$UPLOADS_DIR . '/avatars/' . $filename . '.png';

        return $thumbFile;
    }
}