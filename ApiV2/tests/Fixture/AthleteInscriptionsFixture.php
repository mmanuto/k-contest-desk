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
                'athlete_id' => 1,
                'categorycode_id' => 1,
                'competition_id' => 1,
                'cintura' => 'Lorem ipsum dolor sit amet',
                'n_iscrizione' => 1,
                'inviato' => 1,
                'modificato' => 1,
                'accorpamento' => 1,
                'old_category' => 'Lorem ipsum d',
            ],
        ];
        parent::init();
    }
}
