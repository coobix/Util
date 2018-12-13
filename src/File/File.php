<?php

namespace Coobix\Util\File;

abstract class File {

    

    /**
     * Create a directory
     *
     * @static
     * 
     * @param  string $dirPath
     * @param  string $permissions
     * @return mixed|string
     */
    public static function createDir($dirPath, $permissions = 0777) {
        if (FALSE === file_exists($dirPath)) {
            if (!mkdir($dirPath, $permissions, true)) {
                throw new \Exception("Can't create directory " . $dirPath);
            }
            return true;
        }
    }

    /**
     * Delete a File
     *
     * @static
     * 
     * @param  string $filePath
     * @return mixed|string
     */
    public static function deleteFile($filePath) {
        if (TRUE === file_exists($filePath)) {
            if(!unlink($filePath)) {
                throw new \Exception("Can't delete file " . $filePath);
            }
            return true;
        }
        throw new \Exception("File no exist " . $filePath);      
    }

    /**
     * Delete file tree
     *
     * @static
     * 
     * @param  string $dirPath
     * @return mixed|string
     */
    public static function deleteTree($dirPath) {
        $files = array_diff(scandir($dirPath), array('.', '..'));
        foreach ($files as $file) {
            (is_dir($dirPath.$file)) ? self::delTree($dirPath.$file) : self::deleteFile($dirPath.'/'.$file);
        }
        return rmdir($dirPath);
    }
}
