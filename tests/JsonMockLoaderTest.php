<?php

class JsonMockLoaderTest extends PHPUnit\Framework\TestCase {
    private $dice;
    private $pdo;

    public function setUp() {
        $this->dice = new \Dice\Dice();
        $this->pdo = $this->createMock('PDO');
        $this->pdo->method('getAttribute')->willReturn('mysql');
    }

    protected function getDataSource(\ArrayObject $obj, $primaryKey = 'id') {
		return new \Maphper\DataSource\Mock($obj, $primaryKey);
	}

    private function getMaphper(\ArrayObject $obj = null, $primaryKey = 'id') {
        $obj = $obj ?? new \ArrayObject();
        return new \Maphper\Maphper($this->getDataSource($obj, $primaryKey));
    }

    private function getLoader($json) {
        $loader = new MaphperLoader\Json($json, $this->dice);
        //$loader->addLoader('database', new MockDataBase($this->pdo));
        return $loader;
    }

    public function testBasicConstruct() {
        $json = '
{
    "test" : {
        "type" : "mock",
        "primaryKey" : "id"
    }
}
        ';

        $loader = $this->getLoader($json);

        $actual = $loader->getMaphper('test');
        $expected = $this->getMaphper();

        $this->assertEquals($expected, $actual);
    }

    public function testBasicConstructWithData() {
        $json = '
{
    "test" : {
        "type" : "mock",
        "primaryKey" : "id",
        "data" : [
            {
                "id" : "1",
                "title" : "test1"
            },
            {
                "id" : "2",
                "title" : "test2"
            }
        ]
    }
}
        ';

        $loader = $this->getLoader($json);

        $actual = $loader->getMaphper('test');
        $expected = $this->getMaphper(new \ArrayObject([
            (object)[
                "id" => "1",
                "title" => "test1"
            ],
            (object)[
                "id" => "2",
                "title" => "test2"
            ]
        ]));

        $this->assertEquals($expected[1], $actual[1]);
        $this->assertEquals($expected[2], $actual[2]);
    }

    public function testBasicConstructWithObjData() {
        $json = '
{
    "test" : {
        "type" : "mock",
        "primaryKey" : "id",
        "data" : {
            "2" : {
                "id" : "1",
                "title" : "test1"
            },
            "5" : {
                "id" : "2",
                "title" : "test2"
            }
        }
    }
}
        ';

        $loader = $this->getLoader($json);

        $actual = $loader->getMaphper('test');
        $expected = $this->getMaphper(new \ArrayObject([
            2 => (object)[
                "id" => "1",
                "title" => "test1"
            ],
            5 => (object)[
                "id" => "2",
                "title" => "test2"
            ]
        ]));

        $this->assertEquals($expected[2], $actual[2]);
        $this->assertEquals($expected[5], $actual[5]);
    }

    public function testConstructRelationOne() {
        $json = '
{
    "blog" : {
        "type" : "mock",
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
        "type" : "mock",
        "primaryKey" : "id"
    }
}
        ';

        $loader = $this->getLoader($json);

        $actual = $loader->getMaphper('blog');

        $blogs = $this->getMaphper();
        $authors = $this->getMaphper();
        $blogs->addRelation('author', new \Maphper\Relation\One($authors, 'authorId', 'id'));

        $this->assertEquals($blogs, $actual);
    }

    public function testConstructRelationMany() {
        $json = '
{
    "blog" : {
        "type" : "mock",
        "primaryKey" : "id"
    },
    "author" : {
        "type" : "mock",
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

        $blogs = $this->getMaphper();
        $authors = $this->getMaphper();
        $authors->addRelation('blogs', new \Maphper\Relation\Many($blogs, 'id', 'authorId'));

        $this->assertEquals($authors, $actual);
    }

    public function testConstructRelationsManyAndOne() {
        $json = '
{
    "blog" : {
        "type" : "mock",
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
        "type" : "mock",
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

        $blogs = $this->getMaphper();
        $authors = $this->getMaphper();
        $blogs->addRelation('author', new \Maphper\Relation\One($authors, 'authorId', 'id'));
        $authors->addRelation('blogs', new \Maphper\Relation\Many($blogs, 'id', 'authorId'));

        $this->assertEquals($authors, $actualAuthors);
        $this->assertEquals($blogs, $actualBlogs);
    }

    public function testConstructRelationManyMany() {
        $json = '
{
    "actors" : {
        "type" : "mock",
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
        "type" : "mock",
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
        "type" : "mock",
        "primaryKey" : ["movieId", "actorId"]
    }
}
        ';

        $loader = $this->getLoader($json);

        $actualActors = $loader->getMaphper('actors');
        $actualMovies = $loader->getMaphper('movies');

        // Set up expected
        $actors = $this->getMaphper(new \ArrayObject(), 'aid');
        $movies = $this->getMaphper(new \ArrayObject(), 'mid');
        $cast = $this->getMaphper(new \ArrayObject(), ['movieId', 'actorId']);
		$actors->addRelation('movies', new \Maphper\Relation\ManyMany($cast, $movies, 'mid', 'movieId'));
		$movies->addRelation('actors', new \Maphper\Relation\ManyMany($cast, $actors, 'aid', 'actorId'));

        $this->assertEquals($actors, $actualActors);
        $this->assertEquals($movies, $actualMovies);
    }
}
