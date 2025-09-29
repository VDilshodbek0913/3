<?php
session_start();

// Generate CAPTCHA image with letters and numbers
function generateCaptcha() {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    $captcha_code = '';
    for ($i = 0; $i < 6; $i++) {
        $captcha_code .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    $_SESSION['captcha'] = $captcha_code;
    
    // Create image
    $width = 150;
    $height = 50;
    $image = imagecreate($width, $height);
    
    // Colors
    $bg_color = imagecolorallocate($image, 240, 240, 240);
    $text_color = imagecolorallocate($image, 50, 50, 50);
    $line_color = imagecolorallocate($image, 200, 200, 200);
    
    // Add noise lines
    for ($i = 0; $i < 5; $i++) {
        imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $line_color);
    }
    
    // Add text
    $font_size = 5;
    $x = ($width - strlen($captcha_code) * imagefontwidth($font_size)) / 2;
    $y = ($height - imagefontheight($font_size)) / 2;
    
    imagestring($image, $font_size, $x, $y, $captcha_code, $text_color);
    
    // Output image
    header('Content-Type: image/png');
    imagepng($image);
    imagedestroy($image);
}

generateCaptcha();
?>
