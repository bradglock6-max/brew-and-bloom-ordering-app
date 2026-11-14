<?php
declare(strict_types=1);

final class Uploader
{
    private const MAX_BYTES = 2 * 1024 * 1024;
    private const TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    private const REL_DIR = 'assets/uploads/';

    private static function dir(): string
    {
        return dirname(__DIR__, 2) . '/public/assets/uploads/';
    }

    public static function uploadProductImage(array $file): ?string
    {
        $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($err === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($err !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Image upload failed.');
        }
        if (($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new RuntimeException('Image must be 2MB or smaller.');
        }
        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('Invalid upload.');
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $tmp) : false;
        if ($finfo) {
            finfo_close($finfo);
        }
        if (!is_string($mime) || !isset(self::TYPES[$mime])) {
            throw new RuntimeException('Only JPG, PNG or WEBP images are allowed.');
        }
        $dir = self::dir();
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            throw new RuntimeException('Upload directory unavailable.');
        }
        $name = bin2hex(random_bytes(16)) . '.' . self::TYPES[$mime];
        if (!move_uploaded_file($tmp, $dir . $name)) {
            throw new RuntimeException('Could not save image.');
        }
        return self::REL_DIR . $name;
    }

    public static function deleteFile(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === ''
            || !str_starts_with($relativePath, self::REL_DIR)
            || str_contains($relativePath, '..')
            || str_contains($relativePath, '\\')) {
            return;
        }
        $name = substr($relativePath, strlen(self::REL_DIR));
        if ($name === '' || str_contains($name, '/') || $name === '.gitkeep') {
            return;
        }
        $path = self::dir() . $name;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}