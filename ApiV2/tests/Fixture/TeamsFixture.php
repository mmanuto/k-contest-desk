<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TeamsFixture
 */
class TeamsFixture extends TestFixture
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
                'club_id' => 'Lorem ipsum dolor s',
                'competition_id' => 'Lorem ipsum dolor ',
                'componenti' => 'Lorem ipsum dolor sit amet',
                'categoria' => 'Lorem ipsum dolor sit amet',
                'grado' => 'Lorem ipsum dolor sit amet',
                'created_date' => 1736327159,
                'modified_date' => 1736327159,
            ],
        ];
        parent::init();
    }
}
