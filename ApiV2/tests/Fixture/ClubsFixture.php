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
                'id' => 1,
                'nome_societa' => 'Lorem ipsum dolor sit amet',
                'responsabile' => 'Lorem ipsum dolor sit amet',
                'telefono' => 'Lorem ipsum dolor ',
                'mail' => 'Lorem ipsum dolor sit amet',
                'coach' => 'Lorem ipsum dolor sit amet',
                'CF' => 'Lorem ipsum dolor ',
            ],
        ];
        parent::init();
    }
}
