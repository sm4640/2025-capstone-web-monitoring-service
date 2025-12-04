###############################
# 1단계: Composer로 vendor 설치
###############################
FROM composer:2 AS vendor

WORKDIR /app

# composer 관련 파일만 복사해서 캐시 효율 극대화
COPY composer.json composer.lock ./

# scripts에서 artisan 호출하는 걸 피하기 위해 --no-scripts 사용
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --no-scripts

##################################
# 2단계: 실제 런타임 (PHP-FPM 8.3)
##################################
FROM php:8.3-fpm-alpine

# 빌드 및 런타임에 필요한 패키지 설치
# postgresql-dev: pdo_pgsql 빌드용 헤더/라이브러리
# (필요하면 gd, bcmath 등 추가)
RUN apk add --no-cache \
      postgresql-dev \
      icu-dev \
      oniguruma-dev \
      libzip-dev \
    && docker-php-ext-install \
      pdo \
      pdo_pgsql \
      intl \
      mbstring \
      zip

# 작업 디렉토리 지정 (Laravel 루트)
WORKDIR /var/www/html

# 1단계에서 만든 vendor 복사
COPY --from=vendor /app/vendor ./vendor

# 나머지 Laravel 코드 복사
COPY . .

# storage, cache 디렉터리 생성 및 권한 설정
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache


# (옵션) config 캐시, route 캐시 등
#  - DB 연결이 필요 없는 캐시는 빌드 타임에 해도 됨
#  - DB 관련이 있는 경우(예: migrate)는 런타임에서 처리
RUN php artisan config:cache || true \
    && php artisan route:cache || true

# php-fpm이 listen 할 포트 (기본 9000)
EXPOSE 9000

# php-fpm 실행 (이미 기본 CMD로 설정되어 있지만 명시적으로)
CMD ["php-fpm"]
