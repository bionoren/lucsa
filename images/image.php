<?php
    header('Content-type: image/gif');
    $imgWidth = 100;
    $imgHeight = 20;
    //taken from the WINE project
    $font = "./tahoma.ttf";
    $img = imagecreate($imgWidth, $imgHeight);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    imagefill($img, 0, 0, $white);
    imagecolortransparent($img, $white);

    $bgColor = imagecolorallocate($img, 150, 150, 150);
    $cornerSize = 4;
    imagefilledrectangle($img, 0, $cornerSize, $imgWidth, $imgHeight-$cornerSize, $bgColor);
    imagefilledrectangle($img, $cornerSize, 0, $imgWidth-$cornerSize-1, $cornerSize, $bgColor);
    imagefilledarc($img, $cornerSize, $cornerSize, 2*$cornerSize, 2*$cornerSize, 180, 270, $bgColor, IMG_ARC_PIE);
    imagefilledarc($img, $imgWidth-$cornerSize-1, $cornerSize, 2*$cornerSize, 2*$cornerSize, 270, 0, $bgColor, IMG_ARC_PIE);

    imagesetthickness($img, 1);
//    imagefilledellipse($img, $offsetX+1+6+$incX*($i-1), $offsetY+5+$start*$incY, 10, 10, $bgcolor);
//    imagettftext($img, 10, 0, $offsetX+2+$incX*($i-1), $offsetY+$tmp+$start*$incY, $black, $font, Course::displayTime($class[1])." - ".Course::displayTime($class[2]));

    imagegif($img);
    imagedestroy($img);
?>