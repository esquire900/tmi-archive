# TMI-archive

code for the tmi-archive.com project

## Docker setup for development

1. Build and start the service:
    ```shell
    docker-compose build --pull
    docker-compose up -d
    ```
1. Run migrations:
    ```shell
    docker-compose exec app ./manage.py migrate
    ```
1. Create an admin user:
    ```shell
    docker-compose exec app ./manage.py createsuperuser
    ```
1. Open the admin site and log in: http://127.0.0.1:8000/admin/.
1. Set the site domain: open http://127.0.0.1:8000/admin/sites/site/1/change/ and set _Domain name_ to `127.0.0.1:8000`.
