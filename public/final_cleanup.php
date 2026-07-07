<?php
header('Content-Type: application/json');
@unlink(__FILE__);
echo json_encode(["status" => "self-deleted"], JSON_PRETTY_PRINT);
