#!/usr/bin/env bash

CURRENT_DIR=$(cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd)
PROJECT_DIR=$(dirname "$CURRENT_DIR")

source "$PROJECT_DIR/.env"

GREEN='\033[0;32m'

if [ $# -lt 1 ]; then
  echo -e "${GREEN}Specify some command for the kafka instance"
  echo -e "${GREEN}Use the syntax: ./kafka.sh kafka-topics.sh --list"
  echo -e "${GREEN}The kafka location will be retrieved from the environment file"
  echo -e ""
  echo -e "${GREEN}Also there are some shortcuts for only this application:"
  echo -e "   ${GREEN}show_topic (show the data from the application topic)"
  echo -e "   ${GREEN}list (the topics on the server)"

  exit 1
fi

execute_docker() {
  docker exec -it cbr_puller_kafka /opt/kafka/bin/$@ --bootstrap-server="$KAFKA_HOST":"$KAFKA_PORT"
}

if [ "$1" = "show_topic" ]; then
  execute_docker kafka-console-consumer.sh --topic "$KAFKA_TOPIC" --from-beginning
elif [ "$1" = "list" ]; then
   execute_docker kafka-topics.sh --list
else
  execute_docker $@
fi