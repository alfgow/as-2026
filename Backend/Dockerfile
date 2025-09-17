FROM node:20-bullseye

# Instalar dependencias básicas y PHP + Composer
RUN apt-get update && apt-get install -y \
    git curl unzip \
    php-cli php-mbstring php-xml php-curl php-zip php-mysql \
    python3 python3-pip \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer (última versión desde el instalador oficial)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Instalar Codex CLI global (como root, antes de cambiar de usuario)
RUN npm install -g @openai/codex

# Crear usuario no-root
RUN useradd -ms /bin/bash codex
USER codex
WORKDIR /home/codex/app

CMD ["/bin/bash"]
