<?php


namespace App\Service\UserImport;


class ImportException extends \Exception
{
    public $reason = "Unknown error";

    public function __construct($reason)
    {
        parent::__construct();
        $this->reason = $reason;
    }


}
