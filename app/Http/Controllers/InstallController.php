<?php

namespace App\Http\Controllers;

use JWTAuth, JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Input;

use Illuminate\Http\Request, DB;
use \Hash, \Config, Carbon\Carbon;
use App\Models\UnidadMedica, App\Models\Servidor, App\Models\Proveedor, App\Models\Almacen;

class InstallController extends Controller
{
    public function iniciarInstalacion(){
        $base_clues = [
            "CSSSA018583" => "BELISARIO DOMÍNGUEZ",
            "CSSSA017521" => "H. B. C.  DE TAPILULA",
            "CSSSA018740" => "H. B. C.  DR. RAFAEL ALFARO GONZÁLEZ PIJIJIAPAN",
            "CSSSA000412" => "H. B. C. DE ÁNGEL ALBINO CORZO",
            "CSSSA018793" => "H. B. C. DE CHALCHIHUITÁN",
            "CSSSA000832" => "H. B. C. DE CINTALAPA DE FIGUEROA",
            "CSSSA018781" => "H. B. C. DE FRONTERA COMALAPA",
            "CSSSA017726" => "H. B. C. DE LARRAINZAR",
            "CSSSA003265" => "H. B. C. DE LAS MARGARITAS",
            "CSSSA019645" => "H. B. C. DE OCOSINGO",
            "CSSSA019242" => "H. B. C. DE OSTUACÁN",
            "CSSSA017516" => "H. B. C. DE REVOLUCIÓN MEXICANA",
            "CSSSA019481" => "H. B. C. DE SAN JUAN CHAMULA",
            "CSSSA017731" => "H. B. C. DE SANTO DOMINGO",
            "CSSSA018752" => "H. B. C. DE TEOPISCA",
            "CSSSA006934" => "H. B. C. DE TILA",
            "CSSSA017504" => "H. B. C. DEL PORVENIR",
            "CSSSA000045" => "H. B. C. DR. MANUEL VELASCO SUAREZ ACALA",
            "CSSSA019954" => "HOSPITAL CHIAPAS NOS UNE DR. JESUS GILBERTO GOMEZ MAZA",
            "CSSSA018776" => "HOSPITAL DE LA MUJER COMITÁN",
            "CSSSA005773" => "HOSPITAL DE LA MUJER SAN CRISTÓBAL DE LAS CASAS",
            "CSSSA018764" => "HOSPITAL DE LAS CULTURAS SAN CRISTOBAL DE LAS CASAS",
            "CSSSA018875" => "HOSPITAL GENERAL BICENTENARIO VILLAFLORES",
            "CSSSA007074" => "HOSPITAL GENERAL DR. JUAN C. CORZO TONALÁ",
            "CSSSA002611" => "HOSPITAL GENERAL HUIXTLA",
            "CSSSA000453" => "HOSPITAL GENERAL JUÁREZ ARRIAGA",
            "CSSSA001030" => "HOSPITAL GENERAL MARÍA IGNACIA GANDULFO COMITAN",
            "CSSSA004595" => "HOSPITAL GENERAL PALENQUE",
            "CSSSA004945" => "HOSPITAL GENERAL PICHUCALCO",
            "CSSSA006403" => "HOSPITAL GENERAL TAPACHULA",
            "CSSSA008264" => "HOSPITAL GENERAL YAJALÓN",
            "CSSSA007540" => "HOSPITAL REGIONAL DR. RAFAEL PASCASIO GAMBOA TUXTLA",
            "CSSSA004950" => "PICHUCALCO",
            "CSSSA007634" => "TUXTLA GUTIÉRREZ",
            //Harima: Se agregan Jurisdicciones
            "CSSSA017336" => "ALMACÉN JURISDICCIONAL (TUXTLA GUTIÉRREZ)",
            "CSSSA017341" => "ALMACÉN JURISDICCIONAL (SAN CRISTÓBAL DE LAS CASAS)",
            "CSSSA017353" => "ALMACÉN JURISDICCIONAL (COMITÁN)",
            "CSSSA017365" => "ALMACÉN JURISDICCIONAL (VILLAFLORES)",
            "CSSSA017370" => "ALMACÉN JURISDICCIONAL (PICHUCALCO)",
            "CSSSA017382" => "ALMACÉN JURISDICCIONAL (PALENQUE)",
            "CSSSA017394" => "ALMACÉN JURISDICCIONAL (TAPACHULA)",
            "CSSSA017406" => "ALMACÉN JURISDICCIONAL (TONALÁ)",
            "CSSSA017411" => "ALMACÉN JURISDICCIONAL (OCOSINGO)",
            "JURISMOTO10" => "ALMACÉN JURISDICCIONAL (MOTOZINTLA)",
            // Akira: Se agregan las oficinas jurisdiccionales
            "CSSSA017225" => "OFICINA JURISDICCIONAL (TUXTLA GUTIÉRREZ)",
            "CSSSA017230" => "OFICINA JURISDICCIONAL (SAN CRISTÓBAL DE LAS CASAS)",
            "CSSSA017242" => "OFICINA JURISDICCIONAL (COMITÁN)",
            "CSSSA008112" => "OFICINA JURISDICCIONAL (VILLAFLORES)",
            "CSSSA017266" => "OFICINA JURISDICCIONAL (PICHUCALCO)",
            "CSSSA017271" => "OFICINA JURISDICCIONAL (PALENQUE)",
            "CSSSA017283" => "OFICINA JURISDICCIONAL (TAPACHULA)",
            "CSSSA017295" => "OFICINA JURISDICCIONAL (TONALÁ)",
            "CSSSA017300" => "OFICINA JURISDICCIONAL (OCOSINGO)",
            "CSSSA017312" => "OFICINA JURISDICCIONAL (MOTOZINTLA)",
            //AGREGADAS POR MI
            "CSSSAHICCOR" => "H. B. C. CHIAPA DE CORZO",
            "CSSSA009162" => "USM SAN AGUSTIN",
            "CSSSAHIROSA" => "H. B. C. LAS ROSAS",
            "CSSSAHGREFO" => "HOSPITAL GENERAL REFORMA ",
            "CSSSAHIBERR" => "H. B. C. BERRIOZABAL",
            "CSSSAOXCHUC" => "H. B. C. OXCHUC ",
            "CSSSASIMOJO" => "H. B. C. DE SIMOJOVEL",
            //Harima: Se agrega almacen estatal
            "CSSSA017324" => "ALMACÉN ESTATAL (TUXTLA GUTIÉRREZ)",
        ];

        return view('install',['clues'=>$base_clues]);
    }

