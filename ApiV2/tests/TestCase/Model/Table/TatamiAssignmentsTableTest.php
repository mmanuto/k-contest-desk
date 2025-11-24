<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TatamiAssignmentsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\TatamiAssignmentsTable Test Case
 */
class TatamiAssignmentsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\TatamiAssignmentsTable
     */
    protected $TatamiAssignments;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.TatamiAssignments',
        'app.Users',
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
        $config = $this->getTableLocator()->exists('TatamiAssignments') ? [] : ['className' => TatamiAssignmentsTable::class];
        $this->TatamiAssignments = $this->getTableLocator()->get('TatamiAssignments', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->TatamiAssignments);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\TatamiAssignmentsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\TatamiAssignmentsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
