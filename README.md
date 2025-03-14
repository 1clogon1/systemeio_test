Запуск:
1. Скачать Docker.

2. Клонируем репозиторий:
	1) ssh - git@github.com:1clogon1/systemeio_test.git; 
	2) https - https://github.com/1clogon1/systemeio_test.git; 
	3) Скачать архив и распаковать его у себя.

3. Переходим в папку проекта в терминале(если не в ней находитесь): 
	cd .\systemeio_test\

4. Запускаем:           
	composer install

5. Добавляем данные для базы PostgreSQL:
   DATABASE_URL="postgresql://app:password@postgres:5432/app?serverVersion=13&charset=utf8"

6. Запускаем сборку и запуск контейнеров:          
  docker-compose up -d
   или
  make up

7. Запускаем миграцию таблиц:
  7.1. Создание файла миграции:
  php bin/console make:migration

  7.2. Применение миграции:
  php bin/console doctrine:migrations:migrate

8. Запросы:
  Помимо основных эндпоинтов, я добавил еще один — test-data-db, 
  предназначенный для загрузки тестовых данных в базу данных.
