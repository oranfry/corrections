<?php
namespace corrections\linetype;

class correction extends \Linetype
{
    public function __construct()
    {
        $this->table = 'correction';
        $this->label = 'Correction';
        $this->icon = 'tick-o';
        $this->borrow = [
            'hasgst' => function ($line) : bool {
                return (bool) $line->correctiontransaction->hasgst;
            },
            'date' => function ($line) : string {
                return $line->correctiontransaction->date;
            },
            'account' => function ($line) : string {
                return $line->correctiontransaction->account;
            },
            'claimdate' => function ($line) : string {
                return $line->correctiontransaction->claimdate;
            },
            'errordate' => function ($line) : string {
                return $line->errortransaction->date;
            },
            'errorclaimdate' => function ($line) : string {
                return $line->errortransaction->claimdate;
            },
            'invert' => function ($line) : string {
                return $line->correctiontransaction->invert;
            },
            'description' => function ($line) : ?string {
                return @$line->correctiontransaction->description;
            },
            'net' => function ($line) : string {
                return $line->correctiontransaction->net;
            },
            'gst' => function ($line) : string {
                return $line->correctiontransaction->gst;
            },
            'amount' => function ($line) : string {
                return $line->correctiontransaction->amount;
            },
        ];

        $this->fields = [
            'icon' => function ($records) {
                return "tick-o";
            },
            'created' => function ($records) {
                return @$records['/']->created;
            },
            'broken' => function ($records) {
                if (@$records['/errortransaction']->amount + @$records['/correctiontransaction']->amount != 0) {
                    return 'Error-Correction Imbalance';
                }

                if (@$record['/errortransaction/gstpeer_gst']->amount + @$record['/correctiontransaction/gstpeer_gst']->amount != 0) {
                    return 'Error-Correction GST Imbalance';
                }

                return null;
            },
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
        $line->correctiontransaction = (object) [
            'date' => $line->date,
            'claimdate' => $line->claimdate,
            'account' => $line->account,
            'net' => $line->net,
            'gst' => @$line->gst,
            'description' => @$line->description,
            'invert' => @$line->invert,
        ];

        $line->errortransaction = (object) [
            'date' => $line->errordate,
            'claimdate' => $line->errorclaimdate,
            'account' => $line->account,
            'net' => bcmul('-1', $line->net, 2),
            'gst' => @$line->gst ? bcmul('-1', $line->gst, 2) : null,
            'description' => @$line->description,
            'invert' => @$line->invert,
        ];
    }

    public function get_suggested_values($token)
    {
        $suggestions = [];
        $suggestions['invert'] = ['', 'yes'];

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

        if (@$line->errordate == null) {
            $errors[] = 'no error date';
        }

        return $errors;
    }
}
