# Installation

Place the plugin directory at `plugins/ai_roundcube_assistant/`.

Copy the distributed config:

```sh
cp plugins/ai_roundcube_assistant/config.inc.php.dist plugins/ai_roundcube_assistant/config.inc.php
```

Enable it in Roundcube:

```php
$config['plugins'][] = 'ai_roundcube_assistant';
```

Optional database tables:

- MySQL/MariaDB: `SQL/mysql.initial.sql`
- PostgreSQL: `SQL/postgres.initial.sql`
- SQLite: `SQL/sqlite.initial.sql`

Do not hardcode install paths in plugin configuration. Keep provider endpoints and API keys in server-side config.
