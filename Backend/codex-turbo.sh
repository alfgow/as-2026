#!/bin/bash
set -e

# Ruta fija al archivo credentials.json
export CODEX_CONFIG="/home/codex/.config/codex/credentials.json"

# Mensaje de inicio
echo "⚡ Ejecutando Codex con credenciales persistentes en:"
echo "   $CODEX_CONFIG"
echo

# Inicializar git si no existe
if [ ! -d ".git" ]; then
  git init
  git config user.email "codex@example.com"
  git config user.name "Codex Agent"
  git config --global --add safe.directory /home/codex/app
  git add .
  git commit -m "Estado inicial del proyecto en Docker"
fi

# Crear o cambiar a la rama de pruebas
if git show-ref --quiet refs/heads/codex-playground; then
  git checkout codex-playground
else
  git checkout -b codex-playground
fi

# Ejecutar Codex CLI sin pedir login (usa credentials.json)
codex "$@"

# Mostrar diff de cambios hechos por Codex
echo
echo "📋 Cambios realizados por Codex:"
git status
git diff

# Preguntar si mergear al main SOLO al final
read -p "¿Quieres mergear los cambios a main? (s/n): " merge
if [ "$merge" = "s" ]; then
  git checkout main || git checkout -b main
  git merge codex-playground
  echo "✅ Cambios mergeados a main."
else
  echo "❌ Los cambios se quedan en la rama 'codex-playground'."
fi
