<?php
namespace corrections\linetype;

class correction extends \Linetype
{
    public function __construct()
    {
        $this->table = 'correction';
        $this->label = 'Correction';
        $this->icon = 'tick-o';
        $this->fields = [
            (object) [
                'name' => 'icon',
                'type' => 'text',
                'fuse' => "'tick-o'",
                'derived' => true,
            ],
            (object) [
                'name' => 'hasgst',
                'type' => 'icon',
                'derived' => true,
                'fuse' => "if (errortransaction_gstpeer_gst.amount != 0, 'moneytake', '')",
            ],
            (object) [
                'name' => 'date',
                'type' => 'date',
                'fuse' => 'correctiontransaction.date',
                'main' => true,
            ],
            (object) [
                'name' => 'account',
                'type' => 'text',
                'fuse' => "correctiontransaction.account",
            ],
            (object) [
                'name' => 'claimdate',
                'type' => 'date',
                'fuse' => 'correctiontransaction_gstird_gst.date',
            ],
            (object) [
                'name' => 'errordate',
                'type' => 'date',
                'fuse' => 'errortransaction.date',
            ],
            (object) [
                'name' => 'errorclaimdate',
                'type' => 'date',
                'fuse' => 'errortransaction_gstird_gst.date',
            ],
            (object) [
                'name' => 'sort',
                'type' => 'text',
                'fuse' => "coalesce(if(errortransaction_gstpeer_gst.description in ('sale', 'purchase'), errortransaction_gstpeer_gst.description, null), if(errortransaction_gstpeer_gst.amount > 0, 'sale', 'purchase'))",
                'constrain' => true,
            ],
            (object) [
                'name' => 'description',
                'type' => 'text',
                'fuse' => "errortransaction.description",
            ],
            (object) [
                'name' => 'net',
                'type' => 'number',
                'dp' => 2,
                'summary' => 'sum',
                'fuse' => 'correctiontransaction.amount',
            ],
            (object) [
                'name' => 'gst',
                'type' => 'number',
                'dp' => 2,
                'summary' => 'sum',
                'fuse' => 'correctiontransaction_gstpeer_gst.amount',
            ],
            (object) [
                'name' => 'amount',
                'type' => 'number',
                'dp' => 2,
                'derived' => true,
                'summary' => 'sum',
                'fuse' => 'ifnull(correctiontransaction.amount, 0) + ifnull(correctiontransaction_gstpeer_gst.amount, 0)',
            ],
            (object) [
                'name' => 'broken',
                'type' => 'text',
                'derived' => true,
                'fuse' => "if (errortransaction.amount + correctiontransaction.amount != 0 or errortransaction_gstpeer_gst.amount + correctiontransaction_gstpeer_gst.amount != 0, 'broken', '')",
            ],
        ];
        $this->inlinelinks = [
            (object) [
                'tablelink' => 'correctioncorrection',
                'linetype' => 'transaction',
                'required' => true,
            ],
            (object) [
                'tablelink' => 'correctionerror',
                'linetype' => 'transaction',
                'required' => true,
            ],
        ];
        $this->unfuse_fields = [
            'correctiontransaction.date' => ':date',
            'correctiontransaction.account' => ":account",
            'correctiontransaction.amount' => ':net',
            'correctiontransaction.description' => ':description',

            'correctiontransaction_gstpeer_gst.date' => ':date',
            'correctiontransaction_gstpeer_gst.account' => "'gst'",
            'correctiontransaction_gstpeer_gst.amount' => ':gst',
            'correctiontransaction_gstpeer_gst.description' => "if(if(:gst > 0, 'sale', 'purchase') <> :sort, :sort, null)",

            'correctiontransaction_gstird_gst.date' => ':claimdate',
            'correctiontransaction_gstird_gst.account' => "'gst'",
            'correctiontransaction_gstird_gst.amount' => '-:gst',
            'correctiontransaction_gstird_gst.description' => "if(if(:gst > 0, 'sale', 'purchase') <> :sort, :sort, null)",

            'errortransaction.date' => ':errordate',
            'errortransaction.account' => ":account",
            'errortransaction.amount' => '-:net',
            'errortransaction.description' => ':description',

            'errortransaction_gstpeer_gst.date' => ':errordate',
            'errortransaction_gstpeer_gst.account' => "'gst'",
            'errortransaction_gstpeer_gst.amount' => '-:gst',
            'errortransaction_gstpeer_gst.description' => "if(if(:gst < 0, 'sale', 'purchase') <> :sort, :sort, null)",

            'errortransaction_gstird_gst.date' => ':errorclaimdate',
            'errortransaction_gstird_gst.account' => "'gst'",
            'errortransaction_gstird_gst.amount' => ':gst',
            'errortransaction_gstird_gst.description' => "if(if(:gst < 0, 'sale', 'purchase') <> :sort, :sort, null)",
        ];
    }

    public function has($line, $assoc) {
        if (in_array($assoc, ['errortransaction', 'correctiontransaction',])) {
            return true;
        }

        if (in_array($assoc, ['errortransaction_gstpeer_gst', 'errortransaction_gstird_gst', 'correctiontransaction_gstpeer_gst', 'correctiontransaction_gstird_gst',])) {
            return $line->gst != 0;
        }
    }

    public function get_suggested_values() {
        $suggestions = [];
        $suggestions['sort'] = ['purchase', 'sale'];
        return $suggestions;
    }

    public function complete($line)
    {
        $gstperiod = \Period::load('gst');

        if (!@$line->date) {
            $line->date = date('Y-m-d');
        }

        if (!@$line->claimdate) {
            $line->claimdate = date_shift($gstperiod->rawstart($line->date), "+{$gstperiod->step} +1 month -1 day");
        }

        if (!@$line->errorclaimdate) {
            $line->errorclaimdate = date_shift($gstperiod->rawstart($line->errordate), "+{$gstperiod->step} +1 month -1 day");
        }
    }

    public function validate($line)
    {
        $errors = [];

        if ($line->errordate == null) {
            $errors[] = 'no error date';
        }

        if (!@$line->sort) {
            $errors[] = 'no sort';
        }

        return $errors;
    }
}
