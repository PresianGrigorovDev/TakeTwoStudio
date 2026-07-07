<?php
@unlink(__FILE__);
echo json_encode(["status" => "deleted"]);
