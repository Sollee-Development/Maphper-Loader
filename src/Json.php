<?php

namespace MaphperLoader;
class Json {
    private $config;
    private $dice;

    public function __construct($json, \Dice\Dice $dice) {
        if (is_array($json)) {
            $config = [];
            foreach ($json as $file) $config = array_merge_recursive($config, $this->decodeJson($file));
        }
        else $config = $this->decodeJson($json);

        $this->config = $config;
        $this->dice = $dice;
        $this->addLoader('database', new DataSource\DataBase);
    }

    private function decodeJson($json) {
        if (trim($json)[0] != '{') $json = file_get_contents($json);
        $config = json_decode($json, true);
		if (!is_array($config)) throw new \Exception('Could not decode json: ' . json_last_error_msg());
        return $config;
    }

    public function addLoader($datasourceType, DataSource $datasource) {
        $filteredConfig = array_filter($this->config, function ($config) use ($datasourceType) { return $config['type'] === $datasourceType; });
        foreach ($filteredConfig as $maphperName => $maphperConfig) $this->addMaphper($maphperName, $maphperConfig, $datasource);
    }

    private function addMaphper($name, $config, DataSource $datasource) {
        $this->dice->addRule('$Maphper_Source_' . $name, $datasource->load($config));
        $mapper = [
            'instanceOf' => 'Maphper\\Maphper',
            'substitutions' => ['Maphper\\DataSource' => ['instance' => '$Maphper_Source_' . $name]],
            'shared' => true,
            'call' => []
        ];
        if (isset($config['relations'])) foreach ($config['relations'] as $relation) {
            $relationRuleName = '$Maphper_Relation' . $name . '_' . $relation['name'];
            $this->addRelation($relationRuleName, $relation);
            $mapper['call'][] = ['addRelation', [$relation['name'], ['instance' => $relationRuleName]]];
        }

        // If `resultCLass` option is set then automatically use Dice to resolve dependencies
        if (isset($config['resultClass'])) $mapper['constructParams'] = [['resultClass' => function () use ($config) {
            return $this->dice->create($config['resultClass']);
        }]];
        $this->dice->addRule('$Maphper_' . $name, $mapper);
    }

    private function addRelation($relationRuleName, $relation) {
        if ($relation['type'] === "ManyMany") {
            $this->dice->addRule($relationRuleName, [
                'instanceOf' => 'Maphper\Relation\ManyMany',
                'constructParams' => [
                    ['instance' => '$Maphper_' . $relation['intermediate']],
                    ['instance' => '$Maphper_' . $relation['to']],
                    $relation['foreignKey'], $relation['intermediateKey'], $relation['intermediateField'] ?? null
                ]
            ]);
        }
        else {
            $this->dice->addRule($relationRuleName, [
                'instanceOf' => 'Maphper\Relation\\' . ucwords($relation['type']),
                'constructParams' => [
                    ['instance' => '$Maphper_' . $relation['to']],
                    $relation['localKey'], $relation['foreignKey']
                ]
            ]);
        }
    }

    public function getMaphper($name) {
		return $this->dice->create('$Maphper_' . $name);
	}
}
