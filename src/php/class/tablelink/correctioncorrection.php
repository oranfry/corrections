<?php
namespace tablelink;

class correctioncorrection extends \Tablelink
{
    public function __construct()
    {
        $this->tables = ['correction', 'transaction'];
        $this->middle_table = 'tablelink_correction_correction';
        $this->ids = ['correction', 'correctiontransaction'];
        $this->type = 'oneone';
    }
}
