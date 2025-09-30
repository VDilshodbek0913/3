<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../xato.log');

session_start();

// Generate CAPTCHA image with letters and numbers
function generateCaptcha() {
    try {
        error_log("Captcha generation started at " . date('Y-m-d H:i:s'));
        
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    $captcha_code = '';
    for ($i = 0; $i < 6; $i++) {
        $captcha_code .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    $_SESSION['captcha'] = $captcha_code;
        error_log("Captcha code generated: " . $captcha_code);
    
    // Create image
    $width = 150;
    $height = 50;
    $image = imagecreate($width, $height);
    
        if (!$image) {
            error_log("Failed to create captcha image");
            throw new Exception("Image creation failed");
        }
        
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
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
    imagepng($image);
    imagedestroy($image);
        
        error_log("Captcha image generated successfully");
        
    } catch (Exception $e) {
        error_log("Captcha generation error: " . $e->getMessage());
        
        // Create a simple text-based fallback
        header('Content-Type: text/plain');
        echo "CAPTCHA ERROR";
    }
}

generateCaptcha();
?>
