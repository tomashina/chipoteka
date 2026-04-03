<?php
class ModelToolImage extends Model {
    private function buildImageUrl($path) {
        $segments = explode('/', str_replace('\\', '/', $path));
        $segments = array_map('rawurlencode', $segments);

        return 'https://images.chipoteka.hr/image/' . implode('/', $segments);
    }

    public function resize($filename, $width, $height) {
        if (!is_file(DIR_IMAGE . $filename) || substr(str_replace('\\', '/', realpath(DIR_IMAGE . $filename)), 0, strlen(DIR_IMAGE)) != str_replace('\\', '/', DIR_IMAGE)) {
            return;
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $image_old = $filename;
        $image_new = 'cache/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . (int)$width . 'x' . (int)$height . '.' . $extension;
        $image_new_webp = 'cachewebp/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . (int)$width . 'x' . (int)$height . '.webp';

        if (!is_file(DIR_IMAGE . $image_new) || (filemtime(DIR_IMAGE . $image_old) > filemtime(DIR_IMAGE . $image_new))) {
            list($width_orig, $height_orig, $image_type) = getimagesize(DIR_IMAGE . $image_old);

            if (!in_array($image_type, array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_WEBP))) {
                return DIR_IMAGE . $image_old;
            }

            $path = '';

            $directories = explode('/', dirname($image_new));

            /*	foreach ($directories as $directory) {
                    $path = $path . '/' . $directory;

                    if (!is_dir(DIR_IMAGE . $path)) {
                        @mkdir(DIR_IMAGE . $path, 0777);
                    }
                }*/

            if ($width_orig != $width || $height_orig != $height) {
                //	$image = new Image(DIR_IMAGE . $image_old);
//$image->resize($width, $height);
                //	$image->save(DIR_IMAGE . $image_new);
            } else {
                //copy(DIR_IMAGE . $image_old, DIR_IMAGE . $image_new);
            }
        }

        $gd = gd_info();
        if ($gd['WebP Support']) {
            $webp_file = DIR_IMAGE . $image_new_webp;
            $webp_is_valid = is_file($webp_file) && @getimagesize($webp_file);

            if (!$webp_is_valid || (filemtime(DIR_IMAGE . $image_old) > filemtime($webp_file))) {
                $path = '';

                $directories = explode('/', dirname($image_new_webp));

                foreach ($directories as $directory) {
                    $path = $path . '/' . $directory;

                    if (!is_dir(DIR_IMAGE . $path)) {
                        @mkdir(DIR_IMAGE . $path, 0777);
                    }
                }

                $image_webp = new Image(DIR_IMAGE . $image_old);
                $image_webp->resize($width, $height);
                $image_webp->save_webp($webp_file);
            }
        }

        if ($gd['WebP Support'] && is_file(DIR_IMAGE . $image_new_webp)) {
            return $this->buildImageUrl($image_new_webp);
        }

        return $this->buildImageUrl($image_old);
    }
}
