# Install

`composer require hotlog/php-client`

# Usage

1. Set the required ENV variables

```
HOTLOG_URL='http://localhost:8090'
HOTLOG_CLIENT_ID='my_php_project'
HOTLOG_CLIENT_NAME='my_php_project'
HOTLOG_CLIENT_COLOR='#526CEB'
```

2. Log

`hotlog('My string')`

`hotlog($object)`
