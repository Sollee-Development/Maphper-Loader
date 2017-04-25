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
        $filteredConfig = array_filter($this->config, function ($config) use ($datasourceType) { return $config['type'] === $datasourceType; });
        foreach ($filteredConfig as $maphperName => $maphperConfig) $this->addMaphper($maphperName, $maphperConfig, $datasource);
    }

    private function addMaphper($name, $config, DataSource $datasource) {
        $this->dice->addRule('$Maphper_Source_' . $name, $datasource->load($config));
        $mapper = [
            'instanceOf' => 'Maphper\\Maphper',
            'substitutions' => ['Maphper\\DataSource' => ['instance' => '$Maphper_Source_' . $name]]
        ];
        // If `resultCLass` option is set then automatically use Dice to resolve dependencies
        if (isset($config['resultClass'])) $mapper['constructParams'] = [['resultClass' => function () use ($config) {
            return $this->dice->create($config['resultClass']);
        }]];
        $this->dice->addRule('$Maphper_' . $name, $mapper);
    }

    public function getMaphper($name) {
        $maphper = $this->dice->create('$Maphper_' . $name);
        $this->addRelations($maphper, $name);
		return $maphper;
	}

    private function addRelations(&$maphper, $name, $recursive = true, $toMaphper = null) {
        $config = $this->config[$name];
        if (isset($config['relations'])) foreach ($config['relations'] as $relation) {
            if ($relation['type'] === "ManyMany") $this->addManyMany($maphper, $name, $config, $relation);
            else $this->addOneOrManyRelation($maphper, $name, $config, $relation, $recursive, $toMaphper);
        }
        return $maphper;
    }

    private function addManyMany(&$maphper, $name, $config, $relation) {
        $intermediateMaphper = $this->dice->create('$Maphper_' . $relation['intermediate']);

        $maphperSettings = $this->config[$relation['to']];
        $to = $this->dice->create('$Maphper_' . $relation['to']);
        $maphperRelation = new \Maphper\Relation\ManyMany($intermediateMaphper, $to, $relation['foreignKey'], $relation['intermediateKey'], isset($relation['intermediateField']) ? $relation['intermediateKey'] : null);
        $maphper->addRelation($relation['name'], $maphperRelation);
        $otherRelation = $maphperSettings['relations'][array_search($name, array_column($maphperSettings['relations'], 'to'))];
        $to->addRelation($otherRelation['name'], new \Maphper\Relation\ManyMany($intermediateMaphper, $maphper, $config['primaryKey'],
            $otherRelation['intermediateKey'], isset($otherRelation['intermediateField']) ? $otherRelation['intermediateKey'] : null));
    }

    private function addOneOrManyRelation(&$maphper, $name, $config, $relation, $recursive = true, $toMaphper = null) {
        $relationType = 'Maphper\\Relation\\' . ucwords($relation['type']);
        $maphperSettings = $this->config[$relation['to']];
        $to = $toMaphper ?? $this->dice->create('$Maphper_' . $relation['to']);
        $maphperRelation = new $relationType($to, $relation['localKey'], $relation['foreignKey']);
        $maphper->addRelation($relation['name'], $maphperRelation);

        if ($recursive) $this->addRelations($to, $relation['to'], false, $maphper);
    }
}
