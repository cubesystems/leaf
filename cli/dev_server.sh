#!/bin/bash
HOST=${1:-localhost:8080}
DIR=$(cd . $(dirname "$0"); pwd)
CMD="php -S $HOST -t $DIR $DIR/cli/routing.php"
$CMD
