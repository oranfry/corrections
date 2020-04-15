<?php
namespace blend;

class errors extends \Blend
{
    public $label = 'Errors';
    public $linetypes = ['error'];

    public function __construct()
    {
        $this->groupby = 'date';
        $this->cum = true;
        $this->past = false;
        $this->fields = [
            (object) [
                'name' => 'date',
                'type' => 'date',
                'main' => true,
            ],
            (object) [
                'name' => 'correctiondate',
                'type' => 'date',
            ],
            (object) [
                'name' => 'errorclaimdate',
                'type' => 'date',
                'sacrifice' => true,
            ],
            (object) [
                'name' => 'correctionclaimdate',
                'type' => 'date',
                'sacrifice' => true,
            ],
            (object) [
                'name' => 'net',
                'type' => 'number',
                'summary' => 'sum',
                'dp' => 2,
            ],
            (object) [
                'name' => 'gst',
                'type' => 'number',
                'summary' => 'sum',
                'dp' => 2,
            ],
            (object) [
                'name' => 'amount',
                'type' => 'number',
                'summary' => 'sum',
                'dp' => 2,
            ],
            (object) [
                'name' => 'broken',
                'type' => 'class',
                'default' => '',
            ],
        ];
    }
}
