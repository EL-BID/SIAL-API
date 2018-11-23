#!/bin/sh
# Abortar parches cuando hay error
# - El único parametro es el directorio donde se debe ejecutar el comando git
cd $1
sudo git am --abort
echo "Parche abortado"
