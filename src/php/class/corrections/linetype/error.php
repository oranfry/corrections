<?php
namespace corrections\linetype;

class error extends \Linetype
{
    public function __construct()
    {
        $this->table = 'correction';
        $this->label = 'Error';
        $this->icon = 'times-o';
        $this->fields = [
            (object) [
                'name' => 'icon',
                'type' => 'text',
                'fuse' => "'times-o'",
                'derived' => true,
            ],
            (object) [
                'name' => 'hasgst',
                'type' => 'icon',
                'derived' => true,
                'borrow' => "{t_correctiontransaction_hasgst}",
            ],
            (object) [
                'name' => 'date',
                'type' => 'date',
                'main' => true,
                'borrow' => "{t_errortransaction_date}",
            ],
            (object) [
                'name' => 'account',
                'type' => 'text',
                'borrow' => "{t_correctiontransaction_account}",
            ],
            (object) [
                'name' => 'claimdate',
                'type' => 'date',
                'borrow' => "{t_errortransaction_claimdate}",
            ],
            (object) [
                'name' => 'correctiondate',
                'type' => 'date',
                'borrow' => "{t_correctiontransaction_date}",
            ],
            (object) [
                'name' => 'correctionclaimdate',
                'type' => 'date',
                'borrow' => "{t_correctiontransaction_claimdate}",
            ],
            (object) [
                'name' => 'sort',
                'type' => 'text',
                'constrain' => true,
                'borrow' => "{t_correctiontransaction_sort}",
            ],
            (object) [
                'name' => 'description',
                'type' => 'text',
                'borrow' => "{t_correctiontransaction_description}",
            ],
            (object) [
                'name' => 'created',
                'type' => 'text',
                'fuse' => "{t}.created",
            ],
            (object) [
                'name' => 'net',
                'type' => 'number',
                'dp' => 2,
                'summary' => 'sum',
                'borrow' => "-{t_correctiontransaction_net}",
            ],
            (object) [
                'name' => 'gst',
                'type' => 'number',
                'dp' => 2,
                'summary' => 'sum',
                'borrow' => "-{t_correctiontransaction_gst}",
            ],
            (object) [
                'name' => 'amount',
                'type' => 'number',
                'dp' => 2,
                'derived' => true,
                'summary' => 'sum',
                'borrow' => "-({t_correctiontransaction_amount})",
            ],
            (object) [
                'name' => 'broken',
                'type' => 'text',
                'derived' => true,
                'fuse' => "if ({t}_errortransaction.amount + {t}_correctiontransaction.amount != 0 or {t}_errortransaction_gstpeer_gst.amount + {t}_correctiontransaction_gstpeer_gst.amount != 0, 'broken', '')",
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
    }

    public function has($line, $child)
    {
        return in_array($child, ['errortransaction', 'correctiontransaction']);
    }

    public function unpack($line)
    {
        $line->errortransaction = (object) [
            'date' => $line->date,
            'claimdate' => $line->claimdate,
            'account' => $line->account,
            'net' => $line->net,
            'gst' => @$line->gst,
            'description' => @$line->description,
            'sort' => @$line->sort,
        ];

        $line->correctiontransaction = (object) [
            'date' => $line->correctiondate,
            'claimdate' => $line->correctionclaimdate,
            'account' => $line->account,
            'net' => bcmul('-1', $line->net, 2),
            'gst' => @$line->gst ? bcmul('-1', $line->gst, 2) : null,
            'description' => @$line->description,
            'sort' => @$line->sort,
        ];
    }

    public function get_suggested_values($token)
    {
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

        if (!@$line->correctionclaimdate) {
            $line->correctionclaimdate = date_shift($gstperiod->rawstart($line->correctiondate), "+{$gstperiod->step} +1 month -1 day");
        }
    }

    public function validate($line)
    {
        $errors = [];

        if (@$line->correctiondate == null) {
            $errors[] = 'no error date';
        }

        if (@$line->gst != 0 && !@$line->sort) {
            $errors[] = 'no sort';
        }

        return $errors;
    }
}
