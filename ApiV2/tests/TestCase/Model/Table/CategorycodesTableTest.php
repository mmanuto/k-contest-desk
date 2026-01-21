<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\CategorycodesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\CategorycodesTable Test Case
 */
class CategorycodesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\CategorycodesTable
     */
    protected $Categorycodes;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.Categorycodes',
        'app.AthleteInscriptions',
        'app.Scores',
        'app.Users',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Categorycodes') ? [] : ['className' => CategorycodesTable::class];
        $this->Categorycodes = $this->getTableLocator()->get('Categorycodes', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Categorycodes);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\CategorycodesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\CategorycodesTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
