<?php


namespace App\Service\UserImport;


use Symfony\Component\HttpFoundation\File\File;


interface ImporterInterface
{
    public function import(File $file);
}