    public function instalar(Request $request){
        //Arreglo con los datos para configurar el .env
        $lista_config_server = [
            "CSSSA018583" => [ 'id' => "0034",   'clues' => "CSSSA018583",  'secret' => "2247006975",   'proveedor' => 2,  'almacen' => "0001130",  'nombre' => "BELISARIO DOMÍNGUEZ"],
            "CSSSA017521" => [ 'id' => "0021",   'clues' => "CSSSA017521",  'secret' => "1983359044",   'proveedor' => 3,  'almacen' => "000132",   'nombre' => "H. B. C.  DE TAPILULA"],
            "CSSSA018740" => [ 'id' => "0031",   'clues' => "CSSSA018740",  'secret' => "5078409268",   'proveedor' => 3,  'almacen' => "000135",   'nombre' => "H. B. C.  DR. RAFAEL ALFARO GONZÁLEZ PIJIJIAPAN"],
            "CSSSA000412" => [ 'id' => "0017",   'clues' => "CSSSA000412",  'secret' => "9995519635",   'proveedor' => 1,  'almacen' => "00012",    'nombre' => "H. B. C. DE ÁNGEL ALBINO CORZO"],
            "CSSSA018793" => [ 'id' => "0007",   'clues' => "CSSSA018793",  'secret' => "3170902090",   'proveedor' => 3,  'almacen' => "000140",   'nombre' => "H. B. C. DE CHALCHIHUITÁN"],
            "CSSSA000832" => [ 'id' => "0005",   'clues' => "CSSSA000832",  'secret' => "3660506709",   'proveedor' => 1,  'almacen' => "00015",    'nombre' => "H. B. C. DE CINTALAPA DE FIGUEROA"],
            "CSSSA018781" => [ 'id' => "0013",   'clues' => "CSSSA018781",  'secret' => "2101156574",   'proveedor' => 2,  'almacen' => "000139",   'nombre' => "H. B. C. DE FRONTERA COMALAPA"],
            "CSSSA017726" => [ 'id' => "0010",   'clues' => "CSSSA017726",  'secret' => "4664320142",   'proveedor' => 3,  'almacen' => "000133",   'nombre' => "H. B. C. DE LARRAINZAR"],
            "CSSSA003265" => [ 'id' => "0015",   'clues' => "CSSSA003265",  'secret' => "3138767971",   'proveedor' => 2,  'almacen' => "00019",    'nombre' => "H. B. C. DE LAS MARGARITAS"],
            "CSSSA019645" => [ 'id' => "0033",   'clues' => "CSSSA019645",  'secret' => "4089082143",   'proveedor' => 3,  'almacen' => "000144",   'nombre' => "H. B. C. DE OCOSINGO"],
            "CSSSA019242" => [ 'id' => "0020",   'clues' => "CSSSA019242",  'secret' => "8817975671",   'proveedor' => 3,  'almacen' => "000142",   'nombre' => "H. B. C. DE OSTUACÁN"],
            "CSSSA017516" => [ 'id' => "0018",   'clues' => "CSSSA017516",  'secret' => "7044768263",   'proveedor' => 1,  'almacen' => "000131",   'nombre' => "H. B. C. DE REVOLUCIÓN MEXICANA"],
            "CSSSA019481" => [ 'id' => "0008",   'clues' => "CSSSA019481",  'secret' => "7878769657",   'proveedor' => 3,  'almacen' => "000143",   'nombre' => "H. B. C. DE SAN JUAN CHAMULA"],
            "CSSSA017731" => [ 'id' => "0027",   'clues' => "CSSSA017731",  'secret' => "3736988669",   'proveedor' => 3,  'almacen' => "000134",   'nombre' => "H. B. C. DE SANTO DOMINGO"],
            "CSSSA018752" => [ 'id' => "0011",   'clues' => "CSSSA018752",  'secret' => "3179677262",   'proveedor' => 3,  'almacen' => "000136",   'nombre' => "H. B. C. DE TEOPISCA"],
            "CSSSA006934" => [ 'id' => "0025",   'clues' => "CSSSA006934",  'secret' => "5812555610",   'proveedor' => 3,  'almacen' => "000116",   'nombre' => "H. B. C. DE TILA"],
            "CSSSA017504" => [ 'id' => "0035",   'clues' => "CSSSA017504",  'secret' => "9715488935",   'proveedor' => 2,  'almacen' => "000130",   'nombre' => "H. B. C. DEL PORVENIR"],
            "CSSSA000045" => [ 'id' => "0003",   'clues' => "CSSSA000045",  'secret' => "8933717531",   'proveedor' => 1,  'almacen' => "00011",    'nombre' => "H. B. C. DR. MANUEL VELASCO SUAREZ ACALA"],
            "CSSSA019954" => [ 'id' => "0006",   'clues' => "CSSSA019954",  'secret' => "7739033758",   'proveedor' => 1,  'almacen' => "000145",   'nombre' => "HOSPITAL CHIAPAS NOS UNE DR. JESUS GILBERTO GOMEZ MAZA"],
            "CSSSA018776" => [ 'id' => "0016",   'clues' => "CSSSA018776",  'secret' => "489150398",    'proveedor' => 2,  'almacen' => "000138",   'nombre' => "HOSPITAL DE LA MUJER COMITÁN"],
            "CSSSA005773" => [ 'id' => "0012",   'clues' => "CSSSA005773",  'secret' => "368112968",    'proveedor' => 3,  'almacen' => "000114",   'nombre' => "HOSPITAL DE LA MUJER SAN CRISTÓBAL DE LAS CASAS"],
            "CSSSA018764" => [ 'id' => "0009",   'clues' => "CSSSA018764",  'secret' => "989932662",    'proveedor' => 3,  'almacen' => "000137",   'nombre' => "HOSPITAL DE LAS CULTURAS SAN CRISTOBAL DE LAS CASAS"],
            "CSSSA018875" => [ 'id' => "0019",   'clues' => "CSSSA018875",  'secret' => "8660233373",   'proveedor' => 1,  'almacen' => "000141",   'nombre' => "HOSPITAL GENERAL BICENTENARIO VILLAFLORES"],
            "CSSSA007074" => [ 'id' => "0032",   'clues' => "CSSSA007074",  'secret' => "5220912034",   'proveedor' => 3,  'almacen' => "000117",   'nombre' => "HOSPITAL GENERAL DR. JUAN C. CORZO TONALÁ"],
            "CSSSA002611" => [ 'id' => "0028",   'clues' => "CSSSA002611",  'secret' => "3874109173",   'proveedor' => 3,  'almacen' => "00018",    'nombre' => "HOSPITAL GENERAL HUIXTLA"],
            "CSSSA000453" => [ 'id' => "0030",   'clues' => "CSSSA000453",  'secret' => "9587203042",   'proveedor' => 3,  'almacen' => "00013",    'nombre' => "HOSPITAL GENERAL JUÁREZ ARRIAGA"],
            "CSSSA001030" => [ 'id' => "0014",   'clues' => "CSSSA001030",  'secret' => "2507276605",   'proveedor' => 2,  'almacen' => "00016",    'nombre' => "HOSPITAL GENERAL MARÍA IGNACIA GANDULFO COMITAN"],
            "CSSSA004595" => [ 'id' => "0024",   'clues' => "CSSSA004595",  'secret' => "7167855854",   'proveedor' => 3,  'almacen' => "000111",   'nombre' => "HOSPITAL GENERAL PALENQUE"],
            "CSSSA004945" => [ 'id' => "0022",   'clues' => "CSSSA004945",  'secret' => "1938660584",   'proveedor' => 3,  'almacen' => "000112",   'nombre' => "HOSPITAL GENERAL PICHUCALCO"],
            "CSSSA006403" => [ 'id' => "0029",   'clues' => "CSSSA006403",  'secret' => "2915515032",   'proveedor' => 3,  'almacen' => "000115",   'nombre' => "HOSPITAL GENERAL TAPACHULA"],
            "CSSSA008264" => [ 'id' => "0026",   'clues' => "CSSSA008264",  'secret' => "674539541",    'proveedor' => 3,  'almacen' => "000119",   'nombre' => "HOSPITAL GENERAL YAJALÓN"],
            "CSSSA007540" => [ 'id' => "0004",   'clues' => "CSSSA007540",  'secret' => "6593023161",   'proveedor' => 1,  'almacen' => "000118",   'nombre' => "HOSPITAL REGIONAL DR. RAFAEL PASCASIO GAMBOA TUXTLA"],
            "CSSSA004950" => [ 'id' => "0023",   'clues' => "CSSSA004950",  'secret' => "1958902083",   'proveedor' => 3,  'almacen' => "0001142",  'nombre' => "PICHUCALCO"],
            "CSSSA007634" => [ 'id' => "0002",   'clues' => "CSSSA007634",  'secret' => "7050669754",   'proveedor' => 1,  'almacen' => "000187",   'nombre' => "TUXTLA GUTIÉRREZ"],
            //Harima: Se agregan Jurisdicciones
            "CSSSA017336" => [ 'id' => "0036",   'clues' => "CSSSA017336",   'secret' => "3159842440",  'proveedor' => 1,   'almacen' => "000121",   'nombre' => "ALMACÉN JURISDICCIONAL (TUXTLA GUTIÉRREZ)"],
            "CSSSA017341" => [ 'id' => "0037",   'clues' => "CSSSA017341",   'secret' => "7878818111",  'proveedor' => 3,   'almacen' => "000122",   'nombre' => "ALMACÉN JURISDICCIONAL (SAN CRISTÓBAL DE LAS CASAS)"],
            "CSSSA017353" => [ 'id' => "0038",   'clues' => "CSSSA017353",   'secret' => "8914563545",  'proveedor' => 2,   'almacen' => "000123",   'nombre' => "ALMACÉN JURISDICCIONAL (COMITÁN)"],
            "CSSSA017365" => [ 'id' => "0039",   'clues' => "CSSSA017365",   'secret' => "9936363698",  'proveedor' => 1,   'almacen' => "000124",   'nombre' => "ALMACÉN JURISDICCIONAL (VILLAFLORES)"],
            "CSSSA017370" => [ 'id' => "0040",   'clues' => "CSSSA017370",   'secret' => "1938128168",  'proveedor' => 3,   'almacen' => "000125",   'nombre' => "ALMACÉN JURISDICCIONAL (PICHUCALCO)"],
            "CSSSA017382" => [ 'id' => "0041",   'clues' => "CSSSA017382",   'secret' => "8881550050",  'proveedor' => 3,   'almacen' => "000126",   'nombre' => "ALMACÉN JURISDICCIONAL (PALENQUE)"],
            "CSSSA017394" => [ 'id' => "0042",   'clues' => "CSSSA017394",   'secret' => "7593366056",  'proveedor' => 3,   'almacen' => "000127",   'nombre' => "ALMACÉN JURISDICCIONAL (TAPACHULA)"],
            "CSSSA017406" => [ 'id' => "0043",   'clues' => "CSSSA017406",   'secret' => "1032218043",  'proveedor' => 3,   'almacen' => "000128",   'nombre' => "ALMACÉN JURISDICCIONAL (TONALÁ)"],
            "CSSSA017411" => [ 'id' => "0044",   'clues' => "CSSSA017411",   'secret' => "7830804335",  'proveedor' => 3,   'almacen' => "000129",   'nombre' => "ALMACÉN JURISDICCIONAL (OCOSINGO)"],
            "JURISMOTO10" => [ 'id' => "0045",   'clues' => "JURISMOTO10",   'secret' => "7187480665",  'proveedor' => 2,   'almacen' => "000147",   'nombre' => "ALMACÉN JURISDICCIONAL (MOTOZINTLA)"],
            // Akira: Se agregan las oficinas jurisdiccionales
            "CSSSA017225" => [ 'id' => "0053",   'clues' => "CSSSA017225",   'secret' => "6798232336",  'proveedor' => 1,   'almacen' => "000121",   'nombre' => "OFICINA JURISDICCIONAL (TUXTLA GUTIÉRREZ)"],
            "CSSSA017230" => [ 'id' => "0054",   'clues' => "CSSSA017230",   'secret' => "1073598978",  'proveedor' => 3,   'almacen' => "000122",   'nombre' => "OFICINA JURISDICCIONAL (SAN CRISTÓBAL DE LAS CASAS)"],
            "CSSSA017242" => [ 'id' => "0055",   'clues' => "CSSSA017242",   'secret' => "8627238963",  'proveedor' => 2,   'almacen' => "000123",   'nombre' => "OFICINA JURISDICCIONAL (COMITÁN)"],
            "CSSSA008112" => [ 'id' => "0056",   'clues' => "CSSSA008112",   'secret' => "7133401780",  'proveedor' => 1,   'almacen' => "000124",   'nombre' => "OFICINA JURISDICCIONAL (VILLAFLORES)"],
            "CSSSA017266" => [ 'id' => "0057",   'clues' => "CSSSA017266",   'secret' => "8679923415",  'proveedor' => 3,   'almacen' => "000125",   'nombre' => "OFICINA JURISDICCIONAL (PICHUCALCO)"],
            "CSSSA017271" => [ 'id' => "0058",   'clues' => "CSSSA017271",   'secret' => "8422071869",  'proveedor' => 3,   'almacen' => "000126",   'nombre' => "OFICINA JURISDICCIONAL (PALENQUE)"],
            "CSSSA017283" => [ 'id' => "0059",   'clues' => "CSSSA017283",   'secret' => "7864626441",  'proveedor' => 3,   'almacen' => "000127",   'nombre' => "OFICINA JURISDICCIONAL (TAPACHULA)"],
            "CSSSA017295" => [ 'id' => "0060",   'clues' => "CSSSA017295",   'secret' => "4143478746",  'proveedor' => 3,   'almacen' => "000128",   'nombre' => "OFICINA JURISDICCIONAL (TONALÁ)"],
            "CSSSA017300" => [ 'id' => "0061",   'clues' => "CSSSA017300",   'secret' => "1043177405",  'proveedor' => 3,   'almacen' => "000129",   'nombre' => "OFICINA JURISDICCIONAL (OCOSINGO)"],
            "CSSSA017312" => [ 'id' => "0062",   'clues' => "CSSSA017312",   'secret' => "9005130614",  'proveedor' => 2,   'almacen' => "000147",   'nombre' => "OFICINA JURISDICCIONAL (MOTOZINTLA)"],
            //Nuevos Mario
            "CSSSAHICCOR" => [ 'id' => "0046",   'clues' => "CSSSAHICCOR",   'secret' => "4985136749",  'proveedor' => 1,   'almacen' => "00017",     'nombre' => "H. B. C. CHIAPA DE CORZO"],
            "CSSSA009162" => [ 'id' => "0047",   'clues' => "CSSSA009162",   'secret' => "3154796318",  'proveedor' => 1,   'almacen' => "000120",    'nombre' => "USM SAN AGUSTIN"],
            "CSSSAHIROSA" => [ 'id' => "0048",   'clues' => "CSSSAHIROSA",   'secret' => "5219746318",  'proveedor' => 1,   'almacen' => "000113",    'nombre' => "H. B. C. LAS ROSAS"],
            "CSSSAHGREFO" => [ 'id' => "0049",   'clues' => "CSSSAHGREFO",   'secret' => "3482167945",  'proveedor' => 1,   'almacen' => "000146",    'nombre' => "HOSPITAL GENERAL REFORMA"],
            "CSSSAHIBERR" => [ 'id' => "0050",   'clues' => "CSSSAHIBERR",   'secret' => "2497615487",  'proveedor' => 1,   'almacen' => "00014",     'nombre' => "H. B. C. BERRIOZABAL"],
            "CSSSAOXCHUC" => [ 'id' => "0051",   'clues' => "CSSSAOXCHUC",   'secret' => "3415798462",  'proveedor' => 1,   'almacen' => "0001282",   'nombre' => "H. B. C. OXCHUC"],
            "CSSSASIMOJO" => [ 'id' => "0052",   'clues' => "CSSSASIMOJO",   'secret' => "3486197469",  'proveedor' => 1,   'almacen' => "0001283",   'nombre' => "H. B. C. DE SIMOJOVEL"],
            //Harima: Se agrega almacen estatal
            "CSSSA017324" => [ 'id' => "0063",   'clues' => "CSSSA017324",   'secret' => "5586997806",  'proveedor' => 5,   'almacen' => "0001175",   'nombre' => "ALMACÉN ESTATAL (TUXTLA GUTIÉRREZ)"],
        ];
        
        $lista_personal_clues = [
            ['id'=>'00011',    'incremento' => 1,   'clues' => 'CSSSA004945', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "Dra. Trinidad Vera Juan"],
            ['id'=>'000110',   'incremento' => 10,  'clues' => 'CSSSA018776', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "DR. FRANCISCO JAVIER TREJO ESQUINCA"],
            ['id'=>'0001103',  'incremento' => 103, 'clues' => 'CSSSA001030', 'created_at' => "2017-05-29 06:26:36", 'updated_at' => "2017-05-29 06:26:36", 'nombre' => "Dr. Rodolfo Lopez Solis"],
            ['id'=>'0001104',  'incremento' => 104, 'clues' => 'CSSSA001030', 'created_at' => "2017-05-29 06:26:36", 'updated_at' => "2017-05-29 06:26:36", 'nombre' => "Ing. Alfredo Armando Maza Lopez"],
            ['id'=>'0001105',  'incremento' => 105, 'clues' => 'CSSSA007074', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "MIGUEL ANGEL SURIANO MORALES"],
            ['id'=>'0001106',  'incremento' => 106, 'clues' => 'CSSSA004595', 'created_at' => "2017-06-11 08:06:15", 'updated_at' => "2017-06-11 08:06:15", 'nombre' => "DR. OSCAR ALFARO ZEBADÚA"],
            ['id'=>'0001107',  'incremento' => 107, 'clues' => 'CSSSA006403', 'created_at' => "2017-07-12 09:46:15", 'updated_at' => "2017-07-12 09:46:15", 'nombre' => "DR. ANGEL GABRIEL OCAMPO GONZALEZ"],
            ['id'=>'000111',   'incremento' => 11,  'clues' => 'CSSSA005773', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "DR. FRANCISCO ARTURO MARISCAL OCHOA"],
            ['id'=>'0001112',  'incremento' => 112, 'clues' => 'CSSSA018740', 'created_at' => "2017-08-25 05:54:16", 'updated_at' => "2017-08-25 05:54:18", 'nombre' => "Lic. María Guadalupe Roque Cruz"],
            ['id'=>'0001113',  'incremento' => 113, 'clues' => 'CSSSA007540', 'created_at' => "2017-09-04 06:20:26", 'updated_at' => "2017-09-04 06:20:26", 'nombre' => "DRA. VILMA MAYTE MESSNER RAMOS"],
            ['id'=>'0001116',  'incremento' => 116, 'clues' => 'CSSSA019954', 'created_at' => "2017-10-03 08:39:00", 'updated_at' => "2017-10-03 08:39:00", 'nombre' => "DR. ZEIN NAZAR MORALES"],
            ['id'=>'000112',   'incremento' => 12,  'clues' => 'CSSSA001030', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "Dr. Jorge Antonio Yañez Fuentes"],
            ['id'=>'000113',   'incremento' => 13,  'clues' => 'CSSSA018764', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "DR. MARCO ANTONIO FLORES PEREZ"],
            ['id'=>'000114',   'incremento' => 14,  'clues' => 'CSSSA018752', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "DR. DARVIN ALBERTO RODRIGUEZ MORALES"],
            ['id'=>'000115',   'incremento' => 15,  'clues' => 'CSSSA000045', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "DRA. NOEMI HERNÁNDEZ PÉREZ"],
            ['id'=>'000116',   'incremento' => 16,  'clues' => 'CSSSA018740', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "Dr Pedro Hugo Ibarra Campero"],
            ['id'=>'000117',   'incremento' => 17,  'clues' => 'CSSSA000832', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "Dr. Ramdoll Iván Hernández Rodriguez"],
            ['id'=>'00012',    'incremento' => 2,   'clues' => 'CSSSA018875', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "DR. MARCO ANTONIO MORENO GOMEZ"],
            ['id'=>'000127',   'incremento' => 27,  'clues' => 'CSSSA004595', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "DR. DANIEL ROBERTO MARTINEZ PÉREZ"],
            ['id'=>'000128',   'incremento' => 28,  'clues' => 'CSSSA017731', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "Dr. Alfredo Hernandez Jimenez"],
            ['id'=>'000129',   'incremento' => 29,  'clues' => 'CSSSA019481', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "DRA. SOFIA CARLOTA AGUILAR HERRERA"],
            ['id'=>'00013',    'incremento' => 3,   'clues' => 'CSSSA007540', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "DR. ZEIN NAZAR MORALES"],
            ['id'=>'000130',   'incremento' => 30,  'clues' => 'CSSSA017726', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "Dr. Alam Porfirio Campos Cruz"],
            ['id'=>'000131',   'incremento' => 31,  'clues' => 'CSSSA006403', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "DR. MIGUEL ANGEL BARRIOS ANDALUZ"],
            ['id'=>'000132',   'incremento' => 32,  'clues' => 'CSSSA017504', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "DR. OSVALDO BLANCO PEREZ"],
            ['id'=>'000133',   'incremento' => 33,  'clues' => 'CSSSA018781', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "DR.EDUARD ORLANDO RUIZ DOMINGUEZ"],
            ['id'=>'000134',   'incremento' => 34,  'clues' => 'CSSSA017516', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "DR. ROUSSEL DAMIAN PALENCIA"],
            ['id'=>'000136',   'incremento' => 36,  'clues' => 'CSSSA019954', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "DR. MARTÍN ALONSO JARA BURGUETE"],
            ['id'=>'000137',   'incremento' => 37,  'clues' => 'CSSSA000412', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "Dra. Dania Citlali Molina Palacios"],
            ['id'=>'000138',   'incremento' => 38,  'clues' => 'CSSSA019242', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "Dr. Jose Luis Torres Valdes"],
            ['id'=>'000139',   'incremento' => 39,  'clues' => 'CSSSA008264', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "DR. KRISTIHAN RUBEN HERNANDEZ RODRIGUEZ"],
            ['id'=>'00014',    'incremento' => 4,   'clues' => 'CSSSA019645', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "DR. CARLOS MARTÍN RÍOS AGUILAR"],
            ['id'=>'000140',   'incremento' => 40,  'clues' => 'CSSSA003265', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "LUIS EDUARDO SIERRA LEÓN"],
            ['id'=>'000141',   'incremento' => 41,  'clues' => 'CSSSA018793', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "DRA. MARGARITA DEL ROCIO MARTINEZ JACOBO"],
            ['id'=>'000142',   'incremento' => 42,  'clues' => 'CSSSA017521', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "DRA MANUELLY DE ITZEL CABRERA ABARCA"],
            ['id'=>'00015',    'incremento' => 5,   'clues' => 'CSSSA000453', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "Dr. Alejandro Exzacarias Farrera"],
            ['id'=>'000152',   'incremento' => 52,  'clues' => 'CSSSA004945', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "Lic. Jose Javier Diaz Eliaz"],
            ['id'=>'000153',   'incremento' => 53,  'clues' => 'CSSSA018875', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "LIC. MAYRA ALEJANDRA SIBAJA DOMINGUEZ"],
            ['id'=>'000154',   'incremento' => 54,  'clues' => 'CSSSA007540', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "LIC. RAMON GONZALO LOPEZ AGUILAR"],
            ['id'=>'000155',   'incremento' => 55,  'clues' => 'CSSSA019645', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "LIC. ELISEO PINTO MORRISON"],
            ['id'=>'000156',   'incremento' => 56,  'clues' => 'CSSSA000453', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "Nury Yaneth Hernandez Lopez"],
            ['id'=>'000157',   'incremento' => 57,  'clues' => 'CSSSA002611', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "Ever Chacón Baneco"],
            ['id'=>'000159',   'incremento' => 59,  'clues' => 'CSSSA006934', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "LIC. ERICK E. RAMÍREZ VÁZQUEZ"],
            ['id'=>'00016',    'incremento' => 6,   'clues' => 'CSSSA002611', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "Dr. Víctor Hugo Mendoza Mérida"],
            ['id'=>'000160',   'incremento' => 60,  'clues' => 'CSSSA007074', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "C.P GUILLERMO HERNANDEZ AGUILAR"],
            ['id'=>'000161',   'incremento' => 61,  'clues' => 'CSSSA018776', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "ING. HECTOR EDUARDO SANCHEZ MATAMOROS"],
            ['id'=>'000162',   'incremento' => 62,  'clues' => 'CSSSA005773', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "LIC. CARLOS ALBERTO COSTA GORDILLO"],
            ['id'=>'000163',   'incremento' => 63,  'clues' => 'CSSSA001030', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "C.P. Alejandra Guadalupe Gonzalez De Paz"],
            ['id'=>'000164',   'incremento' => 64,  'clues' => 'CSSSA018764', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "LIC. ROCIO DEL CARMEN SANCHEZ PÉREZ"],
            ['id'=>'000165',   'incremento' => 65,  'clues' => 'CSSSA018752', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "LIC. NALDI ALFARO PEREZ"],
            ['id'=>'000166',   'incremento' => 66,  'clues' => 'CSSSA000045', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "OCTAVIO JESUS VALSECA MORALES"],
            ['id'=>'000167',   'incremento' => 67,  'clues' => 'CSSSA018740', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "LAE. Antonio de Jesus Martinez Hernandez"],
            ['id'=>'000168',   'incremento' => 68,  'clues' => 'CSSSA000832', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "Lic José de Jesús Salinas Santiago"],
            ['id'=>'000178',   'incremento' => 78,  'clues' => 'CSSSA004595', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "LIC. BERTHA ALAVARADO NARVAÉZ"],
            ['id'=>'000179',   'incremento' => 79,  'clues' => 'CSSSA017731', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "Baltazar Ramirez Perez"],
            ['id'=>'00018',    'incremento' => 8,   'clues' => 'CSSSA006934', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "DR. SERGIO ROBERTO SOBRINO RAMÍREZ"],
            ['id'=>'000180',   'incremento' => 80,  'clues' => 'CSSSA019481', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "LIC. LUZ ESTER GÓMEZ AGUILAR"],
            ['id'=>'000181',   'incremento' => 81,  'clues' => 'CSSSA017726', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "C. Lorena Roque Zepeda"],
            ['id'=>'000182',   'incremento' => 82,  'clues' => 'CSSSA006403', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "LIC. ALEX HIRAM CAMEY BANECO"],
            ['id'=>'000183',   'incremento' => 83,  'clues' => 'CSSSA017504', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "C.P. BENJAMIN WILFRIDO ROBLEDO MUÑOZ"],
            ['id'=>'000184',   'incremento' => 84,  'clues' => 'CSSSA018781', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "I.S.C. Alejandro Miguel Monzon Ordoñez"],
            ['id'=>'000185',   'incremento' => 85,  'clues' => 'CSSSA017516', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "LIC. ERNESTO MORENO RODAS"],
            ['id'=>'000187',   'incremento' => 87,  'clues' => 'CSSSA019954', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "LIC. MARIA OLIVIA RIOS LÓPEZ"],
            ['id'=>'000188',   'incremento' => 88,  'clues' => 'CSSSA000412', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "Jesús Moreno salas"],
            ['id'=>'000189',   'incremento' => 89,  'clues' => 'CSSSA019242', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "Sergio perez Salas"],
            ['id'=>'00019',    'incremento' => 9,   'clues' => 'CSSSA007074', 'created_at' => "2017-05-09 07:06:39", 'updated_at' => "2017-05-09 07:06:39", 'nombre' => "DR SERGIO LUIS OROZCO CARRASCO"],
            ['id'=>'000190',   'incremento' => 90,  'clues' => 'CSSSA008264', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "MARTHA EUNICE CONSTANTINO BERMUDEZ"],
            ['id'=>'000191',   'incremento' => 91,  'clues' => 'CSSSA003265', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "ELI ALEJANDRO DOMINGUEZ GARCIA"],
            ['id'=>'000192',   'incremento' => 92,  'clues' => 'CSSSA018793', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "JULIO CESAR MORALES ROBLES"],
            ['id'=>'000193',   'incremento' => 93,  'clues' => 'CSSSA017521', 'created_at' => "2017-05-09 07:13:48", 'updated_at' => "2017-05-09 07:13:48", 'nombre' => "JORGE MAURICIO ROMÁN ARIZMENDIZ"],
            //Harima: Se agregan Jurisdicciones
            ['id'=>'0001111',  'incremento'=>111,	'clues' => 'CSSSA017300', 'created_at' => "2017-08-04 12:44:56", 'updated_at'=>"2017-08-04 12:44:56", 'nombre' => "Dr. José Irán Zenteno Pérez"],
            ['id'=>'0001117',  'incremento'=>117,	'clues' => 'CSSSA017225', 'created_at' => "2018-01-31 10:11:34", 'updated_at'=>"2018-01-31 10:11:34", 'nombre' => "DR. JOSUE RODRIGO COSÍO CERÓN"],
            ['id'=>'0001118',  'incremento'=>118,	'clues' => 'CSSSA017336', 'created_at' => "2018-01-31 10:12:04", 'updated_at'=>"2018-01-31 10:12:04", 'nombre' => "LIC. LUVIA ESTHER DIAZ RINCON"],
            ['id'=>'000118',   'incremento'=>18,	'clues' => 'CSSSA017336', 'created_at' => "2017-05-09 12:06:39", 'updated_at'=>"2017-05-09 12:06:39", 'nombre' => "DR. FRANCISCO ANTONIO CASTELLANOS COUTIÑO"],
            ['id'=>'000119',   'incremento'=>19,	'clues' => 'CSSSA017295', 'created_at' => "2017-05-09 12:06:39", 'updated_at'=>"2017-05-09 12:06:39", 'nombre' => "DR. ALFREDO COUTIÑO MENDEZ"],
            ['id'=>'000120',   'incremento'=>20,	'clues' => 'CSSSA017230', 'created_at' => "2017-05-09 12:06:39", 'updated_at'=>"2017-05-09 12:06:39", 'nombre' => "DR. OCTAVIO ALBERTO COUTIÑO NIÑO"],
            ['id'=>'000121',   'incremento'=>21,	'clues' => 'CSSSA017283', 'created_at' => "2017-05-09 12:06:39", 'updated_at'=>"2017-05-09 12:06:39", 'nombre' => "DR. JOSE ESAU GUZMAN MORALEZ"],
            ['id'=>'000122',   'incremento'=>22,	'clues' => 'CSSSA017411', 'created_at' => "2017-05-09 12:06:39", 'updated_at'=>"2017-05-09 12:06:39", 'nombre' => "DR. GABRIEL AUGUSTO LEÓN JIMENEZ"],
            ['id'=>'000123',   'incremento'=>23,    'clues' => 'CSSSA017312', 'created_at' => "2017-05-09 12:06:39", 'updated_at'=>"2017-05-09 12:06:39", 'nombre' => "DR. CELIN CLEMENTE VARGAS"],
            ['id'=>'000124',   'incremento'=>24,	'clues' => 'CSSSA017242', 'created_at' => "2017-05-09 12:06:39", 'updated_at'=>"2017-05-09 12:06:39", 'nombre' => "DR. HENRY JOEL HERNÁNDEZ BALLINAS"],
            ['id'=>'000125',   'incremento'=>25,	'clues' => 'CSSSA008112', 'created_at' => "2017-05-09 12:06:39", 'updated_at'=>"2017-05-09 12:06:39", 'nombre' => "DR. OSCAR EDGARDO SARMIENTO MACIAS"],
            ['id'=>'000126',   'incremento'=>26,	'clues' => 'CSSSA017266', 'created_at' => "2017-05-09 12:06:39", 'updated_at'=>"2017-05-09 12:06:39", 'nombre' => "DR. CARLOS GARCÍA LARA"],
            ['id'=>'000158',   'incremento'=>58,	'clues' => 'CSSSA017382', 'created_at' => "2017-05-09 12:13:48", 'updated_at'=>"2017-05-09 12:13:48", 'nombre' => "Encargado"],
            ['id'=>'000169',   'incremento'=>69,	'clues' => 'CSSSA017336', 'created_at' => "2017-05-09 12:13:48", 'updated_at'=>"2017-05-09 12:13:48", 'nombre' => "LIC. FABIAN DIAZ ALFARO"],
            ['id'=>'00017',    'incremento'=>7,	    'clues' => 'CSSSA017271', 'created_at' => "2017-05-09 12:06:39", 'updated_at'=>"2017-05-09 12:06:39", 'nombre' => "Director"],
            ['id'=>'000170',   'incremento'=>70,	'clues' => 'CSSSA017406', 'created_at' => "2017-05-09 12:13:48", 'updated_at'=>"2017-05-09 12:13:48", 'nombre' => "ENCARGADO: RAUL BALCAZAR HERNANDEZ"],
            ['id'=>'000171',   'incremento'=>71,	'clues' => 'CSSSA017341', 'created_at' => "2017-05-09 12:13:48", 'updated_at'=>"2017-05-09 12:13:48", 'nombre' => "C.P. JESUS GUADALUPE HERNANDEZ OSEGUERA"],
            ['id'=>'000172',   'incremento'=>72,	'clues' => 'CSSSA017394', 'created_at' => "2017-05-09 12:13:48", 'updated_at'=>"2017-05-09 12:13:48", 'nombre' => "LCI. ANGEL PRIMITIVO VILLATORO MECINAS"],
            ['id'=>'000173',   'incremento'=>73,	'clues' => 'CSSSA017411', 'created_at' => "2017-05-09 12:13:48", 'updated_at'=>"2017-05-09 12:13:48", 'nombre' => "JUAN CARLOS FARRERA TREJO"],
            ['id'=>'000174',   'incremento'=>74,    'clues' => 'JURISMOTO10', 'created_at' => "2017-05-09 12:13:48", 'updated_at'=>"2017-05-09 12:13:48", 'nombre' => "LIC. MARCO ANTONIO GALINDO PEREZ"],
            ['id'=>'000175',   'incremento'=>75,	'clues' => 'CSSSA017353', 'created_at' => "2017-05-09 12:13:48", 'updated_at'=>"2017-05-09 12:13:48", 'nombre' => "C. HERNAN ALEJANDRO MORALES AGUILAR"],
            ['id'=>'000176',   'incremento'=>76,	'clues' => 'CSSSA017365', 'created_at' => "2017-05-09 12:13:48", 'updated_at'=>"2017-05-09 12:13:48", 'nombre' => "LIC. MICHEL ARMANDO ASTUDILLO ARCE"],
            ['id'=>'000177',   'incremento'=>77,	'clues' => 'CSSSA017370', 'created_at' => "2017-05-09 12:13:48", 'updated_at'=>"2017-05-09 12:13:48", 'nombre' => "ENF. LUIS HERNÁNDEZ RUIZ"],
            //Nuevas Clues
            [ 'id'=>'0001102',	'incremento'=>102,	'clues' => 'CSSSAHGREFO', 'created_at' => "2017-05-09 12:13:48", 'updated_at'=>"2017-05-09 12:13:48", 'nombre' => "Nini Morales Mingo"],
            [ 'id'=>'0001108',	'incremento'=>108,	'clues' => 'CSSSAHIBERR', 'created_at' => "2017-07-31 10:29:24", 'updated_at'=>"2017-07-31 10:29:24", 'nombre' => "Lic. Edmundo Armando Saldaña Garcia"],
            [ 'id'=>'0001109',	'incremento'=>109,	'clues' => 'CSSSAHIBERR', 'created_at' => "2017-07-31 10:29:24", 'updated_at'=>"2017-07-31 10:29:24", 'nombre' => "Dr. Rafael Ildefonso Hernandez Gutierrez"],
            [ 'id'=>'000135',	'incremento'=>35,	'clues' => 'CSSSA009162', 'created_at' => "2017-05-09 12:06:39", 'updated_at'=>"2017-05-09 12:06:39", 'nombre' => "PSIC. VIVIANA JANETH ACEVES CHAVEZ"],
            [ 'id'=>'000151',	'incremento'=>51,	'clues' => 'CSSSAHGREFO', 'created_at' => "2017-05-09 12:06:39", 'updated_at'=>"2017-05-09 12:06:39", 'nombre' => "Dr. Tirso Raul Sanchez Parra"],
            [ 'id'=>'000186',	'incremento'=>86,	'clues' => 'CSSSA009162', 'created_at' => "2017-05-09 12:13:48", 'updated_at'=>"2017-05-09 12:13:48", 'nombre' => "LIC. GLORIA DEL CARMEN RODRIGUEZ CARTAGENA"]
        ];

        //Si se ejecuta en el servidor offline
        $host      = Config::get('database.connections.mysql.host');
        $database  = Config::get('database.connections.mysql.database');
        $username  = Config::get('database.connections.mysql.username');
        $password  = Config::get('database.connections.mysql.password');
       
        echo shell_exec(env('PATH_MYSQL').'/mysql -h ' . $host . ' -u ' . $username . ' -p' . $password . ' -e "DROP DATABASE  IF EXISTS '.$database.'; CREATE DATABASE ' . $database . ' DEFAULT CHARACTER SET utf8;"');
        //echo shell_exec(env('PATH_MYSQL').'/mysql -h ' . $host . ' -u ' . $username . ' -p' . $password . ' -e "CREATE DATABASE ' . $database . ' DEFAULT CHARACTER SET utf8"');
        //echo env('PATH_MYSQL').'/mysql -h ' . $host . ' -u ' . $username . ' -p' . $password . ' -e "CREATE DATABASE ' . $database . '"';
        
        \Artisan::call('migrate');
        \Artisan::call('db:seed',['--class'=>'DatosCatalogosSeeder']);
        
        
        $parametros = Input::all();

        $config = $lista_config_server[$parametros['clues']];
        
        $mensaje = '';

        $path = base_path('.env');

        if (file_exists($path)) {
            file_put_contents($path, str_replace('SERVIDOR_ID='.env('SERVIDOR_ID'), 'SERVIDOR_ID='.$config['id'], file_get_contents($path)));
            file_put_contents($path, str_replace('SECRET_KEY='.env('SECRET_KEY'), 'SECRET_KEY='.$config['secret'], file_get_contents($path)));
            file_put_contents($path, str_replace('CLUES='.env('CLUES'), 'CLUES='.$config['clues'], file_get_contents($path)));

            // Si no existe la linea en el .env (que debería), lo agregamos
            if(strpos(file_get_contents($path),'SERVIDOR_INSTALADO') === false){
                file_put_contents($path, "\n\nSERVIDOR_INSTALADO=true", FILE_APPEND);
            } else {
                file_put_contents($path, str_replace('SERVIDOR_INSTALADO=false', 'SERVIDOR_INSTALADO=true', file_get_contents($path)));
            }            
        }

        \Artisan::call('config:clear');

        $servidor = Servidor::find($config['id']);

        if(!$servidor){
            $servidor = new Servidor();
        }

        $servidor->id = $config['id'];
        $servidor->nombre = 'Servidor: '.$config['clues'];
        $servidor->secret_key = $config['secret'];
        $servidor->clues = $config['clues'];
        $servidor->tiene_internet = 0;
        $servidor->catalogos_actualizados = 0;
        $servidor->version = 1.0;
        $servidor->periodo_sincronizacion = 24;
        $servidor->principal = 0;
        $servidor->save();

        //\Artisan::call('db:seed',['--class'=>'UsuariosSeeder']);
        DB::table('usuarios')->insert([
            [
                'id' => $config['id'].':root',
                'servidor_id' =>  $config['id'],
                'password' => Hash::make('ssa.s14l.0ffl1n3.'.$config['id']),
                'nombre' => 'Super',
                'apellidos' => 'Usuario',
                'avatar' => 'avatar-circled-root',
                'su' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => $config['id'].':admin',
                'servidor_id' =>  $config['id'],
                'password' => Hash::make('administrador.'.$config['id']),
                'nombre' => 'Administrador',
                'apellidos' => 'Servidor',
                'avatar' => 'avatar-circled-user-male',
                'su' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => $config['id'].':almacen',
                'servidor_id' =>  $config['id'],
                'password' => Hash::make('almacen.'.$config['id']),
                'nombre' => 'Encargado',
                'apellidos' => 'Almacen',
                'avatar' => 'avatar-circled-user-male',
                'su' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);

        if($config['almacen']){
            DB::insert('insert into almacenes (id, incremento, servidor_id, nivel_almacen, tipo_almacen, clues, proveedor_id, subrogado, externo, unidosis, nombre, usuario_id, created_at, updated_at) values (?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [$config['almacen'],1,$config['id'],1,'ALMPAL',$config['clues'],$config['proveedor'],0,0,0,'ALMACEN PRINCIPAL',$config['id'].":root",Carbon::now(),Carbon::now()]);
        }
        
        $almacen = Almacen::where('clues',$config['clues'])->where('tipo_almacen','ALMPAL')->first();

        $incremento = 0;
        foreach ($lista_personal_clues as $personal) {
            if($personal['clues'] == $config['clues']){
                $incremento += 1;
                DB::insert('insert into personal_clues (id, incremento, servidor_id, clues, nombre, surte_controlados, licencia_controlados, usuario_id, created_at, updated_at) values (?,?,?,?,?,?,?,?,?,?)', [$personal['id'],$incremento,$config['id'],$config['clues'],$personal['nombre'],0,'',$config['id'].':root',$personal['created_at'],$personal['updated_at']]);
            }
        }
        
        DB::insert('insert into rol_usuario (rol_id, usuario_id) values (?,?)', [8,$config['id'].':admin']);
        DB::insert('insert into rol_usuario (rol_id, usuario_id) values (?,?)', [12,$config['id'].':admin']);
        DB::insert('insert into rol_usuario (rol_id, usuario_id) values (?,?)', [19,$config['id'].':admin']);
        DB::insert('insert into rol_usuario (rol_id, usuario_id) values (?,?)', [21,$config['id'].':admin']);

        DB::insert('insert into rol_usuario (rol_id, usuario_id) values (?,?)', [8,$config['id'].':almacen']);
        DB::insert('insert into rol_usuario (rol_id, usuario_id) values (?,?)', [13,$config['id'].':almacen']);
        DB::insert('insert into rol_usuario (rol_id, usuario_id) values (?,?)', [14,$config['id'].':almacen']);
        DB::insert('insert into rol_usuario (rol_id, usuario_id) values (?,?)', [15,$config['id'].':almacen']);
        DB::insert('insert into rol_usuario (rol_id, usuario_id) values (?,?)', [16,$config['id'].':almacen']);
        DB::insert('insert into rol_usuario (rol_id, usuario_id) values (?,?)', [17,$config['id'].':almacen']);
        DB::insert('insert into rol_usuario (rol_id, usuario_id) values (?,?)', [18,$config['id'].':almacen']);
        DB::insert('insert into rol_usuario (rol_id, usuario_id) values (?,?)', [19,$config['id'].':almacen']);
        DB::insert('insert into rol_usuario (rol_id, usuario_id) values (?,?)', [22,$config['id'].':almacen']);
        
        DB::insert('insert into almacen_usuario (usuario_id, almacen_id) values (?,?)', [$config['id'].':admin',$almacen->id]);
        DB::insert('insert into almacen_usuario (usuario_id, almacen_id) values (?,?)', [$config['id'].':almacen',$almacen->id]);

        DB::insert('insert into usuario_unidad_medica (usuario_id, clues) values (?,?)', [$config['id'].':admin',$almacen->clues]);
        DB::insert('insert into usuario_unidad_medica (usuario_id, clues) values (?,?)', [$config['id'].':almacen',$almacen->clues]);

        $datos_view = [
            'servidor' => [
                'id' => $config['id'],
                'nombre' => 'Servidor: '.$config['clues']
            ],
            'unidad' => [
                'clues' => $config['clues'],
                'nombre' => $config['nombre']
            ],
            'usuarios' => [
                [
                    'user' => 'admin',//$config['id'].':admin',
                    'pass' => 'administrador.'.$config['id']
                ],
                [
                    'user' => 'almacen',//$config['id'].':almacen',
                    'pass' => 'almacen.'.$config['id'],
                ]
            ]
        ];

        return view('install_complete',['data'=>$datos_view]);
    }

    public function instalado(Request $request){
        if(env('SERVIDOR_ID') == '0001'){
            $datos_view = [
                'servidor' => [
                    'id' => env('SERVIDOR_ID'),
                    'nombre' => 'Servidor Central'
                ]
            ];
        }else{
            $unidad_medica = UnidadMedica::where('clues',env('CLUES'))->first();
            $datos_view = [
                'servidor' => [
                    'id' => env('SERVIDOR_ID'),
                    'nombre' => 'Servidor: '.env('CLUES')
                ],
                'unidad' => [
                    'clues' => env('CLUES'),
                    'nombre' => $unidad_medica->nombre
                ],
                'usuarios' => [
                    [
                        'user' => 'admin', //env('SERVIDOR_ID').':admin',
                        'pass' => 'administrador.'.env('SERVIDOR_ID')
                    ],
                    [
                        'user' => 'almacen', //env('SERVIDOR_ID').':almacen',
                        'pass' => 'almacen.'.env('SERVIDOR_ID'),
                    ]
                ]
            ];
        }
        return view('install_complete',['data'=>$datos_view]);
    }
}