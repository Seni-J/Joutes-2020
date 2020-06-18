<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(Joutes2019Seeder::class);
        $this->call(FakeResultPoolState::class);
    }
}
