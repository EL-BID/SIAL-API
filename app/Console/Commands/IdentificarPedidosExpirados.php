<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use DB;

class IdentificarPedidosExpirados extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'identificar-pedidos-expirados';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Identifica los pedidos cuya fecha de expiración ya paso, y les asigna el estatus de expirado';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(){
        DB::statement("UPDATE pedidos SET status = 'EX', recepcion_permitida = 0 WHERE deleted_at is null AND status = 'PS' AND datediff(fecha_expiracion,current_date())  < 0");
    }
}
