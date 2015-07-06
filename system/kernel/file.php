<?php

/**
 * ======================================================================
 *  FILE OPERATION
 * ======================================================================
 *
 * -- CODE: -------------------------------------------------------------
 *
 *    // Create a file
 *    File::write('some text')->saveTo('path/to/file.txt');
 *
 * ----------------------------------------------------------------------
 *
 *    // Show file content to page
 *    echo File::open('path/to/file.txt')->read();
 *
 * ----------------------------------------------------------------------
 *
 *    // Append text to file
 *    File::open('path/to/file.txt')->append('test append.')->save();
 *
 * ----------------------------------------------------------------------
 *
 *    // Prepend text to file
 *    File::open('path/to/file.txt')->prepend('test prepend.')->save();
 *
 * ----------------------------------------------------------------------
 *
 *    // Update a file
 *    File::open('path/to/file.txt')->write('some text')->save();
 *
 * ----------------------------------------------------------------------
 *
 *    // Rename a file
 *    File::open('path/to/file.txt')->renameTo('file-1.txt');
 *
 * ----------------------------------------------------------------------
 *
 *    // Delete a file
 *    File::open('path/to/file.txt')->delete();
 *
 * ----------------------------------------------------------------------
 *
 *    // Upload a file
 *    File::upload($_FILES['file'], 'path/to/folder');
 *
 * ----------------------------------------------------------------------
 *
 */

class File extends Base {

    protected static $cache = "";
    protected static $open = null;
    protected static $index = 0;

    public static $config = array(
        'file_size_min_allow' => 0, // Minimum allowed file size
        'file_size_max_allow' => 2097152, // Maximum allowed file size
        'file_extension_allow' => array() // List of allowed file extension(s)
    );

    // Inspect file path
    public static function inspect($path) {
        $path = self::path($path);
        $extension = self::E($path);
        $update = file_exists($path) ? filemtime($path) : null;
        $update_date = ! is_null($update) ? date('Y-m-d H:i:s', $update) : null;
        return array(
            'path' => $path,
            'name' => self::N($path),
            'url' => self::url($path),
            'extension' => strtolower($extension),
            'update_raw' => $update,
            'update' => $update_date,
            'size_raw' => file_exists($path) ? filesize($path) : null,
            'size' => self::size($path)
        );
    }

    // List all file(s) from a folder
    public static function explore($folder = ROOT, $recursive = false, $flat = false, $fallback = false) {
        $results = array();
        $folder = rtrim(self::path($folder), DS);
        $files = array_merge(glob($folder . DS . '*', GLOB_NOSORT), glob($folder . DS . '.*', GLOB_NOSORT));
        foreach($files as $file) {
            if(self::B($file) !== '.' && self::B($file) !== '..') {
                if(is_dir($file)) {
                    if( ! $flat) {
                        $results[$file] = $recursive ? self::explore($file, $recursive, $flat, array()) : 0;
                    } else {
                        $results[$file] = 0;
                        $results = array_merge($results, self::explore($file, $recursive, $flat, array()));
                    }
                } else {
                    $results[$file] = 1;
                }
            }
        }
        return ! empty($results) ? $results : $fallback;
    }

    // Check if file already exist
    public static function exist($path, $fallback = false) {
        $path = self::path($path);
        return file_exists($path) ? $path : $fallback;
    }

    // Open a file
    public static function open($path) {
        $path = self::path($path);
        self::$cache = "";
        self::$open = file_exists($path) ? $path : null;
        self::$index = 0;
        return new static;
    }

    // Append some data to the opened file
    public static function append($data) {
        self::$cache = file_get_contents(self::$open) . $data;
        return new static;
    }

    // Prepend some data to the opened file
    public static function prepend($data) {
        self::$cache = $data . file_get_contents(self::$open);
        return new static;
    }

    // Show the opened file to the screen
    public static function read($fallback = "") {
        return file_exists(self::$open) ? file_get_contents(self::$open) : $fallback;
    }

