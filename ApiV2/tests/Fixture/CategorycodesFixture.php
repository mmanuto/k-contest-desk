<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * CategorycodesFixture
 */
class CategorycodesFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'codice' => 'Lorem ip',
                'categoria' => 'Lorem ipsum dolor sit amet',
                'anno_min' => 1,
                'anno_max' => 1,
                'sesso' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'grado' => 'Lorem ipsum dolor sit amet',
                'cat_peso' => 'Lorem ip',
                'peso_min' => 1,
                'peso_max' => 1,
                'specialita' => 'Lorem ipsum dolor sit amet',
                'costo_intero' => 1,
                'costo_scontato' => 1,
                'codiceTipoCategorie' => 1,
            ],
        ];
        parent::init();
    }
}
