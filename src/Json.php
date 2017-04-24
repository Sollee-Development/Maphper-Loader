<?php

namespace MaphperLoader;
class Json {
    private $config;
    private $dice;

    public function __construct($json, \Dice\Dice $dice) {
        if (is_array($json)) {
            $config = [];
            foreach ($json as $file) {
                $fileJson = file_get_contents($file);
                $fileConfig = json_decode($fileJson, true);
        		if (!is_array($fileConfig)) throw new \Exception('Could not decode json: ' . json_last_error_msg());

                $config = array_merge_recursive($config, $fileConfig);
            }
        }
        else {
            if (trim($json)[0] != '{') $json = file_get_contents($json);

            $config = json_decode($json, true);

    		if (!is_array($config)) throw new \Exception('Could not decode json: ' . json_last_error_msg());
        }

        $this->config = $config;
        $this->dice = $dice;
        $this->addLoader('database', new DataSource\DataBase);
    }

    public function addLoader($datasourceType, DataSource $datasource) {
        foreach ($this->config as $maphperName => $sourceConfig) {
            if ($sourceConfig['type'] != $datasourceType) continue;
            $this->dice->addRule('$Maphper_Source_' . $maphperName, $datasource->load($sourceConfig));
            $rules = [];
            if (isset($sourceConfig['relations'])) foreach ($sourceConfig['relations'] as $relationConfig) {
                if ($relationConfig['type'] === 'ManyMany') continue;
                $relation = [];
			    $relation['instanceOf'] = 'Maphper\\Relation\\' . ucwords($relationConfig['type']);
			    $relation['substitutions'] = ['Maphper\\Maphper' => ['instance' => '$Maphper_' . $relationConfig['to']]];
				$relation['constructParams'] = [$relationConfig['localKey'], $relationConfig['foreignKey']];
				$name = '$Maphper_Relation_' . $maphperName . '_' . $relationConfig['name'];
				$rules[$relationConfig['name']] = $name;
				$this->dice->addRule($name, $relation);
			}
            $mapper = [];
			$mapper['instanceOf'] = 'Maphper\\Maphper';
			$mapper['substitutions'] = ['Maphper\\DataSource' => ['instance' => '$Maphper_Source_' . $maphperName]];
            //$mapper['shared'] = true;
            $mapper['call'] = [];
			foreach ($rules as $name => $rule) $mapper['call'][] = ['addRelation', [$name, ['instance' => $rule]]];
            // If `resultCLass` option is set then automatically use Dice to resolve dependencies
            if (isset($sourceConfig['resultClass'])) $mapper['constructParams'] = [['resultClass' => function () use ($sourceConfig) {
                return $this->dice->create($sourceConfig['resultClass']);
            }]];
			$this->dice->addRule('$Maphper_' . $maphperName, $mapper);
        }
    }

    public function getMaphper($name) {
        $maphper = $this->dice->create('$Maphper_' . $name);
        if (isset($this->config[$name]['relations']) && array_search('ManyMany', array_column($this->config[$name]['relations'], 'type')) !== false)
            $this->addManyMany($maphper, $name);
		return $maphper;
	}

    private function addManyMany(&$maphper, $name) {
        $config = $this->config[$name];
        foreach ($config['relations'] as $relation) {
            if ($relation['type'] !== "ManyMany") continue;
            $intermediateMaphper = $this->dice->create('$Maphper_' . $relation['intermediate']);

            $maphperSettings = $this->config[$relation['to']];
            $to = $this->dice->create('$Maphper_' . $relation['to']);
            $maphperRelation = new \Maphper\Relation\ManyMany($intermediateMaphper, $to, $relation['foreignKey'], $relation['intermediateKey'], isset($relation['intermediateField']) ? $relation['intermediateKey'] : null);
            $maphper->addRelation($relation['name'], $maphperRelation);
            $otherRelation = $maphperSettings['relations'][array_search($name, array_column($maphperSettings['relations'], 'to'))];
            $to->addRelation($otherRelation['name'], new \Maphper\Relation\ManyMany($intermediateMaphper, $maphper, $config['primaryKey'],
                $otherRelation['intermediateKey'], isset($otherRelation['intermediateField']) ? $otherRelation['intermediateKey'] : null));
        }
        return $maphper;
    }
}
