<?php

// Само за администраторски цели
$dir = __DIR__ . '/css/img';

function getImages($path) {
    $images = [];
    if (!is_dir($path)) return $images;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    foreach ($iterator as $file) {
        if ($file->isFile() && in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png'])) {
            $images[] = $file->getPathname();
        }
    }
    return $images;
}

$images = getImages($dir);
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Оптимизиране на Изображения</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f5f7; padding: 40px; color: #333; }
        .card { background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); padding: 24px; max-width: 800px; margin: 0 auto; }
        h1 { margin-top: 0; color: #111; border-bottom: 1px solid #eee; padding-bottom: 12px; }
        .log { background: #1e1e1e; color: #00ff00; padding: 15px; border-radius: 6px; font-family: monospace; font-size: 13px; height: 400px; overflow-y: auto; white-space: pre-wrap; }
        .btn { background: #0071e3; color: white; border: none; padding: 10px 20px; font-size: 14px; border-radius: 6px; cursor: pointer; font-weight: 500; }
        .btn:hover { background: #0077ed; }
        .status { margin-bottom: 20px; font-weight: bold; }
    </style>
</head>
<body>
<div class="card">
    <h1>Оптимизация на статични изображения (WebP)</h1>
    <p>Този инструмент сканира папка <code>public/css/img</code>, намира всички изображения над 150 KB, преоразмерява ги до максимум 1920px ширина и ги компресира до WebP формат с 80% качество.</p>
    
    <div class="status">Намерени общо картинки (JPG/PNG): <?php echo count($images); ?></div>
    
    <?php if (isset($_GET['run'])): ?>
        <div class="log"><?php
            $optimizedCount = 0;
            foreach ($images as $imgPath) {
                $size = filesize($imgPath);
                if ($size < 150 * 1024) {
                    continue;
                }
                
                $sizeKb = round($size / 1024, 2);
                $filename = basename($imgPath);
                echo "Оптимизиране на: {$filename} ({$sizeKb} KB)... ";
                
                list($width, $height, $type) = getimagesize($imgPath);
                
                switch ($type) {
                    case IMAGETYPE_JPEG:
                        $src = @imagecreatefromjpeg($imgPath);
                        break;
                    case IMAGETYPE_PNG:
                        $src = @imagecreatefrompng($imgPath);
                        break;
                    default:
                        echo "неподдържан формат.\n";
                        continue 2;
                }
                
                if (!$src) {
                    echo "ГРЕШКА ПРИ ЗАРЕЖДАНЕ.\n";
                    continue;
                }
                
                $newWidth = $width;
                $newHeight = $height;
                if ($width > 1920) {
                    $newWidth = 1920;
                    $newHeight = round(($height / $width) * 1920);
                }
                
                $dst = imagecreatetruecolor($newWidth, $newHeight);
                if ($type == IMAGETYPE_PNG) {
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                    $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
                    imagefilledrectangle($dst, 0, 0, $newWidth, $newHeight, $transparent);
                }
                
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                
                $pathInfo = pathinfo($imgPath);
                $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
                
                if (imagewebp($dst, $webpPath, 80)) {
                    $newSize = filesize($webpPath);
                    $newSizeKb = round($newSize / 1024, 2);
                    echo "ГОТОВО! Записано като WebP ({$newSizeKb} KB)\n";
                    $optimizedCount++;
                } else {
                    echo "ГРЕШКА ПРИ ЗАПИС.\n";
                }
                
                imagedestroy($src);
                imagedestroy($dst);
            }
            echo "\nОптимизацията приключи! Оптимизирани са {$optimizedCount} изображения.";
        ?></div>
        <br>
        <a href="optimize.php"><button class="btn">Назад</button></a>
    <?php else: ?>
        <a href="optimize.php?run=1"><button class="btn">Стартирай Оптимизацията</button></a>
    <?php endif; ?>
</div>
</body>
</html>
