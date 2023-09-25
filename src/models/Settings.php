<?php
namespace astuteo\pjeShared\models;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    public bool $syncBreadcrumbs = false;
    public bool|array $mergeUriSalesforce = false;
}
