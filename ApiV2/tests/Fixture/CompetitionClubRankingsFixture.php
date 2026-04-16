<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * CompetitionClubRankingsFixture
 */
class CompetitionClubRankingsFixture extends TestFixture
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
                'competition_id' => 'Lorem ipsum dolor sit amet',
                'club_id' => 'Lorem ipsum dolor sit amet',
                'golds' => 1,
                'silvers' => 1,
                'bronzes' => 1,
                'total_points' => 1,
                'rank_position' => 1,
                'created' => '2026-03-18 14:07:49',
            ],
        ];
        parent::init();
    }
}
