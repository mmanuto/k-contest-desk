<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ResultsJudgedPanelTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ResultsJudgedPanelTable Test Case
 */
class ResultsJudgedPanelTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ResultsJudgedPanelTable
     */
    protected $ResultsJudgedPanel;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.ResultsJudgedPanel',
        'app.AthleteInscriptions',
        'app.Users',
        'app.Categorycodes',
        'app.Katas',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('ResultsJudgedPanel') ? [] : ['className' => ResultsJudgedPanelTable::class];
        $this->ResultsJudgedPanel = $this->getTableLocator()->get('ResultsJudgedPanel', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->ResultsJudgedPanel);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\ResultsJudgedPanelTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\ResultsJudgedPanelTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
