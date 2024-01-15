<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AthletesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\AthletesTable Test Case
 */
class AthletesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\AthletesTable
     */
    protected $Athletes;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.Athletes',
        'app.Clubs',
        'app.Federations',
        'app.AthleteInscriptions',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Athletes') ? [] : ['className' => AthletesTable::class];
        $this->Athletes = $this->getTableLocator()->get('Athletes', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Athletes);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\AthletesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\AthletesTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
