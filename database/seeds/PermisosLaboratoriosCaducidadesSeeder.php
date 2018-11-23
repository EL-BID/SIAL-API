<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PermisosLaboratoriosCaducidadesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        DB::table('permisos')->insert([
            [
                'id' => '6sTjs3q8rhHslelQgTUI4hdkNSbiwyhf',
                'descripcion' => 'Sincronizar Recetas Proveedores',
                'grupo' => 'Farmacia Subrogada',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
////********************************************************************

 	        [
                'id' => 'GVnLtL6maGUSPmaiLlCgAT4FzlzHKkN0',
                'descripcion' => 'Almacenes',
                'grupo' => 'Catálogos y parámetros del sistema',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'h9IhilMjvBtC7X64A0poFV26EL5xWAyM',
                'descripcion' => 'Proveedores',
                'grupo' => 'Catálogos y parámetros del sistema',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'DbpT0VqR0DcNcqmCnwRbK7XvgWqDY2yc',
                'descripcion' => 'Servidores',
                'grupo' => 'Catálogos y parámetros del sistema',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => '56R2ES2GDbpovdiLAwFEjj75Rl975MsR',
                'descripcion' => 'Unidades Médicas',
                'grupo' => 'Catálogos y parámetros del sistema',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'Npmc6C155PMjnkPKWUFXcIF3NcegAzIE',
                'descripcion' => 'Forma Farmacéutica',
                'grupo' => 'Catálogos y parámetros del sistema',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'l9PXPHg1MMJYMKTlzXeEHNIsgw9d5oty',
                'descripcion' => 'Grupos de insumos',
                'grupo' => 'Catálogos y parámetros del sistema',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'KbzwkJtDcLGcaNhbuYd24bhdDMGaKXod',
                'descripcion' => 'Marcas',
                'grupo' => 'Catálogos y parámetros del sistema',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'JDAc3VaD3TbCIu0cUIYxZ6gG6QG32I3y',
                'descripcion' => 'Material de curación',
                'grupo' => 'Catálogos y parámetros del sistema',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'xSSmZGx6xgw4Qd4MQKlcDwxE1iD4QvxZ',
                'descripcion' => 'Medicamentos',
                'grupo' => 'Catálogos y parámetros del sistema',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'PtTJ9g7WGYcyuPjTxe5iaJILVzQedccG',
                'descripcion' => 'Presentaciones de medicamentos',
                'grupo' => 'Catálogos y parámetros del sistema',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'ygwsEwz3cUw4yVMCeaQ9hVMCFXUHri5q',
                'descripcion' => 'Programas',
                'grupo' => 'Catálogos y parámetros del sistema',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'OhAoehuuORlLObNSrzy4qpRYE89VfUdt',
                'descripcion' => 'Servicios',
                'grupo' => 'Catálogos y parámetros del sistema',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => '2CGoJAwDzH2JGpaPVUz3Vakcge5ReO9F',
                'descripcion' => 'Tipos de pedidos',
                'grupo' => 'Catálogos y parámetros del sistema',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'JHUfLL82Cp1pUI7tTKaWuCfVIaeKZk5z',
                'descripcion' => 'Tipos de personal',
                'grupo' => 'Catálogos y parámetros del sistema',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'd1V2FX6TNxO6cCSXaAZQfLiAnDoL6rnO',
                'descripcion' => 'Tipos de insumos',
                'grupo' => 'Catálogos y parámetros del sistema',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'S1Yv83vAhv2o7xzq5ur37bmbfHvsomJf',
                'descripcion' => 'Tipos de movimientos',
                'grupo' => 'Catálogos y parámetros del sistema',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'ouIq0jdKpmTNYG1f2MRjMmlKvXmSviPd',
                'descripcion' => 'Unidades de medida',
                'grupo' => 'Catálogos y parámetros del sistema',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'EBQdToSqWCpu1TJTDWBibcuGOpO97ucT',
                'descripcion' => 'Vias de administración',
                'grupo' => 'Catálogos y parámetros del sistema',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => '1ulsQmM7Abnw2V74dD2is5NEeCQq54YE',
                'descripcion' => 'Parámetros globales',
                'grupo' => 'Catálogos y parámetros del sistema',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            
            


////********************************************************************

            [
                'id' => 'PzmTtCd1MbMWVBPwVmttQQWdNfqwzp7p',
                'descripcion' => 'Entradas de Laboratorio Clinico',
                'grupo' => 'Laboratorio',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
 	        [
                'id' => '7GkcqRllVy4Z371KMLPsX0d04dqv3vBE',
                'descripcion' => 'Salidas de Laboratorio Clinico',
                'grupo' => 'Laboratorio',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'mJ2E55G7moiDdV3VpY2u7g5obUzeop7p',
                'descripcion' => 'Existencias de Laboratorio Clinico',
                'grupo' => 'Laboratorio',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
 	        [
                'id' => 'tTxAiFKSsx4xSvJjIv5jodZpliDxFe1y',
                'descripcion' => 'Realizar transferencias entre almacenes',
                'grupo' => 'Farmacia',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
 	        [
                'id' => 'xYbpHsWi4HGSXQDUmG7fcJT8ZzcZKqyb',
                'descripcion' => 'Monitor de Caducidades',
                'grupo' => 'Inventario',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'arxliSoSCp1HEYcgr2pEeyeHP0u4TbWd',
                'descripcion' => 'Movimientos Generales',
                'grupo' => 'Inventario',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],[
                'id' => 'IVgehpUXTeMa5k9BT8uqfEyayEVyxJuD',
                'descripcion' => 'Correcciones',
                'grupo' => 'Inventario',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],[
                'id' => 'H5IV7Z6CAj8V2CRIQ2wnbXrYhvjLsSBk',
                'descripcion' => 'Existencia de insumos médicos',
                'grupo' => 'Inventario',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],[
                'id' => 'cE81erieaVjvmhcb9GCYI4doqYGtTcj1',
                'descripcion' => 'Ajuste menos',
                'grupo' => 'Inventario',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],[
                'id' => '0oADIo1ltfAl4VMDVbyWgLR3rAhYGjlY',
                'descripcion' => 'Ajuste mas',
                'grupo' => 'Inventario',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
 	        [
                'id' => 'zRTSAl0H8YNFMWcn00yeeJPigztCbSdC',
                'descripcion' => 'Mis almacenes',
                'grupo' => 'Configuracion',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],[
                'id' => 'Ki9kBghgqYsY17kqL620GWYl0bpeU6TB',
                'descripcion' => 'Mis servicios',
                'grupo' => 'Configuracion',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],[
                'id' => '9dKCEyujSdLQF2CbpjXiWKeap0NlJCzw',
                'descripcion' => 'Mis Turnos',
                'grupo' => 'Configuracion',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],[
                'id' => 'BnB3LhrDbKNBrbQaeB2BPXKGrLEYrEw7',
                'descripcion' => 'Mis claves',
                'grupo' => 'Configuracion',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],[
                'id' => 'nLSqnSHHppYWQGGCrlbvCDp1Yyjcvyb3',
                'descripcion' => 'Personal Clues',
                'grupo' => 'Configuracion',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],[
                'id' => 'a1OMZVn7dveOf5aUK8V0VsvvSCxz8EMw',
                'descripcion' => 'Entradas Almacen Insumos Medicos',
                'grupo' => 'Farmacia',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],[
                'id' => 'qQvNeb1UFPOfVMKQnNkvxyqjCIUgFuEG',
                'descripcion' => 'Salidas Almacen Insumos Medicos',
                'grupo' => 'Farmacia',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
 	        [
                'id' => 'GPSDLmXckXcdfdj7lD4rdacwMivsTp9g',
                'descripcion' => 'Salidas Recetas',
                'grupo' => 'Farmacia',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => '8u2HduKCBo53Vwa2DiMh1ujytqdL9c7M',
                'descripcion' => 'Entradas Almacén General',
                'grupo' => 'Almacén General',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => '5Pnh7DTayhrND0GyB7bzfbdFK2kA6bgM',
                'descripcion' => 'Entradas Almacén General',
                'grupo' => 'Almacén General',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'VmObT0aDFLEXDMer0yvfKo76gBsGNdcR',
                'descripcion' => 'Inventario Almacén General',
                'grupo' => 'Almacén General',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => '1giAayqzxUGwhGYQgGD6PTYkPin1edAs',
                'descripcion' => 'Articulos',
                'grupo' => 'Almacén General',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'aUYWDYq2gV9RqGaIe6XdRfd2QjZOeRSP',
                'descripcion' => 'Resguardos',
                'grupo' => 'Almacén General',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'fMKARTchWDT56hgX0sNJnmTD9wcwTwK0',
                'descripcion' => 'Categorías',
                'grupo' => 'Almacén General',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]

        ]);

    }
}
