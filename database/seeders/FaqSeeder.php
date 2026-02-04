<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        // Create published FAQs
        Faq::factory(20)
            ->published()
            ->create();

        // Create unpublished FAQs (drafts)
        Faq::factory(5)
            ->unpublished()
            ->create();
    }
}