    // Read the opened file line by line
    public static function get($stop_at = null, $fallback = false, $chars = 1024) {
        $i = 0;
        $results = "";
        if($handle = fopen(self::$open, 'r')) {
            while(($buffer = fgets($handle, $chars)) !== false) {
                $buffer = str_replace("\r", "", $buffer);
                if(
                    is_int($stop_at) && $stop_at === $i ||
                    is_string($stop_at) && strpos($buffer, $stop_at) !== false
                ) {
                    break;
                }
                $results .= $buffer;
                $i++;
            }
            fclose($handle);
            return rtrim($results);
        }
        return $fallback;
    }

    // Write something before saving
    public static function write($data) {
        self::$cache = $data;
        return new static;
    }

    // Serialize the data before saving
    public static function serialize($data) {
        self::$cache = serialize($data);
        return new static;
    }

    // Unserialize the serialized data to output
    public static function unserialize($fallback = array()) {
        if(file_exists(self::$open)) {
            $data = file_get_contents(self::$open);
            return preg_match('#^([adObis]:|N;)#', $data) ? unserialize($data) : $fallback;
        }
        return $fallback;
    }

    // Delete the opened file
    public static function delete() {
        if(file_exists(self::$open)) {
            if(is_dir(self::$open)) {
               foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::$open, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                    if($file->isFile()) {
                        unlink($file->getPathname());
                    } else {
                        rmdir($file->getPathname());
                    }
                }
                rmdir(self::$open);
            } else {
                unlink(self::$open);
            }
        }
    }

    // Save the written data
    public static function save($permission = null) {
        self::saveTo(self::$open, $permission);
        return new static;
    }

    // Save the written data to ...
    public static function saveTo($path, $permission = null) {
        $path = self::path($path);
        if( ! file_exists(File::D($path))) {
            mkdir(File::D($path), 0777, true);
        }
        $handle = fopen($path, 'w') or die('Cannot open file: ' . $path);
        fwrite($handle, self::$cache);
        fclose($handle);
        if( ! is_null($permission)) {
            chmod($path, $permission);
        }
        self::$open = $path;
        return new static;
    }

    // Rename a file
    public static function renameTo($new_name) {
        $root = rtrim(File::D(self::$open), DS) . DS; 
        $old_name = ltrim(self::B(self::$open), DS);
        if($new_name !== $old_name) {
            rename($root . $old_name, $root . $new_name);
        }
        self::$open = $root . $new_name;
        return new static;
    }

    // Move file or folder to ...
    public static function moveTo($destination = ROOT) {
        $destination = rtrim(File::path($destination), DS);
        if(file_exists(self::$open)) {
            if(is_dir($destination)) {
                $destination .= DS . self::B(self::$open);
            }
            if( ! file_exists(File::D($destination))) {
                mkdir(File::D($destination), 0777, true);
            }
            rename(self::$open, $destination);
        }
        self::$open = $destination;
        return new static;
    }

    // Copy a file
    public static function copyTo($destination = ROOT) {
        if(file_exists(self::$open)) {
            $destination = (array) $destination;
            foreach($destination as $dest) {
                $dest = self::path($dest);
                if(is_dir($dest)) {
                    if( ! file_exists($dest)) {
                        mkdir($dest, 0777, true);
                    }
                    $dest = rtrim(self::path($dest), DS) . DS . self::B(self::$open);
                } else {
                    if( ! file_exists(File::D($dest))) {
                        mkdir(File::D($dest), 0777, true);
                    }
                }
                if( ! file_exists($dest) && ! file_exists(preg_replace('#\.(.*?)$#', '.' . self::$index . '.$1', $dest))) {
                    self::$index = 0;
                    copy(self::$open, $dest);
                } else {
                    self::$index++;
                    copy(self::$open, preg_replace('#\.(.*?)$#', '.' . self::$index . '.$1', $dest));
                }
            }
            self::$index = 0;
        }
    }

    // Create new directory
    public static function pocket($paths, $permission = 0777) {
        $paths = (array) $paths;
        foreach($paths as $i => $path) {
            if( ! file_exists($path)) {
                mkdir(self::path($path), (is_array($permission) ? $permission[$i] : $permission), true);
            }
        }
    }

    // Get file base name
    public static function B($path, $s = '/') {
        if($s !== DS && $s !== '/') {
            $p = explode($s, $path);
            return array_pop($p);
        }
        return basename($path);
    }

    // Get file directory name
    public static function D($path, $s = '/') {
        if($s !== DS && $s !== '/') {
            $p = explode($s, $path);
            array_pop($p);
            return implode($s, $p);
        }
        return dirname($path);
    }

    // Get file name without extension
    public static function N($path, $extension = false) {
        return $extension ? basename($path) : basename($path, '.' . pathinfo($path, PATHINFO_EXTENSION));
    }

    // Get file name extension
    public static function E($path, $fallback = "") {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return $extension ? $extension : $fallback;
    }

    // Set file permission
    public static function setPermission($permission) {
        chmod(self::$open, $permission);
        return new static;
    }

    // Upload a file
    public static function upload($file, $destination = ROOT, $callback = null) {
        $config = Config::get();
        $speak = Config::speak();
        $destination = self::path($destination);
        $errors = Mecha::A($speak->notify_file);
        // Create a safe file name
        $file['name'] = Text::parse($file['name'], '->safe_file_name');
        $extension = self::E($file['name']);
        // Something goes wrong
        if($file['error'] > 0 && isset($errors[$file['error']])) {
            Notify::error($errors[$file['error']]);
        } else {
            // Destination not found
            if( ! file_exists($destination)) {
                self::pocket($destination);
            }
            // Unknown file type
            if( ! isset($file['type']) || empty($file['type'])) {
                Notify::error($speak->notify_error_file_type_unknown);
            }
            // Bad file extension
            $extension_allow = array_flip(self::$config['file_extension_allow']);
            if( ! isset($extension_allow[$extension])) {
                Notify::error(Config::speak('notify_error_file_extension', $extension));
            }
            // Too small
            if($file['size'] < self::$config['file_size_min_allow']) {
                Notify::error(Config::speak('notify_error_file_size_min', self::size(self::$config['file_size_min_allow'], 'KB')));
            }
            // Too large
            if($file['size'] > self::$config['file_size_max_allow']) {
                Notify::error(Config::speak('notify_error_file_size_max', self::size(self::$config['file_size_max_allow'], 'KB')));
            }
        }
        if( ! Notify::errors()) {
            // Move the uploaded file to the destination folder
            if( ! file_exists($destination . DS . $file['name'])) {
                move_uploaded_file($file['tmp_name'], $destination . DS . $file['name']);
            } else {
                Notify::error(Config::speak('notify_file_exist', '<code>' . $file['name'] . '</code>'));
            }
            if( ! Notify::errors()) {
                // Create public asset link to show on file uploaded
                $link = self::url($destination) . '/' . $file['name'];
                Notify::success(Config::speak('notify_file_uploaded', '<code>' . $file['name'] . '</code>'));
                self::$open = $destination . DS . $file['name'];
                if(is_callable($callback)) {
                    call_user_func($callback, $file['name'], $file['type'], $file['size'], $link);
                }
            }
            return new static;
        }
        return false;
    }

    // Convert file size to ...
    public static function size($file, $unit = null, $precision = 2) {
        $size = is_numeric($file) ? $file : filesize($file);
        $base = log($size, 1024);
        $suffix = array('B', 'KB', 'MB', 'GB', 'TB');
        if ( ! $u = array_search((string) $unit, $suffix)) {
            $u = ($size > 0) ? floor($base) : 0;
        }
        $output = round($size / pow(1024, $u), $precision);
        return $output < 0 ? Config::speak('unknown') : trim($output . ' ' . $suffix[$u]);
    }

    // Convert URL to file path
    public static function path($url) {
        $base = Config::get('url');
        $p = str_replace(array('\\', '/'), DS, $base);
        return str_replace(array($base, '\\', '/', $p), array(ROOT, DS, DS, ROOT), $url);
    }

    // Convert file path to URL
    public static function url($path) {
        $base = Config::get('url');
        $p = str_replace(array('\\', DS), '/', ROOT);
        return str_replace(array(ROOT, '\\', DS, $p), array($base, '/', '/', $base), $path);
    }

    // Configure ...
    public static function configure($key, $value = null) {
        if(is_array($key)) {
            Mecha::extend(self::$config, $key);
        } else {
            if(is_array($value)) {
                Mecha::extend(self::$config[$key], $value);
            } else {
                self::$config[$key] = $value;
            }
        }
        return new static;
    }

}