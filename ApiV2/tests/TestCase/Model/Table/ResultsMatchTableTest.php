<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ResultsMatchTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ResultsMatchTable Test Case
 */
class ResultsMatchTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ResultsMatchTable
     */
    protected $ResultsMatch;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.ResultsMatch',
        'app.Categorycodes',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('ResultsMatch') ? [] : ['className' => ResultsMatchTable::class];
        $this->ResultsMatch = $this->getTableLocator()->get('ResultsMatch', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->ResultsMatch);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\ResultsMatchTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\ResultsMatchTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
