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
                'id' => '87d7ca89-84aa-4e69-8186-7abf58f65527',
                'club_id' => 'Lorem ipsum dolor s',
                'nome' => 'Lorem ipsum dolor sit amet',
                'cognome' => 'Lorem ipsum dolor sit amet',
                'data_nascita' => 'Lorem ipsum dolor ',
                'sesso' => 'L',
                'cod_fiscale' => 'Lorem ipsum dolor ',
                'federation_id' => 1,
                'n_tessera' => 'Lorem ipsum dolor sit amet',
                'peso' => 1,
                'grado' => 'Lorem ipsum d',
                'created_date' => 1736249891,
                'modified_date' => 1736249891,
            ],
        ];
        parent::init();
    }
}
