Table Archiver
==============
This is a CLI tool to export old data from any SQL database table into ranged ndjson files 
that you can then wherever you like.

## Installation

    git clone git@github.com:linkorb/table-archiver.git
    cd table-archiver
    docker-compose up -d
    docker-compose exec app composer install # install PHP dependencies

## Usage
Command looks like:

    ./bin/console linkorb:table:archive {db_dsn} {table_name} {mode} {date_column} [max_stamp]\
See `./bin/console linkorb:table:archive --help` for more info

(To run it from docker container you need to prepend it with `docker-compose exec app `...)

All gzipped [ndjson](http://ndjson.org/) files can be found under `./output` directory

If you want to change threads number you can do that easily by changing `APP_THREADS_NUMBER` in `.env`

### Example:
    
    ./bin/console linkorb:table:archive mysql://root:root@127.0.0.1:3306/test target_table YEAR_MONTH_DAY timestamp 20200101
