<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * KatasFixture
 */
class KatasFixture extends TestFixture
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
                'kata_name' => 'Lorem ipsum dolor sit amet',
            ],
        ];
        parent::init();
    }
}
