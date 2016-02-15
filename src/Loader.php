<?php

namespace MaphperLoader;
class Loader {
    private $config;
    private $pdo;

    public function __construct($json, \PDO $pdo) {
        if (trim($json)[0] != '{') {
			$path = dirname(realpath($json));
			$json = str_replace('__DIR__', $path, file_get_contents($json));
		}

        $config = json_decode($json, true);

		if (!is_array($config)) throw new \Exception('Could not decode json: ' . json_last_error_msg());

        $this->config = $config;
        $this->pdo = $pdo;
    }

    public function getMaphper($name) {

        if (!isset($this->config[$name])) throw new \Exception("No Maphper of name '$name' is registered.");

        $maphperSettings = $this->config[$name];

        if ($maphperSettings['type'] === 'database')
            $datasource = $this->getDatabaseDataSource($maphperSettings['table'], $maphperSettings['primaryKey']);

        if (!isset($datasource)) throw new \Exception("There is no Datasource for '$name'");

        $maphper = new \Maphper\Maphper($datasource);
        if (isset($maphperSettings['relations'])) foreach ($maphperSettings['relations'] as $relation) {
            $relation = $this->getRelation($relation);
            $maphper->addRelation($relation['name'], $relation);
        }

        return $maphper;
    }

    private function getDatabaseDataSource($tableName, $primaryKey = 'id') {
        $datasource = new \Maphper\DataSource\Database($this->pdo, $tableName, $primaryKey);
        return $datasource;
    }

    private function getRelation($settings) {
        $maphperSettings = $this->config[$settings['to']];
        $datasource = $this->getDatabaseDataSource($maphperSettings['table'], $maphperSettings['primaryKey']);
        $maphper = new \Maphper\Maphper($datasource);
        $relation_class = '\\Maphper\\Relation\\' . ucwords($settings['type']);
        $relation = new $relation_class($maphper, $settings['localKey'], $settings['foreignKey']);
        return $relation;
    }
}

?>
