<?php

$dir = __DIR__ . '/../public/css/img';

function getImages($path) {
    $images = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    foreach ($iterator as $file) {
        if ($file->isFile() && in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png'])) {
            $images[] = $file->getPathname();
        }
    }
    return $images;
}

$images = getImages($dir);
echo "Намерени изображения за оптимизация: " . count($images) . "\n";

foreach ($images as $imgPath) {
    $size = filesize($imgPath);
    $sizeKb = round($size / 1024, 2);
    
    // Оптимизираме само изображения над 150 KB
    if ($size < 150 * 1024) {
        continue;
    }
    
    echo "Оптимизиране на: " . basename($imgPath) . " ({$sizeKb} KB)... ";
    
    // Взимаме размерите
    list($width, $height, $type) = getimagesize($imgPath);
    
    // Зареждаме изображението
    switch ($type) {
        case IMAGETYPE_JPEG:
            $src = imagecreatefromjpeg($imgPath);
            break;
        case IMAGETYPE_PNG:
            $src = imagecreatefrompng($imgPath);
            break;
        default:
            echo "неподдържан формат.\n";
            continue 2;
    }
    
    if (!$src) {
        echo "грешка при зареждане.\n";
        continue;
    }
    
    // Изчисляваме новите размери (максимум 1920px ширина)
    $newWidth = $width;
    $newHeight = $height;
    if ($width > 1920) {
        $newWidth = 1920;
        $newHeight = round(($height / $width) * 1920);
    }
    
    // Създаваме празна картинка с новите размери
    $dst = imagecreatetruecolor($newWidth, $newHeight);
    
    // За запазване на прозрачност при PNG
    if ($type == IMAGETYPE_PNG) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefilledrectangle($dst, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Ресайзваме
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Път към новия WebP файл
    $pathInfo = pathinfo($imgPath);
    $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
    
    // Записваме като WebP
    if (imagewebp($dst, $webpPath, 80)) {
        $newSize = filesize($webpPath);
        $newSizeKb = round($newSize / 1024, 2);
        echo "ГОТОВО! Записано като WebP ({$newSizeKb} KB)\n";
    } else {
        echo "грешка при запис.\n";
    }
    
    imagedestroy($src);
    imagedestroy($dst);
}

echo "Всички изображения са обработени!\n";
