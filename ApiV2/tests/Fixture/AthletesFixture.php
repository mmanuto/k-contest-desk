<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AthletesFixture
 */
class AthletesFixture extends TestFixture
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
                'club_id' => 1,
                'nome' => 'Lorem ipsum dolor sit amet',
                'cognome' => 'Lorem ipsum dolor sit amet',
                'data_nascita' => 'Lorem ipsum dolor ',
                'sesso' => 'L',
                'cod_fiscale' => 'Lorem ipsum dolor ',
                'federation_id' => 1,
                'n_tessera' => 'Lorem ipsum dolor sit amet',
                'peso' => 1,
                'grado' => 'Lorem ipsum d',
            ],
        ];
        parent::init();
    }
}
