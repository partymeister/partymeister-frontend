<?php

namespace Partymeister\Frontend\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Image;

/**
 * Class PartymeisterFrontendCachePhotowallCommand
 */
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

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $basePath = base_path('public/photowall');
        $thumbPath = base_path('public/photowall/thumb');
        $fullPath = base_path('public/photowall/full');

        if (! is_dir($thumbPath)) {
            mkdir($thumbPath, 0755, true);
        }
        if (! is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        foreach (Storage::disk('photowall')->files('') as $file) {
            $split = explode('/', $file);
            $name = array_values(array_slice($split, -1))[0];
            $sourcePath = $basePath.'/'.$file;

            // Generate thumbnail (400px, 75% quality) for grid display
            if (! file_exists($thumbPath.'/'.$name)) {
                try {
                    Image::load($sourcePath)
                        ->width(400)
                        ->quality(75)
                        ->save($thumbPath.'/'.$name);

                    $this->info($file.' thumb created');
                } catch (Exception $e) {
                    $this->error('Thumb: '.$e->getMessage());
                }
            }

            // Generate full size (1920px, 85% quality) for lightbox
            if (! file_exists($fullPath.'/'.$name)) {
                try {
                    Image::load($sourcePath)
                        ->width(1920)
                        ->quality(85)
                        ->save($fullPath.'/'.$name);

                    $this->info($file.' full created');
                } catch (Exception $e) {
                    $this->error('Full: '.$e->getMessage());
                }
            }
        }
    }

    protected function mkdir($directory)
    {
        if (! is_dir($directory)) {
            mkdir($directory);
        }
    }
}
