<?php

namespace Partymeister\Frontend\Database\Seeders;

use Illuminate\Database\Seeder;
use Motor\CMS\Models\Page;
use Motor\CMS\Models\PageVersion;

/**
 * Class PartymeisterFrontendDatabaseSeeder
 */
class PartymeisterFrontendDatabaseSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            PartymeisterFrontendPagesTableSeeder::class,
            PartymeisterFrontendNavigationsTableSeeder::class,
        ]);
    }
}
