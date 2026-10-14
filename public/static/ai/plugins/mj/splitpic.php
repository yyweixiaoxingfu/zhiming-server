<?php
// 接收源文件网址作为参数
$sourceImageUrl = $_GET['url'];

// 获取远程图片的内容
$sourceImageContent = file_get_contents($sourceImageUrl);

// 创建一个新的图像资源
$sourceImage = imagecreatefromstring($sourceImageContent);

// 获取原始图片的宽度和高度
$sourceWidth = imagesx($sourceImage);
$sourceHeight = imagesy($sourceImage);

// 计算截取后的图片宽度和高度
$targetWidth = $sourceWidth / 2;
$targetHeight = $sourceHeight / 2;

// 创建一个新的图像资源，用于存储截取后的图片
$targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

// 截取左上四分之一的图片
if ($_GET['id']=='1'){
    imagecopy($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight);
}
if ($_GET['id']=='2'){
    imagecopy($targetImage, $sourceImage, 0, 0, $targetWidth, 0, $sourceWidth, $targetHeight);
}
if ($_GET['id']=='3'){
    imagecopy($targetImage, $sourceImage, 0, 0, 0, $targetHeight, $targetWidth, $sourceHeight);
}
if ($_GET['id']=='4'){
    imagecopy($targetImage, $sourceImage, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
}

// 设置响应头，指定输出为图片文件
header('Content-Type: image/jpeg');

// 输出截取后的图片
imagejpeg($targetImage);

// 释放资源
imagedestroy($sourceImage);
imagedestroy($targetImage);