<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\UsersCategorycodesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\UsersCategorycodesTable Test Case
 */
class UsersCategorycodesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\UsersCategorycodesTable
     */
    protected $UsersCategorycodes;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.UsersCategorycodes',
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
        $config = $this->getTableLocator()->exists('UsersCategorycodes') ? [] : ['className' => UsersCategorycodesTable::class];
        $this->UsersCategorycodes = $this->getTableLocator()->get('UsersCategorycodes', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->UsersCategorycodes);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\UsersCategorycodesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\UsersCategorycodesTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
