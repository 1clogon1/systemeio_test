Запуск:
1. Скачать Docker.

2. Клонируем репозиторий:

   	2.1. ssh - git@github.com:1clogon1/systemeio_test.git; 
	2.2. https - https://github.com/1clogon1/systemeio_test.git; 
	2.3. Скачать архив и распаковать его у себя.

4. Переходим в папку проекта в терминале(если не в ней находитесь):

	`cd .\systemeio_test\`.

5. Запускаем composer:
   
	4.1. Подключаемся к контейнеру:
   	`docker compose exec sio_test /bin/bash`;

   	4.2. Подкачиваем нужные библиотеки:
	`composer install`.

6. Добавляем данные для базы PostgreSQL:

   	`DATABASE_URL="postgresql://app:password@postgres:5432/app?serverVersion=13&charset=utf8"`.

7. Запускаем сборку и запуск контейнеров:

  	`docker-compose up -d`
   	или
  	`make up`.

7. Запускаем миграцию таблиц:

  	7.1. Создание файла миграции:
  	`php bin/console make:migration`;

  	7.2. Применение миграции:
  	`php bin/console doctrine:migrations:migrate`.

8. Запросы:

  	Помимо основных эндпоинтов, я добавил еще один — `test-data-db`, предназначенный для загрузки тестовых данных в базу данных.
