<?php
namespace corrections\helper;

class CorrectionsHelper
{
    public static function exclude_corrections($linetype)
    {
        $linetype->clauses[] = 'correctioncorrection.id is null and errorcorrection.id is null';
        $linetype->inlinelinks = array_merge(
            $linetype->inlinelinks,
            [
                (object) [
                    'linetype' => 'correction',
                    'tablelink' => 'correctioncorrection',
                    'norecurse' => true,
                    'reverse' => true,
                    'alias' => 'correctioncorrection',
                ],
                (object) [
                    'linetype' => 'correction',
                    'tablelink' => 'correctionerror',
                    'norecurse' => true,
                    'reverse' => true,
                    'alias' => 'errorcorrection',
                ],
            ]
        );
    }
}
