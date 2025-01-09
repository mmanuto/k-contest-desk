<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ClubInscriptionsFixture
 */
class ClubInscriptionsFixture extends TestFixture
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
                'created_date' => 1736249941,
            ],
        ];
        parent::init();
    }
}
