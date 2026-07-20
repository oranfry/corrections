<?php

namespace OranFry\Corrections\Linetypes;

class Correction extends \OranFry\Jars\Core\Linetype
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
        parent::unpack($line, $oldline, $old_inlines);

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
        parent::complete($line);

        if (!@$line->date) {
            $line->date = date('Y-m-d');
        }

        if (!@$line->net && !@$line->gst && @$line->amount) {
            $sign = $line->amount < 0 ? '-' : '';
            $abs = preg_replace('/^-/', '', $line->amount);
            $line->net = $sign . bcmul('1', bcadd(bcdiv(bcmul($abs, '100', 3), '115', 3), '0.005', 3), 2);
            $line->gst = bcsub($line->amount, $line->net, 2);
        } else {
            $line->amount = bcadd(@$line->net ?? '0.00', @$line->gst ?? '0.00', 2);
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
