<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\CompetitionsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\CompetitionsTable Test Case
 */
class CompetitionsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\CompetitionsTable
     */
    protected $Competitions;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.Competitions',
        'app.AthleteInscriptions',
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
        $config = $this->getTableLocator()->exists('Competitions') ? [] : ['className' => CompetitionsTable::class];
        $this->Competitions = $this->getTableLocator()->get('Competitions', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Competitions);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\CompetitionsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
