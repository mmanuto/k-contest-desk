<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersCategorycodesFixture
 */
class UsersCategorycodesFixture extends TestFixture
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
                'id' => 1,
                'user_id' => 'Lore',
                'categorycode_id' => 1,
                'status' => 'Lorem ipsum dolor ',
            ],
        ];
        parent::init();
    }
}
