<?php

namespace astuteo\pjeShared\records;

use astuteo\pje\PJE;

use Craft;
use craft\db\ActiveRecord;

class BreadcrumbsRecords extends ActiveRecord
{
    public static function tableName() : string
    {
        return '{{%pjeshared_breadcrumbs}}';
    }
}


