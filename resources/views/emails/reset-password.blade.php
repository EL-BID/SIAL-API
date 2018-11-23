<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>SIAL</title>
</head>
<body>
<h1>Hola, <?php echo $usuario->nombre; ?></h1>
<p>Haz solicitado un cambio de contraseña del sistema <strong>SIAL</strong>, por favor para proceder da clic en el siguiente enlace:</p>
<p> <a href="<?php echo $reset_url; ?>&id=<?php echo $usuario->id ?>&reset_token=<?php echo $usuario->reset_token; ?>"><?php echo $reset_url; ?>&id=<?php echo $usuario->id ?>&reset_token=<?php echo $usuario->reset_token; ?></a> </p>
<p>Si tu no haz realizado esta acción, por favor ignora este correo electrónico o ponte en contacto con el adminsitrador del sistema.</p>
</body>
</html>