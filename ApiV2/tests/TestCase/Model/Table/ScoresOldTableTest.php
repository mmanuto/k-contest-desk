<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ScoresOldTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ScoresOldTable Test Case
 */
class ScoresOldTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ScoresOldTable
     */
    protected $ScoresOld;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.ScoresOld',
        'app.AthleteInscriptions',
        'app.Users',
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
        $config = $this->getTableLocator()->exists('ScoresOld') ? [] : ['className' => ScoresOldTable::class];
        $this->ScoresOld = $this->getTableLocator()->get('ScoresOld', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->ScoresOld);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\ScoresOldTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\ScoresOldTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
