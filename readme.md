*Esta herramienta digital forma parte del catálogo de herramientas del **Banco Interamericano de Desarrollo**. Puedes conocer más sobre la iniciativa del BID en [code.iadb.org](https://code.iadb.org)*

## API del Sistema de Gestión de Incidencias en Salud SIAL .   

[![Build Status](https://travis-ci.org/EL-BID/SIAL-API.svg?branch=master)](https://travis-ci.org/EL-BID/SIAL-API)
[![Analytics](https://gabeacon.irvinlim.com/UA-4677001-16/SIAL-API/readme?useReferer)](https://github.com/EL-BID/SIAL-API)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=EL-BID_SIAL-API&metric=alert_status)](https://sonarcloud.io/dashboard?id=EL-BID_SIAL-API)

### Descripción y contexto
---
[Salud Mesoamérica, ME-G1004](https://www.iadb.org/en/project/ME-G1004 )

Sistema de información logística en el Sector Salud, mediante la gestión oportuna de equipamiento e insumos para la salud (medicamentos, material de curación, sustancias de laboratorio, mobiliario y activo fijo) a travÈs de la cadena de suministros (Almacén Central, Almacenes jurisdiccionales, Almacenes Hospitales, Laboratorios, Farmacias y otras Unidades Operativas). Ademas, la herramienta permite el monitoreo y evaluación de los procesos logísticos para la toma de decisiones gerenciales y la mejora continua del sistema. Este sistema cuenta con versiones en línea y fuera de línea.

La arquitectura REST es muy útil para construir un cliente/servidor para aplicaciones en red. REST significa Representational State Transfer (Transferencia de Estado Representacional) de sus siglas en inglés. Una API REST es una API, o librería de funciones, a la cual accedemos mediante protocolo HTTP, ósea desde direcciones webs o URL mediante las cuales el servidor procesa una consulta a una base de datos y devuelve los datos del resultado en formato XML, JSON, texto plano, etc. (Para el proyecto CIUM nos enfocaremos en JSON) Mediante REST utilizas los llamados Verbos HTTP, que son GET (Mostrar), POST (Insertar), PUT (Agregar/Actualizar) y DELETE (Borrar).

### Guía de usuario
---

![Configuracion Header para Login desde postman](https://github.com/EL-BID/SIAL-API/blob/master/public/img/LoginHeader.png)

![Login desde postman](https://github.com/EL-BID/SIAL-API/blob/master/public/img/LoginUser.png)

![Listar unidades medicas](https://github.com/EL-BID/SIAL-API/blob/master/public/img/ListaUnidadesMedicas.png)

### Guía de instalación
---
#### Requisitos
##### Software
Para poder instalar y utilizar esta API, deberá asegurarse de que su servidor cumpla con los siguientes requisitos:
* MySQL®
* PHP >= 5.6.4
* OpenSSL PHP Extension
* PDO PHP Extension
* Mbstring PHP Extension
* Tokenizer PHP Extension
* XML PHP Extension
* [Composer](https://getcomposer.org/) es una librería de PHP para el manejo de dependencias.
* Opcional [Postman](https://www.getpostman.com/) que permite el envío de peticiones HTTP REST sin necesidad de desarrollar un cliente.

#### Instalación
Guia de Instalación Oficial de Laravel 5.4 [Aquí](https://laravel.com/docs/5.4/installation)
##### Proyecto (API)
El resto del proceso es sencillo.
1. Clonar el repositorio con: `git clone https://github.com/EL-BID/SIAL-API.git`
2. Instalar dependencias: `composer install`
3. Renombrar el archivo `base.env` a `.env` ubicado en la raiz del directorio de instalación y editarlo.
       
       APP_ENV=local
APP_KEY=base64:7I20xkOJi3sU0NtEE7Ojl6EvA/DdLu2mhi492MKriFI=
APP_DEBUG=true
APP_LOG_LEVEL=debug
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sial
DB_USERNAME=DB_USER
DB_PASSWORD=DB_PASSWORD

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_DRIVER=sync

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_DRIVER=smtp
MAIL_HOST=mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null

SERVIDOR_ID=0029
SECRET_KEY=2915515032
VERSION=0.5
CLUES=CSSSA006403CSSSA006403CSSSA006403CSSSA006403

DB_HOST_SYNC=10.10.10.10
DB_DATABASE_SYNC=sial_central
DB_USERNAME_SYNC=USER
DB_PASSWORD_SYNC=PASSWORD


SERVIDOR_INSTALADO=false

       
  

##### Base de Datos del proyecto
1. Abrir su Sistema Gestor de Base de Datos y crear la base de datos `sial`.
2. Abrir una terminal con la ruta raiz donde fue clonado el proyecto y correr cualquiera de los siguientes comandos:
    * `php artisan migrate --seed` Crea las tablas y e inserta datos precargados de muestra.
    * `php artisan migrate` Solo crea las tablas sin datos.
3. Una vez configurado el proyecto se inicia con `php artisan serve` y nos levanta un servidor: 
    * `http://127.0.0.1:8000` o su equivalente `http://localhost:8000`

### Cómo contribuir
---
Si deseas contribuir con este proyecto, por favor lee las siguientes guías que establece el [BID](https://www.iadb.org/es "BID"):

* [Guía para Publicar Herramientas Digitales](https://el-bid.github.io/guia-de-publicacion/ "Guía para Publicar") 
* [Guía para la Contribución de Código](https://github.com/EL-BID/Plantilla-de-repositorio/blob/master/CONTRIBUTING.md "Guía de Contribución de Código")

### Código de conducta 
---
Puedes ver el código de conducta para este proyecto en el siguiente archivo [CODEOFCONDUCT.md](https://github.com/EL-BID/SIAL-API/blob/master/CODEOFCONDUCT.md).

### Autor/es
---
* **[Joram Roblero Pérez](https://github.com/joramdeveloper  "Github")**   
* **[Ramiro Gabriel Alférez Chavez](mailto:ramiro.alferez@gmail.com "Correo electrónico")**
* **[Eliecer Ramirez Esquinca](https://github.com/checherman "Github")**
* **[Deysi Helen Ortega Roman](https://github.com/deysukiz "Github")**
* ** ISECH ** - Secretaria de Salud del Estado de Chiapas 

### Información adicional
---
Para usar el sistema completo con una interfaz web y no solo realizar las peticiones HTTP REST, debe tener configurado el siguiente proyecto:
* **[Cliente WEB SIAL](https://github.com/EL-BID/SIAL-cliente "Proyecto WEB que complementa el sistema")**

### Licencia 
---
La Documentación de Soporte y Uso del software se encuentra licenciada bajo Creative Commons IGO 3.0 Atribución-NoComercial-SinObraDerivada (CC-IGO 3.0 BY-NC-ND)  [LICENSE.md](https://github.com/EL-BID/SIAL-API/blob/master/LICENSE.md)

## Limitación de responsabilidades

El BID no será responsable, bajo circunstancia alguna, de daño ni indemnización, moral o patrimonial; directo o indirecto; accesorio o especial; o por vía de consecuencia, previsto o imprevisto, que pudiese surgir:

i. Bajo cualquier teoría de responsabilidad, ya sea por contrato, infracción de derechos de propiedad intelectual, negligencia o bajo cualquier otra teoría; y/o

ii. A raíz del uso de la Herramienta Digital, incluyendo, pero sin limitación de potenciales defectos en la Herramienta Digital, o la pérdida o inexactitud de los datos de cualquier tipo. Lo anterior incluye los gastos o daños asociados a fallas de comunicación y/o fallas de funcionamiento de computadoras, vinculados con la utilización de la Herramienta Digital.
