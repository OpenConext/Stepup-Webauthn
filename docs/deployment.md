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
 
```cp config/openconext/parameters.yml.dist config/openconext/parameters.yml```

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

```cp config/openconext/parameters.yml.dist config/openconext/parameters.yml```

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

### 6. Keep the FIDO Metadata Service (MDS) blob up to date

The application validates authenticators against the FIDO Alliance Metadata Service.
The blob must be present at `config/openconext/mds/blob.jwt` and refreshed periodically
(FIDO publishes updates roughly every two weeks; the blob contains a `nextUpdate` field
that declares its expiry date).

If the blob is outdated, newly registered authenticators that are not already cached will
be rejected with a hard error during registration. Existing credentials are not affected.

**Update procedure:**
1. Download the latest blob from https://fidoalliance.org/metadata/ (see "Obtaining blob").
2. Replace `config/openconext/mds/blob.jwt` with the new file.
3. Clear the MDS cache: `bin/console cache:pool:clear cache.app` (or delete `var/mds/`).
4. The root certificate `config/openconext/mds/root.crt` rarely changes; verify it only
   when the FIDO Alliance announces a root rotation.

Recommendation: schedule blob updates as part of your regular maintenance window (monthly is sufficient).

