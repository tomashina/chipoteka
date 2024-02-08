<?php

//run twice to add all product to their parent categories

include('test_agm/boot.php');

$range = cron_range('clean_product_images');
$total = \Agmedia\Models\Product\Product::query()->count();

$products = \Agmedia\Models\Product\Product::query()
    ->where('luceed_uid', '!=', '')
    ->where('image', '!=', '')
    ->offset($range['offset'])
    ->take($range['limit'])
    ->get();

if ($range['offset'] > $total) {
    return json_encode(['success' => 'Import je gotov..!']);
}

foreach ($products as $product) {
    $path = 'catalog/products/' . $product->luceed_uid;
    $replace_path = $path . '/';
    $images = [0 => str_replace($replace_path, '', $product->image)];

    try {
        $files = new DirectoryIterator(DIR_IMAGE . $path);
        $db_images = \Agmedia\Models\Product\ProductImage::query()->where('product_id', $product->product_id)->get();

        if ($db_images->count()) {
            foreach ($db_images as $image) {
                array_push($images, str_replace($replace_path, '', $product->image));
            }
        }

        if ($files->isDir()) {
            foreach ($files as $file) {
                if ( ! in_array($file->getFilename(), $images) && ! $file->isDot()) {
                    \Agmedia\Helpers\Log::store(DIR_IMAGE . $replace_path . $file->getFilename(), 'test');

                    unlink(DIR_IMAGE . $replace_path . $file->getFilename());
                    unlink(DIR_IMAGE . 'cache/' . $replace_path . $file->getFilename());
                    unlink(DIR_IMAGE . 'cachewebp/' . $replace_path . $file->getFilename());
                }
            }
        }

    } catch (Exception $e) {
        \Agmedia\Helpers\Log::store($e->getMessage(), 'test_error');
    }

}

//cron_range('clean_product_images', ($range['offset'] + $range['limit']), $range['limit']);

echo 'Done::';