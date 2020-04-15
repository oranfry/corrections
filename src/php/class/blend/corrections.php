<?php
namespace blend;

class corrections extends \Blend
{
    public $label = 'Corrections';
    public $linetypes = ['correction'];

    public function __construct()
    {
        $this->groupby = 'date';
        $this->fields = [
            (object) [
                'name' => 'date',
                'type' => 'date',
                'main' => true,
            ],
            (object) [
                'name' => 'errordate',
                'type' => 'date',
            ],
            (object) [
                'name' => 'correctionclaimdate',
                'type' => 'date',
                'sacrifice' => true,
            ],
            (object) [
                'name' => 'errorclaimdate',
                'type' => 'date',
                'sacrifice' => true,
            ],
            (object) [
                'name' => 'net',
                'type' => 'number',
                'dp' => 2,
            ],
            (object) [
                'name' => 'gst',
                'type' => 'number',
                'dp' => 2,
            ],
            (object) [
                'name' => 'amount',
                'type' => 'number',
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
