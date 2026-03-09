<?php

namespace Core\Helpers;

class FileUpload
{
    private static array $allowedImages = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private static int $maxSize = 5 * 1024 * 1024; // 5MB

    public static function uploadImage(array $file, string $destination): string|false
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        if (!in_array($file['type'], self::$allowedImages)) {
            return false;
        }

        if ($file['size'] > self::$maxSize) {
            return false;
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = bin2hex(random_bytes(16)) . '.' . strtolower($extension);
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/' . trim($destination, '/');

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $path = $uploadDir . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $path)) {
            return $filename;
        }

        return false;
    }

    public static function delete(string $path): bool
    {
        $fullPath = dirname(__DIR__, 2) . '/public/uploads/' . ltrim($path, '/');
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }
}
