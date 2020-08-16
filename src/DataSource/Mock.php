<?php
namespace MaphperLoader\DataSource;
class Mock implements \MaphperLoader\DataSource {
    public function load(array $config)  {
        $data = isset($config['data']) ? (array)json_decode(json_encode($config['data'])) : [];
        return [
            'instanceOf' => 'Maphper\\DataSource\\Mock',
            'constructParams' => [
                [\Dice\Dice::INSTANCE => 'ArrayObject', 'params' => [$data, 0, 'ArrayIterator']],
                $config['primaryKey']
            ]
        ];
    }
}
