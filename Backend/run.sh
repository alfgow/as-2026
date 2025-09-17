#!/bin/bash

# Construir imagen si no existe
docker build -t codex-sandbox .

# Ejecutar contenedor
docker run -it --rm \
  -v $(pwd):/home/codex/app \
  codex-sandbox
