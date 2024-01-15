<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ClubsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ClubsTable Test Case
 */
class ClubsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ClubsTable
     */
    protected $Clubs;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.Clubs',
        'app.Athletes',
        'app.ClubInscriptions',
        'app.Teams',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Clubs') ? [] : ['className' => ClubsTable::class];
        $this->Clubs = $this->getTableLocator()->get('Clubs', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Clubs);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\ClubsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
