#!/bin/sh
# Aplicar parche
# - El primer parametro es el directorio donde se debe ejecutar el comando git
# - El segundo parametro es la ruta donde se subió el parche para ejecutarlo

cd $1
sudo git am --resolvemsg="Patch error sumami" --signoff  < $2
