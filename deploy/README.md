# Jira Dashboard Backend Deployment

## Docker Image

```
dockerhub.viettelsoftware.com/bu01-reg/jira-dashboard:1.0.0
```

## Prerequisites

- Docker
- Docker Compose

## Files

- docker-compose.yml
- .env.example

Đổi tên `.env.example` thành `.env` và cập nhật các giá trị cần thiết.

## Generate Application Key

Cấu hình `APP_KEY`:

```bash
docker exec -it jira-dashboard php artisan key:generate
```

## Generate JWT Secret

Cấu hình `JWT`:

```bash
docker exec -it jira-dashboard php artisan jwt:secret
```

## Deploy

```bash
docker compose pull
docker compose up -d
```

## Run Database Migrations

Sau khi các container khởi động thành công chạy 2 lệnh:

```bash
docker exec -it jira-dashboard php artisan migrate --force
```

```bash
docker exec -it jira-dashboard php artisan db:seed --force
```

## Access Application

```
http://<server-ip>:8000
```