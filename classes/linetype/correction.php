<?php

namespace corrections\linetype;

class correction extends \jars\Linetype
{
    public function __construct()
    {
        $this->table = 'correction';

        $this->borrow = [
            'date' => fn ($line) : string => $line->correctiontransaction->date,
            'account' => fn ($line) : string => $line->correctiontransaction->account,
            'claimdate' => fn ($line) : ?string => $line->correctiontransaction->claimdate,
            'errordate' => fn ($line) : string => $line->errortransaction->date,
            'errorclaimdate' => fn ($line) : ?string => $line->errortransaction->claimdate,
            'invert' => fn ($line) : bool => $line->correctiontransaction->invert,
            'gsttype' => fn ($line) : ?string => $line->correctiontransaction->gsttype,
            'description' => fn ($line) : ?string => $line->correctiontransaction->description,
            'net' => fn ($line) : string => $line->correctiontransaction->net,
            'gst' => fn ($line) : ?string => @$line->correctiontransaction->gst,
            'amount' => fn ($line) : string => $line->correctiontransaction->amount,
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
                'linetype' => 'hiddentransaction',
                'property' => 'correctiontransaction',
                'tablelink' => 'correction_correction',
            ],
            (object) [
                'linetype' => 'hiddentransaction',
                'property' => 'errortransaction',
                'tablelink' => 'correction_error',
            ],
        ];
    }

    public function unpack($line, $oldline, $old_inlines)
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

        if (!@$line->errorclaimdate) {
            $m = sprintf('%02d', (floor(substr($line->errordate, 5, 2) / 2) * 2 + 11) % 12 + 1);
            $y = date('Y', strtotime($line->errordate)) - ($m > date('m', strtotime($line->errordate)) ? 1 : 0);
            $line->errorclaimdate = date_shift("$y-$m-01", "+3 month -1 day");
        }
    }

    public function validate($line): array
    {
        $errors = parent::validate($line);

        if (@$line->errordate == null) {
            $errors[] = 'no error date';
        }

        return $errors;
    }
}
