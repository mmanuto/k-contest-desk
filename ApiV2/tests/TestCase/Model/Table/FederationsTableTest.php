<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\FederationsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\FederationsTable Test Case
 */
class FederationsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\FederationsTable
     */
    protected $Federations;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.Federations',
        'app.Athletes',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Federations') ? [] : ['className' => FederationsTable::class];
        $this->Federations = $this->getTableLocator()->get('Federations', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Federations);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\FederationsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
