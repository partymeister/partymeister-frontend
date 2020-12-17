<?php

namespace Partymeister\Frontend\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Class PartymeisterFrontendDatabaseSeeder
 */
class PartymeisterFrontendNavigationsTableSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \Motor\CMS\Models\Navigation::create(json_decode(file_get_contents(__DIR__.'/navigation/navigation_main.json'), true));
    }
}
