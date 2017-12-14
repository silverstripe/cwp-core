<?php

namespace CWP\Core\Report;

use SilverStripe\Reports\Report,
    SilverStripe\ORM\ArrayList;

/**
 * Summary report on the page and file counts managed by this CMS.
 */
class CwpStatsReport extends Report
{

    /**
     *
     * @return string
     */
    public function title()
    {
        return _t('CwpStatsReport.Title', 'Summary statistics');
    }

    /**
     *
     * @return string
     */
    public function description()
    {
        return _t(
                'CwpStatsReport.Description', 'This report provides various statistics for this site. The "total live page count" is the number that ' .
                'can be compared against the instance size specifications.'
        );
    }

    /**
     *
     * @return array
     */
    public function columns()
    {
        return array(
            'Name' => _t('CwpStatsReport.Name', 'Name'),
            'Count' => _t('CwpStatsReport.Count', 'Count')
        );
    }

    /**
     * Manually create source records for the report. Agreggates cannot be provided as a column of a DataQuery result.
     *
     * @param type $params
     * @param type $sort
     * @param type $limit
     * @return ArrayList
     */
    public function sourceRecords($params = array(), $sort = null, $limit = null)
    {
        $records = array();

        // Get the query to apply across all variants: looks at all subsites, translations, live stage only.
        $crossVariant = (function($dataQuery) {
                    $params = array(
                        'Subsite.filter' => false,
                        'Versioned.mode' => 'stage',
                        'Versioned.stage' => 'Live'
                    );

                    if (class_exists('Translatable')) {
                        $params[Translatable::QUERY_LOCALE_FILTER_ENABLED] = false;
                    }

                    return $dataQuery->setDataQueryParam($params);
                });

        // Total.
        $records[] = array(
            'Name' => _t(
                    'CwpStatsReport.TotalPageCount', 'Total live page count, across all translations and subsites'
            ),
            'Count' => $crossVariant(SiteTree::get())
                    ->count()
        );

        if (class_exists('Subsite')) {
            // Main site.
            $records[] = array(
                'Name' => _t('CwpStatsReport.PagesForMainSite', '- in the main site'),
                'Count' => $crossVariant(SiteTree::get())
                        ->filter(array('SubsiteID' => 0))
                        ->count()
            );

            // Per subsite.
            $subsites = Subsite::get();
            foreach ($subsites as $subsite) {
                $records[] = array(
                    'Name' => _t(
                            'CwpStatsReport.PagesForSubsite', "- in the subsite '{SubsiteTitle}'", array('SubsiteTitle' => $subsite->Title)
                    ),
                    'Count' => $crossVariant(SiteTree::get())
                            ->filter(array('SubsiteID' => $subsite->ID))
                            ->count()
                );
            }
        }

        // Files.
        $records[] = array(
            'Name' => _t('CwpStatsReport.FileCount', 'File count'),
            'Count' => File::get()
                    ->setDataQueryParam('Subsite.filter', false)
                    ->filter(array('ClassName:not' => 'Folder'))
                    ->count()
        );

        return ArrayList::create($records);
    }

    /**
     *
     * @return \SilverStripe\Forms\FormField 
     */
    public function getReportField()
    {
        $gridField = parent::getReportField();
        $gridConfig = $gridField->getConfig();
        $gridConfig->removeComponentsByType('GridFieldPrintButton');
        $gridConfig->removeComponentsByType('GridFieldExportButton');
        $gridConfig->removeComponentsByType('GridFieldSortableHeader');

        return $gridField;
    }

}
