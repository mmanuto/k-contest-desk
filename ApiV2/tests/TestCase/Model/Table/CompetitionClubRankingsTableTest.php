<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\CompetitionClubRankingsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\CompetitionClubRankingsTable Test Case
 */
class CompetitionClubRankingsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\CompetitionClubRankingsTable
     */
    protected $CompetitionClubRankings;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.CompetitionClubRankings',
        'app.Competitions',
        'app.Clubs',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('CompetitionClubRankings') ? [] : ['className' => CompetitionClubRankingsTable::class];
        $this->CompetitionClubRankings = $this->getTableLocator()->get('CompetitionClubRankings', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->CompetitionClubRankings);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\CompetitionClubRankingsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\CompetitionClubRankingsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
