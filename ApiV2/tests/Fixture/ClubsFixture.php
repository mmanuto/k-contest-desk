<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ClubsFixture
 */
class ClubsFixture extends TestFixture
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
                'id' => '1fe15598-98ce-42d9-9f3a-f45c0b0d748b',
                'club_code' => 'Lorem ipsum dolor sit amet',
                'club_name' => 'Lorem ipsum dolor sit amet',
                'fiscal_code' => 'Lorem ipsum dolor ',
                'short_name' => 'Lorem ipsum dolor ',
                'club_manager' => 'Lorem ipsum dolor sit amet',
                'telephone_n' => 'Lorem ipsum dolor ',
                'mail' => 'Lorem ipsum dolor sit amet',
                'coach' => 'Lorem ipsum dolor sit amet',
                'last_login' => '2025-01-08 16:42:51',
                'created_date' => 1736354571,
                'modified_date' => 1736354571,
            ],
        ];
        parent::init();
    }
}
