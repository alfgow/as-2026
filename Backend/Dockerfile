# Imagen base ligera
FROM ubuntu:22.04

# Instalar dependencias básicas
RUN apt-get update && apt-get install -y \
    git curl nodejs npm python3 python3-pip \
    && rm -rf /var/lib/apt/lists/*

# Crear usuario sin privilegios
RUN useradd -ms /bin/bash codex
USER codex
WORKDIR /home/codex/app

# Entrar directo a la carpeta del proyecto
CMD ["/bin/bash"]
