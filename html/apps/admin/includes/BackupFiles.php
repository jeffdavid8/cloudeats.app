<?php

final class BackupFiles
{
    public static function list(string $directory): array
    {
        if (!is_dir($directory)) {
            if (file_exists($directory)) {
                throw new RuntimeException('The backup path is not a directory.');
            }
            return [];
        }

        $files = [];
        foreach (new DirectoryIterator($directory) as $entry) {
            if (!$entry->isFile() || $entry->isLink() || !self::isJsonName($entry->getFilename())) {
                continue;
            }
            $files[] = $entry->getFilename();
        }
        natcasesort($files);
        return array_values($files);
    }

    public static function resolve(string $directory, string $name): string
    {
        if (!self::isJsonName($name) || basename($name) !== $name || strpbrk($name, '/\\') !== false) {
            throw new InvalidArgumentException('Invalid backup filename.');
        }
        $path = $directory . DIRECTORY_SEPARATOR . $name;
        if (is_link($path) || !is_file($path) || !is_readable($path)) {
            throw new InvalidArgumentException('The selected backup is unavailable.');
        }
        return $path;
    }

    private static function isJsonName(string $name): bool
    {
        return $name !== '' && strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'json';
    }
}
