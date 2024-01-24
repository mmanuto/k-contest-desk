<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ScoresFixture
 */
class ScoresFixture extends TestFixture
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
                'athlete_inscription_id' => 1,
                'user_id' => 1,
                'category_code' => 'Lorem ip',
                'type' => 'Lor',
                'n_prova' => 1,
                'kata_id' => 1,
                'opponentid' => 1.5,
                'referee_1' => 1,
                'referee_2' => 1,
                'referee_3' => 1,
                'referee_4' => 1,
                'referee_5' => 1,
                'min' => 1,
                'max' => 1,
                'valid_1' => 1,
                'valid_2' => 1,
                'valid_3' => 1,
                'total_without_penalties' => 1,
                'minutes' => 1,
                'seconds' => 1,
                'milliseconds' => 1,
                'penalty' => 1,
                'total_time' => 'Lorem ipsum dolor sit amet',
                'total_time_seconds' => 1,
                'total' => 1,
                'senshu' => 1,
                'chui_1' => 1,
                'chui_2' => 1,
                'chui_3' => 1,
                'hans_chui' => 1,
                'hansoku' => 1,
                'shikkaku' => 1,
                'ippon' => 1,
                'yuko' => 1,
                'wazaari' => 1,
                'win' => 1,
                'color' => 'Lorem ip',
                'kiken' => 1,
                'cl_position' => 1,
                'deleted' => 1,
            ],
        ];
        parent::init();
    }
}
