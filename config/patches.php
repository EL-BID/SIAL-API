<?php

/*
 * Este archivo es parte del procedimiento de aplicación de parches al código del sistema de servidores offline
 *
 * (c) Hugo César Gutiérrez Corzo <hugo.corzo@outlook.com>
 *
 */


return [
	[ "nombre" => "25C6083F763EB4F414EC55FC3283F7B7.sial.api.4.patch", "fecha" => "2018-08-10", "ejecutar" =>  "\App\Librerias\Patches\Parches::ejecutarParche4"],
	[ "nombre" => "35915E24B6C820FB9EB4847E05A4AC6F.sial.api.3.patch", "fecha" => "2018-06-15", "ejecutar" =>  "\App\Librerias\Patches\Parches::ejecutar"],
	[ "nombre" => "B8DD2113084EC4D3B2E97F081BD081E4.sial.api.2.patch", "fecha" => "2018-03-16", "ejecutar" =>  "\App\Librerias\Patches\Parches::ejecutar"],
	[ "nombre" => "F24DBF6BE70AB8B018323D6EA4601455.sial.api.1.patch", "fecha" => "2018-03-16", "ejecutar" =>  "\App\Librerias\Patches\Parches::ejecutar"],
];