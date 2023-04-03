# Alpha Tiles Data Setup

Rename .env.example to .env

Install Docker. 

In the Docker settings -> Resources -> File sharing -> add the path to where you saved the project.

Run the following commands in the terminal in the root of your project:

```
$ docker-compose up -d
$ docker exec alphatilesassistant_app_1 composer install
$ cp src/.env.example src/.env
$ docker exec alphatilesassistant_app_1 php artisan key:generate
$ cd src
$ npm install
$ npm run dev
```

Go to http://localhost:8800/ 
