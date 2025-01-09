<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AgecategoriesFixture
 */
class AgecategoriesFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => '10b43c79-0e2b-4a0c-bb2f-57e44851c4cf',
                'description' => 'Lorem ipsum dolor sit amet',
                'age_min' => 1,
                'age_max' => 1,
                'agonist' => 1,
                'out_category' => 1,
            ],
        ];
        parent::init();
    }
}
