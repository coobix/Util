<?php

namespace Coobix\Util\Formatting;

abstract class Formatting {
    
    
    /**
     * source : http://snipplr.com/view/22741/slugify-a-string-in-php/
     *
     * @static
     * 
     * @param  string $text
     * @return mixed|string
     */
    static public function slugify($text)
    {
    	
    	$text =  mb_convert_case($text, MB_CASE_LOWER, mb_detect_encoding($text));
    	
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

        // trim
        $text = trim($text);

        setlocale(LC_ALL, "en_US.UTF-8");
        
        // transliterate
        if (function_exists('iconv')) {
            $text = iconv('UTF-8', 'US-ASCII//TRANSLIT', $text);
        }

        // lowercase
        $text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    /**
     * Sanitizes a filename, replacing whitespace with dashes.
     *
     * Removes special characters that are illegal in filenames on certain
     * operating systems and special characters requiring special escaping
     * to manipulate at the command line. Replaces spaces and consecutive
     * dashes with a single dash. Trims period, dash and underscore from beginning
     * and end of filename.
     *
     * @since 2.1.0
     *
     * @param string $filename The filename to be sanitized
     * @return string The sanitized filename
     */
    
    public function sanitize_file_name($filename) {
        $filename_raw = $filename;
        $special_chars = array("?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}", chr(0));
        /**
         * Filter the list of characters to remove from a filename.
         *
         * @since 2.8.0
         *
         * @param array  $special_chars Characters to remove.
         * @param string $filename_raw  Filename as it was passed into sanitize_file_name().
         */
        $special_chars = apply_filters('sanitize_file_name_chars', $special_chars, $filename_raw);
        $filename = preg_replace("#\x{00a0}#siu", ' ', $filename);
        $filename = str_replace($special_chars, '', $filename);
        $filename = preg_replace('/[\s-]+/', '-', $filename);
        $filename = trim($filename, '.-_');

        // Split the filename into a base and extension[s]
        $parts = explode('.', $filename);

        // Return if only one extension
        if (count($parts) <= 2) {
            /**
             * Filter a sanitized filename string.
             *
             * @since 2.8.0
             *
             * @param string $filename     Sanitized filename.
             * @param string $filename_raw The filename prior to sanitization.
             */
            return apply_filters('sanitize_file_name', $filename, $filename_raw);
        }

        // Process multiple extensions
        $filename = array_shift($parts);
        $extension = array_pop($parts);
        $mimes = get_allowed_mime_types();

        /*
         * Loop over any intermediate extensions. Postfix them with a trailing underscore
         * if they are a 2 - 5 character long alpha string not in the extension whitelist.
         */
        foreach ((array) $parts as $part) {
            $filename .= '.' . $part;

            if (preg_match("/^[a-zA-Z]{2,5}\d?$/", $part)) {
                $allowed = false;
                foreach ($mimes as $ext_preg => $mime_match) {
                    $ext_preg = '!^(' . $ext_preg . ')$!i';
                    if (preg_match($ext_preg, $part)) {
                        $allowed = true;
                        break;
                    }
                }
                if (!$allowed)
                    $filename .= '_';
            }
        }
        $filename .= '.' . $extension;
        /** This filter is documented in wp-includes/formatting.php */
        return apply_filters('sanitize_file_name', $filename, $filename_raw);
    }

}
