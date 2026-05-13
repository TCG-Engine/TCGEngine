# New Docker App Skill

This skill provides instructions on how to add a new TCGEngine root application to the docker-compose environment.

## Workflow to add a new app (e.g., `NewGame`)

1. **Update `docker-compose.yml`**:
   - Duplicate the `web-server`, `mysql-server`, `phpmyadmin`, and `redis` services.
   - Rename them using the pattern `<rootname>-<service-name>` (e.g., `newgame-web-server`).
   - Update the following:
     - **Ports**: Assign a unique host port (e.g., `3300:80` for web, `5103:80` for phpmyadmin, `6484:6379` for redis).
     - **Environment Variables**:
       - `MYSQL_SERVER_NAME`: Set to `<rootname>-mysql-server`.
       - `REDIS_HOST`: Set to `<rootname>-app_redis`.
       - `MYSQL_DATABASE`: Set to the desired database name for the new app.
     - **Depends On**: Update to point to the new `<rootname>-redis` and `<rootname>-mysql-server`.
     - **Container Name**: Update redis `container_name` to `<rootname>_app_redis`.
   - Add a new named volume for the database: `<rootname>-mysql-data`.

2. **Update `docker-compose.override.yml`**:
   - Duplicate the `web-server` debug configuration for the new `<rootname>-web-server`.

3. **Validation**:
   - Run `docker compose up -d`.
   - Ping the new ports to ensure services are reachable.
