<?php
/**
 * RMS Global Helper Functions
 */

if (!function_exists('rms_menu_image')) {
    /**
     * Resolves the proper public URL for any menu item image.
     * Supports uploaded files, preset food images, full URLs, and graceful fallbacks.
     */
    function rms_menu_image($image) {
        $defaultFallback = '/RMS/public/assets/images/food/pos_cheeseburger.jpg';
        if (empty($image)) {
            return $defaultFallback;
        }

        $image = trim($image);

        // If it's a full web URL
        if (strpos($image, 'http://') === 0 || strpos($image, 'https://') === 0) {
            return $image;
        }

        // If it starts with /RMS/
        if (strpos($image, '/RMS/') === 0) {
            $relPath = substr($image, 5); // strip /RMS/
            $diskPath = realpath(__DIR__ . '/../../' . $relPath);
            if ($diskPath && file_exists($diskPath)) {
                return $image;
            }
        }

        // Check in uploads folder
        $uploadsDir = realpath(__DIR__ . '/../../public/assets/uploads');
        if ($uploadsDir && file_exists($uploadsDir . DIRECTORY_SEPARATOR . $image)) {
            return '/RMS/public/assets/uploads/' . $image;
        }

        // Check in food images folder
        $foodDir = realpath(__DIR__ . '/../../public/assets/images/food');
        if ($foodDir && file_exists($foodDir . DIRECTORY_SEPARATOR . $image)) {
            return '/RMS/public/assets/images/food/' . $image;
        }

        return $defaultFallback;
    }
}
