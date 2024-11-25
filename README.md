簡易クエリビルダ―
- 随分前に作ったもの
- PHP7.3以降

こんな感じ
```php
$simpleQueryBuilder = new SimpleQueryBuilder();
$sql = simpleQueryBuilder
    ->select(['author', 'year'])
    ->from('books')
    ->where('author', '=', ['桜玉吉', '竹本泉'])
    ->andWhere('year', '>=', 1990)
    ->order('author')->order('year', 'DESC')
    ->build();

    // SELECT
    //     author,
    //     year
    // FROM books
    // WHERE
    //     (
    //         author = '桜玉吉' OR
    //         author = '竹本泉'
    //     ) AND
    //     year >= 1990
    // ORDER BY author , year DESC
    // ;

$sql = simpleQueryBuilder
    ->insert('friends', ['id', 'name', 'age'], [1, '田中', 25], [2, '佐藤', 30])
    ->build();

    // INSERT INTO friends
    //     (id, name, age)
    // VALUES
    //     (1, '田中', 25),
    //     (2, '佐藤', 30)
    // ;

$sql = simpleQueryBuilder
    ->select(['id', 'name'])
    ->from('friends')
    ->where('age', '>=', 20)
    ->andWhere('age', '<', 30)
    ->build();

    // SELECT
    //     id,
    //     name
    // FROM friends
    // WHERE
    //     age >= 20 AND
    //     age < 30
    // ;

$sql = simpleQueryBuilder
    ->update('friends', ['age', 'name'], [26, '中田'])
    ->where('id', '=', 1)
    ->build();

    // UPDATE friends
    // SET
    //     age = 26,
    //     name = '田中'
    // WHERE
    //     id = 1
    // ;

$sql = simpleQueryBuilder
    ->update('friends', ['age'], ['age + 1'], false)
    ->where('id', '=', 1)
    ->build();

    // UPDATE friends
    // SET
    //     age = age + 1
    // WHERE
    //     id = 1
    // ;

$sql = simpleQueryBuilder
    ->delete('friends')
    ->where('id', '=', 1)
    ->build();

    // DELETE FROM friends
    // WHERE
    // id = 1
    // ;
```
