<?php
namespace tablelink;

class correctionerror extends \Tablelink
{
    public function __construct()
    {
        $this->tables = ['correction', 'transaction'];
        $this->middle_table = 'tablelink_correction_error';
        $this->ids = ['correction', 'errortransaction'];
        $this->type = 'oneone';
    }
}
