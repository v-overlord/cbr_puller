#!/usr/bin/env bash

CURRENT_DIR=$(cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd)
PROJECT_DIR=$(dirname "$CURRENT_DIR")

ENV_EXAMPLE_PATH="$PROJECT_DIR/env.example"
ENV_PATH="$PROJECT_DIR/.env"

PGID="$(id -u)"
PUID="$(id -g)"

RED='\033[0;31m'
GREEN='\033[0;32m'

if [ $# -ne 1 ]; then
  echo -e "${GREEN}Select the appropriate command: init, install, 1 [up], 0 [down]"
  exit 1
fi

docker --version

cd "$CURRENT_DIR" || exit 1

do_composer_install () {
  docker exec -w /app --user "$PUID:$PGID" -it cbr_puller_cli composer install
}

if [ "$1" = "init" ]; then
  ### Prepare environment
  if [ ! -f "$ENV_PATH" ]; then
    cp "$ENV_EXAMPLE_PATH" "$ENV_PATH"
    sed -i "s/USR/$PUID/g" "$ENV_PATH"
    sed -i "s/GRP/$PGID/g" "$ENV_PATH"
  fi

  # shellcheck source=../.env
  source "$ENV_PATH"

  docker compose --env-file "$ENV_PATH" up -d --force-recreate --no-deps --build

  #### Do some required actions
  do_composer_install
elif [ "$1" = "install" ]; then
  do_composer_install
elif [ "$1" = "1" ]; then
  docker compose --env-file "$ENV_PATH" up -d
elif [ "$1" = "0" ]; then
  docker compose --env-file "$ENV_PATH" down --remove-orphans
  docker compose --env-file "$ENV_PATH" rm -svf
else
  echo -e "${RED}Command $1 not found!"
fi