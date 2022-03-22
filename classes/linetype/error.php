<?php

namespace corrections\linetype;

class error extends \jars\Linetype
{
    public function __construct()
    {
        $this->table = 'correction';
        $this->label = 'Error';
        $this->icon = 'times-o';

        $this->borrow = [
            'date' => fn ($line) : string => $line->errortransaction->date,
            'account' => fn ($line) : string => $line->correctiontransaction->account,
            'claimdate' => fn ($line) : ?string => $line->errortransaction->claimdate,
            'correctiondate' => fn ($line) : string => $line->correctiontransaction->date,
            'correctionclaimdate' => fn ($line) : ?string => $line->correctiontransaction->claimdate,
            'invert' => fn ($line) : bool => $line->correctiontransaction->invert,
            'description' => fn ($line) : ?string => $line->correctiontransaction->description,
            'net' => fn ($line) : string => bcsub('0', $line->correctiontransaction->net, 2),
            'gst' => fn ($line) : ?string => bcsub('0', $line->correctiontransaction->gst, 2),
            'amount' => fn ($line) : string => bcsub('0', $line->correctiontransaction->amount, 2),
        ];

        $this->fields = [
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
                'tablelink' => 'correction_correction',
                'linetype' => 'transaction',
                'property' => 'correctiontransaction',
            ],
            (object) [
                'tablelink' => 'correction_error',
                'linetype' => 'transaction',
                'property' => 'errortransaction',
            ],
        ];
    }

    public function unpack($line, $oldline, $old_inlines)
    {
        $line->errortransaction = (object) [
            'date' => $line->date,
            'claimdate' => $line->claimdate,
            'account' => $line->account,
            'net' => $line->net,
            'gst' => @$line->gst,
            'description' => @$line->description,
            'invert' => @$line->invert,
        ];

        $line->correctiontransaction = (object) [
            'date' => $line->correctiondate,
            'claimdate' => $line->correctionclaimdate,
            'account' => $line->account,
            'net' => bcmul('-1', $line->net, 2),
            'gst' => @$line->gst ? bcmul('-1', $line->gst, 2) : null,
            'description' => @$line->description,
            'invert' => @$line->invert,
        ];
    }

    public function complete($line) : void
    {
        if (!@$line->date) {
            $line->date = date('Y-m-d');
        }

        if (!@$line->claimdate) {
            $m = sprintf('%02d', (floor(substr($line->date, 5, 2) / 2) * 2 + 11) % 12 + 1);
            $y = date('Y', strtotime($line->date)) - ($m > date('m', strtotime($line->date)) ? 1 : 0);
            $line->claimdate = date_shift("$y-$m-01", "+3 month -1 day");
        }

        if (!@$line->correctionclaimdate) {
            $m = sprintf('%02d', (floor(substr($line->correctiondate, 5, 2) / 2) * 2 + 11) % 12 + 1);
            $y = date('Y', strtotime($line->correctiondate)) - ($m > date('m', strtotime($line->correctiondate)) ? 1 : 0);
            $line->correctionclaimdate = date_shift("$y-$m-01", "+3 month -1 day");
        }
    }

    public function validate($line)
    {
        $errors = [];

        if (@$line->correctiondate == null) {
            $errors[] = 'no error date';
        }

        return $errors;
    }
}
