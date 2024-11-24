簡易クエリビルダ―
- 随分前に作ったもの
- PHP7.3以降

こんな感じ
```php
$simpleQueryBuilder = new SimpleQueryBuilder();
$sql = simpleQueryBuilder
    ->from('table_books')
    ->where->('author', '=', ['桜玉吉', '竹本泉'])
    ->andWhere('year', '>=', 1990)
    ->order('author')->order('year', 'DESC')
    ->build();    
```
