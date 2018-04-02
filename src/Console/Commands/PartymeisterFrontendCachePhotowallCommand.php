<?php

namespace Partymeister\Frontend\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Image;

class PartymeisterFrontendCachePhotowallCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'partymeister:frontend:cache-photowall';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make symlinks to all uploaded files';

    protected function mkdir($directory) {
        if (!is_dir($directory)) {
            mkdir($directory);
        }
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $basePath = base_path('public/photowall');
        $cachePath = base_path('public/photowall/cache/2018');
        $source = '2018_forrealz';
        if (!is_dir($cachePath)) {
            mkdir($cachePath);
        }
        if (!is_dir($cachePath.'/'.$source)) {
            mkdir($cachePath.'/'.$source);
        }


        foreach (Storage::disk('photowall')->allFiles($source) as $file) {
            $split = explode('/', $file);
            $name = array_values(array_slice($split, -1))[0];
            //$target = str_replace($source, '2018', $);
            if (!file_exists($cachePath.'/'.$name)) {
                 try {
                    Image::load($basePath.'/'.$file)
                         ->width(1280)
                         ->save($cachePath.'/'.$name);

                    $this->info($file. ' converted');
                } catch (\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
        }

    }
}