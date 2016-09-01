<?php
namespace MaphperLoader\DataSource;
class DataBase implements \MaphperLoader\DataSource {
    public function load(array $config)  {
        return [
            'instanceOf' => 'Maphper\\DataSource\\Database',
            'constructParams' => [
                ['instance' => 'PDO'],
                $config['table'],
                $config['primaryKey']
            ]
        ];
    }
}
