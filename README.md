Lightweight PHP template parser

Docs/how-to guide is here: http://webew.ru/articles/3609.webew (russian language)

## Websun namespace

С вводом в игру namespace-а использовать класс надо через статик-хелпер:

```
return Websun\websun::websun_parse_template_path( $template_data, $template_file, $template_path );

```

Или
```
require_once 'engine/Websun/websun.php';

$template = Websun\websun::websun_parse_template_path( [
    'value1'    =>  1,
    'value2'    =>  2
], 'template1.html', __DIR__ . '/templates' );

echo $template , PHP_EOL;


```