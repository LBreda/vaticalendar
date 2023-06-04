# VatiCalendar

iCalendar generator with the :vatican_city:Vatican holidays. A working instance is on
[https://vaticalendar.lbreda.com](https://vaticalendar.lbreda.com). The holidays names are in Italian.

## Installation
```bash
git clone https://github.com/LBreda/vaticalendar
cd vaticalendar
composer install
```

Referencing a php-enabled http server on the `public` directory will generate the ICS file. You can also statically
generate it by executing the index.php file.