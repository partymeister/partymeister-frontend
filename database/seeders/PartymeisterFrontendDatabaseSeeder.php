<?php

namespace Partymeister\Frontend\Database\Seeders;

use Illuminate\Database\Seeder;

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
