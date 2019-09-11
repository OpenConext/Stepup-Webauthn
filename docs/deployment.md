Production deployment guide
=====================

Requirements
-------------------

- php >=7.2
- node >=8 (Only for creation front-end assets)
- yarn (Only for creation front-end assets)
- Composer

Install (with build archive)
-------------------

### 1. Copy and configure the configuration files
 
```cp .env.dist .env```

```cp config/packages/parameters.yml.dist config/packages/parameters.yml```

### 2. Create archive

```composer archive --file=archive```

### 3. Deploy archive on production

Configure web directory to /public.

### 4. Create database (If not exists)

```
 bin/console doctrine:database:create
```

### 5. Update or create schema 

```
 bin/console doctrine:migrations:migrate
```

Install (without build archive)
-------------------

### 1. Copy and configure the configuration files

```cp .env.dist .env```

```cp config/packages/parameters.yml.dist config/packages/parameters.yml```

```composer dump-env prod```

### 2. Build public assets

```
 yarn encore prod
 ./bin/console assets:install
```

### 3. Create database (If not exists)

```
 bin/console doctrine:database:create
```

### 4. Update schema 

```
 bin/console doctrine:migrations:migrate
```

### 5. Warm-up cache

```
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
```

