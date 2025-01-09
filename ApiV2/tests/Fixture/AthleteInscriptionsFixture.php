<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AthleteInscriptionsFixture
 */
class AthleteInscriptionsFixture extends TestFixture
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
                'athlete_id' => 'Lorem ipsum dolor s',
                'categorycode_id' => 'Lorem ip',
                'competition_id' => 'Lorem ipsum dolor ',
                'cintura' => 'Lorem ipsum dolor sit amet',
                'n_iscrizione' => 1,
                'inviato' => 1,
                'modificato' => 1,
                'accorpamento' => 1,
                'deleted' => 1,
                'old_category' => 'Lorem ipsum d',
                'created_date' => 1736249908,
                'modified_date' => 1736249908,
            ],
        ];
        parent::init();
    }
}
