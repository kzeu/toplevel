<?php
declare(strict_types=1);

// Compatibilidade com links antigos: o catálogo comercial agora fica na Loja.
header('Location: loja.php', true, 301);
exit;
