<?php
namespace MaphperLoader\DataSource;
class DataBase implements \MaphperLoader\DataSource {
    private $editMode;

    public function __construct($editMode = false) {
        $this->editMode = $editMode;
    }

    public function load(array $config)  {
        return [
            'instanceOf' => 'Maphper\\DataSource\\Database',
            'constructParams' => [
                ['instance' => 'PDO'],
                $config['table'],
                $config['primaryKey'],
                ['editmode' => $this->editMode]
            ]
        ];
    }
}
