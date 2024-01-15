<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AthleteInscriptionsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\AthleteInscriptionsTable Test Case
 */
class AthleteInscriptionsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\AthleteInscriptionsTable
     */
    protected $AthleteInscriptions;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.AthleteInscriptions',
        'app.Athletes',
        'app.Categorycodes',
        'app.Competitions',
        'app.Scores',
        'app.ScoresOld',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('AthleteInscriptions') ? [] : ['className' => AthleteInscriptionsTable::class];
        $this->AthleteInscriptions = $this->getTableLocator()->get('AthleteInscriptions', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->AthleteInscriptions);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\AthleteInscriptionsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\AthleteInscriptionsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
