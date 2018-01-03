<?php

class JsonDataBaseLoaderTest extends PHPUnit\Framework\TestCase {
    private $dice;
    private $pdo;

    public function setUp() {
        $this->dice = new \Dice\Dice();
        $this->pdo = $this->createMock('PDO');
        $this->pdo->method('getAttribute')->willReturn('mysql');
    }

    protected function getDataSource($name, $primaryKey = 'id', array $options = []) {
		return new \Maphper\DataSource\Database($this->pdo, $name, $primaryKey, $options);
	}

    private function getMaphper($name, $primaryKey = 'id', array $options = []) {
        return new \Maphper\Maphper($this->getDataSource($name, $primaryKey, $options));
    }

    private function getLoader($json) {
        $loader = new MaphperLoader\Json($json, $this->dice);
        $loader->addLoader('database', new MockDataBase($this->pdo));
        return $loader;
    }

    public function testBasicConstruct() {
        $json = '
{
    "test" : {
        "type" : "database",
        "table" : "test",
        "primaryKey" : "id"
    }
}
        ';

        $loader = $this->getLoader($json);

        $actual = $loader->getMaphper('test');
        $expected = $this->getMaphper('test');

        $this->assertEquals($expected, $actual);
    }

    public function testConstructRelationOne() {
        $json = '
{
    "blog" : {
        "type" : "database",
        "table" : "blog",
        "primaryKey" : "id",
        "relations" : [
            {
                "name" : "author",
                "to" : "author",
                "type" : "one",
                "localKey" : "authorId",
                "foreignKey" : "id"
            }
        ]
    },
    "author" : {
        "type" : "database",
        "table" : "author",
        "primaryKey" : "id"
    }
}
        ';

        $loader = $this->getLoader($json);

        $actual = $loader->getMaphper('blog');

        $blogs = $this->getMaphper('blog');
        $authors = $this->getMaphper('author');
        $blogs->addRelation('author', new \Maphper\Relation\One($authors, 'authorId', 'id'));

        $this->assertEquals($blogs, $actual);
    }

    public function testConstructRelationMany() {
        $json = '
{
    "blog" : {
        "type" : "database",
        "table" : "blog",
        "primaryKey" : "id"
    },
    "author" : {
        "type" : "database",
        "table" : "author",
        "primaryKey" : "id",
        "relations" : [
            {
                "name" : "blogs",
                "to" : "blog",
                "type" : "many",
                "localKey" : "id",
                "foreignKey" : "authorId"
            }
        ]
    }
}
        ';

        $loader = $this->getLoader($json);

        $actual = $loader->getMaphper('author');

        $blogs = $this->getMaphper('blog');
        $authors = $this->getMaphper('author');
        $authors->addRelation('blogs', new \Maphper\Relation\Many($blogs, 'id', 'authorId'));

        $this->assertEquals($authors, $actual);
    }

    public function testConstructRelationsManyAndOne() {
        $json = '
{
    "blog" : {
        "type" : "database",
        "table" : "blog",
        "primaryKey" : "id",
        "relations" : [
            {
                "name" : "author",
                "to" : "author",
                "type" : "one",
                "localKey" : "authorId",
                "foreignKey" : "id"
            }
        ]
    },
    "author" : {
        "type" : "database",
        "table" : "author",
        "primaryKey" : "id",
        "relations" : [
            {
                "name" : "blogs",
                "to" : "blog",
                "type" : "many",
                "localKey" : "id",
                "foreignKey" : "authorId"
            }
        ]
    }
}
        ';

        $loader = $this->getLoader($json);

        $actualAuthors = $loader->getMaphper('author');
        $actualBlogs = $loader->getMaphper('blog');

        $blogs = $this->getMaphper('blog');
        $authors = $this->getMaphper('author');
        $blogs->addRelation('author', new \Maphper\Relation\One($authors, 'authorId', 'id'));
        $authors->addRelation('blogs', new \Maphper\Relation\Many($blogs, 'id', 'authorId'));

        $this->assertEquals($authors, $actualAuthors);
        $this->assertEquals($blogs, $actualBlogs);
    }

    public function testConstructRelationManyMany() {
        $json = '
{
    "actors" : {
        "type" : "database",
        "table" : "actor",
        "primaryKey" : "aid",
        "relations" : [
            {
                "name" : "movies",
                "to" : "movies",
                "type" : "ManyMany",
                "intermediate" : "cast",
                "intermediateKey" : "movieId",
                "foreignKey" : "mid"
            }
        ]
    },
    "movies" : {
        "type" : "database",
        "table" : "movie",
        "primaryKey" : "mid",
        "relations" : [
            {
                "name" : "actors",
                "to" : "actors",
                "type" : "ManyMany",
                "intermediate" : "cast",
                "intermediateKey" : "actorId",
                "foreignKey" : "aid"
            }
        ]
    },
    "cast" : {
        "type" : "database",
        "table" : "cast",
        "primaryKey" : ["movieId", "actorId"]
    }
}
        ';

        $loader = $this->getLoader($json);

        $actualActors = $loader->getMaphper('actors');
        $actualMovies = $loader->getMaphper('movies');

        // Set up expected
        $actors = $this->getMaphper('actor', 'aid');
        $movies = $this->getMaphper('movie', 'mid');
        $cast = $this->getMaphper('cast', ['movieId', 'actorId']);
		$actors->addRelation('movies', new \Maphper\Relation\ManyMany($cast, $movies, 'mid', 'movieId'));
		$movies->addRelation('actors', new \Maphper\Relation\ManyMany($cast, $actors, 'aid', 'actorId'));

        $this->assertEquals($actors, $actualActors);
        $this->assertEquals($movies, $actualMovies);
    }

    public function testBasicConstructFromFile() {
        $json = __DIR__ . '/basicConfig.json';

        $loader = $this->getLoader($json);

        $actual = $loader->getMaphper('test');
        $expected = $this->getMaphper('test');

        $this->assertEquals($expected, $actual);
    }

    public function testConstructFromMultipleFiles() {
        $json = [__DIR__ . '/multFile1.json', __DIR__ . '/multFile2.json'];

        $loader = $this->getLoader($json);

        $actual = $loader->getMaphper('author');

        $blogs = $this->getMaphper('blog');
        $authors = $this->getMaphper('author');
        $authors->addRelation('blogs', new \Maphper\Relation\Many($blogs, 'id', 'authorId'));

        $this->assertEquals($authors, $actual);
    }
}

class MockDataBase implements \MaphperLoader\DataSource {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function load(array $config)  {
        return [
            'instanceOf' => 'Maphper\\DataSource\\Database',
            'constructParams' => [
                $this->pdo,
                $config['table'],
                $config['primaryKey']
            ]
        ];
    }
}
