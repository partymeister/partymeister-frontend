<?php

namespace Partymeister\Frontend\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Class PartymeisterFrontendDatabaseSeeder
 */
class PartymeisterFrontendPagesTableSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $pages = json_decode(file_get_contents(__DIR__.'/navigation/pages.json'), true);
        foreach ($pages as $page) {
            $pageData = $page;
            unset($pageData['versions']);
            DB::table('pages')->insert($pageData);
            foreach ($page['versions'] as $version) {
                $versionData = $version;
                unset($versionData['components']);
                DB::table('page_versions')->insert($versionData);
                foreach ($version['components'] as $component) {
                    $componentData = $component;
                    unset($componentData['component']);
                    DB::table('page_version_components')->insert($componentData);
                    if (isset($component['component'])) {
                        $class = $component['component_type'];
                        $c = new $class($component['component']);
                        $c->save();
                    }
                }
            }
        }
    }
}
