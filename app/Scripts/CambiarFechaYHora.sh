#!/bin/sh
# Cambiar fecha y hora del servidor
# - El único parametro es la fecha y hora en el siguiente formato AAAAMMDDHH:MM:SS
sudo date +"%Y-%m-%d %T" -s "$1 $2"