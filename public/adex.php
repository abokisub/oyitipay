<?php

$targetFolder = $_SERVER['DOCUMENT_ROOT'].'/storage/app/profile_image';
$linkFolder = $_SERVER['DOCUMENT_ROOT'].'/public/profile_image';
symlink($targetFolder,$linkFolder);
echo 'Symlink process successfully completed';
